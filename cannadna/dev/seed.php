<?php
/**
 * Seed demo content for local development.
 *
 * Brands here are invented placeholders. Real brand names are deliberately not
 * used: ratings and lab-testing claims about real companies must come from
 * actual editorial review, not generated filler.
 *
 * Run: php wp-cli.phar eval-file seed.php --allow-root
 */

$categories = array(
	'Flowers'  => 'flower',
	'Oils'     => 'oil',
	'Vapes'    => 'vape',
	'Gummies'  => 'gummy',
	'Capsules' => 'capsules',
	'Topicals' => 'cream',
	'Pet care' => 'pet',
	'Drinks'   => 'drink',
);

$concerns = array(
	'Sleep'   => 'sleep',
	'Pain'    => 'pain',
	'Energy'  => 'energy',
	'Calm'    => 'calm',
	'Pets'    => 'pet-care',
);

$regions = array(
	'EU only'   => 'eu-only',
	'UK'        => 'uk',
	'Worldwide' => 'worldwide',
);

function rh_seed_terms( $taxonomy, $pairs ) {
	$ids = array();

	foreach ( $pairs as $name => $slug ) {
		$existing = get_term_by( 'slug', $slug, $taxonomy );

		if ( $existing ) {
			$ids[ $slug ] = (int) $existing->term_id;
			continue;
		}

		$term = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );

		if ( ! is_wp_error( $term ) ) {
			$ids[ $slug ] = (int) $term['term_id'];
		}
	}

	return $ids;
}

rh_seed_terms( 'rh_category', $categories );
rh_seed_terms( 'rh_concern', $concerns );
rh_seed_terms( 'rh_region', $regions );

WP_CLI::log( 'Taxonomy terms seeded.' );

$brands = array(
	array(
		'title'    => 'Northwind Botanicals',
		'rating'   => 4.6,
		'ships'    => 'EU + UK',
		'code'     => 'NORTH15',
		'lab'      => 1,
		'regions'  => array( 'eu-only', 'uk' ),
		'excerpt'  => 'Full-spectrum extracts with batch-level certificates published for every SKU.',
	),
	array(
		'title'    => 'Marrow & Fen',
		'rating'   => 4.2,
		'ships'    => 'Worldwide',
		'code'     => 'FEN10',
		'lab'      => 1,
		'regions'  => array( 'worldwide' ),
		'excerpt'  => 'Small-batch grower focused on single-origin flower and terpene transparency.',
	),
	array(
		'title'    => 'Halcyon Hemp Co.',
		'rating'   => 3.9,
		'ships'    => 'EU only',
		'code'     => 'HALCYON',
		'lab'      => 0,
		'regions'  => array( 'eu-only' ),
		'excerpt'  => 'Budget-friendly range with broad availability but inconsistent lab reporting.',
	),
	array(
		'title'    => 'Verdance Labs',
		'rating'   => 4.8,
		'ships'    => 'Worldwide',
		'code'     => 'VERD20',
		'lab'      => 1,
		'regions'  => array( 'worldwide', 'uk' ),
		'excerpt'  => 'Pharmaceutical-grade isolates, third-party tested and traceable to source batch.',
	),
	array(
		'title'    => 'Ash & Ember',
		'rating'   => 4.0,
		'ships'    => 'UK',
		'code'     => 'EMBER5',
		'lab'      => 1,
		'regions'  => array( 'uk' ),
		'excerpt'  => 'Vape-first brand with a narrow but well-documented hardware range.',
	),
);

$brand_ids = array();

foreach ( $brands as $brand ) {
	$existing = get_page_by_title( $brand['title'], OBJECT, 'rh_brand' );

	if ( $existing ) {
		$brand_ids[ $brand['title'] ] = (int) $existing->ID;
		continue;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => 'rh_brand',
			'post_status'  => 'publish',
			'post_title'   => $brand['title'],
			'post_excerpt' => $brand['excerpt'],
			'post_content' => $brand['excerpt'] . ' This placeholder entry exists so the layout can be reviewed with realistic content shape.',
		)
	);

	update_post_meta( $id, 'rh_rating', $brand['rating'] );
	update_post_meta( $id, 'rh_ships_to', $brand['ships'] );
	update_post_meta( $id, 'rh_discount_code', $brand['code'] );
	update_post_meta( $id, 'rh_lab_tested', $brand['lab'] );
	update_post_meta( $id, 'rh_affiliate_url', 'https://example.com/partner/' . sanitize_title( $brand['title'] ) );

	wp_set_object_terms( $id, $brand['regions'], 'rh_region' );

	$brand_ids[ $brand['title'] ] = $id;
}

WP_CLI::log( 'Brands seeded: ' . count( $brand_ids ) );

$products = array(
	array( 'Daybreak 10% CBD Oil', 'Northwind Botanicals', 4.7, 39.00, 49.00, 1000, 0.2, 1, 'oil', 'calm', array( 'eu-only', 'uk' ) ),
	array( 'Nightfall 20% Oil', 'Northwind Botanicals', 4.5, 69.00, 0, 2000, 0.2, 1, 'oil', 'sleep', array( 'eu-only' ) ),
	array( 'Orchard Gummies 25mg', 'Verdance Labs', 4.8, 24.50, 29.00, 750, 0, 1, 'gummy', 'calm', array( 'worldwide' ) ),
	array( 'Deep Rest Capsules', 'Verdance Labs', 4.6, 32.00, 0, 900, 0, 1, 'capsules', 'sleep', array( 'worldwide', 'uk' ) ),
	array( 'Single-Origin Flower 15g', 'Marrow & Fen', 4.3, 45.00, 0, 0, 0.19, 1, 'flower', 'calm', array( 'worldwide' ) ),
	array( 'Terpene Reserve Flower', 'Marrow & Fen', 4.1, 52.00, 60.00, 0, 0.2, 1, 'flower', 'energy', array( 'worldwide' ) ),
	array( 'Ember Pod Starter Kit', 'Ash & Ember', 4.0, 34.99, 0, 500, 0, 1, 'vape', 'energy', array( 'uk' ) ),
	array( 'Ember Refill Twin Pack', 'Ash & Ember', 3.8, 19.99, 24.99, 400, 0, 1, 'vape', 'calm', array( 'uk' ) ),
	array( 'Everyday Relief Balm', 'Halcyon Hemp Co.', 3.9, 18.00, 0, 300, 0, 0, 'cream', 'pain', array( 'eu-only' ) ),
	array( 'Muscle Recovery Rub', 'Halcyon Hemp Co.', 3.6, 22.00, 27.00, 450, 0, 0, 'cream', 'pain', array( 'eu-only' ) ),
	array( 'Calm Companion Pet Drops', 'Verdance Labs', 4.4, 28.00, 0, 250, 0, 1, 'pet', 'pet-care', array( 'worldwide' ) ),
	array( 'Senior Dog Mobility Oil', 'Northwind Botanicals', 4.2, 31.00, 36.00, 300, 0, 1, 'pet', 'pet-care', array( 'eu-only', 'uk' ) ),
	array( 'Sparkling Hemp Tonic', 'Marrow & Fen', 3.7, 4.50, 0, 20, 0, 0, 'drink', 'energy', array( 'worldwide' ) ),
	array( 'Evening Chamomile Blend', 'Halcyon Hemp Co.', 4.0, 16.00, 0, 150, 0, 0, 'drink', 'sleep', array( 'eu-only' ) ),
	array( 'Focus Micro-Dose Capsules', 'Verdance Labs', 4.5, 38.00, 0, 600, 0, 1, 'capsules', 'energy', array( 'worldwide', 'uk' ) ),
	array( 'Broad-Spectrum 5% Oil', 'Ash & Ember', 4.1, 26.00, 32.00, 500, 0, 1, 'oil', 'pain', array( 'uk' ) ),
);

$count = 0;

foreach ( $products as $row ) {
	list( $title, $brand, $rating, $price, $was, $cbd, $thc, $lab, $category, $concern, $region ) = $row;

	if ( get_page_by_title( $title, OBJECT, 'rh_product' ) ) {
		continue;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => 'rh_product',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => 'Placeholder review copy for ' . $title . ' from ' . $brand . '.',
			'post_content' => 'Demo review body. Replace with editorial assessment covering potency, lab results, value and shipping.',
		)
	);

	update_post_meta( $id, 'rh_rating', $rating );
	update_post_meta( $id, 'rh_brand_id', $brand_ids[ $brand ] );
	update_post_meta( $id, 'rh_price', $price );
	update_post_meta( $id, 'rh_price_was', $was );
	update_post_meta( $id, 'rh_currency', '€' );
	update_post_meta( $id, 'rh_cbd_mg', $cbd );
	update_post_meta( $id, 'rh_thc_pct', $thc );
	update_post_meta( $id, 'rh_lab_tested', $lab );
	update_post_meta( $id, 'rh_discount_code', get_post_meta( $brand_ids[ $brand ], 'rh_discount_code', true ) );
	update_post_meta( $id, 'rh_affiliate_url', 'https://example.com/partner/' . sanitize_title( $brand ) . '/' . sanitize_title( $title ) );

	wp_set_object_terms( $id, array( $category ), 'rh_category' );
	wp_set_object_terms( $id, array( $concern ), 'rh_concern' );
	wp_set_object_terms( $id, $region, 'rh_region' );

	++$count;
}

delete_transient( 'review_hub_stats' );

WP_CLI::log( 'Product reviews seeded: ' . $count );
