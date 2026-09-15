<?php
/**
 * Shared query + card-payload layer used by REST, shortcodes and the finder.
 *
 * @package ReviewHub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds filtered queries and normalises results into card payloads.
 */
class Review_Hub_Query {

	/**
	 * Normalise raw filter input from any source.
	 *
	 * @param array $raw Untrusted input.
	 * @return array{category:string[],concern:string[],region:string[],min_rating:float,lab_tested:bool,search:string,orderby:string,per_page:int,page:int,post_type:string}
	 */
	public static function parse_filters( array $raw ) {
		$slugs = static function ( $value ) {
			$value = is_array( $value ) ? $value : explode( ',', (string) $value );

			return array_values(
				array_filter(
					array_map(
						static function ( $item ) {
							return sanitize_title( trim( (string) $item ) );
						},
						$value
					)
				)
			);
		};

		$allowed_order = array( 'rating', 'price_asc', 'price_desc', 'recent', 'title' );
		$orderby       = isset( $raw['orderby'] ) ? sanitize_key( $raw['orderby'] ) : 'rating';

		$post_type = isset( $raw['post_type'] ) ? sanitize_key( $raw['post_type'] ) : Review_Hub_Content_Types::POST_TYPE_PRODUCT;
		$post_type = in_array(
			$post_type,
			array( Review_Hub_Content_Types::POST_TYPE_PRODUCT, Review_Hub_Content_Types::POST_TYPE_BRAND ),
			true
		) ? $post_type : Review_Hub_Content_Types::POST_TYPE_PRODUCT;

		return array(
			'category'   => isset( $raw['category'] ) ? $slugs( $raw['category'] ) : array(),
			'concern'    => isset( $raw['concern'] ) ? $slugs( $raw['concern'] ) : array(),
			'region'     => isset( $raw['region'] ) ? $slugs( $raw['region'] ) : array(),
			'min_rating' => isset( $raw['min_rating'] ) ? max( 0, min( 5, (float) $raw['min_rating'] ) ) : 0.0,
			'lab_tested' => ! empty( $raw['lab_tested'] ),
			'search'     => isset( $raw['search'] ) ? sanitize_text_field( (string) $raw['search'] ) : '',
			'orderby'    => in_array( $orderby, $allowed_order, true ) ? $orderby : 'rating',
			'per_page'   => isset( $raw['per_page'] ) ? max( 1, min( 48, (int) $raw['per_page'] ) ) : 12,
			'page'       => isset( $raw['page'] ) ? max( 1, (int) $raw['page'] ) : 1,
			'post_type'  => $post_type,
		);
	}

	/**
	 * Run a filtered query.
	 *
	 * @param array $filters Parsed filters.
	 * @return WP_Query
	 */
	public static function run( array $filters ) {
		$args = array(
			'post_type'      => $filters['post_type'],
			'post_status'    => 'publish',
			'posts_per_page' => $filters['per_page'],
			'paged'          => $filters['page'],
			'meta_query'     => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'      => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		);

		$taxonomies = array(
			Review_Hub_Content_Types::TAX_CATEGORY => $filters['category'],
			Review_Hub_Content_Types::TAX_CONCERN  => $filters['concern'],
			Review_Hub_Content_Types::TAX_REGION   => $filters['region'],
		);

		foreach ( $taxonomies as $taxonomy => $terms ) {
			if ( empty( $terms ) ) {
				continue;
			}

			$args['tax_query'][] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $terms,
				'operator' => 'IN',
			);
		}

		if ( count( $args['tax_query'] ) > 1 ) {
			$args['tax_query']['relation'] = 'AND';
		}

		if ( $filters['min_rating'] > 0 ) {
			$args['meta_query'][] = array(
				'key'     => 'rh_rating',
				'value'   => $filters['min_rating'],
				'type'    => 'DECIMAL(10,2)',
				'compare' => '>=',
			);
		}

		if ( $filters['lab_tested'] ) {
			$args['meta_query'][] = array(
				'key'     => 'rh_lab_tested',
				'value'   => '1',
				'compare' => '=',
			);
		}

		if ( '' !== $filters['search'] ) {
			$args['s'] = $filters['search'];
		}

		switch ( $filters['orderby'] ) {
			case 'price_asc':
			case 'price_desc':
				$args['meta_key'] = 'rh_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'price_asc' === $filters['orderby'] ? 'ASC' : 'DESC';
				break;
			case 'recent':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			case 'rating':
			default:
				$args['meta_key'] = 'rh_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;
		}

		return new WP_Query( $args );
	}

	/**
	 * Normalise a post into the card payload shared by PHP and JS rendering.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function card( $post_id ) {
		$post_id   = (int) $post_id;
		$post_type = get_post_type( $post_id );
		$brand_id  = (int) get_post_meta( $post_id, 'rh_brand_id', true );
		$price     = (float) get_post_meta( $post_id, 'rh_price', true );
		$price_was = (float) get_post_meta( $post_id, 'rh_price_was', true );
		$currency  = (string) get_post_meta( $post_id, 'rh_currency', true );

		$regions = wp_get_post_terms( $post_id, Review_Hub_Content_Types::TAX_REGION, array( 'fields' => 'names' ) );
		$regions = is_wp_error( $regions ) ? array() : $regions;

		$categories = wp_get_post_terms( $post_id, Review_Hub_Content_Types::TAX_CATEGORY, array( 'fields' => 'names' ) );
		$categories = is_wp_error( $categories ) ? array() : $categories;

		// Titles come back entity-encoded from the_title filters. Both render
		// paths escape on output, so the payload carries decoded text to avoid
		// double-encoding ("Ash &#038; Ember").
		$decode = static function ( $value ) {
			return html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
		};

		return array(
			'id'            => $post_id,
			'type'          => $post_type,
			'title'         => $decode( get_the_title( $post_id ) ),
			'permalink'     => get_permalink( $post_id ),
			'excerpt'       => $decode( wp_trim_words( get_the_excerpt( $post_id ), 22 ) ),
			'image'         => get_the_post_thumbnail_url( $post_id, 'medium_large' ),
			'rating'        => round( (float) get_post_meta( $post_id, 'rh_rating', true ), 1 ),
			'brand'         => $brand_id ? $decode( get_the_title( $brand_id ) ) : '',
			'brand_id'      => $brand_id,
			'price'         => $price,
			'price_was'     => $price_was,
			'currency'      => '' !== $currency ? $currency : '€',
			'discount_code' => (string) get_post_meta( $post_id, 'rh_discount_code', true ),
			'cbd_mg'        => (float) get_post_meta( $post_id, 'rh_cbd_mg', true ),
			'thc_pct'       => (float) get_post_meta( $post_id, 'rh_thc_pct', true ),
			'lab_tested'    => (bool) get_post_meta( $post_id, 'rh_lab_tested', true ),
			'ships_to'      => (string) get_post_meta( $post_id, 'rh_ships_to', true ),
			'regions'       => array_values( $regions ),
			'categories'    => array_values( $categories ),
			'offer_url'     => Review_Hub_Clicks::offer_url( $post_id ),
			'has_offer'     => '' !== (string) get_post_meta( $post_id, 'rh_affiliate_url', true ),
		);
	}

	/**
	 * Aggregate site statistics, derived from real content rather than hardcoded.
	 *
	 * @return array{brands:int,products:int,avg_rating:float,lab_tested:int}
	 */
	public static function stats() {
		$cached = get_transient( 'review_hub_stats' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$brands   = (int) wp_count_posts( Review_Hub_Content_Types::POST_TYPE_BRAND )->publish;
		$products = (int) wp_count_posts( Review_Hub_Content_Types::POST_TYPE_PRODUCT )->publish;

		$avg = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT AVG(CAST(pm.meta_value AS DECIMAL(10,2)))
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = %s
				   AND p.post_status = 'publish'
				   AND p.post_type IN ( %s, %s )
				   AND pm.meta_value > 0",
				'rh_rating',
				Review_Hub_Content_Types::POST_TYPE_PRODUCT,
				Review_Hub_Content_Types::POST_TYPE_BRAND
			)
		);

		$lab_tested = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COUNT(*)
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = %s
				   AND pm.meta_value = '1'
				   AND p.post_status = 'publish'
				   AND p.post_type = %s",
				'rh_lab_tested',
				Review_Hub_Content_Types::POST_TYPE_PRODUCT
			)
		);

		$stats = array(
			'brands'     => $brands,
			'products'   => $products,
			'avg_rating' => round( (float) $avg, 1 ),
			'lab_tested' => $lab_tested,
		);

		set_transient( 'review_hub_stats', $stats, 10 * MINUTE_IN_SECONDS );

		return $stats;
	}

	/**
	 * Clear the cached statistics when content changes.
	 */
	public static function flush_stats() {
		delete_transient( 'review_hub_stats' );
	}
}

add_action( 'save_post', array( 'Review_Hub_Query', 'flush_stats' ) );
add_action( 'deleted_post', array( 'Review_Hub_Query', 'flush_stats' ) );
