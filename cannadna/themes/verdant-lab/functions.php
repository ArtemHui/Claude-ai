<?php
/**
 * Verdant Lab theme setup.
 *
 * @package VerdantLab
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load the parent stylesheet before the child's.
 */
function verdant_lab_styles() {
	wp_enqueue_style(
		'verdant-lab',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'verdant_lab_styles', 20 );

/**
 * Age verification gate.
 *
 * Age gating is a compliance requirement for this vertical, so the markup is
 * always printed and the gate is dismissed client-side. Consent is stored in
 * localStorage; no personal data is collected or transmitted.
 */
function verdant_lab_age_gate() {
	if ( is_admin() ) {
		return;
	}

	$min_age = (int) apply_filters( 'verdant_lab_minimum_age', 18 );
	?>
	<div class="vl-gate" id="vl-gate" role="dialog" aria-modal="true"
		aria-labelledby="vl-gate-title" aria-describedby="vl-gate-copy" hidden>
		<div class="vl-gate__panel">
			<h2 id="vl-gate-title">
				<?php
				printf(
					/* translators: %d: minimum age. */
					esc_html__( 'Are you %d or over?', 'verdant-lab' ),
					$min_age
				);
				?>
			</h2>
			<p id="vl-gate-copy">
				<?php esc_html_e( 'This site reviews hemp and CBD products. You must confirm your age to continue. Nothing is sold on this site.', 'verdant-lab' ); ?>
			</p>
			<div class="vl-gate__actions">
				<button type="button" class="vl-gate__btn vl-gate__btn--primary" id="vl-gate-yes">
					<?php
					printf(
						/* translators: %d: minimum age. */
						esc_html__( 'Yes, I am %d or over', 'verdant-lab' ),
						$min_age
					);
					?>
				</button>
				<button type="button" class="vl-gate__btn" id="vl-gate-no">
					<?php esc_html_e( 'No', 'verdant-lab' ); ?>
				</button>
			</div>
		</div>
	</div>
	<script>
	( function () {
		var KEY = 'vl-age-ok';
		var gate = document.getElementById( 'vl-gate' );

		if ( ! gate ) {
			return;
		}

		var confirmed = false;

		try {
			confirmed = window.localStorage.getItem( KEY ) === '1';
		} catch ( e ) {
			// Storage unavailable (private mode): fall through and show the gate.
		}

		if ( confirmed ) {
			gate.remove();
			return;
		}

		gate.hidden = false;
		document.documentElement.style.overflow = 'hidden';

		var yes = document.getElementById( 'vl-gate-yes' );
		var no = document.getElementById( 'vl-gate-no' );

		yes.focus();

		yes.addEventListener( 'click', function () {
			try {
				window.localStorage.setItem( KEY, '1' );
			} catch ( e ) {}

			document.documentElement.style.overflow = '';
			gate.remove();
		} );

		no.addEventListener( 'click', function () {
			window.location.href = 'https://www.google.com';
		} );

		// Keep focus inside the dialog while it is open.
		gate.addEventListener( 'keydown', function ( event ) {
			if ( event.key !== 'Tab' ) {
				return;
			}

			var focusable = [ yes, no ];
			var index = focusable.indexOf( document.activeElement );

			event.preventDefault();
			focusable[ ( index + ( event.shiftKey ? -1 : 1 ) + focusable.length ) % focusable.length ].focus();
		} );
	} )();
	</script>
	<?php
}
add_action( 'wp_footer', 'verdant_lab_age_gate' );

/**
 * Affiliate disclosure required for this kind of monetisation.
 *
 * @param string $content Post content.
 * @return string
 */
function verdant_lab_affiliate_disclosure( $content ) {
	if ( is_admin() || ! is_singular( array( 'rh_product', 'rh_brand' ) ) ) {
		return $content;
	}

	$notice = '<p class="vl-disclosure"><small>'
		. esc_html__( 'We may earn a commission if you buy through links on this page. This never affects our scoring.', 'verdant-lab' )
		. '</small></p>';

	return $notice . $content;
}
add_filter( 'the_content', 'verdant_lab_affiliate_disclosure' );
