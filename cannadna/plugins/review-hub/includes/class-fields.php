<?php
/**
 * Meta fields for brands and product reviews, with admin UI and sanitisation.
 *
 * @package ReviewHub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and persists review metadata.
 */
class Review_Hub_Fields {

	/**
	 * Field definitions keyed by post type.
	 *
	 * 'type' drives both the admin control and the sanitiser.
	 *
	 * @return array<string, array<string, array>>
	 */
	public static function schema() {
		return array(
			Review_Hub_Content_Types::POST_TYPE_PRODUCT => array(
				'rh_rating'        => array(
					'label' => __( 'Rating (0-5)', 'review-hub' ),
					'type'  => 'float',
				),
				'rh_brand_id'      => array(
					'label' => __( 'Brand', 'review-hub' ),
					'type'  => 'brand',
				),
				'rh_price'         => array(
					'label' => __( 'Price', 'review-hub' ),
					'type'  => 'float',
				),
				'rh_price_was'     => array(
					'label' => __( 'Price before discount (optional)', 'review-hub' ),
					'type'  => 'float',
				),
				'rh_currency'      => array(
					'label' => __( 'Currency symbol', 'review-hub' ),
					'type'  => 'text',
				),
				'rh_affiliate_url' => array(
					'label' => __( 'Affiliate URL', 'review-hub' ),
					'type'  => 'url',
				),
				'rh_discount_code' => array(
					'label' => __( 'Discount code', 'review-hub' ),
					'type'  => 'text',
				),
				'rh_cbd_mg'        => array(
					'label' => __( 'CBD per unit (mg)', 'review-hub' ),
					'type'  => 'float',
				),
				'rh_thc_pct'       => array(
					'label' => __( 'THC (%)', 'review-hub' ),
					'type'  => 'float',
				),
				'rh_lab_tested'    => array(
					'label' => __( 'Third-party lab tested', 'review-hub' ),
					'type'  => 'bool',
				),
			),
			Review_Hub_Content_Types::POST_TYPE_BRAND   => array(
				'rh_rating'        => array(
					'label' => __( 'Rating (0-5)', 'review-hub' ),
					'type'  => 'float',
				),
				'rh_affiliate_url' => array(
					'label' => __( 'Affiliate URL', 'review-hub' ),
					'type'  => 'url',
				),
				'rh_discount_code' => array(
					'label' => __( 'Discount code', 'review-hub' ),
					'type'  => 'text',
				),
				'rh_ships_to'      => array(
					'label' => __( 'Ships to (short note)', 'review-hub' ),
					'type'  => 'text',
				),
				'rh_lab_tested'    => array(
					'label' => __( 'Publishes lab reports', 'review-hub' ),
					'type'  => 'bool',
				),
			),
		);
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * Expose meta to REST so the block editor and frontend queries can read it.
	 */
	public static function register_meta() {
		foreach ( self::schema() as $post_type => $fields ) {
			foreach ( $fields as $key => $field ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'single'            => true,
						'type'              => self::rest_type( $field['type'] ),
						'show_in_rest'      => true,
						'sanitize_callback' => array( __CLASS__, 'sanitize_' . self::sanitizer( $field['type'] ) ),
						'auth_callback'     => function () {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}
	}

	/**
	 * Map a field type to its REST primitive.
	 *
	 * @param string $type Field type.
	 * @return string
	 */
	private static function rest_type( $type ) {
		switch ( $type ) {
			case 'float':
				return 'number';
			case 'bool':
				return 'boolean';
			case 'brand':
				return 'integer';
			default:
				return 'string';
		}
	}

	/**
	 * Map a field type to its sanitiser suffix.
	 *
	 * @param string $type Field type.
	 * @return string
	 */
	private static function sanitizer( $type ) {
		switch ( $type ) {
			case 'float':
				return 'float';
			case 'bool':
				return 'bool';
			case 'brand':
				return 'int';
			case 'url':
				return 'url';
			default:
				return 'text';
		}
	}

	/**
	 * Sanitise a float value.
	 *
	 * @param mixed $value Raw value.
	 * @return float
	 */
	public static function sanitize_float( $value ) {
		return (float) $value;
	}

	/**
	 * Sanitise a boolean value.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function sanitize_bool( $value ) {
		return (bool) $value;
	}

	/**
	 * Sanitise an integer value.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public static function sanitize_int( $value ) {
		return (int) $value;
	}

	/**
	 * Sanitise a URL.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_url( $value ) {
		return esc_url_raw( (string) $value );
	}

	/**
	 * Sanitise a plain-text value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_text( $value ) {
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Register the editor meta box for both post types.
	 */
	public static function add_meta_box() {
		foreach ( array_keys( self::schema() ) as $post_type ) {
			add_meta_box(
				'review-hub-details',
				__( 'Review details', 'review-hub' ),
				array( __CLASS__, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the meta box controls.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function render_meta_box( $post ) {
		$schema = self::schema();

		if ( ! isset( $schema[ $post->post_type ] ) ) {
			return;
		}

		wp_nonce_field( 'review_hub_save_meta', 'review_hub_meta_nonce' );

		echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">';

		foreach ( $schema[ $post->post_type ] as $key => $field ) {
			$value = get_post_meta( $post->ID, $key, true );

			echo '<p style="margin:0;">';
			printf(
				'<label for="%1$s" style="display:block;font-weight:600;margin-bottom:4px;">%2$s</label>',
				esc_attr( $key ),
				esc_html( $field['label'] )
			);

			if ( 'bool' === $field['type'] ) {
				printf(
					'<input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s />',
					esc_attr( $key ),
					checked( (bool) $value, true, false )
				);
			} elseif ( 'brand' === $field['type'] ) {
				$brands = get_posts(
					array(
						'post_type'      => Review_Hub_Content_Types::POST_TYPE_BRAND,
						'posts_per_page' => 100,
						'orderby'        => 'title',
						'order'          => 'ASC',
					)
				);

				printf( '<select id="%1$s" name="%1$s" style="width:100%%;">', esc_attr( $key ) );
				echo '<option value="0">' . esc_html__( '— none —', 'review-hub' ) . '</option>';

				foreach ( $brands as $brand ) {
					printf(
						'<option value="%1$d" %2$s>%3$s</option>',
						(int) $brand->ID,
						selected( (int) $value, (int) $brand->ID, false ),
						esc_html( $brand->post_title )
					);
				}

				echo '</select>';
			} else {
				$input_type = in_array( $field['type'], array( 'float' ), true ) ? 'number' : 'text';
				printf(
					'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" style="width:100%%;" %4$s />',
					esc_attr( $input_type ),
					esc_attr( $key ),
					esc_attr( (string) $value ),
					'number' === $input_type ? 'step="0.01"' : ''
				);
			}

			echo '</p>';
		}

		echo '</div>';
	}

	/**
	 * Persist meta box values.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save( $post_id, $post ) {
		$schema = self::schema();

		if ( ! isset( $schema[ $post->post_type ] ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['review_hub_meta_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['review_hub_meta_nonce'] ) ), 'review_hub_save_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( $schema[ $post->post_type ] as $key => $field ) {
			if ( 'bool' === $field['type'] ) {
				update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? 1 : 0 );
				continue;
			}

			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$raw       = wp_unslash( $_POST[ $key ] );
			$sanitizer = array( __CLASS__, 'sanitize_' . self::sanitizer( $field['type'] ) );

			update_post_meta( $post_id, $key, call_user_func( $sanitizer, $raw ) );
		}
	}
}
