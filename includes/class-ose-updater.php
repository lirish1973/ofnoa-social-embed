<?php
/**
 * Self-updating from the public GitHub repository.
 *
 * WordPress learns about new releases the same way it learns about
 * wordpress.org plugins, so the normal "Update now" button and the
 * "Enable auto-updates" toggle both work.
 *
 * How the latest version is found, in order:
 *   1. github.com/<user>/<repo>/releases/latest — a plain web redirect to the
 *      newest tag. No API, so no 60-requests-per-hour limit (which shared
 *      hosting IPs exhaust quickly).
 *   2. The GitHub REST API, as a fallback.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * GitHub release updater.
 */
class OSE_Updater {

	const CACHE_KEY  = 'ose_gh_release';
	const STATUS_KEY = 'ose_gh_status';
	const ASSET      = 'ofnoa-social-embed.zip';

	/**
	 * A successful lookup is one HEAD request, so an hour is cheap.
	 */
	const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Failures retry sooner, without hammering GitHub on every page load.
	 */
	const FAIL_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Errors gathered during one lookup, so the status shows both attempts.
	 *
	 * @var string[]
	 */
	private static $errors = array();

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		// When WordPress refreshes its update list...
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		// ...and every time it reads it. Without this, a site that checked
		// wordpress.org before the plugin was installed would not see our
		// update until its next 12-hour refresh.
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );

		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_folder_name' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
		add_action( 'admin_post_ose_check_updates', array( __CLASS__, 'handle_check_now' ) );
		add_action( 'admin_notices', array( __CLASS__, 'check_notice' ) );
	}

	/**
	 * Is the updater switched on?
	 *
	 * @return bool
	 */
	private static function enabled() {
		$settings = OSE_Settings::get();
		return ! empty( $settings['updater'] );
	}

	/**
	 * Did the admin just press "Check again" on Dashboard → Updates?
	 *
	 * @return bool
	 */
	private static function is_forced_check() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only cache bypass, mirrors core.
		return is_admin() && ! empty( $_GET['force-check'] ) && current_user_can( 'update_plugins' );
	}

	/**
	 * The latest release, cached.
	 *
	 * @param bool $force Bypass the cache.
	 * @return array|false
	 */
	public static function latest( $force = false ) {
		if ( ! $force ) {
			$force = self::is_forced_check();
		}
		if ( ! $force ) {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( is_array( $cached ) && isset( $cached['version'], $cached['package'] ) ) {
				return $cached;
			}
			if ( 'none' === $cached ) {
				return false;
			}
		}

		self::$errors = array();

		$data = self::fetch_via_web();
		if ( ! $data ) {
			$data = self::fetch_via_api();
		}

		if ( ! $data ) {
			set_site_transient( self::CACHE_KEY, 'none', self::FAIL_TTL );
			self::record_status( false, '', '', implode( ' | ', self::$errors ) );
			return false;
		}

		set_site_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
		self::record_status( true, $data['source'], $data['version'], '' );
		return $data;
	}

	/**
	 * Read the newest tag from the /releases/latest redirect.
	 *
	 * @return array|false
	 */
	private static function fetch_via_web() {
		$url = sprintf(
			'https://github.com/%s/%s/releases/latest',
			rawurlencode( OSE_GH_USER ),
			rawurlencode( OSE_GH_REPO )
		);

		$response = wp_safe_remote_head(
			$url,
			array(
				'timeout'     => 10,
				'redirection' => 0,
				'user-agent'  => 'OfnoaSocialEmbed/' . OSE_VERSION,
			)
		);

		if ( is_wp_error( $response ) ) {
			self::$errors[] = 'web: ' . $response->get_error_message();
			return false;
		}

		$code     = (int) wp_remote_retrieve_response_code( $response );
		$location = wp_remote_retrieve_header( $response, 'location' );
		if ( is_array( $location ) ) {
			$location = end( $location );
		}
		$location = (string) $location;

		$parsed = self::parse_release_location( $location );
		if ( $code < 300 || $code >= 400 || ! $parsed ) {
			// No release yet redirects to /releases (no tag) — not an error worth shouting about.
			self::$errors[] = 'web: ' . sprintf( 'HTTP %d %s', $code, $location );
			return false;
		}

		return array(
			'version'   => $parsed['version'],
			'package'   => sprintf(
				'https://github.com/%s/%s/releases/download/%s/%s',
				rawurlencode( OSE_GH_USER ),
				rawurlencode( OSE_GH_REPO ),
				rawurlencode( $parsed['tag'] ),
				self::ASSET
			),
			'url'       => sprintf( 'https://github.com/%s/%s/releases/tag/%s', OSE_GH_USER, OSE_GH_REPO, rawurlencode( $parsed['tag'] ) ),
			'published' => '',
			'notes'     => '',
			'source'    => 'web',
		);
	}

	/**
	 * Pull tag and version out of a /releases/tag/<tag> URL.
	 *
	 * @param string $location Redirect target, absolute or relative.
	 * @return array{tag:string,version:string}|false
	 */
	public static function parse_release_location( $location ) {
		if ( ! preg_match( '#/releases/tag/([^/?\#]+)#', (string) $location, $m ) ) {
			return false;
		}
		$tag     = rawurldecode( $m[1] );
		$version = ltrim( $tag, 'vV' );
		if ( ! preg_match( '/^\d+(\.\d+)*([.\-+][0-9A-Za-z.\-]+)?$/', $version ) ) {
			return false;
		}
		return array(
			'tag'     => $tag,
			'version' => $version,
		);
	}

	/**
	 * Fallback: the REST API (rate limited to 60/hour per IP when anonymous).
	 *
	 * @return array|false
	 */
	private static function fetch_via_api() {
		$endpoint = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			rawurlencode( OSE_GH_USER ),
			rawurlencode( OSE_GH_REPO )
		);

		$response = wp_safe_remote_get(
			$endpoint,
			array(
				'timeout' => 12,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'OfnoaSocialEmbed/' . OSE_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			self::$errors[] = 'api: ' . $response->get_error_message();
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$hint = ( 403 === $code || 429 === $code ) ? ' (GitHub rate limit for this server IP)' : '';
			self::$errors[] = 'api: ' . 'HTTP ' . $code . $hint;
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			self::$errors[] = 'api: ' . 'unexpected response';
			return false;
		}

		$package = '';
		if ( ! empty( $body['assets'] ) && is_array( $body['assets'] ) ) {
			foreach ( $body['assets'] as $asset ) {
				if ( ! empty( $asset['browser_download_url'] ) && preg_match( '/\.zip$/i', (string) $asset['name'] ) ) {
					$package = esc_url_raw( $asset['browser_download_url'] );
					break;
				}
			}
		}
		if ( ! $package ) {
			self::$errors[] = 'api: ' . 'release has no zip asset';
			return false;
		}

		return array(
			'version'   => ltrim( sanitize_text_field( $body['tag_name'] ), 'vV' ),
			'package'   => $package,
			'url'       => isset( $body['html_url'] ) ? esc_url_raw( $body['html_url'] ) : '',
			'published' => isset( $body['published_at'] ) ? sanitize_text_field( $body['published_at'] ) : '',
			'notes'     => isset( $body['body'] ) ? wp_kses_post( $body['body'] ) : '',
			'source'    => 'api',
		);
	}

	/**
	 * Remember the outcome of the last lookup, for the status box and notice.
	 *
	 * @param bool   $ok      Success.
	 * @param string $source  web|api.
	 * @param string $version Version found.
	 * @param string $error   Error text.
	 * @return void
	 */
	private static function record_status( $ok, $source, $version, $error ) {
		update_site_option(
			self::STATUS_KEY,
			array(
				'checked' => time(),
				'ok'      => (bool) $ok,
				'source'  => (string) $source,
				'version' => (string) $version,
				'error'   => (string) $error,
			)
		);
	}

	/**
	 * Last lookup, for display.
	 *
	 * @return array
	 */
	public static function status() {
		return wp_parse_args(
			get_site_option( self::STATUS_KEY, array() ),
			array(
				'checked' => 0,
				'ok'      => false,
				'source'  => '',
				'version' => '',
				'error'   => '',
			)
		);
	}

	/**
	 * Drop the cached release and WordPress' own update list.
	 *
	 * @return void
	 */
	public static function forget() {
		delete_site_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
	}

	/**
	 * Add our entry to WordPress' update list.
	 *
	 * @param mixed $transient Update transient.
	 * @return mixed
	 */
	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) || ! self::enabled() ) {
			return $transient;
		}

		$release = self::latest();
		if ( ! $release || empty( $release['package'] ) ) {
			return $transient;
		}

		$item = (object) array(
			'id'            => 'github.com/' . OSE_GH_USER . '/' . OSE_GH_REPO,
			'slug'          => dirname( OSE_BASENAME ),
			'plugin'        => OSE_BASENAME,
			'new_version'   => $release['version'],
			'url'           => $release['url'],
			'package'       => $release['package'],
			'icons'         => array(),
			'banners'       => array(),
			'banners_rtl'   => array(),
			'tested'        => get_bloginfo( 'version' ),
			'requires_php'  => '7.4',
			'compatibility' => new stdClass(),
		);

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = array();
		}

		if ( version_compare( $release['version'], OSE_VERSION, '>' ) ) {
			$transient->response[ OSE_BASENAME ] = $item;
			unset( $transient->no_update[ OSE_BASENAME ] );
		} else {
			// Listing it here is what makes WordPress show "Enable auto-updates".
			$item->new_version = OSE_VERSION;
			$transient->no_update[ OSE_BASENAME ] = $item;
			unset( $transient->response[ OSE_BASENAME ] );
		}

		return $transient;
	}

	/**
	 * Populate the "View details" modal.
	 *
	 * @param false|object|array $result Result.
	 * @param string             $action API action.
	 * @param object             $args   Arguments.
	 * @return false|object|array
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! self::enabled() ) {
			return $result;
		}
		if ( empty( $args->slug ) || dirname( OSE_BASENAME ) !== $args->slug ) {
			return $result;
		}

		$release = self::latest();
		if ( ! $release || empty( $release['package'] ) ) {
			return $result;
		}

		$release = wp_parse_args(
			$release,
			array(
				'version'   => OSE_VERSION,
				'package'   => '',
				'url'       => '',
				'published' => '',
				'notes'     => '',
			)
		);

		$notes = $release['notes']
			? $release['notes']
			: sprintf(
				/* translators: %s: release URL */
				__( 'Full notes: %s', 'ofnoa-social-embed' ),
				'<a href="' . esc_url( $release['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $release['url'] ) . '</a>'
			);

		return (object) array(
			'name'          => 'Ofnoa Social Embed',
			'slug'          => dirname( OSE_BASENAME ),
			'version'       => $release['version'],
			'author'        => '<a href="https://ofnoacomps.co.il">Ofnoacomps</a>',
			'homepage'      => 'https://github.com/' . OSE_GH_USER . '/' . OSE_GH_REPO,
			'download_link' => $release['package'],
			'requires'      => '5.8',
			'requires_php'  => '7.4',
			'tested'        => get_bloginfo( 'version' ),
			'last_updated'  => $release['published'],
			'sections'      => array(
				'description' => esc_html__( 'Embed Instagram, TikTok and Facebook videos in fully designed grids, tabs, carousels and stories bars.', 'ofnoa-social-embed' ),
				'changelog'   => wpautop( $notes ),
			),
		);
	}

	/**
	 * GitHub source archives extract to "repo-abc123"; rename to the plugin folder.
	 *
	 * @param string $source        Extracted path.
	 * @param string $remote_source Remote path.
	 * @param object $upgrader      Upgrader.
	 * @param array  $args          Hook args.
	 * @return string|WP_Error
	 */
	public static function fix_folder_name( $source, $remote_source, $upgrader, $args = array() ) {
		global $wp_filesystem;

		if ( empty( $args['plugin'] ) || OSE_BASENAME !== $args['plugin'] ) {
			return $source;
		}
		if ( ! $wp_filesystem ) {
			return $source;
		}

		$desired = trailingslashit( $remote_source ) . dirname( OSE_BASENAME );
		if ( untrailingslashit( $source ) === $desired ) {
			return $source;
		}

		if ( $wp_filesystem->move( $source, $desired, true ) ) {
			return trailingslashit( $desired );
		}

		return $source;
	}

	/**
	 * Drop the cache after an update runs.
	 *
	 * @param object $upgrader Upgrader.
	 * @param array  $options  Hook options.
	 * @return void
	 */
	public static function clear_cache( $upgrader, $options ) {
		if ( isset( $options['action'], $options['type'] ) && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			delete_site_transient( self::CACHE_KEY );
		}
	}

	/**
	 * Links under the plugin row: check now, repository, releases.
	 *
	 * @param array  $links Row meta.
	 * @param string $file  Plugin file.
	 * @return array
	 */
	public static function row_meta( $links, $file ) {
		if ( OSE_BASENAME !== $file ) {
			return $links;
		}
		if ( self::enabled() && current_user_can( 'update_plugins' ) ) {
			$links[] = '<a href="' . esc_url( self::check_now_url() ) . '">' . esc_html__( 'Check for updates', 'ofnoa-social-embed' ) . '</a>';
		}
		$links[] = '<a href="' . esc_url( 'https://github.com/' . OSE_GH_USER . '/' . OSE_GH_REPO ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'GitHub', 'ofnoa-social-embed' ) . '</a>';
		$links[] = '<a href="' . esc_url( 'https://github.com/' . OSE_GH_USER . '/' . OSE_GH_REPO . '/releases' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Releases', 'ofnoa-social-embed' ) . '</a>';
		return $links;
	}

	/**
	 * Nonce'd URL for the "Check for updates" action.
	 *
	 * @return string
	 */
	public static function check_now_url() {
		return wp_nonce_url( admin_url( 'admin-post.php?action=ose_check_updates' ), 'ose_check_updates' );
	}

	/**
	 * "Check for updates": forget everything, ask GitHub, refresh WordPress' list.
	 *
	 * @return void
	 */
	public static function handle_check_now() {
		check_admin_referer( 'ose_check_updates' );
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ofnoa-social-embed' ) );
		}

		self::forget();
		self::latest( true );

		if ( ! function_exists( 'wp_update_plugins' ) ) {
			require_once ABSPATH . 'wp-includes/update.php';
		}
		wp_update_plugins();

		wp_safe_redirect( add_query_arg( 'ose_checked', '1', admin_url( 'plugins.php' ) ) );
		exit;
	}

	/**
	 * Result banner after "Check for updates".
	 *
	 * @return void
	 */
	public static function check_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		if ( empty( $_GET['ose_checked'] ) || ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$status  = self::status();
		$release = get_site_transient( self::CACHE_KEY );

		if ( is_array( $release ) && ! empty( $release['version'] ) ) {
			if ( version_compare( $release['version'], OSE_VERSION, '>' ) ) {
				$class   = 'notice-warning';
				$message = sprintf(
					/* translators: 1: new version, 2: installed version */
					__( 'Ofnoa Social Embed %1$s is available (you have %2$s). Use "Update now" in the plugin row below.', 'ofnoa-social-embed' ),
					$release['version'],
					OSE_VERSION
				);
			} else {
				$class   = 'notice-success';
				$message = sprintf(
					/* translators: %s: installed version */
					__( 'Ofnoa Social Embed %s is the latest version.', 'ofnoa-social-embed' ),
					OSE_VERSION
				);
			}
		} else {
			$class   = 'notice-error';
			$message = sprintf(
				/* translators: %s: error text */
				__( 'Ofnoa Social Embed could not reach GitHub: %s', 'ofnoa-social-embed' ),
				$status['error'] ? $status['error'] : __( 'unknown error', 'ofnoa-social-embed' )
			);
		}

		printf( '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
}
