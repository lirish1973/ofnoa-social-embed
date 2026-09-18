/**
 * Ofnoa Social Embed — front-end runtime.
 * No dependencies. Every gallery on the page is initialised independently.
 */
( function () {
	'use strict';

	var LIGHTBOX = null;

	/* --------------------------------------------------------------
	 * Utilities
	 * ------------------------------------------------------------ */

	function qs( root, sel ) {
		return root.querySelector( sel );
	}

	function qsa( root, sel ) {
		return Array.prototype.slice.call( root.querySelectorAll( sel ) );
	}

	function el( tag, cls, html ) {
		var node = document.createElement( tag );
		if ( cls ) {
			node.className = cls;
		}
		if ( html ) {
			node.innerHTML = html;
		}
		return node;
	}

	function prefersReducedMotion() {
		return window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	var ICONS = {
		close: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.6 13.4 4.3 19.71 2.89 18.3 9.18 12 2.89 5.71 4.3 4.29l6.3 6.3 6.29-6.3Z"/></svg>',
		prev: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.4 3.6 7 12l8.4 8.4 1.4-1.4L9.8 12l7-7Z"/></svg>',
		next: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.6 3.6 17 12l-8.4 8.4-1.4-1.4L14.2 12l-7-7Z"/></svg>'
	};

	/* --------------------------------------------------------------
	 * Embed helpers
	 * ------------------------------------------------------------ */

	function buildIframe( src, title ) {
		var frame = document.createElement( 'iframe' );
		frame.src = src;
		frame.title = title || 'Social video';
		frame.loading = 'lazy';
		frame.setAttribute( 'frameborder', '0' );
		frame.setAttribute( 'scrolling', 'no' );
		frame.setAttribute( 'allowtransparency', 'true' );
		frame.setAttribute( 'allow', 'autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share; fullscreen' );
		frame.setAttribute( 'allowfullscreen', 'true' );
		frame.setAttribute( 'referrerpolicy', 'origin-when-cross-origin' );
		return frame;
	}

	function isWide( card ) {
		return card.getAttribute( 'data-platform' ) === 'facebook';
	}

	/* --------------------------------------------------------------
	 * Lightbox (one shared instance)
	 * ------------------------------------------------------------ */

	function Lightbox() {
		var self = this;

		this.cards = [];
		this.index = 0;
		this.lastFocus = null;
		this.config = {};

		this.root = el( 'div', 'ose-lightbox' );
		this.root.setAttribute( 'role', 'dialog' );
		this.root.setAttribute( 'aria-modal', 'true' );
		this.root.hidden = true;

		this.stage = el( 'div', 'ose-lightbox__stage' );
		this.close = el( 'button', 'ose-lightbox__btn ose-lightbox__close', ICONS.close );
		this.prev = el( 'button', 'ose-lightbox__btn ose-lightbox__prev', ICONS.prev );
		this.next = el( 'button', 'ose-lightbox__btn ose-lightbox__next', ICONS.next );
		this.caption = el( 'div', 'ose-lightbox__caption' );

		[ this.close, this.prev, this.next ].forEach( function ( b ) {
			b.type = 'button';
		} );

		this.root.appendChild( this.stage );
		this.root.appendChild( this.close );
		this.root.appendChild( this.prev );
		this.root.appendChild( this.next );
		this.root.appendChild( this.caption );
		document.body.appendChild( this.root );

		this.close.addEventListener( 'click', function () {
			self.hide();
		} );
		this.prev.addEventListener( 'click', function () {
			self.go( -1 );
		} );
		this.next.addEventListener( 'click', function () {
			self.go( 1 );
		} );
		this.root.addEventListener( 'click', function ( e ) {
			if ( e.target === self.root ) {
				self.hide();
			}
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( self.root.hidden ) {
				return;
			}
			if ( e.key === 'Escape' ) {
				self.hide();
			} else if ( e.key === 'ArrowRight' ) {
				self.go( document.dir === 'rtl' ? -1 : 1 );
			} else if ( e.key === 'ArrowLeft' ) {
				self.go( document.dir === 'rtl' ? 1 : -1 );
			} else if ( e.key === 'Tab' ) {
				self.trap( e );
			}
		} );
	}

	Lightbox.prototype.trap = function ( e ) {
		var focusables = qsa( this.root, 'button, a[href], iframe' ).filter( function ( n ) {
			return ! n.hidden && n.offsetParent !== null;
		} );
		if ( ! focusables.length ) {
			return;
		}
		var first = focusables[ 0 ];
		var last = focusables[ focusables.length - 1 ];
		if ( e.shiftKey && document.activeElement === first ) {
			e.preventDefault();
			last.focus();
		} else if ( ! e.shiftKey && document.activeElement === last ) {
			e.preventDefault();
			first.focus();
		}
	};

	Lightbox.prototype.open = function ( cards, index, config ) {
		this.cards = cards;
		this.index = index;
		this.config = config || {};
		this.lastFocus = document.activeElement;

		this.root.hidden = false;
		this.root.dir = document.dir || 'ltr';
		document.body.classList.add( 'ose-lock' );

		var multiple = cards.length > 1;
		this.prev.hidden = ! multiple;
		this.next.hidden = ! multiple;

		var self = this;
		window.requestAnimationFrame( function () {
			self.root.classList.add( 'is-open' );
		} );

		this.render();
		this.close.focus();
	};

	Lightbox.prototype.render = function () {
		var card = this.cards[ this.index ];
		if ( ! card ) {
			return;
		}
		var src = card.getAttribute( 'data-embed' );
		var url = card.getAttribute( 'data-url' );
		var title = card.getAttribute( 'data-title' ) || '';
		var i18n = this.config.i18n || {};

		this.root.classList.toggle( 'is-wide', isWide( card ) );
		this.stage.innerHTML = '';

		if ( this.config.consent ) {
			var box = el( 'div', 'ose-consent' );
			box.appendChild( el( 'p', '', this.config.consentText || '' ) );
			var btn = el( 'button', 'ose-consent__btn', i18n.play || 'Play' );
			btn.type = 'button';
			var self = this;
			btn.addEventListener( 'click', function () {
				self.config = Object.assign( {}, self.config, { consent: 0 } );
				self.render();
			} );
			box.appendChild( btn );
			this.stage.appendChild( box );
		} else if ( src ) {
			this.stage.appendChild( el( 'div', 'ose-lightbox__spinner' ) );
			var frame = buildIframe( src, title );
			frame.addEventListener( 'load', function () {
				var sp = qs( this.parentNode || document, '.ose-lightbox__spinner' );
				if ( sp ) {
					sp.remove();
				}
			} );
			this.stage.appendChild( frame );
		}

		this.caption.innerHTML = '';
		if ( title ) {
			this.caption.appendChild( document.createTextNode( title + ' ' ) );
		}
		if ( url ) {
			var link = el( 'a', 'ose-lightbox__link', i18n.open || 'Open' );
			link.href = url;
			link.target = '_blank';
			link.rel = 'noopener noreferrer';
			this.caption.appendChild( link );
		}
		this.caption.hidden = ! title && ! url;

		this.prev.setAttribute( 'aria-label', i18n.prev || 'Previous' );
		this.next.setAttribute( 'aria-label', i18n.next || 'Next' );
		this.close.setAttribute( 'aria-label', i18n.close || 'Close' );
		this.root.setAttribute( 'aria-label', title || 'Video' );
	};

	Lightbox.prototype.go = function ( delta ) {
		if ( ! this.cards.length ) {
			return;
		}
		this.index = ( this.index + delta + this.cards.length ) % this.cards.length;
		this.render();
	};

	Lightbox.prototype.hide = function () {
		var self = this;
		this.root.classList.remove( 'is-open' );
		this.stage.innerHTML = '';
		document.body.classList.remove( 'ose-lock' );
		window.setTimeout( function () {
			self.root.hidden = true;
		}, 240 );
		if ( this.lastFocus && this.lastFocus.focus ) {
			this.lastFocus.focus();
		}
	};

	function lightbox() {
		if ( ! LIGHTBOX ) {
			LIGHTBOX = new Lightbox();
		}
		return LIGHTBOX;
	}

	/* --------------------------------------------------------------
	 * Gallery
	 * ------------------------------------------------------------ */

	function Gallery( root ) {
		this.root = root;
		this.track = qs( root, '.ose__track' );
		this.cards = qsa( root, '.ose__card' );

		var raw = root.getAttribute( 'data-ose' );
		try {
			this.config = raw ? JSON.parse( raw ) : {};
		} catch ( err ) {
			this.config = {};
		}

		this.filterSlug = '*';
		this.visible = this.cards.slice();

		// Load-more state has to exist before the first filter pass runs.
		this.moreBtn = qs( root, '.ose__more-btn' );
		this.batch = this.moreBtn ? this.readBatch() : 0;
		this.limit = this.batch || this.cards.length;

		this.bindCards();
		this.bindTabs();
		this.bindCarousel();
		this.bindLoadMore();
		this.applyVisibility();
		this.observeEntrance();
		this.observeFallbacks();
		this.syncDarkMode();

		root.classList.add( 'is-ready' );
	}

	Gallery.prototype.readBatch = function () {
		var cols = parseInt( window.getComputedStyle( this.root ).getPropertyValue( '--ose-cols' ), 10 ) || 4;
		return Math.max( 2, cols * 2 );
	};

	/**
	 * The single place that decides which cards are on screen. Both the tab bar
	 * and the load-more button change state and call this, so they compose
	 * instead of fighting each other.
	 */
	Gallery.prototype.applyVisibility = function ( animate ) {
		var self = this;
		var slug = this.filterSlug;
		var matched = this.cards.filter( function ( card ) {
			if ( slug === '*' ) {
				return true;
			}
			return ( card.getAttribute( 'data-terms' ) || '' ).split( /\s+/ ).indexOf( slug ) !== -1;
		} );

		this.visible = matched.slice( 0, this.limit );

		this.cards.forEach( function ( card ) {
			card.classList.toggle( 'is-hidden', self.visible.indexOf( card ) === -1 );
		} );

		this.visible.forEach( function ( card, i ) {
			card.style.setProperty( '--ose-i', i );
			if ( animate && ! prefersReducedMotion() ) {
				card.classList.remove( 'is-in' );
				window.requestAnimationFrame( function () {
					card.classList.add( 'is-in' );
				} );
			} else if ( ! self.root.classList.contains( 'ose--anim' ) ) {
				card.classList.add( 'is-in' );
			}
		} );

		if ( this.moreBtn && this.moreBtn.parentNode ) {
			this.moreBtn.parentNode.hidden = this.limit >= matched.length;
		}
	};

	Gallery.prototype.syncDarkMode = function () {
		// Honour a theme that flips a data-theme / class on <html>.
		if ( ! this.root.classList.contains( 'ose--skin-auto' ) ) {
			return;
		}
		var html = document.documentElement;
		var dark =
			html.classList.contains( 'dark' ) ||
			html.getAttribute( 'data-theme' ) === 'dark' ||
			html.getAttribute( 'data-color-scheme' ) === 'dark';
		this.root.classList.toggle( 'ose--forced-dark', !! dark );
	};

	Gallery.prototype.bindCards = function () {
		var self = this;
		this.cards.forEach( function ( card ) {
			var hit = qs( card, '.ose__hit' );
			if ( ! hit || hit.tagName === 'A' ) {
				return;
			}
			hit.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				self.play( card );
			} );
		} );
	};

	Gallery.prototype.play = function ( card ) {
		var mode = this.config.play || 'lightbox';
		var src = card.getAttribute( 'data-embed' );

		if ( mode === 'newtab' ) {
			window.open( card.getAttribute( 'data-url' ), '_blank', 'noopener' );
			return;
		}

		if ( mode === 'inline' && src ) {
			var slot = qs( card, '.ose__inline' );
			if ( ! slot ) {
				return;
			}
			if ( slot.childNodes.length ) {
				return;
			}
			slot.hidden = false;
			slot.appendChild( buildIframe( src, card.getAttribute( 'data-title' ) ) );
			card.classList.add( 'is-playing' );
			return;
		}

		var pool = this.visible.length ? this.visible : this.cards;
		var index = pool.indexOf( card );
		lightbox().open( pool, index < 0 ? 0 : index, this.config );
	};

	Gallery.prototype.bindTabs = function () {
		var self = this;
		var tabs = qsa( this.root, '.ose__tab' );
		if ( ! tabs.length ) {
			return;
		}

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				tabs.forEach( function ( t ) {
					t.classList.remove( 'is-active' );
					t.setAttribute( 'aria-selected', 'false' );
				} );
				tab.classList.add( 'is-active' );
				tab.setAttribute( 'aria-selected', 'true' );
				self.filter( tab.getAttribute( 'data-filter' ) );
			} );

			tab.addEventListener( 'keydown', function ( e ) {
				var i = tabs.indexOf( tab );
				var target = null;
				if ( e.key === 'ArrowRight' ) {
					target = tabs[ ( i + 1 ) % tabs.length ];
				} else if ( e.key === 'ArrowLeft' ) {
					target = tabs[ ( i - 1 + tabs.length ) % tabs.length ];
				}
				if ( target ) {
					e.preventDefault();
					target.focus();
					target.click();
				}
			} );
		} );

		var active = tabs.filter( function ( t ) {
			return t.classList.contains( 'is-active' );
		} )[ 0 ];
		if ( active ) {
			this.filter( active.getAttribute( 'data-filter' ), false );
		}
	};

	Gallery.prototype.filter = function ( slug, animate ) {
		this.filterSlug = slug || '*';
		if ( this.batch ) {
			this.limit = this.batch;
		}
		this.applyVisibility( animate !== false );
	};

	Gallery.prototype.bindCarousel = function () {
		var layout = this.config.layout;
		if ( layout !== 'carousel' && layout !== 'reels' && layout !== 'stories' ) {
			return;
		}
		var self = this;
		var track = this.track;
		var prev = qs( this.root, '.ose__arrow--prev' );
		var next = qs( this.root, '.ose__arrow--next' );
		var dotsWrap = qs( this.root, '.ose__dots' );

		function step() {
			var card = self.cards[ 0 ];
			if ( ! card ) {
				return track.clientWidth;
			}
			var styles = window.getComputedStyle( track );
			var gap = parseFloat( styles.columnGap || styles.gap || '0' ) || 0;
			return card.getBoundingClientRect().width + gap;
		}

		function dir() {
			return window.getComputedStyle( track ).direction === 'rtl' ? -1 : 1;
		}

		function slide( delta ) {
			track.scrollBy( { left: step() * delta * dir(), behavior: prefersReducedMotion() ? 'auto' : 'smooth' } );
		}

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				slide( -1 );
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				if ( self.config.loop && atEnd() ) {
					track.scrollTo( { left: 0, behavior: 'smooth' } );
					return;
				}
				slide( 1 );
			} );
		}

		function atEnd() {
			return Math.abs( track.scrollLeft ) + track.clientWidth >= track.scrollWidth - 4;
		}

		function updateArrows() {
			if ( ! prev || ! next || self.config.loop ) {
				return;
			}
			prev.disabled = Math.abs( track.scrollLeft ) < 4;
			next.disabled = atEnd();
		}

		if ( dotsWrap ) {
			this.cards.forEach( function ( card, i ) {
				var dot = el( 'button', 'ose__dot' + ( i === 0 ? ' is-active' : '' ) );
				dot.type = 'button';
				dot.setAttribute( 'role', 'tab' );
				dot.setAttribute( 'aria-label', String( i + 1 ) );
				dot.addEventListener( 'click', function () {
					track.scrollTo( { left: step() * i * dir(), behavior: 'smooth' } );
				} );
				dotsWrap.appendChild( dot );
			} );
		}

		function updateDots() {
			if ( ! dotsWrap ) {
				return;
			}
			var active = Math.round( Math.abs( track.scrollLeft ) / step() );
			qsa( dotsWrap, '.ose__dot' ).forEach( function ( dot, i ) {
				dot.classList.toggle( 'is-active', i === active );
			} );
		}

		var ticking = false;
		track.addEventListener(
			'scroll',
			function () {
				if ( ticking ) {
					return;
				}
				ticking = true;
				window.requestAnimationFrame( function () {
					updateArrows();
					updateDots();
					ticking = false;
				} );
			},
			{ passive: true }
		);

		updateArrows();

		if ( this.config.autoplay && ! prefersReducedMotion() ) {
			var timer = null;
			var speed = Math.max( 1000, parseInt( this.config.speed, 10 ) || 4000 );

			function start() {
				stop();
				timer = window.setInterval( function () {
					if ( atEnd() ) {
						if ( self.config.loop ) {
							track.scrollTo( { left: 0, behavior: 'smooth' } );
						} else {
							stop();
						}
						return;
					}
					slide( 1 );
				}, speed );
			}

			function stop() {
				if ( timer ) {
					window.clearInterval( timer );
					timer = null;
				}
			}

			this.root.addEventListener( 'mouseenter', stop );
			this.root.addEventListener( 'mouseleave', start );
			this.root.addEventListener( 'focusin', stop );
			this.root.addEventListener( 'touchstart', stop, { passive: true } );
			document.addEventListener( 'visibilitychange', function () {
				if ( document.hidden ) {
					stop();
				} else {
					start();
				}
			} );
			start();
		}

		// Drag to scroll on pointer devices.
		var down = false;
		var startX = 0;
		var startScroll = 0;
		track.addEventListener( 'pointerdown', function ( e ) {
			if ( e.pointerType === 'touch' ) {
				return;
			}
			down = true;
			startX = e.clientX;
			startScroll = track.scrollLeft;
		} );
		window.addEventListener( 'pointerup', function () {
			down = false;
		} );
		track.addEventListener( 'pointermove', function ( e ) {
			if ( ! down ) {
				return;
			}
			track.scrollLeft = startScroll - ( e.clientX - startX );
		} );
	};

	Gallery.prototype.bindLoadMore = function () {
		if ( ! this.moreBtn ) {
			return;
		}
		var self = this;
		this.moreBtn.addEventListener( 'click', function () {
			self.limit += self.batch;
			self.applyVisibility( false );
			self.observeEntrance();
		} );
	};

	Gallery.prototype.observeEntrance = function () {
		if ( ! this.root.classList.contains( 'ose--anim' ) ) {
			return;
		}
		if ( ! ( 'IntersectionObserver' in window ) || prefersReducedMotion() ) {
			this.cards.forEach( function ( card ) {
				card.classList.add( 'is-in' );
			} );
			return;
		}
		var io = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-in' );
						io.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: 0.08 }
		);
		this.cards.forEach( function ( card ) {
			if ( ! card.classList.contains( 'is-in' ) ) {
				io.observe( card );
			}
		} );
	};

	/**
	 * Cards without a resolved poster fall back to the real embed, loaded
	 * lazily so it never costs anything above the fold.
	 */
	Gallery.prototype.observeFallbacks = function () {
		if ( this.config.consent ) {
			return;
		}
		var targets = this.cards.filter( function ( card ) {
			return card.getAttribute( 'data-fallback' ) === '1' && card.getAttribute( 'data-embed' );
		} );
		if ( ! targets.length ) {
			return;
		}

		function mount( card ) {
			var slot = qs( card, '.ose__inline' );
			if ( ! slot || slot.childNodes.length ) {
				return;
			}
			slot.hidden = false;
			slot.style.zIndex = '1';
			slot.appendChild( buildIframe( card.getAttribute( 'data-embed' ), card.getAttribute( 'data-title' ) ) );
			card.classList.add( 'is-embedded' );
		}

		if ( ! ( 'IntersectionObserver' in window ) ) {
			targets.forEach( mount );
			return;
		}

		var io = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						mount( entry.target );
						io.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '200px' }
		);
		targets.forEach( function ( card ) {
			io.observe( card );
		} );
	};

	/* --------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------ */

	function boot( scope ) {
		qsa( scope || document, '.ose:not(.is-ready)' ).forEach( function ( root ) {
			if ( root.classList.contains( 'ose--empty' ) ) {
				return;
			}
			try {
				new Gallery( root );
			} catch ( err ) {
				if ( window.console && window.console.warn ) {
					window.console.warn( '[Ofnoa Social Embed]', err );
				}
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			boot();
		} );
	} else {
		boot();
	}

	// Re-init for AJAX themes, the block editor preview and Elementor.
	window.OSE = { boot: boot };
	document.addEventListener( 'ose:refresh', function () {
		boot();
	} );
	function hookElementor() {
		if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return false;
		}
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/ofnoa_social_embed.default', function ( $scope ) {
			boot( $scope && $scope[ 0 ] ? $scope[ 0 ] : document );
		} );
		return true;
	}

	// Elementor dispatches this through jQuery, which never reaches a native listener.
	if ( ! hookElementor() && window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', hookElementor );
	}
} )();
