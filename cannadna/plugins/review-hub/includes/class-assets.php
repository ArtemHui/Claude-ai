<?php
/**
 * Frontend asset registration.
 *
 * Third-party libraries are bundled with the plugin rather than loaded from a
 * CDN: it keeps the site working without third-party requests and avoids
 * sending visitor IPs to a CDN, which matters for EU traffic.
 *
 * Assets are registered on every request but only enqueued by the shortcodes
 * that actually need them.
 *
 * @package ReviewHub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and conditionally enqueues assets.
 */
class Review_Hub_Assets {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register all handles.
	 */
	public static function register() {
		wp_register_style( 'swiper', REVIEW_HUB_URL . 'assets/vendor/swiper.min.css', array(), '11.2.10' );
		wp_register_script( 'swiper', REVIEW_HUB_URL . 'assets/vendor/swiper.min.js', array(), '11.2.10', true );

		wp_register_style( 'aos', REVIEW_HUB_URL . 'assets/vendor/aos.css', array(), '2.3.4' );
		wp_register_script( 'aos', REVIEW_HUB_URL . 'assets/vendor/aos.js', array(), '2.3.4', true );

		wp_register_style( 'glightbox', REVIEW_HUB_URL . 'assets/vendor/glightbox.min.css', array(), '3.3.1' );
		wp_register_script( 'glightbox', REVIEW_HUB_URL . 'assets/vendor/glightbox.min.js', array(), '3.3.1', true );

		wp_register_style(
			'review-hub',
			REVIEW_HUB_URL . 'assets/css/review-hub.css',
			array( 'swiper', 'aos', 'glightbox' ),
			REVIEW_HUB_VERSION
		);

		wp_register_script(
			'review-hub',
			REVIEW_HUB_URL . 'assets/js/review-hub.js',
			array( 'swiper', 'aos', 'glightbox' ),
			REVIEW_HUB_VERSION,
			true
		);

		wp_localize_script(
			'review-hub',
			'ReviewHubData',
			array(
				'restUrl' => esc_url_raw( rest_url( Review_Hub_REST::NAMESPACE ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'noResults'  => __( 'No products match those filters yet.', 'review-hub' ),
					'clearAll'   => __( 'Clear filters', 'review-hub' ),
					'loading'    => __( 'Loading…', 'review-hub' ),
					'matchOf'    => __( 'matches %1$d of %2$d', 'review-hub' ),
					'viewOffer'  => __( 'View offer', 'review-hub' ),
					'readReview' => __( 'Read review', 'review-hub' ),
					'labLabel'   => __( 'Lab', 'review-hub' ),
					'verified'   => __( 'Verified', 'review-hub' ),
					'startOver'  => __( 'Start over', 'review-hub' ),
					'back'       => __( 'Back', 'review-hub' ),
					'step'       => __( 'Step %1$d of %2$d', 'review-hub' ),
				),
			)
		);
	}

	/**
	 * Enqueue the plugin bundle.
	 */
	public static function enqueue() {
		wp_enqueue_style( 'review-hub' );
		wp_enqueue_script( 'review-hub' );
	}
}
