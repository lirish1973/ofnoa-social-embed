=== Ofnoa Social Embed ===
Contributors: ofnoacomps
Tags: instagram, facebook, reels, video, gallery
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed Instagram and Facebook videos in fully designed grids, tabs, carousels, spotlights and stories bars. Block, widget, shortcode and Elementor.

== Description ==

Ofnoa Social Embed turns a list of Instagram and Facebook links into a gallery that looks like it was designed for your site rather than bolted on.

* Eight layouts: grid, masonry, carousel, tabs, spotlight, reels row, stories bar and list.
* Eight card styles: glass, neon, minimal, gradient border, elevated, outline, polaroid and cinematic.
* Eight hover effects, five entrance animations, six shadow presets and four overlay modes.
* Every colour, radius, gap, column count, font size and toggle is exposed — in the block panel, the widget form, the Elementor panel and as shortcode attributes.
* Separate column counts for desktop, tablet and mobile, driven by container queries.
* Right-to-left aware, keyboard accessible, and respectful of prefers-reduced-motion.
* Light, dark and automatic colour schemes.
* No API keys required. Playback uses the public Instagram and Facebook embed players.
* Optional GDPR mode: no third-party iframe loads until the visitor presses play.
* Updates arrive from the public GitHub repository, straight in the Plugins screen.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip from the Plugins screen.
2. Activate it.
3. Go to **Social Embed → Add video** and paste an Instagram or Facebook link. Use **Bulk import** for a whole list at once.
4. Place a gallery with the block, the widget, the Elementor widget or `[ofnoa_social_embed]`.

== Frequently Asked Questions ==

= Do I need a Meta app or an access token? =

No. Playback works through the public embed players. Credentials only improve automatic poster and caption resolution, and they are entirely optional.

= What if a thumbnail cannot be fetched? =

Set a featured image on the video — it always wins. Failing that, the card falls back to the live embed itself, loaded lazily, so a real frame still appears.

= Does it work in a right-to-left site? =

Yes. The layout uses logical properties throughout and arrows, play icons and the lightbox all mirror correctly.

== Changelog ==

= 1.0.0 =
* First release.
