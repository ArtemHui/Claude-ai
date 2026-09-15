<?php
/**
 * Public REST endpoints backing the filter grid and the product finder.
 *
 * @package ReviewHub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read-only REST routes for published review content.
 */
class Review_Hub_REST {

	const NAMESPACE = 'review-hub/v1';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 */
	public static function register_routes() {
		$filter_args = array(
			'category'   => array( 'default' => '' ),
			'concern'    => array( 'default' => '' ),
			'region'     => array( 'default' => '' ),
			'min_rating' => array( 'default' => 0 ),
			'lab_tested' => array( 'default' => false ),
			'search'     => array( 'default' => '' ),
			'orderby'    => array( 'default' => 'rating' ),
			'per_page'   => array( 'default' => 12 ),
			'page'       => array( 'default' => 1 ),
			'post_type'  => array( 'default' => Review_Hub_Content_Types::POST_TYPE_PRODUCT ),
		);

		register_rest_route(
			self::NAMESPACE,
			'/items',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_items' ),
				'permission_callback' => '__return_true',
				'args'                => $filter_args,
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/finder',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_finder_matches' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'category' => array( 'default' => '' ),
					'concern'  => array( 'default' => '' ),
					'region'   => array( 'default' => '' ),
					'limit'    => array( 'default' => 4 ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/compare',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_comparison' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'ids' => array( 'default' => '' ),
				),
			)
		);
	}

	/**
	 * Filtered listing.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_items( WP_REST_Request $request ) {
		$filters = Review_Hub_Query::parse_filters( $request->get_params() );
		$query   = Review_Hub_Query::run( $filters );

		$items = array();

		foreach ( $query->posts as $post ) {
			$items[] = Review_Hub_Query::card( $post->ID );
		}

		return rest_ensure_response(
			array(
				'items'       => $items,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
				'page'        => $filters['page'],
			)
		);
	}

	/**
	 * Product finder matching.
	 *
	 * Scores candidates by how many of the answered facets they satisfy, so a
	 * partial match still returns useful recommendations rather than nothing.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_finder_matches( WP_REST_Request $request ) {
		$category = sanitize_title( (string) $request->get_param( 'category' ) );
		$concern  = sanitize_title( (string) $request->get_param( 'concern' ) );
		$region   = sanitize_title( (string) $request->get_param( 'region' ) );
		$limit    = max( 1, min( 12, (int) $request->get_param( 'limit' ) ) );

		// Pull a broad candidate pool ordered by rating, then score in PHP.
		$candidates = Review_Hub_Query::run(
			Review_Hub_Query::parse_filters(
				array(
					'post_type' => Review_Hub_Content_Types::POST_TYPE_PRODUCT,
					'per_page'  => 48,
					'orderby'   => 'rating',
				)
			)
		);

		$wanted = array_filter(
			array(
				Review_Hub_Content_Types::TAX_CATEGORY => $category,
				Review_Hub_Content_Types::TAX_CONCERN  => $concern,
				Review_Hub_Content_Types::TAX_REGION   => $region,
			)
		);

		$scored = array();

		foreach ( $candidates->posts as $post ) {
			$score   = 0;
			$matched = array();

			foreach ( $wanted as $taxonomy => $slug ) {
				$terms = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'slugs' ) );

				if ( ! is_wp_error( $terms ) && in_array( $slug, $terms, true ) ) {
					++$score;
					$matched[] = $taxonomy;
				}
			}

			$card                 = Review_Hub_Query::card( $post->ID );
			$card['match_score']  = $score;
			$card['match_of']     = count( $wanted );
			$card['matched_taxa'] = $matched;

			$scored[] = $card;
		}

		usort(
			$scored,
			static function ( $a, $b ) {
				if ( $a['match_score'] === $b['match_score'] ) {
					return $b['rating'] <=> $a['rating'];
				}

				return $b['match_score'] <=> $a['match_score'];
			}
		);

		return rest_ensure_response(
			array(
				'items'   => array_slice( $scored, 0, $limit ),
				'answers' => array(
					'category' => $category,
					'concern'  => $concern,
					'region'   => $region,
				),
			)
		);
	}

	/**
	 * Side-by-side brand comparison.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_comparison( WP_REST_Request $request ) {
		$ids = array_slice(
			array_filter(
				array_map( 'absint', explode( ',', (string) $request->get_param( 'ids' ) ) )
			),
			0,
			3
		);

		$items = array();

		foreach ( $ids as $id ) {
			$post = get_post( $id );

			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			if ( Review_Hub_Content_Types::POST_TYPE_BRAND !== $post->post_type ) {
				continue;
			}

			$card                   = Review_Hub_Query::card( $id );
			$card['review_count']   = self::count_brand_reviews( $id );
			$items[]                = $card;
		}

		return rest_ensure_response( array( 'items' => $items ) );
	}

	/**
	 * Number of published product reviews attached to a brand.
	 *
	 * @param int $brand_id Brand post ID.
	 * @return int
	 */
	private static function count_brand_reviews( $brand_id ) {
		$query = new WP_Query(
			array(
				'post_type'      => Review_Hub_Content_Types::POST_TYPE_PRODUCT,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => 'rh_brand_id',
						'value' => (int) $brand_id,
					),
				),
			)
		);

		return (int) $query->found_posts;
	}
}
