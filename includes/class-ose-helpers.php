<?php
/**
 * Shared helpers: the single source of truth for every design control.
 *
 * Every integration (shortcode, classic widget, Gutenberg block, Elementor)
 * builds its UI from OSE_Helpers::schema() so the whole design surface stays
 * identical everywhere and adding a control in one place adds it everywhere.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Helper utilities.
 */
class OSE_Helpers {

	/**
	 * Cached schema.
	 *
	 * @var array|null
	 */
	private static $schema = null;

	/**
	 * Choice lists used across the UI.
	 *
	 * @return array
	 */
	public static function choices() {
		return array(
			'layout'       => array(
				'grid'      => __( 'Grid', 'ofnoa-social-embed' ),
				'masonry'   => __( 'Masonry', 'ofnoa-social-embed' ),
				'carousel'  => __( 'Carousel / Slider', 'ofnoa-social-embed' ),
				'tabs'      => __( 'Tabs (by collection)', 'ofnoa-social-embed' ),
				'spotlight' => __( 'Spotlight (hero + list)', 'ofnoa-social-embed' ),
				'reels'     => __( 'Reels row (horizontal scroll)', 'ofnoa-social-embed' ),
				'stories'   => __( 'Stories bar (circles)', 'ofnoa-social-embed' ),
				'list'      => __( 'List', 'ofnoa-social-embed' ),
			),
			'card_style'   => array(
				'glass'     => __( 'Glassmorphism', 'ofnoa-social-embed' ),
				'neon'      => __( 'Neon glow', 'ofnoa-social-embed' ),
				'minimal'   => __( 'Minimal', 'ofnoa-social-embed' ),
				'gradient'  => __( 'Gradient border', 'ofnoa-social-embed' ),
				'elevated'  => __( 'Elevated card', 'ofnoa-social-embed' ),
				'outline'   => __( 'Outline', 'ofnoa-social-embed' ),
				'polaroid'  => __( 'Polaroid', 'ofnoa-social-embed' ),
				'cinematic' => __( 'Cinematic', 'ofnoa-social-embed' ),
			),
			'hover_effect' => array(
				'none'   => __( 'None', 'ofnoa-social-embed' ),
				'zoom'   => __( 'Zoom image', 'ofnoa-social-embed' ),
				'lift'   => __( 'Lift up', 'ofnoa-social-embed' ),
				'tilt'   => __( '3D tilt', 'ofnoa-social-embed' ),
				'glow'   => __( 'Glow', 'ofnoa-social-embed' ),
				'reveal' => __( 'Reveal overlay', 'ofnoa-social-embed' ),
				'slide'  => __( 'Slide up info', 'ofnoa-social-embed' ),
				'blur'   => __( 'Blur siblings', 'ofnoa-social-embed' ),
			),
			'aspect'       => array(
				'9-16' => __( 'Vertical 9:16 (Reels)', 'ofnoa-social-embed' ),
				'4-5'  => __( 'Portrait 4:5', 'ofnoa-social-embed' ),
				'1-1'  => __( 'Square 1:1', 'ofnoa-social-embed' ),
				'16-9' => __( 'Landscape 16:9', 'ofnoa-social-embed' ),
				'auto' => __( 'Auto (native)', 'ofnoa-social-embed' ),
			),
			'shadow'       => array(
				'none' => __( 'None', 'ofnoa-social-embed' ),
				'sm'   => __( 'Soft', 'ofnoa-social-embed' ),
				'md'   => __( 'Medium', 'ofnoa-social-embed' ),
				'lg'   => __( 'Large', 'ofnoa-social-embed' ),
				'xl'   => __( 'Dramatic', 'ofnoa-social-embed' ),
				'glow' => __( 'Accent glow', 'ofnoa-social-embed' ),
			),
			'overlay'      => array(
				'none'   => __( 'No overlay', 'ofnoa-social-embed' ),
				'bottom' => __( 'Bottom gradient', 'ofnoa-social-embed' ),
				'full'   => __( 'Full tint', 'ofnoa-social-embed' ),
				'hover'  => __( 'On hover only', 'ofnoa-social-embed' ),
			),
			'play_mode'    => array(
				'lightbox' => __( 'Lightbox (recommended)', 'ofnoa-social-embed' ),
				'inline'   => __( 'Play inside the card', 'ofnoa-social-embed' ),
				'newtab'   => __( 'Open on the network', 'ofnoa-social-embed' ),
			),
			'entrance'     => array(
				'none' => __( 'None', 'ofnoa-social-embed' ),
				'fade' => __( 'Fade in', 'ofnoa-social-embed' ),
				'up'   => __( 'Slide up', 'ofnoa-social-embed' ),
				'zoom' => __( 'Zoom in', 'ofnoa-social-embed' ),
				'flip' => __( 'Flip in', 'ofnoa-social-embed' ),
			),
			'skin'         => array(
				'auto'  => __( 'Follow the theme', 'ofnoa-social-embed' ),
				'light' => __( 'Light', 'ofnoa-social-embed' ),
				'dark'  => __( 'Dark', 'ofnoa-social-embed' ),
			),
			'orderby'      => array(
				'menu_order' => __( 'Manual order', 'ofnoa-social-embed' ),
				'date'       => __( 'Date added', 'ofnoa-social-embed' ),
				'title'      => __( 'Title', 'ofnoa-social-embed' ),
				'rand'       => __( 'Random', 'ofnoa-social-embed' ),
			),
			'order'        => array(
				'DESC' => __( 'Descending', 'ofnoa-social-embed' ),
				'ASC'  => __( 'Ascending', 'ofnoa-social-embed' ),
			),
			'source'       => array(
				'library' => __( 'Video library (collections)', 'ofnoa-social-embed' ),
				'urls'    => __( 'Manual URL list', 'ofnoa-social-embed' ),
			),
			'badge_pos'    => array(
				'tl' => __( 'Top left', 'ofnoa-social-embed' ),
				'tr' => __( 'Top right', 'ofnoa-social-embed' ),
				'bl' => __( 'Bottom left', 'ofnoa-social-embed' ),
				'br' => __( 'Bottom right', 'ofnoa-social-embed' ),
			),
		);
	}

	/**
	 * The full design + behaviour schema.
	 *
	 * type: select | number | text | textarea | color | toggle | range
	 *
	 * @return array
	 */
	public static function schema() {
		if ( null !== self::$schema ) {
			return self::$schema;
		}
		$c = self::choices();

		$schema = array(

			/* ---------------- Content ---------------- */
			'source'            => array(
				'group'   => 'content',
				'label'   => __( 'Source', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['source'],
				'default' => 'library',
			),
			'collection'        => array(
				'group'   => 'content',
				'label'   => __( 'Collections (comma separated slugs)', 'ofnoa-social-embed' ),
				'type'    => 'text',
				'default' => '',
			),
			'urls'              => array(
				'group'   => 'content',
				'label'   => __( 'Video URLs (one per line)', 'ofnoa-social-embed' ),
				'type'    => 'textarea',
				'default' => '',
			),
			'ids'               => array(
				'group'   => 'content',
				'label'   => __( 'Specific video IDs (comma separated)', 'ofnoa-social-embed' ),
				'type'    => 'text',
				'default' => '',
			),
			'limit'             => array(
				'group'   => 'content',
				'label'   => __( 'How many videos', 'ofnoa-social-embed' ),
				'type'    => 'number',
				'default' => 12,
				'min'     => 1,
				'max'     => 100,
			),
			'orderby'           => array(
				'group'   => 'content',
				'label'   => __( 'Order by', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['orderby'],
				'default' => 'menu_order',
			),
			'order'             => array(
				'group'   => 'content',
				'label'   => __( 'Direction', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['order'],
				'default' => 'DESC',
			),
			'heading'           => array(
				'group'   => 'content',
				'label'   => __( 'Heading above the gallery', 'ofnoa-social-embed' ),
				'type'    => 'text',
				'default' => '',
			),
			'subheading'        => array(
				'group'   => 'content',
				'label'   => __( 'Sub heading', 'ofnoa-social-embed' ),
				'type'    => 'text',
				'default' => '',
			),

			/* ---------------- Layout ---------------- */
			'layout'            => array(
				'group'   => 'layout',
				'label'   => __( 'Layout', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['layout'],
				'default' => 'grid',
			),
			'columns'           => array(
				'group'   => 'layout',
				'label'   => __( 'Columns (desktop)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 4,
				'min'     => 1,
				'max'     => 8,
				'css_var' => '--ose-cols',
			),
			'columns_tablet'    => array(
				'group'   => 'layout',
				'label'   => __( 'Columns (tablet)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 3,
				'min'     => 1,
				'max'     => 6,
				'css_var' => '--ose-cols-t',
			),
			'columns_mobile'    => array(
				'group'   => 'layout',
				'label'   => __( 'Columns (mobile)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 2,
				'min'     => 1,
				'max'     => 4,
				'css_var' => '--ose-cols-m',
			),
			'gap'               => array(
				'group'   => 'layout',
				'label'   => __( 'Gap between cards (px)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 18,
				'min'     => 0,
				'max'     => 80,
				'unit'    => 'px',
				'css_var' => '--ose-gap',
			),
			'aspect'            => array(
				'group'   => 'layout',
				'label'   => __( 'Thumbnail ratio', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['aspect'],
				'default' => '9-16',
			),
			'max_width'         => array(
				'group'   => 'layout',
				'label'   => __( 'Max width (px, 0 = full)', 'ofnoa-social-embed' ),
				'type'    => 'number',
				'default' => 0,
				'min'     => 0,
				'max'     => 2400,
			),
			'text_align'        => array(
				'group'   => 'layout',
				'label'   => __( 'Text alignment', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => array(
					'start'  => __( 'Start', 'ofnoa-social-embed' ),
					'center' => __( 'Center', 'ofnoa-social-embed' ),
					'end'    => __( 'End', 'ofnoa-social-embed' ),
				),
				'default' => 'start',
				'css_var' => '--ose-align',
			),

			/* ---------------- Card ---------------- */
			'card_style'        => array(
				'group'   => 'card',
				'label'   => __( 'Card style', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['card_style'],
				'default' => 'glass',
			),
			'radius'            => array(
				'group'   => 'card',
				'label'   => __( 'Corner radius (px)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 18,
				'min'     => 0,
				'max'     => 60,
				'unit'    => 'px',
				'css_var' => '--ose-radius',
			),
			'border_width'      => array(
				'group'   => 'card',
				'label'   => __( 'Border width (px)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 1,
				'min'     => 0,
				'max'     => 10,
				'unit'    => 'px',
				'css_var' => '--ose-bw',
			),
			'shadow'            => array(
				'group'   => 'card',
				'label'   => __( 'Shadow', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['shadow'],
				'default' => 'md',
			),
			'overlay'           => array(
				'group'   => 'card',
				'label'   => __( 'Image overlay', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['overlay'],
				'default' => 'bottom',
			),
			'overlay_opacity'   => array(
				'group'   => 'card',
				'label'   => __( 'Overlay strength (%)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 70,
				'min'     => 0,
				'max'     => 100,
				'css_var' => '--ose-ov-op',
				'scale'   => 0.01,
			),
			'padding'           => array(
				'group'   => 'card',
				'label'   => __( 'Inner padding (px)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 14,
				'min'     => 0,
				'max'     => 48,
				'unit'    => 'px',
				'css_var' => '--ose-pad',
			),

			/* ---------------- Colors ---------------- */
			'accent'            => array(
				'group'   => 'colors',
				'label'   => __( 'Accent', 'ofnoa-social-embed' ),
				'type'    => 'color',
				'default' => '#e1306c',
				'css_var' => '--ose-accent',
			),
			'accent2'           => array(
				'group'   => 'colors',
				'label'   => __( 'Accent 2 (gradients)', 'ofnoa-social-embed' ),
				'type'    => 'color',
				'default' => '#8a3ab9',
				'css_var' => '--ose-accent2',
			),
			'card_bg'           => array(
				'group'   => 'colors',
				'label'   => __( 'Card background', 'ofnoa-social-embed' ),
				'type'    => 'color',
				'default' => '',
				'css_var' => '--ose-card-bg',
			),
			'border_color'      => array(
				'group'   => 'colors',
				'label'   => __( 'Border colour', 'ofnoa-social-embed' ),
				'type'    => 'color',
				'default' => '',
				'css_var' => '--ose-border',
			),
			'title_color'       => array(
				'group'   => 'colors',
				'label'   => __( 'Title colour', 'ofnoa-social-embed' ),
				'type'    => 'color',
				'default' => '',
				'css_var' => '--ose-title-color',
			),
			'meta_color'        => array(
				'group'   => 'colors',
				'label'   => __( 'Meta colour', 'ofnoa-social-embed' ),
				'type'    => 'color',
				'default' => '',
				'css_var' => '--ose-meta-color',
			),
			'overlay_color'     => array(
				'group'   => 'colors',
				'label'   => __( 'Overlay colour', 'ofnoa-social-embed' ),
				'type'    => 'color',
				'default' => '#000000',
				'css_var' => '--ose-ov-color',
			),
			'bg'                => array(
				'group'   => 'colors',
				'label'   => __( 'Section background', 'ofnoa-social-embed' ),
				'type'    => 'color',
				'default' => '',
				'css_var' => '--ose-bg',
			),
			'skin'              => array(
				'group'   => 'colors',
				'label'   => __( 'Colour scheme', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['skin'],
				'default' => 'auto',
			),

			/* ---------------- Typography ---------------- */
			'title_size'        => array(
				'group'   => 'typography',
				'label'   => __( 'Title size (px)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 15,
				'min'     => 10,
				'max'     => 34,
				'unit'    => 'px',
				'css_var' => '--ose-title-size',
			),
			'title_weight'      => array(
				'group'   => 'typography',
				'label'   => __( 'Title weight', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => array(
					'400' => '400',
					'500' => '500',
					'600' => '600',
					'700' => '700',
					'800' => '800',
					'900' => '900',
				),
				'default' => '700',
				'css_var' => '--ose-title-weight',
			),
			'meta_size'         => array(
				'group'   => 'typography',
				'label'   => __( 'Meta size (px)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 12,
				'min'     => 9,
				'max'     => 22,
				'unit'    => 'px',
				'css_var' => '--ose-meta-size',
			),
			'font_family'       => array(
				'group'   => 'typography',
				'label'   => __( 'Font family (CSS, empty = theme)', 'ofnoa-social-embed' ),
				'type'    => 'text',
				'default' => '',
				'css_var' => '--ose-font',
			),
			'title_lines'       => array(
				'group'   => 'typography',
				'label'   => __( 'Title lines before clamping', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 2,
				'min'     => 1,
				'max'     => 6,
				'css_var' => '--ose-title-lines',
			),

			/* ---------------- Elements ---------------- */
			'show_title'        => array(
				'group'   => 'elements',
				'label'   => __( 'Show title', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 1,
			),
			'show_caption'      => array(
				'group'   => 'elements',
				'label'   => __( 'Show caption', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 0,
			),
			'show_author'       => array(
				'group'   => 'elements',
				'label'   => __( 'Show author handle', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 1,
			),
			'show_date'         => array(
				'group'   => 'elements',
				'label'   => __( 'Show date', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 0,
			),
			'show_badge'        => array(
				'group'   => 'elements',
				'label'   => __( 'Show platform badge', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 1,
			),
			'badge_pos'         => array(
				'group'   => 'elements',
				'label'   => __( 'Badge position', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['badge_pos'],
				'default' => 'tr',
			),
			'show_play'         => array(
				'group'   => 'elements',
				'label'   => __( 'Show play button', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 1,
			),
			'show_duration'     => array(
				'group'   => 'elements',
				'label'   => __( 'Show duration chip', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 1,
			),
			'show_stats'        => array(
				'group'   => 'elements',
				'label'   => __( 'Show views / likes', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 0,
			),
			'show_filter'       => array(
				'group'   => 'elements',
				'label'   => __( 'Show filter bar', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 0,
			),
			'show_arrows'       => array(
				'group'   => 'elements',
				'label'   => __( 'Carousel arrows', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 1,
			),
			'show_dots'         => array(
				'group'   => 'elements',
				'label'   => __( 'Carousel dots', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 1,
			),
			'show_cta'          => array(
				'group'   => 'elements',
				'label'   => __( 'Show "Load more" button', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 0,
			),

			/* ---------------- Behaviour ---------------- */
			'play_mode'         => array(
				'group'   => 'behaviour',
				'label'   => __( 'When a card is clicked', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['play_mode'],
				'default' => 'lightbox',
			),
			'autoplay'          => array(
				'group'   => 'behaviour',
				'label'   => __( 'Auto advance carousel', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 0,
			),
			'autoplay_speed'    => array(
				'group'   => 'behaviour',
				'label'   => __( 'Auto advance every (ms)', 'ofnoa-social-embed' ),
				'type'    => 'number',
				'default' => 4000,
				'min'     => 1000,
				'max'     => 20000,
			),
			'loop'              => array(
				'group'   => 'behaviour',
				'label'   => __( 'Loop carousel', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 1,
			),
			'lazy'              => array(
				'group'   => 'behaviour',
				'label'   => __( 'Lazy load thumbnails', 'ofnoa-social-embed' ),
				'type'    => 'toggle',
				'default' => 1,
			),
			'hover_effect'      => array(
				'group'   => 'behaviour',
				'label'   => __( 'Hover effect', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['hover_effect'],
				'default' => 'zoom',
			),
			'hover_speed'       => array(
				'group'   => 'behaviour',
				'label'   => __( 'Transition speed (ms)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 400,
				'min'     => 0,
				'max'     => 1500,
				'unit'    => 'ms',
				'css_var' => '--ose-speed',
			),
			'entrance'          => array(
				'group'   => 'behaviour',
				'label'   => __( 'Entrance animation', 'ofnoa-social-embed' ),
				'type'    => 'select',
				'options' => $c['entrance'],
				'default' => 'up',
			),
			'stagger'           => array(
				'group'   => 'behaviour',
				'label'   => __( 'Stagger between cards (ms)', 'ofnoa-social-embed' ),
				'type'    => 'range',
				'default' => 60,
				'min'     => 0,
				'max'     => 400,
			),

			/* ---------------- Advanced ---------------- */
			'css_class'         => array(
				'group'   => 'advanced',
				'label'   => __( 'Extra CSS class', 'ofnoa-social-embed' ),
				'type'    => 'text',
				'default' => '',
			),
			'tabs_from'         => array(
				'group'   => 'advanced',
				'label'   => __( 'Tabs built from (slugs, empty = all collections)', 'ofnoa-social-embed' ),
				'type'    => 'text',
				'default' => '',
			),
			'tab_all_label'     => array(
				'group'   => 'advanced',
				'label'   => __( 'Label of the "all" tab (empty = hide)', 'ofnoa-social-embed' ),
				'type'    => 'text',
				'default' => '',
			),
			'follow_url'        => array(
				'group'   => 'advanced',
				'label'   => __( 'Follow button URL', 'ofnoa-social-embed' ),
				'type'    => 'text',
				'default' => '',
			),
			'follow_label'      => array(
				'group'   => 'advanced',
				'label'   => __( 'Follow button label', 'ofnoa-social-embed' ),
				'type'    => 'text',
				'default' => '',
			),
		);

		/**
		 * Filter the full control schema.
		 *
		 * @param array $schema Control definitions.
		 */
		self::$schema = apply_filters( 'ose_control_schema', $schema );
		return self::$schema;
	}

	/**
	 * Default value map.
	 *
	 * @return array
	 */
	public static function defaults() {
		$out = array();
		foreach ( self::schema() as $key => $def ) {
			$out[ $key ] = $def['default'];
		}
		return $out;
	}

	/**
	 * Sanitize a single value against its schema definition.
	 *
	 * @param string $key   Control key.
	 * @param mixed  $value Raw value.
	 * @return mixed
	 */
	public static function sanitize_value( $key, $value ) {
		$schema = self::schema();
		if ( ! isset( $schema[ $key ] ) ) {
			return null;
		}
		$def = $schema[ $key ];

		switch ( $def['type'] ) {
			case 'select':
				$value = is_scalar( $value ) ? (string) $value : '';
				return array_key_exists( $value, $def['options'] ) ? $value : $def['default'];

			case 'toggle':
				if ( is_string( $value ) ) {
					$value = in_array( strtolower( $value ), array( 'yes', 'true', '1', 'on' ), true ) ? 1 : 0;
				}
				return $value ? 1 : 0;

			case 'number':
			case 'range':
				$value = is_numeric( $value ) ? (float) $value : (float) $def['default'];
				if ( isset( $def['min'] ) ) {
					$value = max( (float) $def['min'], $value );
				}
				if ( isset( $def['max'] ) ) {
					$value = min( (float) $def['max'], $value );
				}
				return ( floor( $value ) === $value ) ? (int) $value : round( $value, 2 );

			case 'color':
				return self::sanitize_color( $value );

			case 'textarea':
				return is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';

			case 'text':
			default:
				return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
		}
	}

	/**
	 * Sanitize the whole attribute bag, filling defaults.
	 *
	 * @param array $atts Raw attributes.
	 * @return array
	 */
	public static function sanitize_atts( $atts ) {
		$atts   = is_array( $atts ) ? $atts : array();
		$out    = array();
		$schema = self::schema();
		$base   = class_exists( 'OSE_Settings' ) ? OSE_Settings::design_defaults() : self::defaults();

		foreach ( $schema as $key => $def ) {
			$fallback = array_key_exists( $key, $base ) ? $base[ $key ] : $def['default'];
			$given    = array_key_exists( $key, $atts ) ? $atts[ $key ] : null;
			$raw      = ( null === $given || '' === $given ) ? $fallback : $given;

			$out[ $key ] = self::sanitize_value( $key, $raw );
		}
		return $out;
	}

	/**
	 * Accept hex, rgb(a) and CSS variables; reject anything else.
	 *
	 * @param mixed $value Raw colour.
	 * @return string
	 */
	public static function sanitize_color( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^rgba?\(\s*[\d.]+\s*,\s*[\d.]+\s*,\s*[\d.]+\s*(,\s*[\d.]+\s*)?\)$/i', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^var\(--[A-Za-z0-9_-]+\)$/', $value ) ) {
			return $value;
		}
		$named = sanitize_hex_color( $value );
		return $named ? $named : '';
	}

	/**
	 * Escape an arbitrary string for use as a CSS value.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function esc_css( $value ) {
		$value = (string) $value;
		$value = str_replace( array( '<', '>', '{', '}', ';', '"', "'", '\\', '@' ), '', $value );
		return trim( preg_replace( '/[\r\n\t]+/', ' ', $value ) );
	}

	/**
	 * Build the inline CSS custom-property block for one instance.
	 *
	 * @param array $a Sanitized attributes.
	 * @return string
	 */
	public static function style_vars( $a ) {
		$vars   = array();
		$schema = self::schema();

		foreach ( $schema as $key => $def ) {
			if ( empty( $def['css_var'] ) ) {
				continue;
			}
			$value = isset( $a[ $key ] ) ? $a[ $key ] : $def['default'];
			if ( '' === $value || null === $value ) {
				continue;
			}
			if ( isset( $def['scale'] ) ) {
				$value = round( (float) $value * (float) $def['scale'], 3 );
			}
			if ( ! empty( $def['unit'] ) ) {
				$value = $value . $def['unit'];
			}
			$vars[ $def['css_var'] ] = self::esc_css( $value );
		}

		// Derived values.
		$ratio = self::aspect_ratio( isset( $a['aspect'] ) ? $a['aspect'] : '9-16' );
		if ( $ratio ) {
			$vars['--ose-ratio'] = $ratio;
		}
		if ( ! empty( $a['max_width'] ) ) {
			$vars['--ose-maxw'] = (int) $a['max_width'] . 'px';
		}
		if ( isset( $a['stagger'] ) ) {
			$vars['--ose-stagger'] = (int) $a['stagger'] . 'ms';
		}

		$out = '';
		foreach ( $vars as $prop => $val ) {
			$out .= $prop . ':' . $val . ';';
		}
		return $out;
	}

	/**
	 * Translate an aspect key into a CSS aspect-ratio value.
	 *
	 * @param string $key Aspect key.
	 * @return string
	 */
	public static function aspect_ratio( $key ) {
		$map = array(
			'9-16' => '9 / 16',
			'4-5'  => '4 / 5',
			'1-1'  => '1 / 1',
			'16-9' => '16 / 9',
			'auto' => '',
		);
		return isset( $map[ $key ] ) ? $map[ $key ] : '9 / 16';
	}

	/**
	 * Human readable compact number (12.4K).
	 *
	 * @param int $n Number.
	 * @return string
	 */
	public static function compact_number( $n ) {
		$n = (int) $n;
		if ( $n >= 1000000 ) {
			return rtrim( rtrim( number_format( $n / 1000000, 1 ), '0' ), '.' ) . 'M';
		}
		if ( $n >= 1000 ) {
			return rtrim( rtrim( number_format( $n / 1000, 1 ), '0' ), '.' ) . 'K';
		}
		return (string) $n;
	}

	/**
	 * Inline SVG icon markup.
	 *
	 * @param string $name Icon name.
	 * @return string
	 */
	public static function icon( $name ) {
		$icons = array(
			'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9a3.7 3.7 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16Zm0 3.18A6.66 6.66 0 1 0 18.66 12 6.66 6.66 0 0 0 12 5.34Zm0 10.99A4.33 4.33 0 1 1 16.33 12 4.33 4.33 0 0 1 12 16.33Zm6.92-11.25a1.56 1.56 0 1 1-1.56-1.55 1.56 1.56 0 0 1 1.56 1.55Z"/></svg>',
			'facebook'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.52 1.5-3.91 3.77-3.91 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.78-1.63 1.57v1.89h2.78l-.45 2.9h-2.33V22c4.78-.79 8.44-4.94 8.44-9.94Z"/></svg>',
			'tiktok'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 1 1-2.59-2.59c.27 0 .53.04.78.12v-3.2a5.8 5.8 0 0 0-.78-.05 5.79 5.79 0 1 0 5.79 5.79V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3a4.3 4.3 0 0 1-3.35-1.48Z"/></svg>',
			'play'      => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5.14v13.72c0 .78.85 1.26 1.52.86l11.06-6.86a1 1 0 0 0 0-1.72L9.52 4.28A1 1 0 0 0 8 5.14Z"/></svg>',
			'close'     => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.6 13.4 4.3 19.71 2.89 18.3 9.18 12 2.89 5.71 4.3 4.29l6.3 6.3 6.29-6.3Z"/></svg>',
			'prev'      => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15.4 4.6 7 13l8.4 8.4 1.4-1.4L9.8 13l7-7Z" transform="translate(0,-1)"/></svg>',
			'next'      => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8.6 4.6 17 13l-8.4 8.4-1.4-1.4L14.2 13l-7-7Z" transform="translate(0,-1)"/></svg>',
			'heart'     => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21s-7.5-4.6-9.5-9A5.3 5.3 0 0 1 12 6.6 5.3 5.3 0 0 1 21.5 12c-2 4.4-9.5 9-9.5 9Z"/></svg>',
			'eye'       => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5C6.5 5 2.7 9.2 1.5 12 2.7 14.8 6.5 19 12 19s9.3-4.2 10.5-7C21.3 9.2 17.5 5 12 5Zm0 11.5A4.5 4.5 0 1 1 16.5 12 4.5 4.5 0 0 1 12 16.5Zm0-7a2.5 2.5 0 1 0 2.5 2.5A2.5 2.5 0 0 0 12 9.5Z"/></svg>',
		);
		return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
	}

	/**
	 * Allowed SVG/HTML for wp_kses in our templates.
	 *
	 * @return array
	 */
	public static function kses_svg() {
		return array(
			'svg'   => array(
				'viewbox'     => true,
				'xmlns'       => true,
				'aria-hidden' => true,
				'focusable'   => true,
				'class'       => true,
				'width'       => true,
				'height'      => true,
				'fill'        => true,
			),
			'path'  => array(
				'd'         => true,
				'fill'      => true,
				'transform' => true,
			),
			'g'     => array( 'fill' => true ),
			'circle' => array(
				'cx' => true,
				'cy' => true,
				'r'  => true,
			),
		);
	}
}
