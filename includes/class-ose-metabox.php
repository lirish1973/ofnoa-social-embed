<?php
/**
 * The editor screen for a single video, plus the bulk URL importer.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Metaboxes.
 */
class OSE_Metabox {

	/**
	 * Meta keys handled here.
	 *
	 * @var array
	 */
	private static $fields = array(
		'_ose_url'       => 'url',
		'_ose_poster'    => 'url',
		'_ose_poster_id' => 'int',
		'_ose_author'    => 'text',
		'_ose_duration'  => 'text',
		'_ose_views'     => 'int',
		'_ose_likes'     => 'int',
		'_ose_cta_url'   => 'url',
		'_ose_cta_label' => 'text',
	);

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_boxes' ) );
		add_action( 'save_post_' . OSE_CPT::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'import_page' ) );
		add_action( 'admin_post_ose_bulk_import', array( __CLASS__, 'handle_bulk_import' ) );
		add_action( 'wp_ajax_ose_refresh_meta', array( __CLASS__, 'ajax_refresh_meta' ) );
	}

	/**
	 * Expose meta to the REST API so the block editor can read it.
	 *
	 * @return void
	 */
	public static function register_meta() {
		foreach ( self::$fields as $key => $type ) {
			register_post_meta(
				OSE_CPT::POST_TYPE,
				$key,
				array(
					'type'              => 'int' === $type ? 'integer' : 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'int' === $type ? 'absint' : ( 'url' === $type ? 'esc_url_raw' : 'sanitize_text_field' ),
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
		foreach ( array( '_ose_platform', '_ose_pid', '_ose_type' ) as $key ) {
			register_post_meta(
				OSE_CPT::POST_TYPE,
				$key,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/**
	 * Register the metaboxes.
	 *
	 * @return void
	 */
	public static function add_boxes() {
		add_meta_box(
			'ose_video_source',
			__( 'Video source', 'ofnoa-social-embed' ),
			array( __CLASS__, 'render_source' ),
			OSE_CPT::POST_TYPE,
			'normal',
			'high'
		);
		add_meta_box(
			'ose_video_extra',
			__( 'Card details', 'ofnoa-social-embed' ),
			array( __CLASS__, 'render_extra' ),
			OSE_CPT::POST_TYPE,
			'normal',
			'default'
		);
		add_meta_box(
			'ose_video_preview',
			__( 'Live preview', 'ofnoa-social-embed' ),
			array( __CLASS__, 'render_preview' ),
			OSE_CPT::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Source metabox.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public static function render_source( $post ) {
		wp_nonce_field( 'ose_save_meta', 'ose_meta_nonce' );
		$url      = get_post_meta( $post->ID, '_ose_url', true );
		$poster   = get_post_meta( $post->ID, '_ose_poster', true );
		$pid      = (int) get_post_meta( $post->ID, '_ose_poster_id', true );
		$platform = get_post_meta( $post->ID, '_ose_platform', true );
		?>
		<div class="ose-meta-wrap">
			<p class="ose-field">
				<label for="ose_url"><strong><?php esc_html_e( 'Instagram, TikTok or Facebook video URL', 'ofnoa-social-embed' ); ?></strong></label>
				<input type="url" class="widefat code" id="ose_url" name="_ose_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://www.instagram.com/reel/XXXXXXXXXXX/" />
				<span class="description">
					<?php esc_html_e( 'Reels, posts, IGTV, TikTok videos and photo posts, Facebook videos and Reels, watch links, fb.watch and vm.tiktok.com short links are all accepted.', 'ofnoa-social-embed' ); ?>
				</span>
			</p>

			<?php if ( $platform ) : ?>
				<p class="ose-detected">
					<span class="ose-chip ose-chip--<?php echo esc_attr( $platform ); ?>"><?php echo esc_html( ucfirst( $platform ) ); ?></span>
					<code><?php echo esc_html( get_post_meta( $post->ID, '_ose_pid', true ) ); ?></code>
				</p>
			<?php endif; ?>

			<p class="ose-field">
				<label for="ose_poster"><strong><?php esc_html_e( 'Poster image URL', 'ofnoa-social-embed' ); ?></strong></label>
				<input type="url" class="widefat code" id="ose_poster" name="_ose_poster" value="<?php echo esc_attr( $poster ); ?>" />
				<input type="hidden" id="ose_poster_id" name="_ose_poster_id" value="<?php echo esc_attr( $pid ); ?>" />
				<button type="button" class="button" id="ose-pick-poster"><?php esc_html_e( 'Choose from media library', 'ofnoa-social-embed' ); ?></button>
				<button type="button" class="button" id="ose-refresh-meta" data-post="<?php echo esc_attr( $post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'ose_refresh_' . $post->ID ) ); ?>">
					<?php esc_html_e( 'Fetch automatically', 'ofnoa-social-embed' ); ?>
				</button>
				<span class="description">
					<?php esc_html_e( 'Leave empty to let the plugin fetch one. A featured image, if set, always wins.', 'ofnoa-social-embed' ); ?>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Card details metabox.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public static function render_extra( $post ) {
		$author   = get_post_meta( $post->ID, '_ose_author', true );
		$duration = get_post_meta( $post->ID, '_ose_duration', true );
		$views    = get_post_meta( $post->ID, '_ose_views', true );
		$likes    = get_post_meta( $post->ID, '_ose_likes', true );
		$cta_url  = get_post_meta( $post->ID, '_ose_cta_url', true );
		$cta_lbl  = get_post_meta( $post->ID, '_ose_cta_label', true );
		?>
		<div class="ose-meta-grid">
			<p>
				<label for="ose_author"><?php esc_html_e( 'Author handle', 'ofnoa-social-embed' ); ?></label>
				<input type="text" class="widefat" id="ose_author" name="_ose_author" value="<?php echo esc_attr( $author ); ?>" placeholder="@ofnoacomps" />
			</p>
			<p>
				<label for="ose_duration"><?php esc_html_e( 'Duration', 'ofnoa-social-embed' ); ?></label>
				<input type="text" class="widefat" id="ose_duration" name="_ose_duration" value="<?php echo esc_attr( $duration ); ?>" placeholder="0:28" />
			</p>
			<p>
				<label for="ose_views"><?php esc_html_e( 'Views', 'ofnoa-social-embed' ); ?></label>
				<input type="number" class="widefat" id="ose_views" name="_ose_views" value="<?php echo esc_attr( $views ); ?>" min="0" />
			</p>
			<p>
				<label for="ose_likes"><?php esc_html_e( 'Likes', 'ofnoa-social-embed' ); ?></label>
				<input type="number" class="widefat" id="ose_likes" name="_ose_likes" value="<?php echo esc_attr( $likes ); ?>" min="0" />
			</p>
			<p>
				<label for="ose_cta_url"><?php esc_html_e( 'Card button URL', 'ofnoa-social-embed' ); ?></label>
				<input type="url" class="widefat" id="ose_cta_url" name="_ose_cta_url" value="<?php echo esc_attr( $cta_url ); ?>" />
			</p>
			<p>
				<label for="ose_cta_label"><?php esc_html_e( 'Card button label', 'ofnoa-social-embed' ); ?></label>
				<input type="text" class="widefat" id="ose_cta_label" name="_ose_cta_label" value="<?php echo esc_attr( $cta_lbl ); ?>" />
			</p>
		</div>
		<p class="description">
			<?php esc_html_e( 'The excerpt field is used as the card caption.', 'ofnoa-social-embed' ); ?>
		</p>
		<?php
	}

	/**
	 * Side preview.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public static function render_preview( $post ) {
		$poster = OSE_Query::poster_url( $post->ID );
		echo '<div class="ose-side-preview">';
		if ( $poster ) {
			printf( '<img src="%s" alt="" />', esc_url( $poster ) );
		} else {
			echo '<div class="ose-side-preview__empty">' . esc_html__( 'No poster resolved yet.', 'ofnoa-social-embed' ) . '</div>';
		}
		echo '</div>';
		echo '<p class="description">' . esc_html__( 'Save the video to refresh this preview.', 'ofnoa-social-embed' ) . '</p>';
	}

	/**
	 * Persist the metabox values.
	 *
	 * @param int     $post_id Post id.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['ose_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ose_meta_nonce'] ) ), 'ose_save_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( self::$fields as $key => $type ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw = wp_unslash( $_POST[ $key ] );
			switch ( $type ) {
				case 'url':
					$value = esc_url_raw( trim( (string) $raw ) );
					break;
				case 'int':
					$value = absint( $raw );
					break;
				default:
					$value = sanitize_text_field( (string) $raw );
			}
			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}

		self::sync_from_url( $post_id );
	}

	/**
	 * Re-derive platform/id and, when missing, poster + author from the URL.
	 *
	 * @param int  $post_id Post id.
	 * @param bool $force   Force a poster refresh.
	 * @return array
	 */
	public static function sync_from_url( $post_id, $force = false ) {
		$url = get_post_meta( $post_id, '_ose_url', true );
		if ( ! $url ) {
			return array( 'ok' => false );
		}
		$parsed = OSE_Resolver::parse( $url );
		if ( ! $parsed ) {
			delete_post_meta( $post_id, '_ose_platform' );
			return array( 'ok' => false );
		}

		update_post_meta( $post_id, '_ose_platform', $parsed['platform'] );
		update_post_meta( $post_id, '_ose_pid', $parsed['id'] );
		update_post_meta( $post_id, '_ose_type', $parsed['type'] );

		$settings   = OSE_Settings::get();
		$has_poster = get_post_meta( $post_id, '_ose_poster', true ) || has_post_thumbnail( $post_id );

		if ( ( ! $has_poster || $force ) && ! empty( $settings['auto_fetch'] ) ) {
			$meta = OSE_Resolver::fetch_meta( $url, $force );
			if ( ! empty( $meta['thumbnail'] ) ) {
				if ( ! empty( $settings['sideload'] ) ) {
					$att = OSE_Resolver::sideload_poster( $meta['thumbnail'], $post_id );
					if ( $att ) {
						set_post_thumbnail( $post_id, $att );
						update_post_meta( $post_id, '_ose_poster_id', $att );
					} else {
						update_post_meta( $post_id, '_ose_poster', $meta['thumbnail'] );
					}
				} else {
					update_post_meta( $post_id, '_ose_poster', $meta['thumbnail'] );
				}
			}
			if ( ! empty( $meta['author'] ) && ! get_post_meta( $post_id, '_ose_author', true ) ) {
				update_post_meta( $post_id, '_ose_author', $meta['author'] );
			}
			$post = get_post( $post_id );
			if ( $post && ! empty( $meta['title'] ) && ( '' === trim( $post->post_title ) || __( 'Auto Draft' ) === $post->post_title ) ) {
				remove_action( 'save_post_' . OSE_CPT::POST_TYPE, array( __CLASS__, 'save' ), 10 );
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => wp_html_excerpt( $meta['title'], 90, '…' ),
					)
				);
				add_action( 'save_post_' . OSE_CPT::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
			}
		}

		return array(
			'ok'       => true,
			'platform' => $parsed['platform'],
			'poster'   => OSE_Query::poster_url( $post_id ),
		);
	}

	/**
	 * AJAX: refetch poster/author for one video.
	 *
	 * @return void
	 */
	public static function ajax_refresh_meta() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		check_ajax_referer( 'ose_refresh_' . $post_id, 'nonce' );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'ofnoa-social-embed' ) ), 403 );
		}
		if ( isset( $_POST['url'] ) ) {
			$url = esc_url_raw( wp_unslash( $_POST['url'] ) );
			if ( $url ) {
				update_post_meta( $post_id, '_ose_url', $url );
			}
		}
		$result = self::sync_from_url( $post_id, true );
		if ( empty( $result['ok'] ) ) {
			wp_send_json_error( array( 'message' => __( 'That URL was not recognised as an Instagram, TikTok or Facebook video.', 'ofnoa-social-embed' ) ) );
		}
		wp_send_json_success( $result );
	}

	/**
	 * Register the bulk import screen.
	 *
	 * @return void
	 */
	public static function import_page() {
		add_submenu_page(
			'edit.php?post_type=' . OSE_CPT::POST_TYPE,
			__( 'Bulk import', 'ofnoa-social-embed' ),
			__( 'Bulk import', 'ofnoa-social-embed' ),
			'edit_posts',
			'ose-import',
			array( __CLASS__, 'render_import_page' )
		);
	}

	/**
	 * Bulk import screen markup.
	 *
	 * @return void
	 */
	public static function render_import_page() {
		$terms = get_terms(
			array(
				'taxonomy'   => OSE_CPT::TAXONOMY,
				'hide_empty' => false,
			)
		);
		$done   = isset( $_GET['imported'] ) ? absint( $_GET['imported'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$as_draft = isset( $_GET['status'] ) && 'draft' === $_GET['status']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap ose-import">
			<h1><?php esc_html_e( 'Bulk import videos', 'ofnoa-social-embed' ); ?></h1>
			<?php if ( $done ) : ?>
				<div class="notice notice-success"><p>
					<?php
					printf(
						/* translators: %d: number of videos */
						esc_html( _n( '%d video imported.', '%d videos imported.', $done, 'ofnoa-social-embed' ) ),
						(int) $done
					);
					if ( $as_draft ) {
						echo ' ' . esc_html__( 'They were saved as drafts because your role cannot publish.', 'ofnoa-social-embed' );
					}
					?>
				</p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Paste one Instagram, TikTok or Facebook URL per line. Each becomes a video in the library; posters are resolved automatically.', 'ofnoa-social-embed' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ose_bulk_import' ); ?>
				<input type="hidden" name="action" value="ose_bulk_import" />
				<textarea name="ose_urls" rows="12" class="large-text code" placeholder="https://www.instagram.com/reel/XXXXXXXXXXX/&#10;https://www.tiktok.com/@user/video/1234567890123456789&#10;https://www.facebook.com/watch/?v=123456789"></textarea>
				<p>
					<label for="ose_import_collection"><?php esc_html_e( 'Add to collection', 'ofnoa-social-embed' ); ?></label>
					<select name="ose_collection" id="ose_import_collection">
						<option value=""><?php esc_html_e( '— none —', 'ofnoa-social-embed' ); ?></option>
						<?php if ( ! is_wp_error( $terms ) ) : ?>
							<?php foreach ( $terms as $term ) : ?>
								<option value="<?php echo esc_attr( $term->term_id ); ?>"><?php echo esc_html( $term->name ); ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
					<input type="text" name="ose_new_collection" placeholder="<?php esc_attr_e( 'or type a new collection name', 'ofnoa-social-embed' ); ?>" class="regular-text" />
				</p>
				<?php submit_button( __( 'Import videos', 'ofnoa-social-embed' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Process the bulk import.
	 *
	 * @return void
	 */
	public static function handle_bulk_import() {
		check_admin_referer( 'ose_bulk_import' );

		$post_type = get_post_type_object( OSE_CPT::POST_TYPE );
		$can_edit  = $post_type && current_user_can( $post_type->cap->edit_posts );
		if ( ! $can_edit ) {
			wp_die( esc_html__( 'Not allowed.', 'ofnoa-social-embed' ) );
		}

		// Contributors may add videos, but they land as drafts for review.
		$can_publish = current_user_can( $post_type->cap->publish_posts );
		$status      = $can_publish ? 'publish' : 'draft';

		$taxonomy    = get_taxonomy( OSE_CPT::TAXONOMY );
		$can_add_term = $taxonomy && current_user_can( $taxonomy->cap->manage_terms );
		$can_assign   = $taxonomy && current_user_can( $taxonomy->cap->assign_terms );

		$raw   = isset( $_POST['ose_urls'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ose_urls'] ) ) : '';
		$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) );

		$term_id = isset( $_POST['ose_collection'] ) ? absint( $_POST['ose_collection'] ) : 0;
		$new     = isset( $_POST['ose_new_collection'] ) ? sanitize_text_field( wp_unslash( $_POST['ose_new_collection'] ) ) : '';
		if ( $new && $can_add_term ) {
			$created = wp_insert_term( $new, OSE_CPT::TAXONOMY );
			if ( ! is_wp_error( $created ) ) {
				$term_id = (int) $created['term_id'];
			}
		}

		$count = 0;
		$order = 0;
		foreach ( $lines as $line ) {
			$parsed = OSE_Resolver::parse( $line );
			if ( ! $parsed ) {
				continue;
			}
			$post_id = wp_insert_post(
				array(
					'post_type'   => OSE_CPT::POST_TYPE,
					'post_status' => $status,
					'post_title'  => sprintf(
						/* translators: 1: platform, 2: id */
						__( '%1$s video %2$s', 'ofnoa-social-embed' ),
						ucfirst( $parsed['platform'] ),
						$parsed['id'] ? $parsed['id'] : ''
					),
					'menu_order'  => $order++,
				)
			);
			if ( is_wp_error( $post_id ) ) {
				continue;
			}
			update_post_meta( $post_id, '_ose_url', $parsed['url'] );
			if ( $term_id && $can_assign ) {
				wp_set_object_terms( $post_id, array( $term_id ), OSE_CPT::TAXONOMY );
			}
			self::sync_from_url( $post_id );
			++$count;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => OSE_CPT::POST_TYPE,
					'page'      => 'ose-import',
					'imported'  => $count,
					'status'    => $status,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}
}
