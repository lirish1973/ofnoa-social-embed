/**
 * Ofnoa Social Embed — admin helpers (media picker + auto fetch).
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var i18n = ( window.OSE_ADMIN && window.OSE_ADMIN.i18n ) || {};

		if ( $.fn.wpColorPicker ) {
			$( '.ose-color-field' ).wpColorPicker();
		}

		$( '#ose-pick-poster' ).on( 'click', function ( e ) {
			e.preventDefault();
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}
			var frame = window.wp.media( {
				title: i18n.choose || 'Choose a poster image',
				button: { text: i18n.use || 'Use this image' },
				library: { type: 'image' },
				multiple: false
			} );
			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				$( '#ose_poster' ).val( att.url );
				$( '#ose_poster_id' ).val( att.id );
				$( '.ose-side-preview' ).html( '<img src="' + att.url + '" alt="" />' );
			} );
			frame.open();
		} );

		$( '#ose-refresh-meta' ).on( 'click', function ( e ) {
			e.preventDefault();
			var $btn = $( this );
			var original = $btn.text();

			$btn.prop( 'disabled', true ).text( i18n.fetching || 'Fetching…' );

			$.post(
				window.OSE_ADMIN.ajaxUrl,
				{
					action: 'ose_refresh_meta',
					post_id: $btn.data( 'post' ),
					nonce: $btn.data( 'nonce' ),
					url: $( '#ose_url' ).val()
				}
			)
				.done( function ( res ) {
					if ( res && res.success && res.data && res.data.poster ) {
						$( '#ose_poster' ).val( res.data.poster );
						$( '.ose-side-preview' ).html( '<img src="' + res.data.poster + '" alt="" />' );
					} else {
						window.alert( ( res && res.data && res.data.message ) || i18n.failed || 'Failed' );
					}
				} )
				.fail( function () {
					window.alert( i18n.failed || 'Failed' );
				} )
				.always( function () {
					$btn.prop( 'disabled', false ).text( original );
				} );
		} );
	} );
} )( window.jQuery );
