=== Ofnoa Social Embed ===
Contributors: ofnoacomps
Tags: instagram, tiktok, facebook, reels, video
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed Instagram, TikTok and Facebook videos in fully designed grids, tabs, carousels, spotlights and stories bars. Block, widget, shortcode and Elementor.

== Description ==

Ofnoa Social Embed turns a list of Instagram, TikTok and Facebook links into a gallery that looks like it was designed for your site rather than bolted on.

* Eight layouts: grid, masonry, carousel, tabs, spotlight, reels row, stories bar and list.
* Eight card styles: glass, neon, minimal, gradient border, elevated, outline, polaroid and cinematic.
* Eight hover effects, five entrance animations, six shadow presets and four overlay modes.
* Every colour, radius, gap, column count, font size and toggle is exposed — in the block panel, the widget form, the Elementor panel and as shortcode attributes.
* Separate column counts for desktop, tablet and mobile, driven by container queries.
* Right-to-left aware, keyboard accessible, and respectful of prefers-reduced-motion.
* Light, dark and automatic colour schemes.
* No API keys required. Playback uses the public Instagram, TikTok and Facebook embed players.
* Optional GDPR mode: no third-party iframe loads until the visitor presses play.
* TikTok posters and handles resolve automatically through TikTok's open oEmbed endpoint.
* Updates arrive from the public GitHub repository, straight in the Plugins screen.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip from the Plugins screen.
2. Activate it.
3. Go to **Social Embed → Add video** and paste an Instagram, TikTok or Facebook link. Use **Bulk import** for a whole list at once.
4. Place a gallery with the block, the widget, the Elementor widget or `[ofnoa_social_embed]`.

== Frequently Asked Questions ==

= Do I need a Meta app or an access token? =

No. Playback works through the public embed players. Credentials only improve automatic poster and caption resolution for Instagram and Facebook, and they are entirely optional. TikTok needs nothing at all — its oEmbed endpoint is open.

= Which TikTok links work? =

Video links, photo posts, and vm.tiktok.com / vt.tiktok.com / tiktok.com/t short links, which are followed to the real video automatically.

= What if a thumbnail cannot be fetched? =

Set a featured image on the video — it always wins. Failing that, the card falls back to the live embed itself, loaded lazily, so a real frame still appears.

= Does it work in a right-to-left site? =

Yes. The layout uses logical properties throughout and arrows, play icons and the lightbox all mirror correctly.

== Changelog ==

= 1.1.7 =
* Reels (and any layout whose cards snap to the centre) no longer get stuck: arrows, autoplay, dots and drag now move between the exact positions the cards snap to, so every video can be reached in both directions, LTR and RTL.

= 1.1.6 =
* Links pasted with nothing between them ("…068https://…") are now split into separate videos, so every video in the list shows.
* Buttons (arrows, dots, tabs, play, load more, lightbox) are isolated from theme button styles (e.g. Hello Elementor), so every layout looks as designed on desktop.
* A closed lightbox no longer leaves an invisible overlay that blocks clicks on the page.
* The Elementor widget is excluded from Element Caching so setting changes show immediately.

= 1.1.5 =
* A manual URL list now shows every link in it. "How many videos" is a library setting and no longer cuts a URL list short; it is hidden in the block and Elementor panels when the source is a URL list.
* URL lists accept links separated by new lines, spaces, tabs, commas, semicolons or pipes — two links pasted on one line are no longer merged into one.
* Links that cannot be shown (another network, or a profile rather than a single video) are listed under the gallery, with the reason, for logged-in editors only. Visitors never see the note.
* Editors and editor previews (Elementor, block editor) resolve every poster immediately, so the cache is warm before visitors arrive.

= 1.1.4 =
* Carousel always slides: when there are no more videos than columns, it shows one card fewer per view (at every breakpoint) so arrows, dots and auto-advance have somewhere to go. Previously the controls were hidden and nothing moved.
* Carousels now auto-advance by default. Pauses while the pointer or keyboard focus is on it and resumes afterwards; respects the visitor's "reduce motion" setting.

= 1.1.3 =
* Carousel: arrows and dots are hidden when every video already fits on screen — previously they showed and did nothing.
* Carousel: navigation rebuilt around card positions (exact landing on a card, LTR and RTL alike), dots now count reachable positions instead of cards, "previous" works from the last position.
* Carousel: mouse drag starts only after a real movement, can no longer get stuck when the release lands on an embedded player, snaps to a card when released, and never opens the lightbox by accident.
* Carousel: arrow keys move the slider when it has focus; sizes are re-measured on resize, on poster load and when a tab or "load more" changes the visible cards.
* The front-end script is excluded from WP Rocket, LiteSpeed, Autoptimize and Cloudflare Rocket Loader delay/defer, which otherwise swallow the first click.

= 1.1.2 =
* Updates now appear without waiting for WordPress' 12-hour refresh: the update is injected every time WordPress reads its update list, not only when it rebuilds it.
* The latest version is read from the github.com/…/releases/latest redirect instead of the REST API, so shared-hosting IPs no longer hit GitHub's 60-requests-per-hour limit (the API stays as a fallback).
* New "Check for updates" link under the plugin row, with a result banner.
* New update status in Settings → General: installed version, latest found, last check and the error if any.
* The plugin is now listed for WordPress' "Enable auto-updates" toggle.
* Cache shortened to one hour (15 minutes after a failed check).

= 1.1.1 =
* Dashboard → Updates → "Check again" now asks GitHub directly instead of waiting out the six-hour cache.
* The settings cache button also clears the update check.

= 1.1.0 =
* Added TikTok: video and photo posts, short links, the official TikTok player and automatic poster/handle resolution through its open oEmbed endpoint.
* Share and short links now fall back to GET when a network refuses HEAD.

= 1.0.0 =
* First release.
