<?php
/**
 * Shortcode entry point.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode.
 */
class OSE_Shortcode {

	const TAG = 'ofnoa_social_embed';

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( self::TAG, array( __CLASS__, 'render' ) );
		add_shortcode( 'ofnoa_reels', array( __CLASS__, 'render' ) );
		add_filter( 'widget_text', 'do_shortcode' );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();

		// Shortcodes lowercase their keys already; normalise anyway.
		$clean = array();
		foreach ( $atts as $key => $value ) {
			$clean[ strtolower( (string) $key ) ] = $value;
		}

		return OSE_Render::gallery( $clean );
	}
}
