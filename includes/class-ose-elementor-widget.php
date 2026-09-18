<?php
/**
 * The Elementor widget class. Loaded only when Elementor is active.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Social Video Gallery for Elementor.
 */
class OSE_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ofnoa_social_embed';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Social Video Gallery', 'ofnoa-social-embed' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	/**
	 * Panel category.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'ofnoa', 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'instagram', 'facebook', 'reels', 'video', 'gallery', 'embed', 'social' );
	}

	/**
	 * Front-end script handles.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'ose-frontend' );
	}

	/**
	 * Front-end style handles.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'ose-frontend' );
	}

	/**
	 * Build the panel from the shared schema.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$schema = OSE_Helpers::schema();
		$groups = array(
			'content'    => array( __( 'Content', 'ofnoa-social-embed' ), \Elementor\Controls_Manager::TAB_CONTENT ),
			'layout'     => array( __( 'Layout', 'ofnoa-social-embed' ), \Elementor\Controls_Manager::TAB_CONTENT ),
			'elements'   => array( __( 'Elements', 'ofnoa-social-embed' ), \Elementor\Controls_Manager::TAB_CONTENT ),
			'behaviour'  => array( __( 'Behaviour', 'ofnoa-social-embed' ), \Elementor\Controls_Manager::TAB_CONTENT ),
			'card'       => array( __( 'Card', 'ofnoa-social-embed' ), \Elementor\Controls_Manager::TAB_STYLE ),
			'colors'     => array( __( 'Colours', 'ofnoa-social-embed' ), \Elementor\Controls_Manager::TAB_STYLE ),
			'typography' => array( __( 'Typography', 'ofnoa-social-embed' ), \Elementor\Controls_Manager::TAB_STYLE ),
			'advanced'   => array( __( 'Extras', 'ofnoa-social-embed' ), \Elementor\Controls_Manager::TAB_STYLE ),
		);

		foreach ( $groups as $group => $meta ) {
			$this->start_controls_section(
				'ose_section_' . $group,
				array(
					'label' => $meta[0],
					'tab'   => $meta[1],
				)
			);

			foreach ( $schema as $key => $def ) {
				if ( $def['group'] !== $group ) {
					continue;
				}
				$this->add_control( $key, $this->control_args( $key, $def ) );
			}

			$this->end_controls_section();
		}
	}

	/**
	 * Translate one schema entry into Elementor control arguments.
	 *
	 * @param string $key Control key.
	 * @param array  $def Definition.
	 * @return array
	 */
	private function control_args( $key, $def ) {
		$args = array(
			'label'   => $def['label'],
			'default' => $def['default'],
		);

		switch ( $def['type'] ) {
			case 'select':
				$args['type']    = \Elementor\Controls_Manager::SELECT;
				$args['options'] = $def['options'];
				break;

			case 'toggle':
				$args['type']        = \Elementor\Controls_Manager::SWITCHER;
				$args['label_on']    = __( 'Yes', 'ofnoa-social-embed' );
				$args['label_off']   = __( 'No', 'ofnoa-social-embed' );
				$args['return_value'] = 'yes';
				$args['default']     = $def['default'] ? 'yes' : '';
				break;

			case 'color':
				$args['type'] = \Elementor\Controls_Manager::COLOR;
				break;

			case 'range':
				$args['type']  = \Elementor\Controls_Manager::SLIDER;
				$args['range'] = array(
					'px' => array(
						'min'  => isset( $def['min'] ) ? $def['min'] : 0,
						'max'  => isset( $def['max'] ) ? $def['max'] : 100,
						'step' => 1,
					),
				);
				$args['default'] = array(
					'unit' => 'px',
					'size' => (float) $def['default'],
				);
				break;

			case 'number':
				$args['type'] = \Elementor\Controls_Manager::NUMBER;
				$args['min']  = isset( $def['min'] ) ? $def['min'] : 0;
				$args['max']  = isset( $def['max'] ) ? $def['max'] : 9999;
				break;

			case 'textarea':
				$args['type'] = \Elementor\Controls_Manager::TEXTAREA;
				$args['rows'] = 6;
				break;

			default:
				$args['type']        = \Elementor\Controls_Manager::TEXT;
				$args['label_block'] = true;
		}

		if ( 'urls' === $key ) {
			$args['condition'] = array( 'source' => 'urls' );
		}
		if ( in_array( $key, array( 'collection', 'ids' ), true ) ) {
			$args['condition'] = array( 'source' => 'library' );
		}
		if ( in_array( $key, array( 'show_arrows', 'show_dots', 'autoplay', 'autoplay_speed', 'loop' ), true ) ) {
			$args['condition'] = array( 'layout' => array( 'carousel', 'reels', 'stories' ) );
		}
		if ( in_array( $key, array( 'tabs_from', 'tab_all_label' ), true ) ) {
			$args['condition'] = array( 'layout' => 'tabs' );
		}

		return $args;
	}

	/**
	 * Render the widget.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$atts     = array();

		foreach ( OSE_Helpers::schema() as $key => $def ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				continue;
			}
			$value = $settings[ $key ];

			if ( 'toggle' === $def['type'] ) {
				$atts[ $key ] = ( 'yes' === $value ) ? 1 : 0;
				continue;
			}
			if ( 'range' === $def['type'] && is_array( $value ) ) {
				$atts[ $key ] = isset( $value['size'] ) ? $value['size'] : $def['default'];
				continue;
			}
			$atts[ $key ] = $value;
		}

		echo OSE_Render::gallery( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the renderer.
	}
}
