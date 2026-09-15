<?php
/**
 * Content types: brands, product reviews, and the taxonomies used for faceting.
 *
 * @package ReviewHub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers post types and taxonomies.
 */
class Review_Hub_Content_Types {

	const POST_TYPE_PRODUCT = 'rh_product';
	const POST_TYPE_BRAND   = 'rh_brand';

	const TAX_CATEGORY = 'rh_category';
	const TAX_CONCERN  = 'rh_concern';
	const TAX_REGION   = 'rh_region';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register post types and taxonomies.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE_BRAND,
			array(
				'labels'       => array(
					'name'          => __( 'Brands', 'review-hub' ),
					'singular_name' => __( 'Brand', 'review-hub' ),
					'add_new_item'  => __( 'Add Brand', 'review-hub' ),
					'edit_item'     => __( 'Edit Brand', 'review-hub' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'menu_icon'    => 'dashicons-awards',
				'menu_position'=> 26,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'rewrite'      => array( 'slug' => 'brands' ),
				'show_in_rest' => true,
			)
		);

		register_post_type(
			self::POST_TYPE_PRODUCT,
			array(
				'labels'       => array(
					'name'          => __( 'Product Reviews', 'review-hub' ),
					'singular_name' => __( 'Product Review', 'review-hub' ),
					'add_new_item'  => __( 'Add Product Review', 'review-hub' ),
					'edit_item'     => __( 'Edit Product Review', 'review-hub' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'menu_icon'    => 'dashicons-star-half',
				'menu_position'=> 25,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'rewrite'      => array( 'slug' => 'reviews' ),
				'show_in_rest' => true,
			)
		);

		self::register_taxonomy(
			self::TAX_CATEGORY,
			__( 'Product Types', 'review-hub' ),
			__( 'Product Type', 'review-hub' ),
			'product-type'
		);

		self::register_taxonomy(
			self::TAX_CONCERN,
			__( 'Goals', 'review-hub' ),
			__( 'Goal', 'review-hub' ),
			'goal'
		);

		self::register_taxonomy(
			self::TAX_REGION,
			__( 'Availability', 'review-hub' ),
			__( 'Region', 'review-hub' ),
			'availability'
		);
	}

	/**
	 * Register one shared-shape taxonomy across both post types.
	 *
	 * @param string $slug     Taxonomy key.
	 * @param string $plural   Plural label.
	 * @param string $singular Singular label.
	 * @param string $rewrite  URL base.
	 */
	private static function register_taxonomy( $slug, $plural, $singular, $rewrite ) {
		register_taxonomy(
			$slug,
			array( self::POST_TYPE_PRODUCT, self::POST_TYPE_BRAND ),
			array(
				'labels'            => array(
					'name'          => $plural,
					'singular_name' => $singular,
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => $rewrite ),
			)
		);
	}

	/**
	 * Terms shown as the icon grid, in display order.
	 *
	 * Each term may carry an icon key stored in term meta; the frontend falls
	 * back to a generic glyph when none is set.
	 *
	 * @return WP_Term[]
	 */
	public static function get_category_terms() {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAX_CATEGORY,
				'hide_empty' => false,
			)
		);

		return is_wp_error( $terms ) ? array() : $terms;
	}
}
