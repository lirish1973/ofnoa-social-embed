/**
 * Ofnoa Social Embed — block editor UI.
 * Written without JSX so the plugin ships with no build step.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;
	var ServerSideRender = wp.serverSideRender;

	var C = wp.components;
	var PanelBody = C.PanelBody;
	var SelectControl = C.SelectControl;
	var RangeControl = C.RangeControl;
	var ToggleControl = C.ToggleControl;
	var TextControl = C.TextControl;
	var TextareaControl = C.TextareaControl;
	var Placeholder = C.Placeholder;
	var Notice = C.Notice;

	var DATA = window.OSE_BLOCK_DATA || { schema: [], collections: {}, defaults: {} };

	var GROUPS = [
		{ id: 'content', title: __( 'Content', 'ofnoa-social-embed' ), open: true },
		{ id: 'layout', title: __( 'Layout', 'ofnoa-social-embed' ) },
		{ id: 'card', title: __( 'Card', 'ofnoa-social-embed' ) },
		{ id: 'typography', title: __( 'Typography', 'ofnoa-social-embed' ) },
		{ id: 'elements', title: __( 'Elements', 'ofnoa-social-embed' ) },
		{ id: 'behaviour', title: __( 'Behaviour', 'ofnoa-social-embed' ) },
		{ id: 'advanced', title: __( 'Advanced', 'ofnoa-social-embed' ) }
	];

	/* ----------------------------------------------------------
	 * Attributes, derived from the shared schema
	 * -------------------------------------------------------- */

	function buildAttributes() {
		var attrs = { align: { type: 'string' } };
		DATA.schema.forEach( function ( def ) {
			if ( def.type === 'toggle' ) {
				attrs[ def.key ] = { type: 'boolean', default: !! def.default };
			} else if ( def.type === 'number' || def.type === 'range' ) {
				attrs[ def.key ] = { type: 'number', default: Number( def.default ) || 0 };
			} else {
				attrs[ def.key ] = { type: 'string', default: String( def.default == null ? '' : def.default ) };
			}
		} );
		return attrs;
	}

	/* ----------------------------------------------------------
	 * Control factory
	 * -------------------------------------------------------- */

	function control( def, attributes, setAttributes ) {
		var value = attributes[ def.key ];
		var set = function ( next ) {
			var payload = {};
			payload[ def.key ] = next;
			setAttributes( payload );
		};

		if ( def.type === 'toggle' ) {
			return el( ToggleControl, {
				key: def.key,
				label: def.label,
				checked: !! value,
				onChange: set,
				__nextHasNoMarginBottom: true
			} );
		}

		if ( def.type === 'select' ) {
			return el( SelectControl, {
				key: def.key,
				label: def.label,
				value: String( value ),
				options: def.options,
				onChange: set,
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true
			} );
		}

		if ( def.type === 'range' ) {
			return el( RangeControl, {
				key: def.key,
				label: def.label,
				value: Number( value ),
				min: def.min === null ? 0 : Number( def.min ),
				max: def.max === null ? 100 : Number( def.max ),
				onChange: function ( next ) {
					set( next === undefined ? Number( def.default ) : next );
				},
				__nextHasNoMarginBottom: true
			} );
		}

		if ( def.type === 'number' ) {
			return el( TextControl, {
				key: def.key,
				type: 'number',
				label: def.label,
				value: value,
				min: def.min,
				max: def.max,
				onChange: function ( next ) {
					set( next === '' ? 0 : Number( next ) );
				},
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true
			} );
		}

		if ( def.type === 'textarea' ) {
			return el( TextareaControl, {
				key: def.key,
				label: def.label,
				value: value || '',
				rows: 5,
				onChange: set,
				__nextHasNoMarginBottom: true
			} );
		}

		// Collections get a friendlier picker when terms exist.
		if ( def.key === 'collection' || def.key === 'tabs_from' ) {
			var slugs = Object.keys( DATA.collections || {} );
			if ( slugs.length ) {
				var options = [ { value: '', label: __( '— all collections —', 'ofnoa-social-embed' ) } ].concat(
					slugs.map( function ( slug ) {
						return { value: slug, label: DATA.collections[ slug ] };
					} )
				);
				return el( Fragment, { key: def.key },
					el( SelectControl, {
						label: def.label,
						value: String( value ),
						options: options,
						onChange: set,
						__nextHasNoMarginBottom: true,
						__next40pxDefaultSize: true
					} ),
					el( TextControl, {
						label: __( '…or several, comma separated', 'ofnoa-social-embed' ),
						value: value || '',
						onChange: set,
						__nextHasNoMarginBottom: true,
						__next40pxDefaultSize: true
					} )
				);
			}
		}

		return el( TextControl, {
			key: def.key,
			label: def.label,
			value: value || '',
			onChange: set,
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true
		} );
	}

	function groupControls( group, attributes, setAttributes ) {
		return DATA.schema
			.filter( function ( def ) {
				if ( def.group !== group ) {
					return false;
				}
				// Hide irrelevant controls instead of confusing the editor.
				if ( def.key === 'urls' && attributes.source !== 'urls' ) {
					return false;
				}
				if ( ( def.key === 'collection' || def.key === 'ids' ) && attributes.source === 'urls' ) {
					return false;
				}
				if ( ( def.key === 'tabs_from' || def.key === 'tab_all_label' ) && attributes.layout !== 'tabs' && ! attributes.show_filter ) {
					return false;
				}
				if ( ( def.key === 'autoplay' || def.key === 'autoplay_speed' || def.key === 'loop' ) &&
					[ 'carousel', 'reels', 'stories' ].indexOf( attributes.layout ) === -1 ) {
					return false;
				}
				if ( ( def.key === 'show_arrows' || def.key === 'show_dots' ) &&
					[ 'carousel', 'reels' ].indexOf( attributes.layout ) === -1 ) {
					return false;
				}
				return true;
			} )
			.map( function ( def ) {
				return control( def, attributes, setAttributes );
			} );
	}

	function colorPanel( attributes, setAttributes ) {
		var colorDefs = DATA.schema.filter( function ( def ) {
			return def.type === 'color';
		} );

		var settings = colorDefs.map( function ( def ) {
			return {
				value: attributes[ def.key ] || undefined,
				label: def.label,
				onChange: function ( next ) {
					var payload = {};
					payload[ def.key ] = next || '';
					setAttributes( payload );
				}
			};
		} );

		var skin = DATA.schema.filter( function ( def ) {
			return def.key === 'skin';
		} )[ 0 ];

		return el( PanelColorSettings, {
			title: __( 'Colours', 'ofnoa-social-embed' ),
			initialOpen: false,
			colorSettings: settings
		}, skin ? control( skin, attributes, setAttributes ) : null );
	}

	/* ----------------------------------------------------------
	 * Block
	 * -------------------------------------------------------- */

	registerBlockType( 'ofnoa/social-embed', {
		apiVersion: 2,
		title: __( 'Social Video Gallery', 'ofnoa-social-embed' ),
		description: __( 'Instagram, TikTok & Facebook videos in a designed grid, tabs, carousel or stories bar.', 'ofnoa-social-embed' ),
		category: 'ofnoa',
		icon: 'format-video',
		keywords: [ 'instagram', 'tiktok', 'facebook', 'reels', 'video', 'gallery' ],
		supports: { align: [ 'wide', 'full' ], anchor: true, html: false },
		attributes: buildAttributes(),

		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps ? useBlockProps() : {};

			var noContent =
				attributes.source === 'urls'
					? ! String( attributes.urls || '' ).trim()
					: false;

			var inspector = el( InspectorControls, {},
				GROUPS.map( function ( group ) {
					var controls = groupControls( group.id, attributes, setAttributes );
					if ( ! controls.length ) {
						return null;
					}
					return el( PanelBody, {
						key: group.id,
						title: group.title,
						initialOpen: !! group.open
					}, controls );
				} ),
				colorPanel( attributes, setAttributes )
			);

			var preview = noContent
				? el( Placeholder, {
					icon: 'format-video',
					label: __( 'Social Video Gallery', 'ofnoa-social-embed' ),
					instructions: __( 'Paste one Instagram, TikTok or Facebook URL per line in the Content panel, or switch the source back to your video library.', 'ofnoa-social-embed' )
				} )
				: el( ServerSideRender, {
					block: 'ofnoa/social-embed',
					attributes: attributes,
					EmptyResponsePlaceholder: function () {
						return el( Placeholder, {
							icon: 'format-video',
							label: __( 'Social Video Gallery', 'ofnoa-social-embed' ),
							instructions: __( 'No videos matched yet. Add videos to the library, or pick another collection.', 'ofnoa-social-embed' )
						} );
					}
				} );

			return el( Fragment, {},
				inspector,
				el( 'div', blockProps,
					el( Notice, { status: 'info', isDismissible: false, className: 'ose-block-note' },
						__( 'Preview is rendered live from the server — the finished gallery is interactive on the front end.', 'ofnoa-social-embed' )
					),
					preview
				)
			);
		},

		save: function () {
			return null;
		}
	} );
} )( window.wp );
