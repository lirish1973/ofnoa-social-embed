<?php
/**
 * The video library: a custom post type plus a "collection" taxonomy that
 * doubles as the source of the tabbed layout.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Custom post type + taxonomy.
 */
class OSE_CPT {

	const POST_TYPE = 'ose_video';
	const TAXONOMY  = 'ose_collection';

	/**
	 * Hook everything up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'default_admin_order' ) );
	}

	/**
	 * Register the post type.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Social Videos', 'ofnoa-social-embed' ),
			'singular_name'      => __( 'Social Video', 'ofnoa-social-embed' ),
			'menu_name'          => __( 'Social Embed', 'ofnoa-social-embed' ),
			'add_new'            => __( 'Add video', 'ofnoa-social-embed' ),
			'add_new_item'       => __( 'Add a video', 'ofnoa-social-embed' ),
			'edit_item'          => __( 'Edit video', 'ofnoa-social-embed' ),
			'new_item'           => __( 'New video', 'ofnoa-social-embed' ),
			'view_item'          => __( 'View video', 'ofnoa-social-embed' ),
			'search_items'       => __( 'Search videos', 'ofnoa-social-embed' ),
			'not_found'          => __( 'No videos yet — paste an Instagram, TikTok or Facebook link to begin.', 'ofnoa-social-embed' ),
			'not_found_in_trash' => __( 'No videos in the trash.', 'ofnoa-social-embed' ),
			'all_items'          => __( 'All videos', 'ofnoa-social-embed' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-format-video',
				'menu_position'       => 26,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'exclude_from_search' => true,
				'capability_type'     => 'post',
			)
		);
	}

	/**
	 * Register the collection taxonomy.
	 *
	 * @return void
	 */
	public static function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Collections', 'ofnoa-social-embed' ),
					'singular_name' => __( 'Collection', 'ofnoa-social-embed' ),
					'add_new_item'  => __( 'Add collection', 'ofnoa-social-embed' ),
					'edit_item'     => __( 'Edit collection', 'ofnoa-social-embed' ),
					'search_items'  => __( 'Search collections', 'ofnoa-social-embed' ),
					'all_items'     => __( 'All collections', 'ofnoa-social-embed' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'hierarchical'      => true,
				'rewrite'           => false,
			)
		);
	}

	/**
	 * Admin list columns.
	 *
	 * @param array $cols Existing columns.
	 * @return array
	 */
	public static function columns( $cols ) {
		$new = array();
		foreach ( $cols as $key => $label ) {
			if ( 'title' === $key ) {
				$new['ose_thumb'] = __( 'Preview', 'ofnoa-social-embed' );
			}
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['ose_platform'] = __( 'Platform', 'ofnoa-social-embed' );
				$new['ose_url']      = __( 'Link', 'ofnoa-social-embed' );
			}
		}
		$new['menu_order'] = __( 'Order', 'ofnoa-social-embed' );
		return $new;
	}

	/**
	 * Render admin list cells.
	 *
	 * @param string $col     Column key.
	 * @param int    $post_id Post id.
	 * @return void
	 */
	public static function column_content( $col, $post_id ) {
		switch ( $col ) {
			case 'ose_thumb':
				$thumb = OSE_Query::poster_url( $post_id );
				if ( $thumb ) {
					printf(
						'<img src="%s" alt="" style="width:54px;height:72px;object-fit:cover;border-radius:8px;display:block" loading="lazy" />',
						esc_url( $thumb )
					);
				} else {
					echo '<span class="ose-noimg" style="display:flex;width:54px;height:72px;border-radius:8px;align-items:center;justify-content:center;background:linear-gradient(135deg,#8a3ab9,#e1306c);color:#fff;font-size:11px">&#9654;</span>';
				}
				break;

			case 'ose_platform':
				$platform = get_post_meta( $post_id, '_ose_platform', true );
				$map      = array(
					'instagram' => 'Instagram',
					'facebook'  => 'Facebook',
					'tiktok'    => 'TikTok',
				);
				echo esc_html( isset( $map[ $platform ] ) ? $map[ $platform ] : '—' );
				break;

			case 'ose_url':
				$url = get_post_meta( $post_id, '_ose_url', true );
				if ( $url ) {
					printf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url( $url ),
						esc_html( wp_html_excerpt( $url, 46, '…' ) )
					);
				}
				break;

			case 'menu_order':
				echo (int) get_post_field( 'menu_order', $post_id );
				break;
		}
	}

	/**
	 * Allow sorting by manual order.
	 *
	 * @param array $cols Sortable columns.
	 * @return array
	 */
	public static function sortable( $cols ) {
		$cols['menu_order'] = 'menu_order';
		return $cols;
	}

	/**
	 * Default the admin list to manual order.
	 *
	 * @param WP_Query $query Query object.
	 * @return void
	 */
	public static function default_admin_order( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( ! $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}
	}
}
