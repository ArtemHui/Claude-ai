<?php
/**
 * Outbound affiliate click tracking.
 *
 * The outbound click is this site's conversion event, so it is routed through
 * an internal endpoint that records the click and then redirects.
 *
 * The destination is always read from post meta by ID. A URL supplied in the
 * request is never used as a redirect target, so this cannot be abused as an
 * open redirect.
 *
 * @package ReviewHub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Records and redirects outbound affiliate clicks.
 */
class Review_Hub_Clicks {

	const QUERY_VAR = 'rh_go';
	const TABLE     = 'review_hub_clicks';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ) );
	}

	/**
	 * Create the click log table.
	 */
	public static function create_table() {
		global $wpdb;

		$table           = $wpdb->prefix . self::TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT UNSIGNED NOT NULL,
			clicked_at DATETIME NOT NULL,
			referrer VARCHAR(255) NOT NULL DEFAULT '',
			source VARCHAR(64) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY clicked_at (clicked_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Register the /go/{id} rewrite.
	 */
	public static function add_rewrite() {
		add_rewrite_rule( '^go/([0-9]+)/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
	}

	/**
	 * Allow the query var through.
	 *
	 * @param string[] $vars Registered query vars.
	 * @return string[]
	 */
	public static function register_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	/**
	 * Build the tracked outbound URL for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string Empty string when the post has no affiliate URL.
	 */
	public static function offer_url( $post_id ) {
		$destination = (string) get_post_meta( (int) $post_id, 'rh_affiliate_url', true );

		if ( '' === $destination ) {
			return '';
		}

		return home_url( '/go/' . (int) $post_id . '/' );
	}

	/**
	 * Log the click and redirect to the stored destination.
	 */
	public static function maybe_redirect() {
		$post_id = (int) get_query_var( self::QUERY_VAR );

		if ( $post_id <= 0 ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			wp_safe_redirect( home_url( '/' ), 302 );
			exit;
		}

		$destination = (string) get_post_meta( $post_id, 'rh_affiliate_url', true );

		// Only ever redirect to a stored, validated destination.
		if ( '' === $destination || ! wp_http_validate_url( $destination ) ) {
			wp_safe_redirect( get_permalink( $post_id ), 302 );
			exit;
		}

		self::record( $post_id );

		// Affiliate destinations are external by design, so wp_redirect is
		// correct here; the value comes from an editor-supplied meta field
		// validated above, never from the request.
		wp_redirect( $destination, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/**
	 * Insert a click row.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function record( $post_id ) {
		global $wpdb;

		$referrer = isset( $_SERVER['HTTP_REFERER'] )
			? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) )
			: '';

		$source = isset( $_GET['src'] ) ? sanitize_key( wp_unslash( $_GET['src'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . self::TABLE,
			array(
				'post_id'    => (int) $post_id,
				'clicked_at' => current_time( 'mysql' ),
				'referrer'   => mb_substr( $referrer, 0, 255 ),
				'source'     => mb_substr( $source, 0, 64 ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Total recorded clicks for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public static function count_for( $post_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE post_id = %d", (int) $post_id )
		);
	}
}
