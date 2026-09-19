<?php
/**
 * The renderer. Every integration funnels into OSE_Render::gallery().
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renderer.
 */
class OSE_Render {

	/**
	 * Instance counter, for unique ids.
	 *
	 * @var int
	 */
	private static $uid = 0;

	/**
	 * Render a complete gallery.
	 *
	 * @param array $atts Raw attributes.
	 * @return string
	 */
	public static function gallery( $atts ) {
		$a = OSE_Helpers::sanitize_atts( $atts );

		OSE_Assets::mark_needed();

		$items = OSE_Query::get_items( $a );
		if ( empty( $items ) ) {
			return self::empty_state( $a ) . self::capture_report( $a );
		}

		++self::$uid;
		$uid      = 'ose-' . self::$uid . '-' . wp_rand( 100, 999 );
		$settings = OSE_Settings::get();
		$layout   = $a['layout'];
		$is_tabs  = ( 'tabs' === $layout );
		$tabs     = $is_tabs || $a['show_filter'] ? OSE_Query::tabs_for( $items, $a ) : array();

		$classes = array(
			'ose',
			'ose--' . $layout,
			'ose--card-' . $a['card_style'],
			'ose--hover-' . $a['hover_effect'],
			'ose--overlay-' . $a['overlay'],
			'ose--shadow-' . $a['shadow'],
			'ose--skin-' . $a['skin'],
			'ose--badge-' . $a['badge_pos'],
			'ose--play-' . $a['play_mode'],
		);
		if ( 'none' !== $a['entrance'] ) {
			$classes[] = 'ose--anim ose--anim-' . $a['entrance'];
		}
		if ( 'auto' === $a['aspect'] ) {
			$classes[] = 'ose--ratio-auto';
		}
		if ( $a['css_class'] ) {
			foreach ( preg_split( '/[\s,]+/', (string) $a['css_class'] ) as $extra ) {
				$extra = sanitize_html_class( $extra );
				if ( '' !== $extra ) {
					$classes[] = $extra;
				}
			}
		}

		$config = array(
			'layout'   => $layout,
			'play'     => $a['play_mode'],
			'autoplay' => (int) $a['autoplay'],
			'speed'    => (int) $a['autoplay_speed'],
			'loop'     => (int) $a['loop'],
			'consent'  => (int) $settings['consent_mode'],
			'consentText' => $settings['consent_text'] ? $settings['consent_text'] : __( 'Playing this video loads content from Instagram / TikTok / Facebook.', 'ofnoa-social-embed' ),
			'i18n'     => array(
				'close' => __( 'Close', 'ofnoa-social-embed' ),
				'prev'  => __( 'Previous video', 'ofnoa-social-embed' ),
				'next'  => __( 'Next video', 'ofnoa-social-embed' ),
				'play'  => __( 'Play video', 'ofnoa-social-embed' ),
				'open'  => __( 'Open on the original network', 'ofnoa-social-embed' ),
			),
		);

		ob_start();
		?>
		<div id="<?php echo esc_attr( $uid ); ?>"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			style="<?php echo esc_attr( OSE_Helpers::style_vars( $a ) ); ?>"
			data-ose="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">

			<?php self::head( $a ); ?>

			<?php if ( $tabs ) : ?>
				<?php self::tabs( $tabs, $a, $uid ); ?>
			<?php endif; ?>

			<div class="ose__viewport">
				<div class="ose__track" <?php echo $tabs ? 'role="tabpanel"' : ''; ?>>
					<?php
					$i = 0;
					foreach ( $items as $item ) {
						self::card( $item, $a, $i, $settings );
						++$i;
					}
					?>
				</div>
			</div>

			<?php if ( in_array( $layout, array( 'carousel', 'reels' ), true ) && $a['show_arrows'] ) : ?>
				<button type="button" class="ose__arrow ose__arrow--prev" aria-label="<?php esc_attr_e( 'Previous', 'ofnoa-social-embed' ); ?>">
					<?php echo wp_kses( OSE_Helpers::icon( 'prev' ), OSE_Helpers::kses_svg() ); ?>
				</button>
				<button type="button" class="ose__arrow ose__arrow--next" aria-label="<?php esc_attr_e( 'Next', 'ofnoa-social-embed' ); ?>">
					<?php echo wp_kses( OSE_Helpers::icon( 'next' ), OSE_Helpers::kses_svg() ); ?>
				</button>
			<?php endif; ?>

			<?php if ( 'carousel' === $layout && $a['show_dots'] ) : ?>
				<div class="ose__dots" role="tablist" aria-label="<?php esc_attr_e( 'Slides', 'ofnoa-social-embed' ); ?>"></div>
			<?php endif; ?>

			<?php self::editor_report( $a ); ?>

			<?php if ( $a['show_cta'] ) : ?>
				<div class="ose__more">
					<button type="button" class="ose__more-btn"><?php esc_html_e( 'Load more', 'ofnoa-social-embed' ); ?></button>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Heading / sub heading / follow button.
	 *
	 * @param array $a Attributes.
	 * @return void
	 */
	private static function head( $a ) {
		$has_follow = $a['follow_url'] && $a['follow_label'];
		if ( ! $a['heading'] && ! $a['subheading'] && ! $has_follow ) {
			return;
		}
		?>
		<header class="ose__head">
			<div class="ose__head-text">
				<?php if ( $a['heading'] ) : ?>
					<h2 class="ose__heading"><?php echo esc_html( $a['heading'] ); ?></h2>
				<?php endif; ?>
				<?php if ( $a['subheading'] ) : ?>
					<p class="ose__subheading"><?php echo esc_html( $a['subheading'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $has_follow ) : ?>
				<a class="ose__follow" href="<?php echo esc_url( $a['follow_url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $a['follow_label'] ); ?>
				</a>
			<?php endif; ?>
		</header>
		<?php
	}

	/**
	 * Tab / filter bar.
	 *
	 * @param array  $tabs Slug => label.
	 * @param array  $a    Attributes.
	 * @param string $uid  Instance id.
	 * @return void
	 */
	private static function tabs( $tabs, $a, $uid ) {
		$all_label = $a['tab_all_label'];
		$show_all  = '' !== trim( (string) $all_label );
		?>
		<div class="ose__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Collections', 'ofnoa-social-embed' ); ?>">
			<?php if ( $show_all ) : ?>
				<button type="button" class="ose__tab is-active" role="tab" aria-selected="true" data-filter="*" id="<?php echo esc_attr( $uid ); ?>-tab-all">
					<span><?php echo esc_html( $all_label ); ?></span>
				</button>
			<?php endif; ?>
			<?php
			$first = ! $show_all;
			foreach ( $tabs as $slug => $label ) :
				?>
				<button type="button"
					class="ose__tab <?php echo $first ? 'is-active' : ''; ?>"
					role="tab"
					aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
					data-filter="<?php echo esc_attr( $slug ); ?>"
					id="<?php echo esc_attr( $uid . '-tab-' . $slug ); ?>">
					<span><?php echo esc_html( $label ); ?></span>
				</button>
				<?php
				$first = false;
			endforeach;
			?>
			<span class="ose__tab-ink" aria-hidden="true"></span>
		</div>
		<?php
	}

	/**
	 * One card.
	 *
	 * @param array $item     Item.
	 * @param array $a        Attributes.
	 * @param int   $index    Position.
	 * @param array $settings Plugin settings.
	 * @return void
	 */
	private static function card( $item, $a, $index, $settings ) {
		$terms    = implode( ' ', array_keys( $item['terms'] ) );
		$has_img  = ! empty( $item['poster'] );
		$fallback = ( ! $has_img && empty( $settings['consent_mode'] ) ) ? 1 : 0;
		$title    = $item['title'] ? $item['title'] : __( 'Social video', 'ofnoa-social-embed' );
		$tag      = ( 'newtab' === $a['play_mode'] ) ? 'a' : 'button';

		$card_classes = array( 'ose__card' );
		if ( ! $has_img ) {
			$card_classes[] = 'ose__card--noimg';
		}
		?>
		<article class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>"
			data-index="<?php echo esc_attr( $index ); ?>"
			data-terms="<?php echo esc_attr( $terms ); ?>"
			data-embed="<?php echo esc_attr( $item['embed'] ); ?>"
			data-url="<?php echo esc_attr( $item['url'] ); ?>"
			data-platform="<?php echo esc_attr( $item['platform'] ); ?>"
			data-title="<?php echo esc_attr( $title ); ?>"
			data-fallback="<?php echo esc_attr( $fallback ); ?>"
			style="--ose-i:<?php echo esc_attr( $index ); ?>">

			<div class="ose__media">
				<?php
				printf(
					'<%1$s class="ose__hit" %2$s aria-label="%3$s">',
					esc_attr( $tag ),
					'a' === $tag
						? 'href="' . esc_url( $item['url'] ) . '" target="_blank" rel="noopener noreferrer"'
						: 'type="button"',
					esc_attr(
						sprintf(
							/* translators: %s: video title */
							__( 'Play: %s', 'ofnoa-social-embed' ),
							$title
						)
					)
				);
				?>
					<?php if ( $has_img ) : ?>
						<img class="ose__img"
							src="<?php echo esc_url( $item['poster'] ); ?>"
							alt="<?php echo esc_attr( $title ); ?>"
							<?php echo $a['lazy'] ? 'loading="lazy"' : ''; ?>
							decoding="async"
							referrerpolicy="no-referrer" />
					<?php else : ?>
						<span class="ose__placeholder" aria-hidden="true">
							<?php echo wp_kses( OSE_Helpers::icon( $item['platform'] ? $item['platform'] : 'play' ), OSE_Helpers::kses_svg() ); ?>
						</span>
					<?php endif; ?>

					<span class="ose__grad" aria-hidden="true"></span>

					<?php if ( $a['show_play'] ) : ?>
						<span class="ose__play" aria-hidden="true">
							<?php echo wp_kses( OSE_Helpers::icon( 'play' ), OSE_Helpers::kses_svg() ); ?>
						</span>
					<?php endif; ?>
				<?php printf( '</%s>', esc_attr( $tag ) ); ?>

				<?php if ( $a['show_badge'] && $item['platform'] ) : ?>
					<span class="ose__badge ose__badge--<?php echo esc_attr( $item['platform'] ); ?>" aria-hidden="true">
						<?php echo wp_kses( OSE_Helpers::icon( $item['platform'] ), OSE_Helpers::kses_svg() ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $a['show_duration'] && $item['duration'] ) : ?>
					<span class="ose__dur"><?php echo esc_html( $item['duration'] ); ?></span>
				<?php endif; ?>

				<div class="ose__inline" hidden></div>
			</div>

			<?php if ( self::has_body( $item, $a ) ) : ?>
				<div class="ose__body">
					<?php if ( $a['show_title'] && $item['title'] ) : ?>
						<h3 class="ose__title"><?php echo esc_html( $item['title'] ); ?></h3>
					<?php endif; ?>

					<?php if ( $a['show_caption'] && $item['caption'] ) : ?>
						<p class="ose__caption"><?php echo esc_html( $item['caption'] ); ?></p>
					<?php endif; ?>

					<?php if ( self::has_meta( $item, $a ) ) : ?>
						<div class="ose__meta">
							<?php if ( $a['show_author'] && $item['author'] ) : ?>
								<span class="ose__author">@<?php echo esc_html( ltrim( $item['author'], '@' ) ); ?></span>
							<?php endif; ?>
							<?php if ( $a['show_date'] && $item['date'] ) : ?>
								<span class="ose__date"><?php echo esc_html( $item['date'] ); ?></span>
							<?php endif; ?>
							<?php if ( $a['show_stats'] && ( $item['views'] || $item['likes'] ) ) : ?>
								<span class="ose__stats">
									<?php if ( $item['views'] ) : ?>
										<span class="ose__stat">
											<?php echo wp_kses( OSE_Helpers::icon( 'eye' ), OSE_Helpers::kses_svg() ); ?>
											<?php echo esc_html( OSE_Helpers::compact_number( $item['views'] ) ); ?>
										</span>
									<?php endif; ?>
									<?php if ( $item['likes'] ) : ?>
										<span class="ose__stat">
											<?php echo wp_kses( OSE_Helpers::icon( 'heart' ), OSE_Helpers::kses_svg() ); ?>
											<?php echo esc_html( OSE_Helpers::compact_number( $item['likes'] ) ); ?>
										</span>
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $item['cta_url'] && $item['cta_label'] ) : ?>
						<a class="ose__cta" href="<?php echo esc_url( $item['cta_url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $item['cta_label'] ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</article>
		<?php
	}

	/**
	 * Does this card need a body block at all?
	 *
	 * @param array $item Item.
	 * @param array $a    Attributes.
	 * @return bool
	 */
	private static function has_body( $item, $a ) {
		if ( 'stories' === $a['layout'] ) {
			return (bool) ( $a['show_title'] && $item['title'] );
		}
		return ( $a['show_title'] && $item['title'] )
			|| ( $a['show_caption'] && $item['caption'] )
			|| self::has_meta( $item, $a )
			|| ( $item['cta_url'] && $item['cta_label'] );
	}

	/**
	 * Does this card need a meta row?
	 *
	 * @param array $item Item.
	 * @param array $a    Attributes.
	 * @return bool
	 */
	private static function has_meta( $item, $a ) {
		return ( $a['show_author'] && $item['author'] )
			|| ( $a['show_date'] && $item['date'] )
			|| ( $a['show_stats'] && ( $item['views'] || $item['likes'] ) );
	}

	/**
	 * The editor report as a string (used by the empty state).
	 *
	 * @param array $a Attributes.
	 * @return string
	 */
	private static function capture_report( $a ) {
		ob_start();
		self::editor_report( $a );
		return (string) ob_get_clean();
	}

	/**
	 * Tell editors (never visitors) which links from a manual list did not
	 * make it into the gallery, and why.
	 *
	 * @param array $a Attributes.
	 * @return void
	 */
	private static function editor_report( $a ) {
		if ( 'urls' !== $a['source'] || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$report = OSE_Query::$last_report;
		if ( empty( $report['skipped'] ) ) {
			return;
		}
		?>
		<div class="ose__editor-note" role="note">
			<strong>
				<?php
				printf(
					/* translators: 1: shown, 2: total */
					esc_html__( 'Ofnoa Social Embed — showing %1$d of %2$d links. Only editors see this note.', 'ofnoa-social-embed' ),
					(int) $report['shown'],
					(int) $report['total']
				);
				?>
			</strong>
			<ul>
				<?php foreach ( $report['skipped'] as $row ) : ?>
					<li>
						<?php if ( $row['url'] ) : ?>
							<code><?php echo esc_html( wp_html_excerpt( $row['url'], 80, '…' ) ); ?></code> —
						<?php endif; ?>
						<?php echo esc_html( $row['reason'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Placeholder shown when nothing matched.
	 *
	 * @param array $a Attributes.
	 * @return string
	 */
	private static function empty_state( $a ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}
		$url = admin_url( 'edit.php?post_type=' . OSE_CPT::POST_TYPE );
		return sprintf(
			'<div class="ose ose--empty"><p>%1$s</p><p><a href="%2$s">%3$s</a></p></div>',
			esc_html__( 'Ofnoa Social Embed: no videos matched this gallery.', 'ofnoa-social-embed' ),
			esc_url( $url ),
			esc_html__( 'Open the video library', 'ofnoa-social-embed' )
		);
	}
}
