<?php
/**
 * Gutenberg block. Attributes are generated from the shared control schema so
 * the block panel always exposes exactly the same options as everywhere else.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block.
 */
class OSE_Block {

	const NAME = 'ofnoa/social-embed';

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'block_categories_all', array( __CLASS__, 'category' ), 10, 1 );
	}

	/**
	 * Add our own block category.
	 *
	 * @param array $categories Existing categories.
	 * @return array
	 */
	public static function category( $categories ) {
		foreach ( $categories as $cat ) {
			if ( isset( $cat['slug'] ) && 'ofnoa' === $cat['slug'] ) {
				return $categories;
			}
		}
		array_unshift(
			$categories,
			array(
				'slug'  => 'ofnoa',
				'title' => __( 'Ofnoa', 'ofnoa-social-embed' ),
				'icon'  => null,
			)
		);
		return $categories;
	}

	/**
	 * Attribute definitions for register_block_type().
	 *
	 * @return array
	 */
	public static function attributes() {
		$attrs = array();
		foreach ( OSE_Helpers::schema() as $key => $def ) {
			switch ( $def['type'] ) {
				case 'toggle':
					$attrs[ $key ] = array(
						'type'    => 'boolean',
						'default' => (bool) $def['default'],
					);
					break;
				case 'number':
				case 'range':
					$attrs[ $key ] = array(
						'type'    => 'number',
						'default' => (float) $def['default'],
					);
					break;
				default:
					$attrs[ $key ] = array(
						'type'    => 'string',
						'default' => (string) $def['default'],
					);
			}
		}
		// Block alignment (wide / full) is separate from the card text alignment.
		$attrs['align'] = array( 'type' => 'string' );

		return $attrs;
	}

	/**
	 * Register the block.
	 *
	 * @return void
	 */
	public static function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::NAME,
			array(
				'api_version'     => 2,
				'title'           => __( 'Social Video Gallery', 'ofnoa-social-embed' ),
				'description'     => __( 'Instagram, TikTok & Facebook videos in a designed grid, tabs, carousel or stories bar.', 'ofnoa-social-embed' ),
				'category'        => 'ofnoa',
				'icon'            => 'format-video',
				'keywords'        => array( 'instagram', 'tiktok', 'facebook', 'reels', 'video', 'gallery', 'embed' ),
				'attributes'      => self::attributes(),
				'supports'        => array(
					'align'  => array( 'wide', 'full' ),
					'anchor' => true,
					'html'   => false,
				),
				'render_callback' => array( __CLASS__, 'render' ),
				'editor_script'   => 'ose-block',
				'style'           => 'ose-frontend',
			)
		);
	}

	/**
	 * Server-side render.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		$attributes = is_array( $attributes ) ? $attributes : array();

		// Booleans arrive as real booleans from the block editor.
		foreach ( $attributes as $key => $value ) {
			if ( is_bool( $value ) ) {
				$attributes[ $key ] = $value ? 1 : 0;
			}
		}

		$extra = '';
		if ( ! empty( $attributes['align'] ) ) {
			$extra = ' align' . sanitize_html_class( $attributes['align'] );
			unset( $attributes['align'] );
		}

		$html = OSE_Render::gallery( $attributes );
		if ( '' === $html ) {
			return '';
		}

		return '<div class="wp-block-ofnoa-social-embed' . esc_attr( $extra ) . '">' . $html . '</div>';
	}

	/**
	 * Schema shaped for the editor UI.
	 *
	 * @return array
	 */
	public static function editor_schema() {
		$out = array();
		foreach ( OSE_Helpers::schema() as $key => $def ) {
			$out[] = array(
				'key'     => $key,
				'group'   => $def['group'],
				'label'   => $def['label'],
				'type'    => $def['type'],
				'options' => isset( $def['options'] )
					? array_map(
						function ( $value, $label ) {
							return array(
								'value' => (string) $value,
								'label' => (string) $label,
							);
						},
						array_keys( $def['options'] ),
						array_values( $def['options'] )
					)
					: array(),
				'min'     => isset( $def['min'] ) ? $def['min'] : null,
				'max'     => isset( $def['max'] ) ? $def['max'] : null,
				'default' => $def['default'],
			);
		}
		return $out;
	}
}
