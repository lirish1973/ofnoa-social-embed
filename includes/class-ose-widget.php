<?php
/**
 * Classic widget, generated straight from the control schema so every design
 * option is available in a sidebar too.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registration wrapper.
 */
class OSE_Widget {

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action(
			'widgets_init',
			function () {
				register_widget( 'OSE_Gallery_Widget' );
			}
		);
	}
}

/**
 * The widget itself.
 */
class OSE_Gallery_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'ose_gallery_widget',
			__( 'Social Video Gallery', 'ofnoa-social-embed' ),
			array(
				'description'                 => __( 'Instagram & Facebook videos in a designed grid, carousel, tabs or stories bar.', 'ofnoa-social-embed' ),
				'classname'                   => 'ose-widget',
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Sidebar args.
	 * @param array $instance Saved values.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$title = isset( $instance['widget_title'] ) ? $instance['widget_title'] : '';

		echo wp_kses_post( $args['before_widget'] );

		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . esc_html( $title ) . $args['after_title'] );
		}

		echo OSE_Render::gallery( $instance ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the renderer.

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Save handler.
	 *
	 * @param array $new_instance Submitted values.
	 * @param array $old_instance Previous values.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$out = array();

		$out['widget_title'] = isset( $new_instance['widget_title'] ) ? sanitize_text_field( $new_instance['widget_title'] ) : '';

		foreach ( OSE_Helpers::schema() as $key => $def ) {
			if ( 'toggle' === $def['type'] ) {
				$out[ $key ] = empty( $new_instance[ $key ] ) ? 0 : 1;
				continue;
			}
			if ( ! isset( $new_instance[ $key ] ) ) {
				continue;
			}
			$out[ $key ] = OSE_Helpers::sanitize_value( $key, $new_instance[ $key ] );
		}

		return $out;
	}

	/**
	 * Admin form.
	 *
	 * @param array $instance Saved values.
	 * @return void
	 */
	public function form( $instance ) {
		$schema   = OSE_Helpers::schema();
		$defaults = OSE_Settings::design_defaults();
		$groups   = array(
			'content'    => __( 'Content', 'ofnoa-social-embed' ),
			'layout'     => __( 'Layout', 'ofnoa-social-embed' ),
			'card'       => __( 'Card', 'ofnoa-social-embed' ),
			'colors'     => __( 'Colours', 'ofnoa-social-embed' ),
			'typography' => __( 'Typography', 'ofnoa-social-embed' ),
			'elements'   => __( 'Elements', 'ofnoa-social-embed' ),
			'behaviour'  => __( 'Behaviour', 'ofnoa-social-embed' ),
			'advanced'   => __( 'Advanced', 'ofnoa-social-embed' ),
		);
		?>
		<div class="ose-widget-form">
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>"><?php esc_html_e( 'Widget title', 'ofnoa-social-embed' ); ?></label>
				<input class="widefat" type="text"
					id="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'widget_title' ) ); ?>"
					value="<?php echo esc_attr( isset( $instance['widget_title'] ) ? $instance['widget_title'] : '' ); ?>" />
			</p>

			<?php foreach ( $groups as $group => $label ) : ?>
				<details class="ose-widget-group" <?php echo 'content' === $group ? 'open' : ''; ?>>
					<summary><?php echo esc_html( $label ); ?></summary>
					<div>
						<?php
						foreach ( $schema as $key => $def ) :
							if ( $def['group'] !== $group ) {
								continue;
							}
							$value = array_key_exists( $key, $instance ) ? $instance[ $key ] : $defaults[ $key ];
							$this->control( $key, $def, $value );
						endforeach;
						?>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render one widget control.
	 *
	 * @param string $key   Control key.
	 * @param array  $def   Definition.
	 * @param mixed  $value Current value.
	 * @return void
	 */
	private function control( $key, $def, $value ) {
		$id   = $this->get_field_id( $key );
		$name = $this->get_field_name( $key );

		echo '<p>';

		if ( 'toggle' === $def['type'] ) {
			printf(
				'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
				esc_attr( $id ),
				esc_attr( $name ),
				checked( (int) $value, 1, false ),
				esc_html( $def['label'] )
			);
			echo '</p>';
			return;
		}

		printf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_html( $def['label'] ) );

		switch ( $def['type'] ) {
			case 'select':
				printf( '<select class="widefat" id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $def['options'] as $ov => $ol ) {
					printf(
						'<option value="%1$s" %3$s>%2$s</option>',
						esc_attr( $ov ),
						esc_html( $ol ),
						selected( (string) $value, (string) $ov, false )
					);
				}
				echo '</select>';
				break;

			case 'number':
			case 'range':
				printf(
					'<input class="widefat" type="number" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( isset( $def['min'] ) ? $def['min'] : '' ),
					esc_attr( isset( $def['max'] ) ? $def['max'] : '' )
				);
				break;

			case 'color':
				printf(
					'<input class="widefat ose-color-field" type="text" id="%1$s" name="%2$s" value="%3$s" data-default-color="%4$s" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $def['default'] )
				);
				break;

			case 'textarea':
				printf(
					'<textarea class="widefat" rows="4" id="%1$s" name="%2$s">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $value )
				);
				break;

			default:
				printf(
					'<input class="widefat" type="text" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
		}

		echo '</p>';
	}
}
