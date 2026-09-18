<?php
/**
 * Asset registration. Front-end CSS/JS are only enqueued on pages that
 * actually render a gallery.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Assets.
 */
class OSE_Assets {

	/**
	 * Whether a gallery was rendered on this request.
	 *
	 * @var bool
	 */
	private static $needed = false;

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		// Registered on init so block registration can reference the handles.
		add_action( 'init', array( __CLASS__, 'register_front' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_early' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor' ) );
	}

	/**
	 * Register (not enqueue) the front-end bundle.
	 *
	 * @return void
	 */
	public static function register_front() {
		wp_register_style( 'ose-frontend', OSE_URL . 'assets/css/ose-frontend.css', array(), OSE_VERSION );
		wp_register_script( 'ose-frontend', OSE_URL . 'assets/js/ose-frontend.js', array(), OSE_VERSION, true );

		wp_register_script(
			'ose-block',
			OSE_URL . 'blocks/social-embed/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render', 'wp-data' ),
			OSE_VERSION,
			true
		);
	}

	/**
	 * The customizer preview renders widgets outside the normal flow.
	 *
	 * @return void
	 */
	public static function maybe_enqueue_early() {
		if ( is_customize_preview() ) {
			self::enqueue_front();
		}
	}

	/**
	 * Called by the renderer.
	 *
	 * @return void
	 */
	public static function mark_needed() {
		if ( self::$needed ) {
			return;
		}
		self::$needed = true;
		self::enqueue_front();
	}

	/**
	 * Enqueue the bundle and print the custom CSS.
	 *
	 * @return void
	 */
	private static function enqueue_front() {
		$settings = OSE_Settings::get();

		if ( ! empty( $settings['load_css'] ) ) {
			wp_enqueue_style( 'ose-frontend' );
			if ( ! empty( $settings['custom_css'] ) ) {
				wp_add_inline_style( 'ose-frontend', $settings['custom_css'] );
			}
		}
		wp_enqueue_script( 'ose-frontend' );
	}

	/**
	 * Admin styles and the metabox helper script.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function admin( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_ose = ( $screen && OSE_CPT::POST_TYPE === $screen->post_type )
			|| in_array( $hook, array( 'widgets.php', 'customize.php' ), true );

		if ( ! $is_ose ) {
			return;
		}

		wp_enqueue_style( 'ose-admin', OSE_URL . 'assets/css/ose-admin.css', array( 'wp-color-picker' ), OSE_VERSION );
		wp_enqueue_script(
			'ose-admin',
			OSE_URL . 'assets/js/ose-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			OSE_VERSION,
			true
		);
		wp_enqueue_media();
		wp_localize_script(
			'ose-admin',
			'OSE_ADMIN',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'choose'   => __( 'Choose a poster image', 'ofnoa-social-embed' ),
					'use'      => __( 'Use this image', 'ofnoa-social-embed' ),
					'fetching' => __( 'Fetching…', 'ofnoa-social-embed' ),
					'fetch'    => __( 'Fetch automatically', 'ofnoa-social-embed' ),
					'failed'   => __( 'Could not resolve that link.', 'ofnoa-social-embed' ),
				),
			)
		);
	}

	/**
	 * Block editor assets.
	 *
	 * @return void
	 */
	public static function editor() {
		wp_enqueue_script( 'ose-block' );
		wp_enqueue_style( 'ose-frontend' );
		wp_enqueue_style( 'ose-block-editor', OSE_URL . 'assets/css/ose-admin.css', array(), OSE_VERSION );

		wp_localize_script(
			'ose-block',
			'OSE_BLOCK_DATA',
			array(
				'schema'      => OSE_Block::editor_schema(),
				'collections' => OSE_Query::all_collections(),
				'defaults'    => OSE_Settings::design_defaults(),
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'ose-block', 'ofnoa-social-embed', OSE_DIR . 'languages' );
		}
	}
}
