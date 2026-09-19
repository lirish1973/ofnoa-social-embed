<?php
/**
 * Settings screen: credentials (optional), caching, privacy, performance and
 * the site-wide design defaults that every new gallery starts from.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings.
 */
class OSE_Settings {

	const OPTION = 'ose_settings';

	/**
	 * Runtime cache.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_ose_flush_cache', array( __CLASS__, 'handle_flush' ) );
	}

	/**
	 * Non-design defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'fb_app_id'       => '',
			'fb_app_secret'   => '',
			'fb_access_token' => '',
			'auto_fetch'      => 1,
			'sideload'        => 0,
			'cache_ttl'       => 12,
			'load_css'        => 1,
			'consent_mode'    => 0,
			'consent_text'    => '',
			'updater'         => 1,
			'custom_css'      => '',
			'design'          => array(),
		);
	}

	/**
	 * Read the stored settings.
	 *
	 * @return array
	 */
	public static function get() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}
		$stored      = get_option( self::OPTION, array() );
		$stored      = is_array( $stored ) ? $stored : array();
		self::$cache = wp_parse_args( $stored, self::defaults() );
		if ( ! is_array( self::$cache['design'] ) ) {
			self::$cache['design'] = array();
		}
		return self::$cache;
	}

	/**
	 * Drop the runtime cache (used after a save, and by tests).
	 *
	 * @return void
	 */
	public static function reset_cache() {
		self::$cache = null;
	}

	/**
	 * Site-wide design defaults merged over the schema defaults.
	 *
	 * @return array
	 */
	public static function design_defaults() {
		$settings = self::get();
		$base     = OSE_Helpers::defaults();
		foreach ( $settings['design'] as $key => $value ) {
			if ( array_key_exists( $key, $base ) ) {
				$base[ $key ] = $value;
			}
		}
		return $base;
	}

	/**
	 * Add the menu entry.
	 *
	 * @return void
	 */
	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . OSE_CPT::POST_TYPE,
			__( 'Settings', 'ofnoa-social-embed' ),
			__( 'Settings', 'ofnoa-social-embed' ),
			'manage_options',
			'ose-settings',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Register the option.
	 *
	 * @return void
	 */
	public static function register() {
		register_setting(
			'ose_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize the whole option.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();

		// Each tab posts only its own fields, so start from what is already
		// stored and merge the submitted tab over it. Anything else would wipe
		// the settings that were not on screen.
		$out = self::get();
		$tab = isset( $input['__tab'] ) ? sanitize_key( $input['__tab'] ) : '';

		$text_fields = array(
			'general' => array( 'cache_ttl' ),
			'privacy' => array( 'consent_text', 'custom_css' ),
			'api'     => array( 'fb_app_id', 'fb_app_secret', 'fb_access_token' ),
		);

		$checkboxes = array(
			'general' => array( 'auto_fetch', 'sideload', 'updater' ),
			'privacy' => array( 'load_css', 'consent_mode' ),
			'api'     => array(),
			'design'  => array(),
		);

		// Checkboxes only report when ticked, so a tab's own boxes default to 0.
		if ( isset( $checkboxes[ $tab ] ) ) {
			foreach ( $checkboxes[ $tab ] as $key ) {
				$out[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
			}
		}

		if ( isset( $text_fields[ $tab ] ) ) {
			foreach ( $text_fields[ $tab ] as $key ) {
				if ( ! array_key_exists( $key, $input ) ) {
					continue;
				}
				if ( 'cache_ttl' === $key ) {
					$out[ $key ] = max( 1, min( 720, absint( $input[ $key ] ) ) );
				} elseif ( 'custom_css' === $key ) {
					$out[ $key ] = wp_strip_all_tags( (string) $input[ $key ] );
				} else {
					$out[ $key ] = sanitize_text_field( (string) $input[ $key ] );
				}
			}
		}

		if ( 'design' === $tab ) {
			$submitted = ( isset( $input['design'] ) && is_array( $input['design'] ) ) ? $input['design'] : array();
			$design    = array();

			foreach ( OSE_Helpers::schema() as $key => $def ) {
				if ( in_array( $def['group'], array( 'content', 'advanced' ), true ) ) {
					continue;
				}
				if ( ! array_key_exists( $key, $submitted ) ) {
					// An unticked checkbox is a real "off", anything else keeps its value.
					if ( 'toggle' === $def['type'] ) {
						$design[ $key ] = 0;
					} elseif ( isset( $out['design'][ $key ] ) ) {
						$design[ $key ] = $out['design'][ $key ];
					}
					continue;
				}
				$design[ $key ] = OSE_Helpers::sanitize_value( $key, $submitted[ $key ] );
			}
			$out['design'] = $design;
		}

		unset( $out['__tab'] );

		self::$cache = null;
		return $out;
	}

	/**
	 * Settings screen markup.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s      = self::get();
		$schema = OSE_Helpers::schema();
		$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs   = array(
			'general' => __( 'General', 'ofnoa-social-embed' ),
			'design'  => __( 'Design defaults', 'ofnoa-social-embed' ),
			'privacy' => __( 'Privacy & performance', 'ofnoa-social-embed' ),
			'api'     => __( 'API (optional)', 'ofnoa-social-embed' ),
			'help'    => __( 'How to use', 'ofnoa-social-embed' ),
		);
		?>
		<div class="wrap ose-settings">
			<h1><?php esc_html_e( 'Ofnoa Social Embed', 'ofnoa-social-embed' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( array( 'post_type' => OSE_CPT::POST_TYPE, 'page' => 'ose-settings', 'tab' => $slug ), admin_url( 'edit.php' ) ) ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php
			if ( 'help' === $tab ) {
				self::render_help();
				echo '</div>';
				return;
			}
			?>

			<form method="post" action="options.php">
				<?php settings_fields( 'ose_settings_group' ); ?>
				<input type="hidden" name="<?php echo esc_attr( self::OPTION ); ?>[__tab]" value="<?php echo esc_attr( $tab ); ?>" />

				<?php if ( 'general' === $tab ) : ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Fetch posters automatically', 'ofnoa-social-embed' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[auto_fetch]" value="1" <?php checked( $s['auto_fetch'], 1 ); ?> />
									<?php esc_html_e( 'Try to pull the thumbnail, caption and handle when a video is saved', 'ofnoa-social-embed' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Store posters locally', 'ofnoa-social-embed' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[sideload]" value="1" <?php checked( $s['sideload'], 1 ); ?> />
									<?php esc_html_e( 'Copy fetched thumbnails into the media library (recommended — remote CDN links expire)', 'ofnoa-social-embed' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ose_cache_ttl"><?php esc_html_e( 'Cache lifetime (hours)', 'ofnoa-social-embed' ); ?></label></th>
							<td>
								<input type="number" min="1" max="720" id="ose_cache_ttl" name="<?php echo esc_attr( self::OPTION ); ?>[cache_ttl]" value="<?php echo esc_attr( $s['cache_ttl'] ); ?>" class="small-text" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Plugin updates', 'ofnoa-social-embed' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[updater]" value="1" <?php checked( $s['updater'], 1 ); ?> />
									<?php
									printf(
										/* translators: %s: repository link */
										esc_html__( 'Offer updates from the public GitHub repository (%s)', 'ofnoa-social-embed' ),
										'<code>' . esc_html( OSE_GH_USER . '/' . OSE_GH_REPO ) . '</code>'
									);
									?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Cache', 'ofnoa-social-embed' ); ?></th>
							<td>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ose_flush_cache' ), 'ose_flush_cache' ) ); ?>">
									<?php esc_html_e( 'Clear cached posters, lookups & update check', 'ofnoa-social-embed' ); ?>
								</a>
							</td>
						</tr>
					</table>

				<?php elseif ( 'privacy' === $tab ) : ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Load the plugin stylesheet', 'ofnoa-social-embed' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[load_css]" value="1" <?php checked( $s['load_css'], 1 ); ?> />
									<?php esc_html_e( 'Uncheck only if you are styling the gallery entirely yourself', 'ofnoa-social-embed' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Consent before loading players', 'ofnoa-social-embed' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[consent_mode]" value="1" <?php checked( $s['consent_mode'], 1 ); ?> />
									<?php esc_html_e( 'GDPR mode: no Instagram, TikTok or Facebook iframe loads until the visitor clicks play', 'ofnoa-social-embed' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'The gallery itself always renders from your own server; only the player iframe is deferred.', 'ofnoa-social-embed' ); ?></p>
								<input type="text" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[consent_text]" value="<?php echo esc_attr( $s['consent_text'] ); ?>" placeholder="<?php esc_attr_e( 'Playing this video loads content from Instagram / TikTok / Facebook.', 'ofnoa-social-embed' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ose_custom_css"><?php esc_html_e( 'Custom CSS', 'ofnoa-social-embed' ); ?></label></th>
							<td>
								<textarea id="ose_custom_css" class="large-text code" rows="8" name="<?php echo esc_attr( self::OPTION ); ?>[custom_css]"><?php echo esc_textarea( $s['custom_css'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Printed after the plugin stylesheet on any page that renders a gallery.', 'ofnoa-social-embed' ); ?></p>
							</td>
						</tr>
					</table>

				<?php elseif ( 'api' === $tab ) : ?>
					<p class="description" style="max-width:760px">
						<?php esc_html_e( 'Credentials are entirely optional. Playback always works through the public Instagram, TikTok and Facebook embed players, and TikTok posters resolve through its open oEmbed endpoint. Supplying a Meta app only improves poster and caption resolution for Instagram and Facebook.', 'ofnoa-social-embed' ); ?>
					</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="ose_fb_app_id"><?php esc_html_e( 'Meta App ID', 'ofnoa-social-embed' ); ?></label></th>
							<td><input type="text" class="regular-text" id="ose_fb_app_id" name="<?php echo esc_attr( self::OPTION ); ?>[fb_app_id]" value="<?php echo esc_attr( $s['fb_app_id'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="ose_fb_app_secret"><?php esc_html_e( 'Meta App Secret', 'ofnoa-social-embed' ); ?></label></th>
							<td><input type="password" class="regular-text" id="ose_fb_app_secret" name="<?php echo esc_attr( self::OPTION ); ?>[fb_app_secret]" value="<?php echo esc_attr( $s['fb_app_secret'] ); ?>" autocomplete="new-password" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="ose_fb_access_token"><?php esc_html_e( 'Access token (alternative)', 'ofnoa-social-embed' ); ?></label></th>
							<td><input type="password" class="regular-text" id="ose_fb_access_token" name="<?php echo esc_attr( self::OPTION ); ?>[fb_access_token]" value="<?php echo esc_attr( $s['fb_access_token'] ); ?>" autocomplete="new-password" /></td>
						</tr>
					</table>

				<?php else : ?>
					<p class="description" style="max-width:820px">
						<?php esc_html_e( 'Every gallery starts from these values. Any block, widget, shortcode or Elementor widget can still override each one individually.', 'ofnoa-social-embed' ); ?>
					</p>
					<?php
					$groups = array(
						'layout'     => __( 'Layout', 'ofnoa-social-embed' ),
						'card'       => __( 'Card', 'ofnoa-social-embed' ),
						'colors'     => __( 'Colours', 'ofnoa-social-embed' ),
						'typography' => __( 'Typography', 'ofnoa-social-embed' ),
						'elements'   => __( 'Elements', 'ofnoa-social-embed' ),
						'behaviour'  => __( 'Behaviour', 'ofnoa-social-embed' ),
					);
					foreach ( $groups as $group => $title ) :
						?>
						<h2><?php echo esc_html( $title ); ?></h2>
						<table class="form-table" role="presentation">
							<?php
							foreach ( $schema as $key => $def ) :
								if ( $def['group'] !== $group ) {
									continue;
								}
								$value = array_key_exists( $key, $s['design'] ) ? $s['design'][ $key ] : $def['default'];
								$name  = self::OPTION . '[design][' . $key . ']';
								?>
								<tr>
									<th scope="row"><label for="ose_d_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $def['label'] ); ?></label></th>
									<td><?php self::field( $key, $def, $name, $value ); ?></td>
								</tr>
							<?php endforeach; ?>
						</table>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render one control.
	 *
	 * @param string $key   Control key.
	 * @param array  $def   Definition.
	 * @param string $name  Input name.
	 * @param mixed  $value Current value.
	 * @return void
	 */
	public static function field( $key, $def, $name, $value ) {
		$id = 'ose_d_' . $key;
		switch ( $def['type'] ) {
			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
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

			case 'toggle':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( (int) $value, 1, false ),
					esc_html__( 'Enabled', 'ofnoa-social-embed' )
				);
				break;

			case 'color':
				printf(
					'<input type="text" class="ose-color-field" id="%1$s" name="%2$s" value="%3$s" data-default-color="%4$s" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $def['default'] )
				);
				break;

			case 'range':
			case 'number':
				printf(
					'<input type="number" class="small-text" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" />%6$s',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( isset( $def['min'] ) ? $def['min'] : '' ),
					esc_attr( isset( $def['max'] ) ? $def['max'] : '' ),
					isset( $def['unit'] ) ? ' <span class="description">' . esc_html( $def['unit'] ) . '</span>' : ''
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="4" class="large-text code">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $value )
				);
				break;

			default:
				printf(
					'<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
		}
	}

	/**
	 * Documentation tab.
	 *
	 * @return void
	 */
	private static function render_help() {
		$schema = OSE_Helpers::schema();
		?>
		<div class="ose-help">
			<h2><?php esc_html_e( 'Four ways to place a gallery', 'ofnoa-social-embed' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Block editor: add the "Social Video Gallery" block.', 'ofnoa-social-embed' ); ?></li>
				<li><?php esc_html_e( 'Widgets screen: add the "Social Video Gallery" widget to any sidebar.', 'ofnoa-social-embed' ); ?></li>
				<li><?php esc_html_e( 'Elementor: search for "Social Video Gallery" in the widget panel.', 'ofnoa-social-embed' ); ?></li>
				<li><?php esc_html_e( 'Anywhere else: the shortcode below.', 'ofnoa-social-embed' ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'Shortcode', 'ofnoa-social-embed' ); ?></h2>
			<p><code>[ofnoa_social_embed layout="tabs" columns="4" card_style="glass" aspect="9-16"]</code></p>
			<p><code>[ofnoa_social_embed source="urls" urls="https://www.instagram.com/reel/XXXX/|https://www.facebook.com/watch/?v=123"]</code></p>
			<p class="description"><?php esc_html_e( 'In the shortcode, separate multiple URLs with a pipe character.', 'ofnoa-social-embed' ); ?></p>

			<h2><?php esc_html_e( 'Every attribute', 'ofnoa-social-embed' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Attribute', 'ofnoa-social-embed' ); ?></th>
						<th><?php esc_html_e( 'Description', 'ofnoa-social-embed' ); ?></th>
						<th><?php esc_html_e( 'Accepted values', 'ofnoa-social-embed' ); ?></th>
						<th><?php esc_html_e( 'Default', 'ofnoa-social-embed' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $schema as $key => $def ) : ?>
					<tr>
						<td><code><?php echo esc_html( $key ); ?></code></td>
						<td><?php echo esc_html( $def['label'] ); ?></td>
						<td>
							<?php
							if ( 'select' === $def['type'] ) {
								echo '<code>' . esc_html( implode( ' | ', array_keys( $def['options'] ) ) ) . '</code>';
							} elseif ( 'toggle' === $def['type'] ) {
								echo '<code>yes | no</code>';
							} elseif ( in_array( $def['type'], array( 'number', 'range' ), true ) ) {
								echo '<code>' . esc_html( ( isset( $def['min'] ) ? $def['min'] : '0' ) . '–' . ( isset( $def['max'] ) ? $def['max'] : '∞' ) ) . '</code>';
							} else {
								echo '<code>' . esc_html__( 'text', 'ofnoa-social-embed' ) . '</code>';
							}
							?>
						</td>
						<td><code><?php echo esc_html( '' === $def['default'] ? '—' : $def['default'] ); ?></code></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Clear caches.
	 *
	 * @return void
	 */
	public static function handle_flush() {
		check_admin_referer( 'ose_flush_cache' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ofnoa-social-embed' ) );
		}
		OSE_Resolver::flush_cache();
		OSE_Updater::forget();
		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => OSE_CPT::POST_TYPE,
					'page'      => 'ose-settings',
					'flushed'   => 1,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}
}
