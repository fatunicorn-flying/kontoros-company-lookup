/**
 * Kontoros Company Lookup – company autocomplete for the WooCommerce checkout.
 * Works with the classic (shortcode) checkout and the block checkout. No dependencies.
 */
( function () {
	'use strict';

	var cfg = window.kontorosCL;
	if ( ! cfg ) {
		return;
	}

	// Company name field and our company code field, classic (billing_…) and block checkout (billing-… / shipping-…).
	// The block checkout hides Company by default, so searching from the company code field matters.
	var SELECTOR = '#billing_company, #billing-company, #shipping-company, #billing_company_code, #billing-kontoros-company-code, #shipping-kontoros-company-code';
	var state = { input: null, list: null, items: [], active: -1, timer: null, ctrl: null, cache: {}, picked: '' };

	function el( tag, cls, text ) {
		var e = document.createElement( tag );
		if ( cls ) { e.className = cls; }
		if ( text ) { e.textContent = text; }
		return e;
	}

	function close() {
		if ( state.list ) {
			state.list.remove();
		}
		state.list = null;
		state.items = [];
		state.active = -1;
		if ( state.input ) {
			state.input.setAttribute( 'aria-expanded', 'false' );
		}
	}

	function render( input, items ) {
		close();
		if ( ! items.length ) {
			return;
		}
		state.input = input;
		state.items = items;
		var list = el( 'ul', 'kontoros-cl-list' );
		list.id = 'kontoros-cl-list';
		list.setAttribute( 'role', 'listbox' );

		items.forEach( function ( c, i ) {
			var li = el( 'li', 'kontoros-cl-item' + ( c.active ? '' : ' is-inactive' ) );
			li.id = 'kontoros-cl-opt-' + i;
			li.setAttribute( 'role', 'option' );
			var name = el( 'span', 'kontoros-cl-name', c.name );
			if ( ! c.active ) {
				name.appendChild( el( 'span', 'kontoros-cl-badge', cfg.i18n.inactive ) );
			}
			li.appendChild( name );
			var meta = cfg.i18n.code + ' ' + c.code + ( c.vat_code ? ' · ' + cfg.i18n.vat + ' ' + c.vat_code : '' ) + ( c.address ? ' · ' + c.address : '' );
			li.appendChild( el( 'span', 'kontoros-cl-meta', meta ) );
			// mousedown, so the pick happens before the input loses focus
			li.addEventListener( 'mousedown', function ( e ) {
				e.preventDefault();
				pick( i );
			} );
			list.appendChild( li );
		} );

		if ( cfg.attribution ) {
			var src = el( 'li', 'kontoros-cl-source' );
			var a = el( 'a', '', cfg.i18n.source );
			a.href = 'https://www.kontoros.lt';
			a.target = '_blank';
			a.rel = 'noopener';
			src.appendChild( a );
			list.appendChild( src );
		}

		var wrap = input.parentElement;
		if ( window.getComputedStyle( wrap ).position === 'static' ) {
			wrap.style.position = 'relative';
		}
		wrap.appendChild( list );
		state.list = list;
		input.setAttribute( 'aria-expanded', 'true' );
		input.setAttribute( 'aria-controls', list.id );
	}

	function highlight( i ) {
		if ( ! state.list ) {
			return;
		}
		var opts = state.list.querySelectorAll( '.kontoros-cl-item' );
		state.active = ( i + opts.length ) % opts.length;
		opts.forEach( function ( o, n ) {
			o.classList.toggle( 'is-active', n === state.active );
		} );
		state.input.setAttribute( 'aria-activedescendant', opts[ state.active ].id );
		opts[ state.active ].scrollIntoView( { block: 'nearest' } );
	}

	function search( input ) {
		var q = input.value.trim();
		if ( q.length < 2 || q === state.picked ) {
			close();
			return;
		}
		var key = q.toLowerCase();
		if ( state.cache[ key ] ) {
			render( input, state.cache[ key ] );
			return;
		}
		if ( state.ctrl ) {
			state.ctrl.abort();
		}
		state.ctrl = window.AbortController ? new AbortController() : null;
		var url = cfg.endpoint + ( cfg.endpoint.indexOf( '?' ) === -1 ? '?' : '&' ) + 'q=' + encodeURIComponent( q );
		fetch( url, { credentials: 'same-origin', signal: state.ctrl ? state.ctrl.signal : undefined } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( json ) {
				var items = ( json && json.data ) || [];
				state.cache[ key ] = items;
				if ( document.activeElement === input && input.value.trim() === q ) {
					render( input, items );
				}
			} )
			.catch( function () { /* aborted or offline: customer types by hand */ } );
	}

	/* ---------- filling the form ---------- */

	function pick( i ) {
		var c = state.items[ i ];
		var input = state.input;
		close();
		if ( ! c || ! input ) {
			return;
		}
		state.picked = /company[_-]code|company-code/.test( input.id ) ? c.code : c.name;
		if ( input.id.indexOf( '-' ) !== -1 && window.wp && wp.data ) {
			fillBlocks( input.id.split( '-' )[ 0 ], c );
		} else {
			fillClassic( c );
		}
	}

	function setValue( id, value ) {
		var f = document.getElementById( id );
		if ( ! f || value === undefined || value === null ) {
			return;
		}
		f.value = value;
		f.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		f.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function fillClassic( c ) {
		var $ = window.jQuery;
		setValue( 'billing_company', c.name );
		if ( cfg.fillFields ) {
			setValue( 'billing_company_code', c.code );
			setValue( 'billing_vat_code', c.vat_code );
		}
		if ( cfg.fillAddress && c.address_1 ) {
			var country = document.getElementById( 'billing_country' );
			if ( country && country.value !== 'LT' && country.querySelector( 'option[value="LT"]' ) ) {
				if ( $ ) {
					$( country ).val( 'LT' ).trigger( 'change' ); // select2 + WooCommerce field refresh
				} else {
					setValue( 'billing_country', 'LT' );
				}
			}
			setValue( 'billing_address_1', c.address_1 );
			setValue( 'billing_address_2', '' );
			setValue( 'billing_city', c.city );
			if ( c.state ) {
				setValue( 'billing_state', c.state );
			}
			setValue( 'billing_postcode', c.postcode );
		}
		if ( $ ) {
			$( document.body ).trigger( 'update_checkout' );
		}
	}

	function fillBlocks( type, c ) {
		var store = wp.data.select( 'wc/store/cart' );
		var dispatch = wp.data.dispatch( 'wc/store/cart' );
		if ( ! store || ! dispatch ) {
			return;
		}
		var data = store.getCustomerData();
		var current = ( type === 'shipping' ? data.shippingAddress : data.billingAddress ) || {};
		var next = Object.assign( {}, current, { company: c.name } );
		if ( cfg.fillFields ) {
			next[ cfg.blockCode ] = c.code;
			next[ cfg.blockVat ] = c.vat_code || '';
		}
		if ( cfg.fillAddress && c.address_1 ) {
			Object.assign( next, { address_1: c.address_1, address_2: '', city: c.city, postcode: c.postcode, country: 'LT' } );
			if ( c.state ) {
				next.state = c.state; // "Apskritis", required by WooCommerce for LT
			}
		}
		if ( type === 'shipping' ) {
			dispatch.setShippingAddress( next );
			// "Use same address for billing": the billing copy isn't synced on store updates, so set it too.
			var checkout = wp.data.select( 'wc/store/checkout' );
			if ( checkout && checkout.getUseShippingAsBilling && checkout.getUseShippingAsBilling() ) {
				dispatch.setBillingAddress( Object.assign( {}, data.billingAddress || {}, next, { email: ( data.billingAddress || {} ).email || '' } ) );
			}
		} else {
			dispatch.setBillingAddress( next );
		}
	}

	/* ---------- events (delegated: block checkout renders fields late) ---------- */

	document.addEventListener( 'input', function ( e ) {
		var input = e.target;
		if ( ! input.matches || ! input.matches( SELECTOR ) ) {
			return;
		}
		if ( ! input.hasAttribute( 'aria-autocomplete' ) ) {
			input.setAttribute( 'role', 'combobox' );
			input.setAttribute( 'aria-autocomplete', 'list' );
			input.setAttribute( 'autocomplete', 'off' );
		}
		if ( e.isTrusted === false ) {
			return; // our own value updates
		}
		state.picked = '';
		clearTimeout( state.timer );
		state.timer = setTimeout( function () { search( input ); }, 250 );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( ! state.list || e.target !== state.input ) {
			return;
		}
		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			highlight( state.active + 1 );
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			highlight( state.active - 1 );
		} else if ( e.key === 'Enter' && state.active >= 0 ) {
			e.preventDefault(); // don't submit the order
			pick( state.active );
		} else if ( e.key === 'Escape' ) {
			close();
		}
	} );

	document.addEventListener( 'focusout', function ( e ) {
		if ( e.target === state.input ) {
			setTimeout( close, 150 );
		}
	} );
}() );
