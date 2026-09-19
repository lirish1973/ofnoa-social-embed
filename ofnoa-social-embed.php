<?php
/**
 * Plugin Name:       Ofnoa Social Embed
 * Plugin URI:        https://github.com/lirish1973/ofnoa-social-embed
 * Description:       Embed Instagram, TikTok & Facebook videos (Reels, posts, IGTV, TikToks, FB videos) in stunning, fully customizable grids, tabs, carousels and spotlight layouts. Gutenberg block, classic widget, shortcode and Elementor widget included.
 * Version:           1.1.5
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Ofnoacomps
 * Author URI:        https://ofnoacomps.co.il
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ofnoa-social-embed
 * Domain Path:       /languages
 * GitHub Plugin URI: lirish1973/ofnoa-social-embed
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

define( 'OSE_VERSION', '1.1.5' );
define( 'OSE_FILE', __FILE__ );
define( 'OSE_DIR', plugin_dir_path( __FILE__ ) );
define( 'OSE_URL', plugin_dir_url( __FILE__ ) );
define( 'OSE_BASENAME', plugin_basename( __FILE__ ) );
define( 'OSE_GH_USER', 'lirish1973' );
define( 'OSE_GH_REPO', 'ofnoa-social-embed' );

require_once OSE_DIR . 'includes/class-ose-helpers.php';
require_once OSE_DIR . 'includes/class-ose-resolver.php';
require_once OSE_DIR . 'includes/class-ose-cpt.php';
require_once OSE_DIR . 'includes/class-ose-metabox.php';
require_once OSE_DIR . 'includes/class-ose-settings.php';
require_once OSE_DIR . 'includes/class-ose-query.php';
require_once OSE_DIR . 'includes/class-ose-render.php';
require_once OSE_DIR . 'includes/class-ose-assets.php';
require_once OSE_DIR . 'includes/class-ose-shortcode.php';
require_once OSE_DIR . 'includes/class-ose-widget.php';
require_once OSE_DIR . 'includes/class-ose-block.php';
require_once OSE_DIR . 'includes/class-ose-rest.php';
require_once OSE_DIR . 'includes/class-ose-elementor.php';
require_once OSE_DIR . 'includes/class-ose-updater.php';

/**
 * Main plugin container.
 */
final class Ofnoa_Social_Embed {

	/**
	 * Singleton instance.
	 *
	 * @var Ofnoa_Social_Embed|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Ofnoa_Social_Embed
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: wires every module.
	 */
	private function __construct() {
		OSE_CPT::init();
		OSE_Metabox::init();
		OSE_Settings::init();
		OSE_Assets::init();
		OSE_Shortcode::init();
		OSE_Widget::init();
		OSE_Block::init();
		OSE_REST::init();
		OSE_Elementor::init();
		OSE_Updater::init();

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_filter( 'plugin_action_links_' . OSE_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'ofnoa-social-embed', false, dirname( OSE_BASENAME ) . '/languages' );
	}

	/**
	 * Add quick links on the plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$custom = array(
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=ose_video&page=ose-settings' ) ) . '">' . esc_html__( 'Settings', 'ofnoa-social-embed' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=ose_video' ) ) . '">' . esc_html__( 'Videos', 'ofnoa-social-embed' ) . '</a>',
		);
		return array_merge( $custom, $links );
	}

	/**
	 * Activation routine.
	 *
	 * @return void
	 */
	public static function activate() {
		require_once OSE_DIR . 'includes/class-ose-cpt.php';
		OSE_CPT::register_post_type();
		OSE_CPT::register_taxonomy();
		if ( false === get_option( 'ose_settings' ) ) {
			add_option( 'ose_settings', OSE_Settings::defaults() );
		}
		flush_rewrite_rules();
	}

	/**
	 * Deactivation routine.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}

register_activation_hook( __FILE__, array( 'Ofnoa_Social_Embed', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Ofnoa_Social_Embed', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Ofnoa_Social_Embed', 'instance' ) );
