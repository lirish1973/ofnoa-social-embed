<?php
/**
 * Elementor widget, generated from the same control schema.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Elementor integration bootstrap.
 */
class OSE_Elementor {

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register' ) );
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'category' ) );
	}

	/**
	 * Register the widget.
	 *
	 * @param mixed $widgets_manager Elementor widget manager.
	 * @return void
	 */
	public static function register( $widgets_manager ) {
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}
		require_once OSE_DIR . 'includes/class-ose-elementor-widget.php';
		$widgets_manager->register( new OSE_Elementor_Widget() );
	}

	/**
	 * Add an Ofnoa category to the panel.
	 *
	 * @param mixed $manager Elements manager.
	 * @return void
	 */
	public static function category( $manager ) {
		if ( ! is_object( $manager ) || ! method_exists( $manager, 'add_category' ) ) {
			return;
		}
		$manager->add_category(
			'ofnoa',
			array(
				'title' => __( 'Ofnoa', 'ofnoa-social-embed' ),
				'icon'  => 'eicon-video-camera',
			)
		);
	}
}
