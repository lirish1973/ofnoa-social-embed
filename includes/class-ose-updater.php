<?php
/**
 * Self-updating from the public GitHub repository.
 *
 * WordPress is told about new releases the same way it learns about wordpress.org
 * plugins, so the usual "Update now" button in Plugins works with no extra tooling.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * GitHub release updater.
 */
class OSE_Updater {

	const CACHE_KEY = 'ose_gh_release';
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_folder_name' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
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
	 * Fetch the latest release, cached.
	 *
	 * @param bool $force Bypass the cache.
	 * @return array|false
	 */
	public static function latest( $force = false ) {
		if ( ! $force ) {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( is_array( $cached ) && isset( $cached['version'], $cached['package'] ) ) {
				return $cached;
			}
			if ( 'none' === $cached ) {
				// A recent lookup failed; do not hammer the API on every page load.
				return false;
			}
		}

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

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( self::CACHE_KEY, 'none', HOUR_IN_SECONDS );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			set_site_transient( self::CACHE_KEY, 'none', HOUR_IN_SECONDS );
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
		if ( ! $package && ! empty( $body['zipball_url'] ) ) {
			$package = esc_url_raw( $body['zipball_url'] );
		}

		$data = array(
			'version'   => ltrim( sanitize_text_field( $body['tag_name'] ), 'vV' ),
			'package'   => $package,
			'url'       => isset( $body['html_url'] ) ? esc_url_raw( $body['html_url'] ) : '',
			'published' => isset( $body['published_at'] ) ? sanitize_text_field( $body['published_at'] ) : '',
			'notes'     => isset( $body['body'] ) ? wp_kses_post( $body['body'] ) : '',
		);

		set_site_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
		return $data;
	}

	/**
	 * Tell WordPress an update is available.
	 *
	 * @param object $transient Update transient.
	 * @return object
	 */
	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) || ! self::enabled() ) {
			return $transient;
		}

		$release = self::latest();
		if ( ! $release || empty( $release['package'] ) ) {
			return $transient;
		}

		if ( ! version_compare( $release['version'], OSE_VERSION, '>' ) ) {
			if ( isset( $transient->response[ OSE_BASENAME ] ) ) {
				unset( $transient->response[ OSE_BASENAME ] );
			}
			return $transient;
		}

		$item = (object) array(
			'id'            => OSE_GH_USER . '/' . OSE_GH_REPO,
			'slug'          => dirname( OSE_BASENAME ),
			'plugin'        => OSE_BASENAME,
			'new_version'   => $release['version'],
			'url'           => $release['url'],
			'package'       => $release['package'],
			'icons'         => array(),
			'banners'       => array(),
			'tested'        => get_bloginfo( 'version' ),
			'requires_php'  => '7.4',
			'compatibility' => new stdClass(),
		);

		$transient->response[ OSE_BASENAME ] = $item;
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

		$notes = $release['notes'] ? $release['notes'] : __( 'See the repository for the full changelog.', 'ofnoa-social-embed' );

		return (object) array(
			'name'           => 'Ofnoa Social Embed',
			'slug'           => dirname( OSE_BASENAME ),
			'version'        => $release['version'],
			'author'         => '<a href="https://ofnoacomps.co.il">Ofnoacomps</a>',
			'homepage'       => 'https://github.com/' . OSE_GH_USER . '/' . OSE_GH_REPO,
			'download_link'  => $release['package'],
			'requires'       => '5.8',
			'requires_php'   => '7.4',
			'tested'         => get_bloginfo( 'version' ),
			'last_updated'   => $release['published'],
			'sections'       => array(
				'description' => esc_html__( 'Embed Instagram and Facebook videos in fully designed grids, tabs, carousels and stories bars.', 'ofnoa-social-embed' ),
				'changelog'   => wpautop( $notes ),
			),
		);
	}

	/**
	 * GitHub archives extract to "repo-abc123"; rename to the plugin folder.
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
	 * Drop the cached release after an update runs.
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
	 * Add a repository link under the plugin row.
	 *
	 * @param array  $links Row meta.
	 * @param string $file  Plugin file.
	 * @return array
	 */
	public static function row_meta( $links, $file ) {
		if ( OSE_BASENAME !== $file ) {
			return $links;
		}
		$links[] = '<a href="' . esc_url( 'https://github.com/' . OSE_GH_USER . '/' . OSE_GH_REPO ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'GitHub repository', 'ofnoa-social-embed' ) . '</a>';
		$links[] = '<a href="' . esc_url( 'https://github.com/' . OSE_GH_USER . '/' . OSE_GH_REPO . '/releases' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Releases', 'ofnoa-social-embed' ) . '</a>';
		return $links;
	}
}
