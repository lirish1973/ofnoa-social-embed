<?php
/**
 * URL parsing, embed building and best-effort poster / caption resolution.
 *
 * No API credentials are required: playback uses the public Instagram and
 * Facebook embed players. Credentials, when supplied in the settings screen,
 * are only used to improve poster and caption resolution through oEmbed.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolver.
 */
class OSE_Resolver {

	/**
	 * Identify the platform and canonical id behind a URL.
	 *
	 * @param string $url Raw URL.
	 * @return array{platform:string,type:string,id:string,url:string}|false
	 */
	public static function parse( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return false;
		}
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = 'https://' . ltrim( $url, '/' );
		}
		$url  = esc_url_raw( $url );
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$host = preg_replace( '/^(www|m|web|mbasic)\./', '', $host );

		/* ---------------- Instagram ---------------- */
		if ( 'instagram.com' === $host || 'instagr.am' === $host || 'ig.me' === $host ) {
			if ( preg_match( '#/(p|reel|reels|tv)/([A-Za-z0-9_-]+)#', $url, $m ) ) {
				$type = ( 'reels' === $m[1] ) ? 'reel' : $m[1];
				return array(
					'platform' => 'instagram',
					'type'     => $type,
					'id'       => $m[2],
					'url'      => 'https://www.instagram.com/' . $type . '/' . $m[2] . '/',
				);
			}
			if ( preg_match( '#/share/(?:reel/|p/)?([A-Za-z0-9_-]+)#', $url, $m ) ) {
				$resolved = self::follow_redirect( $url );
				if ( $resolved && $resolved !== $url ) {
					return self::parse( $resolved );
				}
				return array(
					'platform' => 'instagram',
					'type'     => 'share',
					'id'       => $m[1],
					'url'      => $url,
				);
			}
			return array(
				'platform' => 'instagram',
				'type'     => 'profile',
				'id'       => '',
				'url'      => $url,
			);
		}

		/* ---------------- Facebook ---------------- */
		if ( 'facebook.com' === $host || 'fb.com' === $host || 'fb.watch' === $host || 'fb.gg' === $host ) {
			if ( 'fb.watch' === $host ) {
				$resolved = self::follow_redirect( $url );
				if ( $resolved && $resolved !== $url ) {
					$parsed = self::parse( $resolved );
					if ( $parsed ) {
						return $parsed;
					}
				}
				return array(
					'platform' => 'facebook',
					'type'     => 'short',
					'id'       => trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ),
					'url'      => $url,
				);
			}
			if ( preg_match( '#[?&]v=(\d+)#', $url, $m ) ) {
				return array(
					'platform' => 'facebook',
					'type'     => 'video',
					'id'       => $m[1],
					'url'      => 'https://www.facebook.com/watch/?v=' . $m[1],
				);
			}
			if ( preg_match( '#/reel/(\d+)#', $url, $m ) ) {
				return array(
					'platform' => 'facebook',
					'type'     => 'reel',
					'id'       => $m[1],
					'url'      => 'https://www.facebook.com/reel/' . $m[1],
				);
			}
			if ( preg_match( '#/videos/(?:[^/]+/)?(\d+)#', $url, $m ) ) {
				return array(
					'platform' => 'facebook',
					'type'     => 'video',
					'id'       => $m[1],
					'url'      => $url,
				);
			}
			if ( preg_match( '#/share/(?:v|r)/([A-Za-z0-9_-]+)#', $url, $m ) ) {
				$resolved = self::follow_redirect( $url );
				if ( $resolved && $resolved !== $url ) {
					$parsed = self::parse( $resolved );
					if ( $parsed ) {
						return $parsed;
					}
				}
				return array(
					'platform' => 'facebook',
					'type'     => 'share',
					'id'       => $m[1],
					'url'      => $url,
				);
			}
			return array(
				'platform' => 'facebook',
				'type'     => 'post',
				'id'       => '',
				'url'      => $url,
			);
		}

		return false;
	}

	/**
	 * Resolve a short link to its destination without downloading the body.
	 *
	 * @param string $url Short URL.
	 * @return string|false
	 */
	private static function follow_redirect( $url ) {
		$cache_key = 'ose_rd_' . md5( $url );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}
		$response = wp_safe_remote_head(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 5,
				'user-agent'  => self::user_agent(),
			)
		);
		if ( is_wp_error( $response ) ) {
			set_transient( $cache_key, $url, HOUR_IN_SECONDS );
			return $url;
		}
		$final = isset( $response['http_response'] ) && method_exists( $response['http_response'], 'get_response_object' )
			? $response['http_response']->get_response_object()->url
			: $url;
		$final = esc_url_raw( (string) $final );
		set_transient( $cache_key, $final, DAY_IN_SECONDS );
		return $final;
	}

	/**
	 * Build the player iframe URL for a parsed video.
	 *
	 * @param array $parsed Parse result.
	 * @param bool  $captioned Include the caption panel (Instagram only).
	 * @return string
	 */
	public static function embed_url( $parsed, $captioned = false ) {
		if ( empty( $parsed['platform'] ) ) {
			return '';
		}
		if ( 'instagram' === $parsed['platform'] ) {
			if ( empty( $parsed['id'] ) ) {
				return '';
			}
			$type = in_array( $parsed['type'], array( 'p', 'reel', 'tv' ), true ) ? $parsed['type'] : 'p';
			$base = 'https://www.instagram.com/' . $type . '/' . rawurlencode( $parsed['id'] ) . '/embed/';
			if ( $captioned ) {
				$base .= 'captioned/';
			}
			return $base;
		}
		if ( 'facebook' === $parsed['platform'] ) {
			return add_query_arg(
				array(
					'href'        => rawurlencode( $parsed['url'] ),
					'show_text'   => 'false',
					'autoplay'    => 'true',
					'allowfullscreen' => 'true',
				),
				'https://www.facebook.com/plugins/video.php'
			);
		}
		return '';
	}

	/**
	 * Best-effort metadata (poster, title, author) for a URL.
	 *
	 * Results are cached; failures are cached for a shorter window so a
	 * temporarily unreachable network does not stall every page view.
	 *
	 * @param string $url     Video URL.
	 * @param bool   $refresh Force a refresh.
	 * @return array{thumbnail:string,title:string,author:string,html:string}
	 */
	public static function fetch_meta( $url, $refresh = false ) {
		static $budget = null;

		$empty  = array(
			'thumbnail' => '',
			'title'     => '',
			'author'    => '',
			'html'      => '',
		);
		$parsed = self::parse( $url );
		if ( ! $parsed ) {
			return $empty;
		}

		$key = 'ose_meta_' . md5( $parsed['url'] );
		if ( ! $refresh ) {
			$cached = get_transient( $key );
			if ( is_array( $cached ) ) {
				return wp_parse_args( $cached, $empty );
			}
		}

		/*
		 * A cold cache must never turn a page view into a chain of remote calls.
		 * Only a few lookups are allowed per request; the rest resolve on later
		 * views, or instantly in the admin where $refresh is true.
		 */
		if ( ! $refresh && ! is_admin() && ! wp_doing_cron() ) {
			if ( null === $budget ) {
				/**
				 * Filter how many posters may be resolved during one front-end request.
				 *
				 * @param int $budget Number of lookups.
				 */
				$budget = (int) apply_filters( 'ose_front_fetch_budget', 4 );
			}
			if ( $budget <= 0 ) {
				return $empty;
			}
			--$budget;
		}

		$settings = OSE_Settings::get();
		$data     = $empty;

		// 1) Official oEmbed, when an app token is configured.
		$token = trim( (string) $settings['fb_app_id'] ) && trim( (string) $settings['fb_app_secret'] )
			? trim( $settings['fb_app_id'] ) . '|' . trim( $settings['fb_app_secret'] )
			: trim( (string) $settings['fb_access_token'] );

		if ( $token ) {
			$data = self::oembed_official( $parsed, $token );
		}

		// 2) Public embed page scrape (no credentials needed).
		if ( '' === $data['thumbnail'] ) {
			$scraped = self::scrape_embed_page( $parsed );
			foreach ( $scraped as $k => $v ) {
				if ( '' === $data[ $k ] && '' !== $v ) {
					$data[ $k ] = $v;
				}
			}
		}

		$ttl = '' !== $data['thumbnail'] ? (int) $settings['cache_ttl'] * HOUR_IN_SECONDS : HOUR_IN_SECONDS;
		set_transient( $key, $data, max( HOUR_IN_SECONDS, $ttl ) );

		return $data;
	}

	/**
	 * Query the Graph oEmbed endpoints.
	 *
	 * @param array  $parsed Parse result.
	 * @param string $token  Access token.
	 * @return array
	 */
	private static function oembed_official( $parsed, $token ) {
		$out      = array(
			'thumbnail' => '',
			'title'     => '',
			'author'    => '',
			'html'      => '',
		);
		$endpoint = 'instagram' === $parsed['platform']
			? 'https://graph.facebook.com/v19.0/instagram_oembed'
			: 'https://graph.facebook.com/v19.0/oembed_video';

		$request = add_query_arg(
			array(
				'url'          => rawurlencode( $parsed['url'] ),
				'access_token' => rawurlencode( $token ),
				'omitscript'   => 'true',
				'fields'       => 'thumbnail_url,author_name,title,html',
			),
			$endpoint
		);

		$response = wp_safe_remote_get(
			$request,
			array(
				'timeout'    => 8,
				'user-agent' => self::user_agent(),
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return $out;
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return $out;
		}
		$out['thumbnail'] = isset( $body['thumbnail_url'] ) ? esc_url_raw( $body['thumbnail_url'] ) : '';
		$out['author']    = isset( $body['author_name'] ) ? sanitize_text_field( $body['author_name'] ) : '';
		$out['title']     = isset( $body['title'] ) ? sanitize_text_field( $body['title'] ) : '';
		return $out;
	}

	/**
	 * Read the public embed page and pull out whatever is there.
	 *
	 * @param array $parsed Parse result.
	 * @return array
	 */
	private static function scrape_embed_page( $parsed ) {
		$out = array(
			'thumbnail' => '',
			'title'     => '',
			'author'    => '',
			'html'      => '',
		);

		$target = 'instagram' === $parsed['platform']
			? self::embed_url( $parsed, true )
			: $parsed['url'];

		if ( '' === $target ) {
			return $out;
		}

		$response = wp_safe_remote_get(
			$target,
			array(
				'timeout'    => 8,
				'user-agent' => self::user_agent(),
				'headers'    => array( 'Accept-Language' => 'en-US,en;q=0.9' ),
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return $out;
		}
		$html = wp_remote_retrieve_body( $response );
		if ( ! is_string( $html ) || '' === $html ) {
			return $out;
		}

		// og:image / twitter:image.
		if ( preg_match( '#<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']#i', $html, $m ) ) {
			$out['thumbnail'] = esc_url_raw( html_entity_decode( $m[1] ) );
		} elseif ( preg_match( '#<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']#i', $html, $m ) ) {
			$out['thumbnail'] = esc_url_raw( html_entity_decode( $m[1] ) );
		} elseif ( preg_match( '#class="EmbeddedMediaImage"[^>]*src="([^"]+)"#i', $html, $m ) ) {
			$out['thumbnail'] = esc_url_raw( html_entity_decode( $m[1] ) );
		} elseif ( preg_match( '#"display_url"\s*:\s*"([^"]+)"#', $html, $m ) ) {
			$out['thumbnail'] = esc_url_raw( stripslashes( $m[1] ) );
		}

		// Title / description.
		if ( preg_match( '#<meta[^>]+property=["\']og:(?:title|description)["\'][^>]+content=["\']([^"\']+)["\']#i', $html, $m ) ) {
			$out['title'] = sanitize_text_field( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
		}

		// Author handle.
		if ( preg_match( '#"owner"\s*:\s*\{[^}]*"username"\s*:\s*"([^"]+)"#', $html, $m ) ) {
			$out['author'] = sanitize_text_field( $m[1] );
		} elseif ( preg_match( '#<span class="UsernameText">([^<]+)</span>#i', $html, $m ) ) {
			$out['author'] = sanitize_text_field( $m[1] );
		}

		return $out;
	}

	/**
	 * Locally mirror a remote poster into the media library.
	 *
	 * @param string $url     Remote image URL.
	 * @param int    $post_id Attach to this post.
	 * @return int Attachment id or 0.
	 */
	public static function sideload_poster( $url, $post_id = 0 ) {
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$url = esc_url_raw( $url );
		if ( '' === $url ) {
			return 0;
		}
		$id = media_sideload_image( $url, $post_id, null, 'id' );
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * User agent used for outgoing requests.
	 *
	 * @return string
	 */
	private static function user_agent() {
		return 'Mozilla/5.0 (compatible; OfnoaSocialEmbed/' . OSE_VERSION . '; +' . home_url( '/' ) . ')';
	}

	/**
	 * Clear every cached lookup.
	 *
	 * @return int Rows removed.
	 */
	public static function flush_cache() {
		global $wpdb;
		$count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_ose_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_ose_' ) . '%'
			)
		);
		wp_cache_flush();
		return (int) $count;
	}
}
