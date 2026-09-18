<?php
/**
 * A small REST surface used by the editor tooling.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST routes.
 */
class OSE_REST {

	const NS = 'ose/v1';

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public static function routes() {
		register_rest_route(
			self::NS,
			'/resolve',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'resolve' ),
				'permission_callback' => array( __CLASS__, 'can_edit' ),
				'args'                => array(
					'url' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/collections',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'collections' ),
				'permission_callback' => array( __CLASS__, 'can_edit' ),
			)
		);
	}

	/**
	 * Capability gate.
	 *
	 * @return bool
	 */
	public static function can_edit() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Resolve one URL.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function resolve( $request ) {
		$url    = $request->get_param( 'url' );
		$parsed = OSE_Resolver::parse( $url );

		if ( ! $parsed ) {
			return new WP_Error(
				'ose_unrecognised',
				__( 'That link is not an Instagram or Facebook video.', 'ofnoa-social-embed' ),
				array( 'status' => 400 )
			);
		}

		$meta = OSE_Resolver::fetch_meta( $parsed['url'] );

		return rest_ensure_response(
			array(
				'platform'  => $parsed['platform'],
				'type'      => $parsed['type'],
				'id'        => $parsed['id'],
				'url'       => $parsed['url'],
				'embed'     => OSE_Resolver::embed_url( $parsed ),
				'thumbnail' => $meta['thumbnail'],
				'title'     => $meta['title'],
				'author'    => $meta['author'],
			)
		);
	}

	/**
	 * List collections.
	 *
	 * @return WP_REST_Response
	 */
	public static function collections() {
		return rest_ensure_response( OSE_Query::all_collections() );
	}
}
