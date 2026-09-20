/*
 * Verdant Lab preview.
 *
 * Mirrors the Review Hub plugin's frontend behaviour, but reads from an
 * embedded catalogue instead of the WordPress REST API so the page runs as a
 * single static file.
 */
( function () {
	'use strict';

	var DATA = window.VERDANT_DATA;
	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function esc( value ) {
		return String( value == null ? '' : value ).replace( /[&<>"']/g, function ( ch ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ ch ];
		} );
	}

	function money( value, currency ) {
		return currency + Number( value ).toFixed( 2 );
	}

	function byId( id ) {
		return document.getElementById( id );
	}

	/* ------------------------------------------------------------ toast -- */

	var toastTimer = null;

	function toast( message ) {
		var el = byId( 'toast' );

		el.textContent = message;
		el.classList.add( 'is-visible' );

		clearTimeout( toastTimer );
		toastTimer = setTimeout( function () {
			el.classList.remove( 'is-visible' );
		}, 3400 );
	}

	/* ------------------------------------------------------------- card -- */

	function swatch( item ) {
		var slug = ( item.cat_slugs && item.cat_slugs[ 0 ] ) || 'oil';

		return '<span class="rh-card__placeholder" data-swatch="' + esc( slug ) + '" aria-hidden="true">'
			+ icon( slug ) + '</span>';
	}

	function cardHtml( item, extra ) {
		var discounted = item.price_was > 0 && item.price_was > item.price;
		var html = '';

		html += '<article class="rh-card" data-id="' + esc( item.id ) + '">';

		html += '<button type="button" class="rh-card__media" data-quick="' + esc( item.id ) + '" aria-label="'
			+ esc( 'View details for ' + item.title ) + '">';
		html += swatch( item );

		if ( item.rating > 0 ) {
			html += '<span class="rh-card__score">' + esc( item.rating.toFixed( 1 ) ) + '</span>';
		}

		html += '</button>';

		html += '<button type="button" class="rh-card__quick" data-quick="' + esc( item.id ) + '">Quick view</button>';

		html += '<div class="rh-card__body">';

		if ( item.brand ) {
			html += '<span class="rh-card__brand">' + esc( item.brand ) + '</span>';
		}

		html += '<h3 class="rh-card__title"><button type="button" data-quick="' + esc( item.id ) + '">'
			+ esc( item.title ) + '</button></h3>';

		if ( item.cbd_mg > 0 || item.lab_tested ) {
			html += '<dl class="rh-card__spec">';

			if ( item.cbd_mg > 0 ) {
				html += '<div><dt>CBD</dt><dd>' + esc( item.cbd_mg ) + ' mg</dd></div>';
			}

			if ( item.thc_pct > 0 ) {
				html += '<div><dt>THC</dt><dd>' + esc( item.thc_pct ) + '%</dd></div>';
			}

			if ( item.lab_tested ) {
				html += '<div class="rh-card__verified"><dt>Lab</dt><dd>Verified</dd></div>';
			}

			html += '</dl>';
		}

		if ( item.regions && item.regions.length ) {
			html += '<span class="rh-card__region">' + esc( item.regions.join( ' · ' ) ) + '</span>';
		}

		html += '<div class="rh-card__foot">';

		if ( item.price > 0 ) {
			html += '<span class="rh-card__price">';
			if ( discounted ) {
				html += '<s>' + esc( money( item.price_was, item.currency ) ) + '</s>';
			}
			html += '<strong>' + esc( money( item.price, item.currency ) ) + '</strong></span>';
		}

		html += '<button type="button" class="rh-card__cta" data-offer="' + esc( item.title ) + '">View offer</button>';
		html += '</div>';

		if ( item.discount_code ) {
			html += '<button type="button" class="rh-card__code" data-copy="' + esc( item.discount_code ) + '">'
				+ 'Code <code>' + esc( item.discount_code ) + '</code></button>';
		}

		html += '</div>';

		if ( extra ) {
			html += extra;
		}

		html += '</article>';

		return html;
	}

	function icon( slug ) {
		var paths = {
			flower: '<path d="M12 3a3 3 0 013 3c0 1-.4 1.8-1 2.4A3 3 0 0121 11a3 3 0 01-3 3c-.6 0-1.2-.2-1.7-.5.4.6.7 1.3.7 2a3 3 0 01-6 0c0-.7.3-1.4.7-2-.5.3-1.1.5-1.7.5a3 3 0 01-3-3 3 3 0 013-3h.2A3.3 3.3 0 019 6a3 3 0 013-3z"/>',
			oil: '<path d="M12 2s5 6.4 5 10a5 5 0 01-10 0c0-3.6 5-10 5-10z"/>',
			vape: '<path d="M4 14h11a3 3 0 100-6h-1M4 14v3a1 1 0 001 1h9a1 1 0 001-1v-3M6 11V6"/>',
			gummy: '<path d="M7 10a5 5 0 0110 0v6a3 3 0 01-3 3h-4a3 3 0 01-3-3v-6z"/>',
			capsul: '<rect x="3" y="9" width="18" height="6" rx="3"/><path d="M12 9v6"/>',
			cream: '<path d="M8 8h8v12H8z"/><path d="M10 8V5h4v3"/>',
			pet: '<circle cx="8" cy="9" r="2"/><circle cx="16" cy="9" r="2"/><path d="M12 13c-3 0-5 2-5 4a2 2 0 002 2h6a2 2 0 002-2c0-2-2-4-5-4z"/>',
			drink: '<path d="M6 4h12l-1.5 16h-9L6 4z"/><path d="M7 10h10"/>',
			sleep: '<path d="M20 14a8 8 0 11-9-9 6.5 6.5 0 009 9z"/>',
			pain: '<path d="M20 9a5 5 0 00-8-3 5 5 0 00-8 3c0 6 8 10 8 10s8-4 8-10z"/>',
			energy: '<path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z"/>',
			calm: '<path d="M4 12h4l2-5 3 10 2-5h5"/>'
		};

		var match = '<circle cx="12" cy="12" r="8"/>';

		Object.keys( paths ).forEach( function ( needle ) {
			if ( String( slug ).indexOf( needle ) !== -1 && match === '<circle cx="12" cy="12" r="8"/>' ) {
				match = paths[ needle ];
			}
		} );

		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" '
			+ 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + match + '</svg>';
	}

	/* ----------------------------------------------------------- motion -- */

	var revealObserver = null;
	var failsafeTimer = null;

	function observeReveal( scope ) {
		if ( reduceMotion || typeof IntersectionObserver === 'undefined' ) {
			return;
		}

		if ( ! revealObserver ) {
			revealObserver = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-revealed' );
						revealObserver.unobserve( entry.target );
					}
				} );
			}, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' } );
		}

		( scope || document ).querySelectorAll( '.rh-card:not(.rh-reveal), .rh-category:not(.rh-reveal)' )
			.forEach( function ( el, index ) {
				var box = el.getBoundingClientRect();

				el.classList.add( 'rh-reveal' );

				if ( box.top < window.innerHeight && box.bottom > 0 ) {
					el.classList.add( 'is-revealed' );
					return;
				}

				el.style.setProperty( '--rh-reveal-delay', ( index % 4 ) * 45 + 'ms' );
				revealObserver.observe( el );
			} );

		// Nothing may stay invisible just because it was never scrolled to.
		if ( ! failsafeTimer ) {
			failsafeTimer = setTimeout( function () {
				document.querySelectorAll( '.rh-reveal:not(.is-revealed)' ).forEach( function ( el ) {
					el.classList.add( 'is-revealed' );
				} );
				failsafeTimer = null;
			}, 2500 );
		}
	}

	/* ------------------------------------------------------- quick view -- */

	var qvLastFocus = null;

	function openQuickView( id ) {
		var item = DATA.products.concat( DATA.brands ).filter( function ( p ) {
			return String( p.id ) === String( id );
		} )[ 0 ];

		if ( ! item ) {
			return;
		}

		qvLastFocus = document.activeElement;

		var overlay = byId( 'qv' );
		var content = byId( 'qv-content' );
		var html = '';

		html += '<div class="rh-qv__media">' + swatch( item ) + '</div>';
		html += '<div class="rh-qv__info">';

		if ( item.brand ) {
			html += '<span class="rh-card__brand">' + esc( item.brand ) + '</span>';
		}

		html += '<h2 class="rh-qv__title" id="qv-title">' + esc( item.title ) + '</h2>';

		if ( item.rating > 0 ) {
			html += '<div class="rh-qv__score">'
				+ '<div class="rh-qv__meter"><span class="rh-qv__meter-fill" data-fill="'
				+ ( item.rating / 5 ) * 100 + '"></span></div>'
				+ '<strong>' + esc( item.rating.toFixed( 1 ) ) + '</strong><span>/ 5</span></div>';
		}

		if ( item.cbd_mg > 0 || item.thc_pct > 0 || item.lab_tested ) {
			html += '<dl class="rh-card__spec">';

			if ( item.cbd_mg > 0 ) {
				html += '<div><dt>CBD</dt><dd>' + esc( item.cbd_mg ) + ' mg</dd></div>';
			}

			if ( item.thc_pct > 0 ) {
				html += '<div><dt>THC</dt><dd>' + esc( item.thc_pct ) + '%</dd></div>';
			}

			if ( item.lab_tested ) {
				html += '<div class="rh-card__verified"><dt>Lab</dt><dd>Verified</dd></div>';
			}

			html += '</dl>';
		}

		if ( item.concerns && item.concerns.length ) {
			html += '<p class="rh-qv__tags">' + item.concerns.map( function ( t ) {
				return '<span>' + esc( t ) + '</span>';
			} ).join( '' ) + '</p>';
		}

		if ( item.excerpt ) {
			html += '<p class="rh-qv__excerpt">' + esc( item.excerpt ) + '</p>';
		}

		if ( item.regions && item.regions.length ) {
			html += '<p class="rh-card__region">' + esc( item.regions.join( ' · ' ) ) + '</p>';
		}

		html += '<div class="rh-qv__actions">';

		if ( item.price > 0 ) {
			html += '<span class="rh-card__price">';
			if ( item.price_was > item.price ) {
				html += '<s>' + esc( money( item.price_was, item.currency ) ) + '</s>';
			}
			html += '<strong>' + esc( money( item.price, item.currency ) ) + '</strong></span>';
		}

		html += '<button type="button" class="rh-card__cta" data-offer="' + esc( item.title ) + '">View offer</button>';
		html += '</div>';

		if ( item.discount_code ) {
			html += '<button type="button" class="rh-card__code" data-copy="' + esc( item.discount_code ) + '">'
				+ 'Code <code>' + esc( item.discount_code ) + '</code></button>';
		}

		html += '</div>';

		content.innerHTML = html;
		overlay.hidden = false;
		document.documentElement.style.overflow = 'hidden';

		requestAnimationFrame( function () {
			overlay.classList.add( 'is-open' );

			var fill = overlay.querySelector( '.rh-qv__meter-fill' );

			if ( fill ) {
				requestAnimationFrame( function () {
					fill.style.width = fill.dataset.fill + '%';
				} );
			}
		} );

		byId( 'qv-close' ).focus();
	}

	function closeQuickView() {
		var overlay = byId( 'qv' );

		overlay.classList.remove( 'is-open' );
		overlay.hidden = true;
		document.documentElement.style.overflow = '';

		if ( qvLastFocus ) {
			qvLastFocus.focus();
		}
	}

	/* ------------------------------------------------------------- grid -- */

	var state = { category: [], concern: '', region: '', min_rating: 0, lab_tested: false, orderby: 'rating' };

	function matches( item ) {
		if ( state.category.length && ! state.category.some( function ( slug ) {
			return item.cat_slugs.indexOf( slug ) !== -1;
		} ) ) {
			return false;
		}

		if ( state.concern && item.concern_slugs.indexOf( state.concern ) === -1 ) {
			return false;
		}

		if ( state.region && item.region_slugs.indexOf( state.region ) === -1 ) {
			return false;
		}

		if ( state.min_rating && item.rating < Number( state.min_rating ) ) {
			return false;
		}

		if ( state.lab_tested && ! item.lab_tested ) {
			return false;
		}

		return true;
	}

	function sorted( items ) {
		var list = items.slice();

		list.sort( function ( a, b ) {
			switch ( state.orderby ) {
				case 'price_asc':
					return a.price - b.price;
				case 'price_desc':
					return b.price - a.price;
				case 'recent':
					return b.id - a.id;
				default:
					return b.rating - a.rating;
			}
		} );

		return list;
	}

	function isFiltered() {
		return Boolean(
			state.category.length || state.concern || state.region ||
			Number( state.min_rating ) > 0 || state.lab_tested
		);
	}

	function skeletons( count ) {
		var one = '<div class="rh-skeleton" aria-hidden="true">'
			+ '<div class="rh-skeleton__media"></div>'
			+ '<div class="rh-skeleton__line rh-skeleton__line--sm"></div>'
			+ '<div class="rh-skeleton__line"></div>'
			+ '<div class="rh-skeleton__line rh-skeleton__line--sm"></div></div>';

		return new Array( count + 1 ).join( one );
	}

	function renderPills() {
		var pills = byId( 'pills' );
		var active = [];

		state.category.forEach( function ( slug ) {
			var term = DATA.taxonomies.categories.filter( function ( t ) {
				return t.slug === slug;
			} )[ 0 ];

			active.push( { key: 'category', value: slug, label: term ? term.name : slug } );
		} );

		[ 'concern', 'region', 'min_rating' ].forEach( function ( key ) {
			var control = byId( 'f-' + key );

			if ( ! control || ! control.value || control.value === '0' ) {
				return;
			}

			active.push( {
				key: key,
				value: control.value,
				label: control.options[ control.selectedIndex ].textContent.trim()
			} );
		} );

		if ( state.lab_tested ) {
			active.push( { key: 'lab_tested', value: '1', label: 'Lab tested only' } );
		}

		pills.hidden = ! active.length;
		pills.innerHTML = active.map( function ( item ) {
			return '<button type="button" class="rh-pill" data-key="' + esc( item.key ) + '" data-value="'
				+ esc( item.value ) + '">' + esc( item.label ) + '<span aria-hidden="true">&times;</span></button>';
		} ).join( '' );
	}

	function renderGrid( instant ) {
		var results = byId( 'results' );
		var empty = byId( 'empty' );
		var list = sorted( DATA.products.filter( matches ) );

		function paint() {
			results.setAttribute( 'aria-busy', 'false' );

			if ( ! list.length ) {
				results.innerHTML = '';
				empty.hidden = false;
			} else {
				empty.hidden = true;
				results.innerHTML = list.map( function ( item ) {
					return cardHtml( item );
				} ).join( '' );
				observeReveal( results );
			}

			var label = byId( 'count' );

			label.textContent = list.length === 1 ? '1 result' : list.length + ' results';
			label.classList.remove( 'is-pulsing' );
			void label.offsetWidth;
			label.classList.add( 'is-pulsing' );

			byId( 'clear' ).hidden = ! isFiltered();
			renderPills();
		}

		if ( instant ) {
			paint();
			return;
		}

		results.setAttribute( 'aria-busy', 'true' );
		results.innerHTML = skeletons( Math.min( 8, Math.max( 4, list.length || 6 ) ) );
		empty.hidden = true;

		setTimeout( paint, 340 );
	}

	/* ----------------------------------------------------------- finder -- */

	var finderAnswers = {};
	var finderStep = 0;

	function showFinderStep( index ) {
		var steps = document.querySelectorAll( '[data-step]' );

		finderStep = index;

		steps.forEach( function ( step, i ) {
			step.hidden = i !== index;
		} );

		byId( 'finder-results' ).hidden = index < steps.length;
		byId( 'finder-bar' ).style.width = Math.round( ( ( index + 1 ) / ( steps.length + 1 ) ) * 100 ) + '%';
	}

	function finishFinder() {
		var list = byId( 'finder-list' );
		var wanted = Object.keys( finderAnswers ).filter( function ( key ) {
			return finderAnswers[ key ];
		} );

		var scored = DATA.products.map( function ( item ) {
			var score = 0;

			if ( finderAnswers.category && item.cat_slugs.indexOf( finderAnswers.category ) !== -1 ) {
				score++;
			}

			if ( finderAnswers.concern && item.concern_slugs.indexOf( finderAnswers.concern ) !== -1 ) {
				score++;
			}

			if ( finderAnswers.region && item.region_slugs.indexOf( finderAnswers.region ) !== -1 ) {
				score++;
			}

			return { item: item, score: score };
		} );

		scored.sort( function ( a, b ) {
			return b.score === a.score ? b.item.rating - a.item.rating : b.score - a.score;
		} );

		showFinderStep( document.querySelectorAll( '[data-step]' ).length );

		list.innerHTML = scored.slice( 0, 4 ).map( function ( entry ) {
			var note = wanted.length
				? '<p class="rh-finder__match">' + entry.score + '/' + wanted.length + ' criteria matched</p>'
				: '';

			return cardHtml( entry.item, note );
		} ).join( '' );

		observeReveal( list );
	}

	/* ---------------------------------------------------------- compare -- */

	function renderCompare() {
		var picked = Array.prototype.slice.call(
			document.querySelectorAll( '[data-compare]:checked' )
		).map( function ( box ) {
			return DATA.brands.filter( function ( b ) {
				return String( b.id ) === box.value;
			} )[ 0 ];
		} ).filter( Boolean );

		var table = byId( 'compare-table' );

		if ( picked.length < 2 ) {
			table.innerHTML = '';
			return;
		}

		var rows = [
			[ 'Score', function ( b ) { return b.rating ? b.rating.toFixed( 1 ) + ' / 5' : '—'; } ],
			[ 'Reviews', function ( b ) { return b.review_count || 0; } ],
			[ 'Ships to', function ( b ) { return b.ships_to || '—'; } ],
			[ 'Discount code', function ( b ) { return b.discount_code || '—'; } ],
			[ 'Lab reports', function ( b ) { return b.lab_tested ? 'Published' : '—'; } ]
		];

		var html = '<table><thead><tr><th scope="col"></th>';

		picked.forEach( function ( b ) {
			html += '<th scope="col">' + esc( b.title ) + '</th>';
		} );

		html += '</tr></thead><tbody>';

		rows.forEach( function ( row ) {
			html += '<tr><th scope="row">' + esc( row[ 0 ] ) + '</th>';
			picked.forEach( function ( b ) {
				html += '<td>' + esc( row[ 1 ]( b ) ) + '</td>';
			} );
			html += '</tr>';
		} );

		html += '</tbody></table>';
		table.innerHTML = html;
	}

	/* ------------------------------------------------------------ stats -- */

	function countUp() {
		if ( reduceMotion || typeof IntersectionObserver === 'undefined' ) {
			return;
		}

		var observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) {
					return;
				}

				var el = entry.target;
				var target = parseFloat( el.dataset.count );
				var suffix = el.dataset.suffix || '';
				var decimals = ( el.dataset.count.split( '.' )[ 1 ] || '' ).length;
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

		document.querySelectorAll( '[data-count]' ).forEach( function ( el ) {
			observer.observe( el );
		} );
	}

	/* ------------------------------------------------------------- boot -- */

	function boot() {
		// Age gate
		var gate = byId( 'gate' );
		var confirmed = false;

		try {
			confirmed = window.localStorage.getItem( 'vl-age-ok' ) === '1';
		} catch ( e ) {}

		if ( confirmed ) {
			gate.remove();
		} else {
			gate.hidden = false;
			document.documentElement.style.overflow = 'hidden';
			byId( 'gate-yes' ).focus();

			byId( 'gate-yes' ).addEventListener( 'click', function () {
				try {
					window.localStorage.setItem( 'vl-age-ok', '1' );
				} catch ( e ) {}

				document.documentElement.style.overflow = '';
				gate.remove();
			} );

			byId( 'gate-no' ).addEventListener( 'click', function () {
				toast( 'On the live site this sends the visitor away. Here it just stays put.' );
			} );
		}

		renderGrid( true );
		countUp();
		observeReveal( document );

		// Category chips
		document.querySelectorAll( '.rh-category' ).forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				var pressed = chip.getAttribute( 'aria-pressed' ) === 'true';

				chip.setAttribute( 'aria-pressed', pressed ? 'false' : 'true' );

				state.category = Array.prototype.map.call(
					document.querySelectorAll( '.rh-category[aria-pressed="true"]' ),
					function ( el ) {
						return el.dataset.slug;
					}
				);

				renderGrid();
			} );
		} );

		// Facets
		[ 'concern', 'region', 'min_rating', 'orderby' ].forEach( function ( key ) {
			var control = byId( 'f-' + key );

			if ( control ) {
				control.addEventListener( 'change', function () {
					state[ key ] = control.value;
					renderGrid();
				} );
			}
		} );

		byId( 'f-lab' ).addEventListener( 'change', function () {
			state.lab_tested = this.checked;
			renderGrid();
		} );

		byId( 'clear' ).addEventListener( 'click', function () {
			state = { category: [], concern: '', region: '', min_rating: 0, lab_tested: false, orderby: 'rating' };

			document.querySelectorAll( '.rh-category[aria-pressed="true"]' ).forEach( function ( chip ) {
				chip.setAttribute( 'aria-pressed', 'false' );
			} );

			[ 'concern', 'region', 'min_rating' ].forEach( function ( key ) {
				byId( 'f-' + key ).selectedIndex = 0;
			} );

			byId( 'f-orderby' ).value = 'rating';
			byId( 'f-lab' ).checked = false;

			renderGrid();
		} );

		byId( 'pills' ).addEventListener( 'click', function ( event ) {
			var pill = event.target.closest( '.rh-pill' );

			if ( ! pill ) {
				return;
			}

			var key = pill.dataset.key;

			if ( key === 'category' ) {
				state.category = state.category.filter( function ( slug ) {
					return slug !== pill.dataset.value;
				} );

				var chip = document.querySelector( '.rh-category[data-slug="' + pill.dataset.value + '"]' );

				if ( chip ) {
					chip.setAttribute( 'aria-pressed', 'false' );
				}
			} else if ( key === 'lab_tested' ) {
				state.lab_tested = false;
				byId( 'f-lab' ).checked = false;
			} else {
				state[ key ] = key === 'min_rating' ? 0 : '';
				byId( 'f-' + key ).selectedIndex = 0;
			}

			renderGrid();
		} );

		// Finder
		document.querySelectorAll( '[data-step] .rh-finder__option' ).forEach( function ( option ) {
			option.addEventListener( 'click', function () {
				var step = option.closest( '[data-step]' );

				finderAnswers[ step.dataset.key ] = option.dataset.value;

				if ( finderStep + 1 < document.querySelectorAll( '[data-step]' ).length ) {
					showFinderStep( finderStep + 1 );
				} else {
					finishFinder();
				}
			} );
		} );

		document.querySelectorAll( '[data-finder-back]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				showFinderStep( Math.max( 0, finderStep - 1 ) );
			} );
		} );

		byId( 'finder-restart' ).addEventListener( 'click', function () {
			finderAnswers = {};
			showFinderStep( 0 );
		} );

		// Compare
		document.querySelectorAll( '[data-compare]' ).forEach( function ( box ) {
			box.addEventListener( 'change', function () {
				if ( document.querySelectorAll( '[data-compare]:checked' ).length > 3 ) {
					box.checked = false;
					toast( 'Three brands is the maximum, so the table stays readable.' );
					return;
				}

				renderCompare();
			} );
		} );

		// Quick view, offers, copy codes
		document.addEventListener( 'click', function ( event ) {
			var quick = event.target.closest( '[data-quick]' );

			if ( quick ) {
				openQuickView( quick.dataset.quick );
				return;
			}

			var offer = event.target.closest( '[data-offer]' );

			if ( offer ) {
				toast( 'On the WordPress build this redirects through /go/{id}/ and records the click.' );
				return;
			}

			var copy = event.target.closest( '[data-copy]' );

			if ( copy ) {
				if ( navigator.clipboard ) {
					navigator.clipboard.writeText( copy.dataset.copy ).then( function () {
						copy.dataset.copied = '1';
						toast( 'Copied ' + copy.dataset.copy );
						setTimeout( function () {
							delete copy.dataset.copied;
						}, 1800 );
					} ).catch( function () {
						toast( 'Code: ' + copy.dataset.copy );
					} );
				} else {
					toast( 'Code: ' + copy.dataset.copy );
				}

				return;
			}

			if ( event.target.closest( '[data-qv-close]' ) ) {
				closeQuickView();
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && ! byId( 'qv' ).hidden ) {
				closeQuickView();
			}
		} );

		// Sticky finder CTA
		var sticky = byId( 'sticky' );
		var finder = byId( 'finder' );

		sticky.addEventListener( 'click', function () {
			finder.scrollIntoView( { behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' } );
			finder.classList.add( 'is-flagged' );
			setTimeout( function () {
				finder.classList.remove( 'is-flagged' );
			}, 1400 );
		} );

		if ( typeof IntersectionObserver !== 'undefined' ) {
			new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					sticky.classList.toggle( 'is-visible', ! entry.isIntersecting );
				} );
			}, { threshold: 0.2 } ).observe( finder );
		}

		// Carousel
		var track = byId( 'carousel' );

		track.innerHTML = '<div class="swiper-wrapper">' + sorted( DATA.products ).slice( 0, 8 ).map( function ( item ) {
			return '<div class="swiper-slide">' + cardHtml( item ) + '</div>';
		} ).join( '' ) + '</div><div class="swiper-pagination"></div>';

		if ( typeof window.Swiper !== 'undefined' ) {
			new window.Swiper( track, {
				slidesPerView: 1.15,
				spaceBetween: 14,
				grabCursor: true,
				watchOverflow: true,
				a11y: { enabled: true },
				pagination: { el: track.querySelector( '.swiper-pagination' ), clickable: true },
				navigation: { prevEl: byId( 'carousel-prev' ), nextEl: byId( 'carousel-next' ) },
				breakpoints: {
					560: { slidesPerView: 2.2, spaceBetween: 16 },
					820: { slidesPerView: 3, spaceBetween: 18 },
					1180: { slidesPerView: 4, spaceBetween: 20 }
				}
			} );
		} else {
			// Swiper missing: the track stays a natively swipeable scroll-snap row.
			track.classList.add( 'is-fallback' );
		}

		observeReveal( track );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
