<?php
/**
 * Turns a set of attributes into a normalised list of renderable video items,
 * whether they come from the library or from a pasted list of URLs.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Item builder.
 */
class OSE_Query {

	/**
	 * Resolve the poster for a library video.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public static function poster_url( $post_id ) {
		if ( has_post_thumbnail( $post_id ) ) {
			$src = get_the_post_thumbnail_url( $post_id, 'large' );
			if ( $src ) {
				return $src;
			}
		}
		$meta = get_post_meta( $post_id, '_ose_poster', true );
		return $meta ? $meta : '';
	}

	/**
	 * Build the item list for one gallery instance.
	 *
	 * @param array $a Sanitized attributes.
	 * @return array
	 */
	public static function get_items( $a ) {
		$items = ( 'urls' === $a['source'] )
			? self::items_from_urls( $a )
			: self::items_from_library( $a );

		/**
		 * Filter the resolved items before rendering.
		 *
		 * @param array $items Item list.
		 * @param array $a     Attributes.
		 */
		return apply_filters( 'ose_items', $items, $a );
	}

	/**
	 * Items from a manual URL list.
	 *
	 * @param array $a Attributes.
	 * @return array
	 */
	private static function items_from_urls( $a ) {
		$raw = (string) $a['urls'];
		$raw = str_replace( array( '|', ',' ), "\n", $raw );
		$list = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) );
		$list = array_slice( array_values( array_unique( $list ) ), 0, (int) $a['limit'] );

		$items = array();
		foreach ( $list as $index => $url ) {
			$parsed = OSE_Resolver::parse( $url );
			if ( ! $parsed ) {
				continue;
			}
			$meta    = OSE_Resolver::fetch_meta( $parsed['url'] );
			$items[] = self::normalise(
				array(
					'id'       => 0,
					'key'      => 'u' . $index,
					'url'      => $parsed['url'],
					'platform' => $parsed['platform'],
					'embed'    => OSE_Resolver::embed_url( $parsed ),
					'poster'   => $meta['thumbnail'],
					'title'    => $meta['title'],
					'author'   => $meta['author'],
				)
			);
		}
		return $items;
	}

	/**
	 * Items from the video library.
	 *
	 * @param array $a Attributes.
	 * @return array
	 */
	private static function items_from_library( $a ) {
		$args = array(
			'post_type'              => OSE_CPT::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => (int) $a['limit'],
			'orderby'                => $a['orderby'],
			'order'                  => $a['order'],
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => true,
		);
		if ( 'menu_order' === $a['orderby'] ) {
			$args['orderby'] = array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			);
			unset( $args['order'] );
		}

		if ( ! empty( $a['ids'] ) ) {
			$ids = array_filter( array_map( 'absint', preg_split( '/[\s,]+/', (string) $a['ids'] ) ) );
			if ( $ids ) {
				$args['post__in'] = $ids;
				if ( 'menu_order' === $a['orderby'] ) {
					$args['orderby'] = 'post__in';
					unset( $args['order'] );
				}
			}
		}

		$slugs = self::slug_list( $a['collection'] );
		if ( $slugs ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => OSE_CPT::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $slugs,
				),
			);
		}

		$query = new WP_Query( $args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$url = get_post_meta( $post->ID, '_ose_url', true );
			if ( ! $url ) {
				continue;
			}
			$parsed = OSE_Resolver::parse( $url );
			if ( ! $parsed ) {
				continue;
			}
			$terms = wp_get_object_terms( $post->ID, OSE_CPT::TAXONOMY, array( 'fields' => 'all' ) );
			$slug_map = array();
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$slug_map[ $term->slug ] = $term->name;
				}
			}

			$items[] = self::normalise(
				array(
					'id'        => $post->ID,
					'key'       => 'p' . $post->ID,
					'url'       => $parsed['url'],
					'platform'  => $parsed['platform'],
					'embed'     => OSE_Resolver::embed_url( $parsed ),
					'poster'    => self::poster_url( $post->ID ),
					'title'     => get_the_title( $post ),
					'caption'   => $post->post_excerpt,
					'author'    => get_post_meta( $post->ID, '_ose_author', true ),
					'duration'  => get_post_meta( $post->ID, '_ose_duration', true ),
					'views'     => (int) get_post_meta( $post->ID, '_ose_views', true ),
					'likes'     => (int) get_post_meta( $post->ID, '_ose_likes', true ),
					'cta_url'   => get_post_meta( $post->ID, '_ose_cta_url', true ),
					'cta_label' => get_post_meta( $post->ID, '_ose_cta_label', true ),
					'date'      => get_the_date( '', $post ),
					'terms'     => $slug_map,
				)
			);
		}

		wp_reset_postdata();
		return $items;
	}

	/**
	 * Fill in missing keys with safe defaults.
	 *
	 * @param array $item Partial item.
	 * @return array
	 */
	private static function normalise( $item ) {
		return wp_parse_args(
			$item,
			array(
				'id'        => 0,
				'key'       => '',
				'url'       => '',
				'platform'  => '',
				'embed'     => '',
				'poster'    => '',
				'title'     => '',
				'caption'   => '',
				'author'    => '',
				'duration'  => '',
				'views'     => 0,
				'likes'     => 0,
				'cta_url'   => '',
				'cta_label' => '',
				'date'      => '',
				'terms'     => array(),
			)
		);
	}

	/**
	 * Split a comma separated slug string.
	 *
	 * @param string $value Raw value.
	 * @return array
	 */
	public static function slug_list( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return array();
		}
		$parts = array_filter( array_map( 'sanitize_title', preg_split( '/[\s,]+/', $value ) ) );
		return array_values( array_unique( $parts ) );
	}

	/**
	 * Build the tab list for the tabbed layout, from the items themselves so
	 * empty tabs never appear.
	 *
	 * @param array $items Items.
	 * @param array $a     Attributes.
	 * @return array Array of slug => label.
	 */
	public static function tabs_for( $items, $a ) {
		$wanted = self::slug_list( $a['tabs_from'] );
		$tabs   = array();

		if ( $wanted ) {
			foreach ( $wanted as $slug ) {
				$term = get_term_by( 'slug', $slug, OSE_CPT::TAXONOMY );
				if ( $term && ! is_wp_error( $term ) ) {
					$tabs[ $term->slug ] = $term->name;
				}
			}
		} else {
			foreach ( $items as $item ) {
				foreach ( $item['terms'] as $slug => $name ) {
					$tabs[ $slug ] = $name;
				}
			}
		}

		return $tabs;
	}

	/**
	 * All collections, for admin dropdowns.
	 *
	 * @return array slug => name
	 */
	public static function all_collections() {
		$terms = get_terms(
			array(
				'taxonomy'   => OSE_CPT::TAXONOMY,
				'hide_empty' => false,
			)
		);
		$out = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$out[ $term->slug ] = $term->name;
			}
		}
		return $out;
	}
}
