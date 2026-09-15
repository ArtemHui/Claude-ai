<?php
/**
 * Shortcodes rendering the public components.
 *
 * Every listing renders server-side first so the page is useful without JS and
 * indexable by search engines; JS then takes over for filtering.
 *
 * @package ReviewHub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the public shortcodes.
 */
class Review_Hub_Shortcodes {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_shortcode( 'rh_stats', array( __CLASS__, 'stats' ) );
		add_shortcode( 'rh_categories', array( __CLASS__, 'categories' ) );
		add_shortcode( 'rh_grid', array( __CLASS__, 'grid' ) );
		add_shortcode( 'rh_carousel', array( __CLASS__, 'carousel' ) );
		add_shortcode( 'rh_finder', array( __CLASS__, 'finder' ) );
		add_shortcode( 'rh_compare', array( __CLASS__, 'compare' ) );
	}

	/**
	 * Statistics band. Values are derived from published content, so they can
	 * never render as a placeholder zero the way a hardcoded counter can.
	 *
	 * @return string
	 */
	public static function stats() {
		Review_Hub_Assets::enqueue();

		$stats = Review_Hub_Query::stats();

		$tiles = array(
			array(
				'value'  => $stats['products'],
				'label'  => __( 'Products reviewed', 'review-hub' ),
				'suffix' => '',
			),
			array(
				'value'  => $stats['brands'],
				'label'  => __( 'Brands assessed', 'review-hub' ),
				'suffix' => '',
			),
			array(
				'value'  => $stats['lab_tested'],
				'label'  => __( 'With lab reports', 'review-hub' ),
				'suffix' => '',
			),
			array(
				'value'  => $stats['avg_rating'],
				'label'  => __( 'Average score', 'review-hub' ),
				'suffix' => '/5',
			),
		);

		ob_start();
		?>
		<div class="rh-stats" data-rh-stats>
			<?php foreach ( $tiles as $tile ) : ?>
				<div class="rh-stat">
					<span class="rh-stat__value"
						data-rh-count="<?php echo esc_attr( (string) $tile['value'] ); ?>"
						data-rh-suffix="<?php echo esc_attr( $tile['suffix'] ); ?>">
						<?php echo esc_html( $tile['value'] . $tile['suffix'] ); ?>
					</span>
					<span class="rh-stat__label"><?php echo esc_html( $tile['label'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Category chips that drive the grid below them.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function categories( $atts ) {
		Review_Hub_Assets::enqueue();

		$atts = shortcode_atts(
			array( 'target' => 'rh-main-grid' ),
			$atts,
			'rh_categories'
		);

		$terms = Review_Hub_Content_Types::get_category_terms();

		if ( empty( $terms ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="rh-categories" data-rh-categories data-target="<?php echo esc_attr( $atts['target'] ); ?>">
			<?php foreach ( $terms as $term ) : ?>
				<button type="button"
					class="rh-category"
					data-slug="<?php echo esc_attr( $term->slug ); ?>"
					aria-pressed="false">
					<span class="rh-category__icon" aria-hidden="true">
						<?php echo self::category_icon( $term->slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="rh-category__name"><?php echo esc_html( $term->name ); ?></span>
					<span class="rh-category__count"><?php echo esc_html( (string) $term->count ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Faceted, filterable listing.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function grid( $atts ) {
		Review_Hub_Assets::enqueue();

		$atts = shortcode_atts(
			array(
				'id'        => 'rh-main-grid',
				'post_type' => Review_Hub_Content_Types::POST_TYPE_PRODUCT,
				'per_page'  => 12,
				'facets'    => 'category,concern,region,rating,lab',
			),
			$atts,
			'rh_grid'
		);

		$facets  = array_map( 'trim', explode( ',', (string) $atts['facets'] ) );
		$filters = Review_Hub_Query::parse_filters(
			array(
				'post_type' => $atts['post_type'],
				'per_page'  => $atts['per_page'],
			)
		);
		$query   = Review_Hub_Query::run( $filters );

		ob_start();
		?>
		<div class="rh-grid-wrap"
			id="<?php echo esc_attr( $atts['id'] ); ?>"
			data-rh-grid
			data-post-type="<?php echo esc_attr( $atts['post_type'] ); ?>"
			data-per-page="<?php echo esc_attr( (string) (int) $atts['per_page'] ); ?>">

			<div class="rh-toolbar">
				<div class="rh-toolbar__facets">
					<?php if ( in_array( 'concern', $facets, true ) ) : ?>
						<?php self::facet_select( Review_Hub_Content_Types::TAX_CONCERN, __( 'Any goal', 'review-hub' ), 'concern' ); ?>
					<?php endif; ?>

					<?php if ( in_array( 'region', $facets, true ) ) : ?>
						<?php self::facet_select( Review_Hub_Content_Types::TAX_REGION, __( 'Any region', 'review-hub' ), 'region' ); ?>
					<?php endif; ?>

					<?php if ( in_array( 'rating', $facets, true ) ) : ?>
						<label class="rh-field">
							<span class="rh-field__label"><?php esc_html_e( 'Minimum score', 'review-hub' ); ?></span>
							<select data-rh-filter="min_rating">
								<option value="0"><?php esc_html_e( 'Any score', 'review-hub' ); ?></option>
								<option value="3">3.0+</option>
								<option value="4">4.0+</option>
								<option value="4.5">4.5+</option>
							</select>
						</label>
					<?php endif; ?>

					<label class="rh-field">
						<span class="rh-field__label"><?php esc_html_e( 'Sort by', 'review-hub' ); ?></span>
						<select data-rh-filter="orderby">
							<option value="rating"><?php esc_html_e( 'Highest score', 'review-hub' ); ?></option>
							<option value="price_asc"><?php esc_html_e( 'Price: low to high', 'review-hub' ); ?></option>
							<option value="price_desc"><?php esc_html_e( 'Price: high to low', 'review-hub' ); ?></option>
							<option value="recent"><?php esc_html_e( 'Recently reviewed', 'review-hub' ); ?></option>
						</select>
					</label>

					<?php if ( in_array( 'lab', $facets, true ) ) : ?>
						<label class="rh-check">
							<input type="checkbox" data-rh-filter="lab_tested" value="1" />
							<span><?php esc_html_e( 'Lab tested only', 'review-hub' ); ?></span>
						</label>
					<?php endif; ?>
				</div>

				<div class="rh-toolbar__meta">
					<span data-rh-count-label>
						<?php
						printf(
							/* translators: %d: number of results. */
							esc_html( _n( '%d result', '%d results', (int) $query->found_posts, 'review-hub' ) ),
							(int) $query->found_posts
						);
						?>
					</span>
					<button type="button" class="rh-clear" data-rh-clear hidden>
						<?php esc_html_e( 'Clear filters', 'review-hub' ); ?>
					</button>
				</div>
			</div>

			<div class="rh-grid" data-rh-results aria-live="polite" aria-busy="false">
				<?php foreach ( $query->posts as $post ) : ?>
					<?php echo self::render_card( Review_Hub_Query::card( $post->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>

			<div class="rh-grid__empty" data-rh-empty hidden>
				<p><?php esc_html_e( 'No products match those filters yet.', 'review-hub' ); ?></p>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Swiper carousel of reviews.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function carousel( $atts ) {
		Review_Hub_Assets::enqueue();

		$atts = shortcode_atts(
			array(
				'title'     => '',
				'category'  => '',
				'orderby'   => 'rating',
				'per_page'  => 10,
				'autoplay'  => 'false',
				'post_type' => Review_Hub_Content_Types::POST_TYPE_PRODUCT,
			),
			$atts,
			'rh_carousel'
		);

		$query = Review_Hub_Query::run(
			Review_Hub_Query::parse_filters(
				array(
					'post_type' => $atts['post_type'],
					'category'  => $atts['category'],
					'orderby'   => $atts['orderby'],
					'per_page'  => $atts['per_page'],
				)
			)
		);

		if ( ! $query->have_posts() ) {
			return '';
		}

		$uid = wp_unique_id( 'rh-carousel-' );

		ob_start();
		?>
		<section class="rh-carousel" data-rh-carousel data-autoplay="<?php echo esc_attr( $atts['autoplay'] ); ?>">
			<?php if ( '' !== $atts['title'] ) : ?>
				<header class="rh-carousel__head">
					<h2 class="rh-carousel__title"><?php echo esc_html( $atts['title'] ); ?></h2>
					<div class="rh-carousel__nav">
						<button type="button" class="rh-nav-btn" data-rh-prev aria-label="<?php esc_attr_e( 'Previous', 'review-hub' ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
						<button type="button" class="rh-nav-btn" data-rh-next aria-label="<?php esc_attr_e( 'Next', 'review-hub' ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
					</div>
				</header>
			<?php endif; ?>

			<div class="swiper" id="<?php echo esc_attr( $uid ); ?>">
				<div class="swiper-wrapper">
					<?php foreach ( $query->posts as $post ) : ?>
						<div class="swiper-slide">
							<?php echo self::render_card( Review_Hub_Query::card( $post->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="swiper-pagination"></div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Guided product finder.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function finder( $atts ) {
		Review_Hub_Assets::enqueue();

		$atts = shortcode_atts(
			array(
				'mode'  => 'inline',
				'label' => __( 'Find your product', 'review-hub' ),
			),
			$atts,
			'rh_finder'
		);

		$steps = array(
			array(
				'key'      => 'category',
				'question' => __( 'What format are you after?', 'review-hub' ),
				'taxonomy' => Review_Hub_Content_Types::TAX_CATEGORY,
			),
			array(
				'key'      => 'concern',
				'question' => __( 'What are you hoping it helps with?', 'review-hub' ),
				'taxonomy' => Review_Hub_Content_Types::TAX_CONCERN,
			),
			array(
				'key'      => 'region',
				'question' => __( 'Where should it ship to?', 'review-hub' ),
				'taxonomy' => Review_Hub_Content_Types::TAX_REGION,
			),
		);

		ob_start();
		?>
		<div class="rh-finder <?php echo 'modal' === $atts['mode'] ? 'rh-finder--modal' : ''; ?>"
			data-rh-finder
			data-mode="<?php echo esc_attr( $atts['mode'] ); ?>">

			<?php if ( 'modal' === $atts['mode'] ) : ?>
				<button type="button" class="rh-finder__trigger" data-rh-finder-open>
					<?php echo esc_html( $atts['label'] ); ?>
				</button>
			<?php endif; ?>

			<div class="rh-finder__panel" data-rh-finder-panel <?php echo 'modal' === $atts['mode'] ? 'hidden' : ''; ?>>
				<?php if ( 'modal' === $atts['mode'] ) : ?>
					<button type="button" class="rh-finder__close" data-rh-finder-close aria-label="<?php esc_attr_e( 'Close', 'review-hub' ); ?>">&times;</button>
				<?php endif; ?>

				<div class="rh-finder__progress">
					<div class="rh-finder__bar" data-rh-finder-bar style="width:33%"></div>
				</div>

				<?php foreach ( $steps as $index => $step ) : ?>
					<?php
					$terms = get_terms(
						array(
							'taxonomy'   => $step['taxonomy'],
							'hide_empty' => false,
						)
					);

					if ( is_wp_error( $terms ) ) {
						continue;
					}
					?>
					<fieldset class="rh-finder__step"
						data-rh-step="<?php echo esc_attr( (string) $index ); ?>"
						data-key="<?php echo esc_attr( $step['key'] ); ?>"
						<?php echo 0 === $index ? '' : 'hidden'; ?>>

						<legend class="rh-finder__question"><?php echo esc_html( $step['question'] ); ?></legend>

						<div class="rh-finder__options">
							<?php foreach ( $terms as $term ) : ?>
								<button type="button"
									class="rh-finder__option"
									data-value="<?php echo esc_attr( $term->slug ); ?>">
									<span class="rh-finder__option-icon" aria-hidden="true">
										<?php echo self::category_icon( $term->slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</span>
									<span><?php echo esc_html( $term->name ); ?></span>
								</button>
							<?php endforeach; ?>

							<button type="button" class="rh-finder__option rh-finder__option--skip" data-value="">
								<span><?php esc_html_e( 'No preference', 'review-hub' ); ?></span>
							</button>
						</div>

						<?php if ( $index > 0 ) : ?>
							<button type="button" class="rh-finder__back" data-rh-finder-back>
								<?php esc_html_e( 'Back', 'review-hub' ); ?>
							</button>
						<?php endif; ?>
					</fieldset>
				<?php endforeach; ?>

				<div class="rh-finder__results" data-rh-finder-results hidden>
					<h3 class="rh-finder__results-title"><?php esc_html_e( 'Your matches', 'review-hub' ); ?></h3>
					<div class="rh-finder__list" data-rh-finder-list></div>
					<button type="button" class="rh-finder__restart" data-rh-finder-restart>
						<?php esc_html_e( 'Start over', 'review-hub' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Brand comparison picker and table.
	 *
	 * @return string
	 */
	public static function compare() {
		Review_Hub_Assets::enqueue();

		$brands = get_posts(
			array(
				'post_type'      => Review_Hub_Content_Types::POST_TYPE_BRAND,
				'posts_per_page' => 50,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $brands ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="rh-compare" data-rh-compare>
			<p class="rh-compare__hint"><?php esc_html_e( 'Pick up to three brands to compare side by side.', 'review-hub' ); ?></p>

			<div class="rh-compare__picker">
				<?php foreach ( $brands as $brand ) : ?>
					<label class="rh-compare__choice">
						<input type="checkbox" data-rh-compare-id value="<?php echo esc_attr( (string) $brand->ID ); ?>" />
						<span><?php echo esc_html( $brand->post_title ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>

			<div class="rh-compare__table" data-rh-compare-table></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render a taxonomy dropdown facet.
	 *
	 * @param string $taxonomy    Taxonomy key.
	 * @param string $placeholder Empty-option label.
	 * @param string $filter_key  Filter name sent to REST.
	 */
	private static function facet_select( $taxonomy, $placeholder, $filter_key ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		$labels = array(
			'concern' => __( 'Goal', 'review-hub' ),
			'region'  => __( 'Ships to', 'review-hub' ),
		);
		?>
		<label class="rh-field">
			<span class="rh-field__label">
				<?php echo esc_html( isset( $labels[ $filter_key ] ) ? $labels[ $filter_key ] : $filter_key ); ?>
			</span>
			<select data-rh-filter="<?php echo esc_attr( $filter_key ); ?>">
				<option value=""><?php echo esc_html( $placeholder ); ?></option>
				<?php foreach ( $terms as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	/**
	 * Render one card. Mirrors the markup produced by the JS renderer so that
	 * server-rendered and filtered results are visually identical.
	 *
	 * @param array $card Card payload.
	 * @return string
	 */
	public static function render_card( array $card ) {
		$discounted = $card['price_was'] > 0 && $card['price_was'] > $card['price'];

		ob_start();
		?>
		<?php /* No scroll-reveal on cards: product content must never depend on a scroll handler firing. */ ?>
		<article class="rh-card" data-rh-card data-id="<?php echo esc_attr( (string) $card['id'] ); ?>">
			<a class="rh-card__media" href="<?php echo esc_url( $card['permalink'] ); ?>">
				<?php if ( $card['image'] ) : ?>
					<img src="<?php echo esc_url( $card['image'] ); ?>"
						alt="<?php echo esc_attr( $card['title'] ); ?>"
						loading="lazy" decoding="async" width="480" height="360" />
				<?php else : ?>
					<span class="rh-card__placeholder" aria-hidden="true"></span>
				<?php endif; ?>

				<?php if ( $card['rating'] > 0 ) : ?>
					<span class="rh-card__score" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: rating out of five. */ __( 'Rated %s out of 5', 'review-hub' ), $card['rating'] ) ); ?>">
						<?php echo esc_html( number_format_i18n( $card['rating'], 1 ) ); ?>
					</span>
				<?php endif; ?>
			</a>

			<button type="button" class="rh-card__quick" data-rh-quickview="<?php echo esc_attr( (string) $card['id'] ); ?>">
				<?php esc_html_e( 'Quick view', 'review-hub' ); ?>
			</button>

			<div class="rh-card__body">
				<?php if ( '' !== $card['brand'] ) : ?>
					<span class="rh-card__brand"><?php echo esc_html( $card['brand'] ); ?></span>
				<?php endif; ?>

				<h3 class="rh-card__title">
					<a href="<?php echo esc_url( $card['permalink'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a>
				</h3>

				<?php if ( $card['cbd_mg'] > 0 || $card['lab_tested'] ) : ?>
					<dl class="rh-card__spec">
						<?php if ( $card['cbd_mg'] > 0 ) : ?>
							<div>
								<dt>CBD</dt>
								<dd><?php echo esc_html( number_format_i18n( $card['cbd_mg'] ) ); ?> mg</dd>
							</div>
						<?php endif; ?>
						<?php if ( $card['thc_pct'] > 0 ) : ?>
							<div>
								<dt>THC</dt>
								<dd><?php echo esc_html( number_format_i18n( $card['thc_pct'], 1 ) ); ?>%</dd>
							</div>
						<?php endif; ?>
						<?php if ( $card['lab_tested'] ) : ?>
							<div class="rh-card__verified">
								<dt><?php esc_html_e( 'Lab', 'review-hub' ); ?></dt>
								<dd><?php esc_html_e( 'Verified', 'review-hub' ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>
				<?php endif; ?>

				<?php if ( ! empty( $card['regions'] ) ) : ?>
					<span class="rh-card__region"><?php echo esc_html( implode( ' · ', $card['regions'] ) ); ?></span>
				<?php endif; ?>

				<div class="rh-card__foot">
					<?php if ( $card['price'] > 0 ) : ?>
						<span class="rh-card__price">
							<?php if ( $discounted ) : ?>
								<s><?php echo esc_html( $card['currency'] . number_format_i18n( $card['price_was'], 2 ) ); ?></s>
							<?php endif; ?>
							<strong><?php echo esc_html( $card['currency'] . number_format_i18n( $card['price'], 2 ) ); ?></strong>
						</span>
					<?php endif; ?>

					<?php if ( $card['has_offer'] ) : ?>
						<a class="rh-card__cta"
							href="<?php echo esc_url( $card['offer_url'] ); ?>"
							rel="nofollow sponsored noopener"
							target="_blank">
							<?php esc_html_e( 'View offer', 'review-hub' ); ?>
						</a>
					<?php else : ?>
						<a class="rh-card__cta rh-card__cta--ghost" href="<?php echo esc_url( $card['permalink'] ); ?>">
							<?php esc_html_e( 'Read review', 'review-hub' ); ?>
						</a>
					<?php endif; ?>
				</div>

				<?php if ( '' !== $card['discount_code'] ) : ?>
					<button type="button" class="rh-card__code" data-rh-copy="<?php echo esc_attr( $card['discount_code'] ); ?>">
						<?php esc_html_e( 'Code', 'review-hub' ); ?>
						<code><?php echo esc_html( $card['discount_code'] ); ?></code>
					</button>
				<?php endif; ?>
			</div>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Inline SVG icon for a taxonomy term, chosen by slug keyword.
	 *
	 * SVG is used rather than emoji so the glyphs stay legible and themeable.
	 *
	 * @param string $slug Term slug.
	 * @return string
	 */
	private static function category_icon( $slug ) {
		$paths = array(
			'flower' => '<path d="M12 3a3 3 0 013 3c0 1-.4 1.8-1 2.4A3 3 0 0121 11a3 3 0 01-3 3c-.6 0-1.2-.2-1.7-.5.4.6.7 1.3.7 2a3 3 0 01-6 0c0-.7.3-1.4.7-2-.5.3-1.1.5-1.7.5a3 3 0 01-3-3 3 3 0 013-3h.2A3.3 3.3 0 019 6a3 3 0 013-3z"/>',
			'oil'    => '<path d="M12 2s5 6.4 5 10a5 5 0 01-10 0c0-3.6 5-10 5-10z"/>',
			'vape'   => '<path d="M4 14h11a3 3 0 100-6h-1M4 14v3a1 1 0 001 1h9a1 1 0 001-1v-3M6 11V6"/>',
			'gummy'  => '<path d="M7 10a5 5 0 0110 0v6a3 3 0 01-3 3h-4a3 3 0 01-3-3v-6z"/>',
			'capsul' => '<rect x="3" y="9" width="18" height="6" rx="3"/><path d="M12 9v6"/>',
			'cream'  => '<path d="M8 8h8v12H8z"/><path d="M10 8V5h4v3"/>',
			'pet'    => '<circle cx="8" cy="9" r="2"/><circle cx="16" cy="9" r="2"/><path d="M12 13c-3 0-5 2-5 4a2 2 0 002 2h6a2 2 0 002-2c0-2-2-4-5-4z"/>',
			'drink'  => '<path d="M6 4h12l-1.5 16h-9L6 4z"/><path d="M7 10h10"/>',
			'sleep'  => '<path d="M20 14a8 8 0 11-9-9 6.5 6.5 0 009 9z"/>',
			'pain'   => '<path d="M20 9a5 5 0 00-8-3 5 5 0 00-8 3c0 6 8 10 8 10s8-4 8-10z"/>',
			'energy' => '<path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z"/>',
			'calm'   => '<path d="M4 12h4l2-5 3 10 2-5h5"/>',
		);

		$match = '<circle cx="12" cy="12" r="8"/>';

		foreach ( $paths as $needle => $path ) {
			if ( false !== strpos( $slug, $needle ) ) {
				$match = $path;
				break;
			}
		}

		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $match . '</svg>';
	}
}
