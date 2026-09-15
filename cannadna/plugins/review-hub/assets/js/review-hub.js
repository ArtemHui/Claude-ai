/*
 * Review Hub frontend.
 *
 * Each component is initialised independently so a page can use any subset of
 * the shortcodes. All listings render server-side first; JS only replaces
 * markup once a filter actually changes.
 */
( function () {
	'use strict';

	var data = window.ReviewHubData || {};
	var i18n = data.i18n || {};
	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function esc( value ) {
		return String( value == null ? '' : value ).replace( /[&<>"']/g, function ( ch ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ ch ];
		} );
	}

	function money( value, currency ) {
		return currency + Number( value ).toFixed( 2 );
	}

	function request( path, params ) {
		var url = new URL( data.restUrl + path );

		Object.keys( params || {} ).forEach( function ( key ) {
			var value = params[ key ];

			if ( value === '' || value === null || value === undefined || value === false ) {
				return;
			}

			url.searchParams.set( key, Array.isArray( value ) ? value.join( ',' ) : value );
		} );

		return fetch( url.toString(), {
			headers: { 'X-WP-Nonce': data.nonce || '' },
			credentials: 'same-origin'
		} ).then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( 'Request failed: ' + response.status );
			}

			return response.json();
		} );
	}

	/**
	 * Card markup. Mirrors Review_Hub_Shortcodes::render_card so server-rendered
	 * and filtered cards are indistinguishable.
	 */
	function cardHtml( card ) {
		var discounted = card.price_was > 0 && card.price_was > card.price;
		var html = '';

		html += '<article class="rh-card" data-rh-card data-id="' + esc( card.id ) + '">';

		html += '<a class="rh-card__media" href="' + esc( card.permalink ) + '">';
		html += card.image
			? '<img src="' + esc( card.image ) + '" alt="' + esc( card.title ) + '" loading="lazy" decoding="async" width="480" height="360" />'
			: '<span class="rh-card__placeholder" aria-hidden="true"></span>';

		if ( card.rating > 0 ) {
			html += '<span class="rh-card__score">' + esc( card.rating.toFixed( 1 ) ) + '</span>';
		}

		html += '</a>';

		html += '<div class="rh-card__body">';

		if ( card.brand ) {
			html += '<span class="rh-card__brand">' + esc( card.brand ) + '</span>';
		}

		html += '<h3 class="rh-card__title"><a href="' + esc( card.permalink ) + '">' + esc( card.title ) + '</a></h3>';

		if ( card.cbd_mg > 0 || card.lab_tested ) {
			html += '<dl class="rh-card__spec">';

			if ( card.cbd_mg > 0 ) {
				html += '<div><dt>CBD</dt><dd>' + esc( card.cbd_mg ) + ' mg</dd></div>';
			}

			if ( card.thc_pct > 0 ) {
				html += '<div><dt>THC</dt><dd>' + esc( card.thc_pct ) + '%</dd></div>';
			}

			if ( card.lab_tested ) {
				html += '<div class="rh-card__verified"><dt>' + esc( i18n.labLabel || 'Lab' ) + '</dt>'
					+ '<dd>' + esc( i18n.verified || 'Verified' ) + '</dd></div>';
			}

			html += '</dl>';
		}

		if ( card.regions && card.regions.length ) {
			html += '<span class="rh-card__region">' + esc( card.regions.join( ' · ' ) ) + '</span>';
		}

		html += '<div class="rh-card__foot">';

		if ( card.price > 0 ) {
			html += '<span class="rh-card__price">';
			if ( discounted ) {
				html += '<s>' + esc( money( card.price_was, card.currency ) ) + '</s>';
			}
			html += '<strong>' + esc( money( card.price, card.currency ) ) + '</strong></span>';
		}

		if ( card.has_offer ) {
			html += '<a class="rh-card__cta" href="' + esc( card.offer_url ) + '" rel="nofollow sponsored noopener" target="_blank">'
				+ esc( i18n.viewOffer || 'View offer' ) + '</a>';
		} else {
			html += '<a class="rh-card__cta rh-card__cta--ghost" href="' + esc( card.permalink ) + '">'
				+ esc( i18n.readReview || 'Read review' ) + '</a>';
		}

		html += '</div>';

		if ( card.discount_code ) {
			html += '<button type="button" class="rh-card__code" data-rh-copy="' + esc( card.discount_code ) + '">'
				+ 'Code <code>' + esc( card.discount_code ) + '</code></button>';
		}

		html += '</div></article>';

		return html;
	}

	/* ------------------------------------------------------------- grid -- */

	function initGrid( root ) {
		var results = root.querySelector( '[data-rh-results]' );
		var empty = root.querySelector( '[data-rh-empty]' );
		var countLabel = root.querySelector( '[data-rh-count-label]' );
		var clearBtn = root.querySelector( '[data-rh-clear]' );
		var controls = root.querySelectorAll( '[data-rh-filter]' );

		var state = {
			post_type: root.dataset.postType,
			per_page: root.dataset.perPage,
			category: []
		};

		function isFiltered() {
			return Boolean(
				state.category.length ||
				state.concern ||
				state.region ||
				( state.min_rating && Number( state.min_rating ) > 0 ) ||
				state.lab_tested
			);
		}

		function render( payload ) {
			results.setAttribute( 'aria-busy', 'false' );

			if ( ! payload.items.length ) {
				results.innerHTML = '';
				empty.hidden = false;
			} else {
				empty.hidden = true;
				results.innerHTML = payload.items.map( cardHtml ).join( '' );
			}

			if ( countLabel ) {
				countLabel.textContent = payload.total === 1 ? '1 result' : payload.total + ' results';
			}

			if ( clearBtn ) {
				clearBtn.hidden = ! isFiltered();
			}
		}

		function refresh() {
			results.setAttribute( 'aria-busy', 'true' );

			request( '/items', state )
				.then( render )
				.catch( function () {
					results.setAttribute( 'aria-busy', 'false' );
				} );
		}

		controls.forEach( function ( control ) {
			control.addEventListener( 'change', function () {
				var key = control.dataset.rhFilter;

				state[ key ] = control.type === 'checkbox'
					? ( control.checked ? 1 : '' )
					: control.value;

				refresh();
			} );
		} );

		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', function () {
				controls.forEach( function ( control ) {
					if ( control.type === 'checkbox' ) {
						control.checked = false;
					} else {
						control.selectedIndex = 0;
					}
				} );

				state = {
					post_type: root.dataset.postType,
					per_page: root.dataset.perPage,
					category: []
				};

				root.querySelectorAll( '[aria-pressed="true"]' ).forEach( function ( chip ) {
					chip.setAttribute( 'aria-pressed', 'false' );
				} );

				document.querySelectorAll( '[data-rh-categories][data-target="' + root.id + '"] [aria-pressed="true"]' )
					.forEach( function ( chip ) {
						chip.setAttribute( 'aria-pressed', 'false' );
					} );

				refresh();
			} );
		}

		// Category chips elsewhere on the page drive this grid.
		root.rhSetCategories = function ( slugs ) {
			state.category = slugs;
			refresh();
		};
	}

	function initCategories( group ) {
		var targetId = group.dataset.target;

		group.querySelectorAll( '.rh-category' ).forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				var pressed = chip.getAttribute( 'aria-pressed' ) === 'true';
				chip.setAttribute( 'aria-pressed', pressed ? 'false' : 'true' );

				var active = Array.prototype.map.call(
					group.querySelectorAll( '[aria-pressed="true"]' ),
					function ( el ) {
						return el.dataset.slug;
					}
				);

				var target = document.getElementById( targetId );

				if ( target && typeof target.rhSetCategories === 'function' ) {
					target.rhSetCategories( active );
				}
			} );
		} );
	}

	/* --------------------------------------------------------- carousel -- */

	function initCarousel( section ) {
		var container = section.querySelector( '.swiper' );

		if ( ! container || typeof window.Swiper === 'undefined' ) {
			return;
		}

		var prev = section.querySelector( '[data-rh-prev]' );
		var next = section.querySelector( '[data-rh-next]' );

		var options = {
			slidesPerView: 1.15,
			spaceBetween: 14,
			grabCursor: true,
			watchOverflow: true,
			a11y: { enabled: true },
			pagination: {
				el: section.querySelector( '.swiper-pagination' ),
				clickable: true
			},
			breakpoints: {
				560: { slidesPerView: 2.2, spaceBetween: 16 },
				820: { slidesPerView: 3, spaceBetween: 18 },
				1180: { slidesPerView: 4, spaceBetween: 20 }
			}
		};

		if ( prev && next ) {
			options.navigation = { prevEl: prev, nextEl: next };
		}

		// Autoplay is opt-in and never runs against a reduced-motion preference.
		if ( section.dataset.autoplay === 'true' && ! reduceMotion ) {
			options.autoplay = { delay: 4500, pauseOnMouseEnter: true, disableOnInteraction: true };
		}

		new window.Swiper( container, options );
	}

	/* ----------------------------------------------------------- finder -- */

	function initFinder( root ) {
		var panel = root.querySelector( '[data-rh-finder-panel]' );
		var steps = Array.prototype.slice.call( root.querySelectorAll( '[data-rh-step]' ) );
		var resultsBox = root.querySelector( '[data-rh-finder-results]' );
		var list = root.querySelector( '[data-rh-finder-list]' );
		var bar = root.querySelector( '[data-rh-finder-bar]' );
		var openBtn = root.querySelector( '[data-rh-finder-open]' );
		var closeBtn = root.querySelector( '[data-rh-finder-close]' );
		var restartBtn = root.querySelector( '[data-rh-finder-restart]' );
		var isModal = root.dataset.mode === 'modal';

		var answers = {};
		var index = 0;
		var backdrop = null;

		function show( next ) {
			index = next;

			steps.forEach( function ( step, i ) {
				step.hidden = i !== index;
			} );

			resultsBox.hidden = index < steps.length;

			if ( bar ) {
				bar.style.width = Math.round( ( ( index + 1 ) / ( steps.length + 1 ) ) * 100 ) + '%';
			}

			var visible = index < steps.length ? steps[ index ] : resultsBox;
			var focusable = visible.querySelector( 'button' );

			if ( focusable ) {
				focusable.focus();
			}
		}

		function finish() {
			list.innerHTML = '<p>' + esc( i18n.loading || 'Loading…' ) + '</p>';
			show( steps.length );

			request( '/finder', {
				category: answers.category || '',
				concern: answers.concern || '',
				region: answers.region || '',
				limit: 4
			} ).then( function ( payload ) {
				if ( ! payload.items.length ) {
					list.innerHTML = '<p>' + esc( i18n.noResults || 'No matches yet.' ) + '</p>';
					return;
				}

				list.innerHTML = payload.items.map( function ( card ) {
					var note = '';

					if ( card.match_of > 0 ) {
						note = '<p class="rh-finder__match">' + esc( card.match_score ) + '/' + esc( card.match_of ) + ' criteria matched</p>';
					}

					return '<div>' + cardHtml( card ) + note + '</div>';
				} ).join( '' );
			} ).catch( function () {
				list.innerHTML = '<p>' + esc( i18n.noResults || 'Something went wrong.' ) + '</p>';
			} );
		}

		steps.forEach( function ( step ) {
			step.querySelectorAll( '.rh-finder__option' ).forEach( function ( option ) {
				option.addEventListener( 'click', function () {
					answers[ step.dataset.key ] = option.dataset.value;

					if ( index + 1 < steps.length ) {
						show( index + 1 );
					} else {
						finish();
					}
				} );
			} );

			var back = step.querySelector( '[data-rh-finder-back]' );

			if ( back ) {
				back.addEventListener( 'click', function () {
					show( Math.max( 0, index - 1 ) );
				} );
			}
		} );

		if ( restartBtn ) {
			restartBtn.addEventListener( 'click', function () {
				answers = {};
				show( 0 );
			} );
		}

		function openModal() {
			backdrop = document.createElement( 'div' );
			backdrop.className = 'rh-backdrop';
			backdrop.addEventListener( 'click', closeModal );
			document.body.appendChild( backdrop );

			panel.hidden = false;
			panel.setAttribute( 'role', 'dialog' );
			panel.setAttribute( 'aria-modal', 'true' );
			document.addEventListener( 'keydown', onKey );
			show( index );
		}

		function closeModal() {
			panel.hidden = true;

			if ( backdrop ) {
				backdrop.remove();
				backdrop = null;
			}

			document.removeEventListener( 'keydown', onKey );

			if ( openBtn ) {
				openBtn.focus();
			}
		}

		function onKey( event ) {
			if ( event.key === 'Escape' ) {
				closeModal();
			}
		}

		if ( isModal && openBtn ) {
			openBtn.addEventListener( 'click', openModal );
		}

		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', closeModal );
		}
	}

	/* ---------------------------------------------------------- compare -- */

	function initCompare( root ) {
		var boxes = Array.prototype.slice.call( root.querySelectorAll( '[data-rh-compare-id]' ) );
		var table = root.querySelector( '[data-rh-compare-table]' );

		function selected() {
			return boxes.filter( function ( box ) {
				return box.checked;
			} ).map( function ( box ) {
				return box.value;
			} );
		}

		function render( items ) {
			if ( items.length < 2 ) {
				table.innerHTML = '';
				return;
			}

			var rows = [
				[ 'Score', function ( item ) { return item.rating ? item.rating.toFixed( 1 ) + ' / 5' : '—'; } ],
				[ 'Reviews', function ( item ) { return item.review_count || 0; } ],
				[ 'Ships to', function ( item ) { return item.ships_to || ( item.regions.join( ', ' ) || '—' ); } ],
				[ 'Discount code', function ( item ) { return item.discount_code || '—'; } ],
				[ 'Lab reports', function ( item ) { return item.lab_tested ? 'Published' : '—'; } ]
			];

			var html = '<table><thead><tr><th scope="col"></th>';

			items.forEach( function ( item ) {
				html += '<th scope="col">' + esc( item.title ) + '</th>';
			} );

			html += '</tr></thead><tbody>';

			rows.forEach( function ( row ) {
				html += '<tr><th scope="row">' + esc( row[ 0 ] ) + '</th>';

				items.forEach( function ( item ) {
					html += '<td>' + esc( row[ 1 ]( item ) ) + '</td>';
				} );

				html += '</tr>';
			} );

			html += '</tbody></table>';
			table.innerHTML = html;
		}

		boxes.forEach( function ( box ) {
			box.addEventListener( 'change', function () {
				var ids = selected();

				// Cap the comparison at three columns so the table stays readable.
				if ( ids.length > 3 ) {
					box.checked = false;
					return;
				}

				if ( ids.length < 2 ) {
					table.innerHTML = '';
					return;
				}

				request( '/compare', { ids: ids.join( ',' ) } )
					.then( function ( payload ) {
						render( payload.items );
					} )
					.catch( function () {
						table.innerHTML = '';
					} );
			} );
		} );
	}

	/* ------------------------------------------------------------ misc -- */

	function initCounters() {
		var tiles = document.querySelectorAll( '[data-rh-count]' );

		if ( ! tiles.length ) {
			return;
		}

		// Values are already correct in the markup; the count-up is decoration
		// layered on top, so it is skipped entirely under reduced motion.
		if ( reduceMotion || typeof IntersectionObserver === 'undefined' ) {
			return;
		}

		var observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) {
					return;
				}

				var el = entry.target;
				var target = parseFloat( el.dataset.rhCount );
				var suffix = el.dataset.rhSuffix || '';
				var decimals = ( el.dataset.rhCount.split( '.' )[ 1 ] || '' ).length;
				var start = performance.now();

				function tick( now ) {
					var progress = Math.min( 1, ( now - start ) / 900 );
					var eased = 1 - Math.pow( 1 - progress, 3 );

					el.textContent = ( target * eased ).toFixed( decimals ) + suffix;

					if ( progress < 1 ) {
						requestAnimationFrame( tick );
					} else {
						el.textContent = target.toFixed( decimals ) + suffix;
					}
				}

				requestAnimationFrame( tick );
				observer.unobserve( el );
			} );
		}, { threshold: 0.4 } );

		tiles.forEach( function ( tile ) {
			observer.observe( tile );
		} );
	}

	function initCopyCodes() {
		document.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-rh-copy]' );

			if ( ! button || ! navigator.clipboard ) {
				return;
			}

			navigator.clipboard.writeText( button.dataset.rhCopy ).then( function () {
				button.dataset.copied = '1';
				setTimeout( function () {
					delete button.dataset.copied;
				}, 1800 );
			} );
		} );
	}

	function boot() {
		document.querySelectorAll( '[data-rh-grid]' ).forEach( initGrid );
		document.querySelectorAll( '[data-rh-categories]' ).forEach( initCategories );
		document.querySelectorAll( '[data-rh-carousel]' ).forEach( initCarousel );
		document.querySelectorAll( '[data-rh-finder]' ).forEach( initFinder );
		document.querySelectorAll( '[data-rh-compare]' ).forEach( initCompare );

		initCounters();
		initCopyCodes();

		if ( typeof window.AOS !== 'undefined' && ! reduceMotion ) {
			window.AOS.init( { duration: 500, easing: 'ease-out', once: true, offset: 40 } );
		}

		if ( typeof window.GLightbox !== 'undefined' ) {
			window.GLightbox( { selector: '.rh-lightbox' } );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
