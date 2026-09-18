# Ofnoa Social Embed

Embed Instagram and Facebook videos in WordPress — grids, tabs, carousels, spotlights and stories bars that look designed rather than bolted on.

Works as a **Gutenberg block**, a **classic widget**, an **Elementor widget** and a **shortcode**. No API keys required.

![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue) ![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b) ![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)

---

## What it does

Paste Instagram and Facebook links into a small library, group them into collections, and drop a gallery anywhere. Playback happens in a lightbox, inline in the card, or on the original network — your choice, per gallery.

### Layouts

| Layout | What it is |
| --- | --- |
| `grid` | Responsive grid with independent desktop / tablet / mobile column counts |
| `masonry` | CSS-column masonry, native heights |
| `carousel` | Scroll-snap slider with arrows, dots, autoplay, loop and pointer drag |
| `tabs` | Collections become tabs, filtered client-side with re-staggered animation |
| `spotlight` | First video takes a 2×2 hero, the rest fill in around it |
| `reels` | Horizontal reels row, centre-snapped |
| `stories` | Circular stories bar with a gradient ring |
| `list` | Thumbnail beside text |

### Card styles

`glass` · `neon` · `minimal` · `gradient` · `elevated` · `outline` · `polaroid` · `cinematic`

### Hover effects

`zoom` · `lift` · `tilt` (3D) · `glow` · `reveal` · `slide` · `blur siblings` · `none`

### Everything else that is exposed

Columns per breakpoint, gap, aspect ratio (9:16 / 4:5 / 1:1 / 16:9 / auto), max width, alignment, corner radius, border width and colour, padding, shadow preset, overlay mode and strength, accent and secondary accent, card background, title / meta / overlay / section colours, colour scheme (auto / light / dark), title size, weight and line clamp, meta size, font family, every element toggle (title, caption, handle, date, platform badge and its corner, play button, duration chip, view and like counts, filter bar, arrows, dots, load-more), play mode, autoplay and its interval, loop, lazy loading, transition speed, entrance animation and stagger, plus a custom CSS class.

Set site-wide defaults once under **Social Embed → Settings → Design defaults**; every gallery starts from them and can override any single value.

---

## Installation

**From a release**

1. Download `ofnoa-social-embed.zip` from the [latest release](../../releases/latest).
2. *Plugins → Add New → Upload Plugin* → choose the zip → **Install Now** → **Activate**.

**From source**

```bash
git clone https://github.com/lirish1973/ofnoa-social-embed.git
# copy the folder into wp-content/plugins/
```

Once active, the plugin checks this repository for new releases and offers them in the normal Plugins screen — no extra update plugin needed. Turn it off under *Settings → General* if you would rather not.

---

## Usage

### 1. Add videos

**Social Embed → Add video**, paste the link, save. Accepted formats:

```
https://www.instagram.com/reel/XXXXXXXXXXX/
https://www.instagram.com/p/XXXXXXXXXXX/
https://www.instagram.com/tv/XXXXXXXXXXX/
https://www.facebook.com/watch/?v=123456789
https://www.facebook.com/reel/123456789
https://www.facebook.com/PageName/videos/123456789/
https://fb.watch/XXXXXXXX/
```

**Social Embed → Bulk import** takes a whole list at once and can drop them all into a collection.

### 2. Place a gallery

**Block** — search for *Social Video Gallery*.
**Widget** — *Appearance → Widgets*, add *Social Video Gallery*.
**Elementor** — search for *Social Video Gallery* in the panel.
**Shortcode:**

```
[ofnoa_social_embed]

[ofnoa_social_embed layout="tabs" columns="4" card_style="glass" aspect="9-16" hover_effect="tilt"]

[ofnoa_social_embed layout="carousel" collection="reels,promos" autoplay="yes" autoplay_speed="5000"]

[ofnoa_social_embed source="urls" urls="https://www.instagram.com/reel/AAAA/|https://www.facebook.com/watch/?v=123"]
```

Separate multiple URLs with `|`. A full attribute reference, generated from the live schema, lives under **Settings → How to use**.

---

## How playback works

No credentials are involved. Instagram plays through `instagram.com/<type>/<id>/embed/` and Facebook through the public `plugins/video.php` player. That is why the plugin keeps working when tokens expire.

Posters are resolved best-effort, in this order:

1. The video's featured image (always wins — set it for full control).
2. A manually entered poster URL.
3. An automatic fetch: official oEmbed if you supplied a Meta app, otherwise the public embed page's Open Graph image.
4. If nothing resolves, the card lazily mounts the real embed so a genuine frame still appears.

Results are cached in transients for a configurable window, and **Settings → General** has a one-click cache flush.

### Privacy mode

Switch on *Consent before loading players* and no Instagram or Facebook iframe is requested until the visitor presses play. The gallery itself is rendered entirely from your own server.

---

## Developer notes

Every control lives in one schema, `OSE_Helpers::schema()`. The block panel, widget form, Elementor panel, settings defaults, sanitisation and the documentation table are all generated from it, so a new control appears in all four integrations at once.

```php
// Add your own control everywhere.
add_filter( 'ose_control_schema', function ( $schema ) {
	$schema['ribbon_text'] = array(
		'group'   => 'elements',
		'label'   => 'Ribbon text',
		'type'    => 'text',
		'default' => '',
	);
	return $schema;
} );

// Change the resolved items before render.
add_filter( 'ose_items', function ( $items, $atts ) {
	return $items;
}, 10, 2 );
```

Render a gallery from your own template:

```php
echo OSE_Render::gallery( array(
	'layout'     => 'spotlight',
	'collection' => 'campaign-q4',
	'columns'    => 4,
) );
```

REST (requires `edit_posts`): `GET /wp-json/ose/v1/resolve?url=…` and `GET /wp-json/ose/v1/collections`.

### Releasing

Bump `Version:` in `ofnoa-social-embed.php` (and `OSE_VERSION`, and `Stable tag` in `readme.txt`), push to `main`. The workflow lints every PHP file, builds `ofnoa-social-embed.zip`, tags `vX.Y.Z` and publishes the release. Installed sites see the update within six hours, or immediately after a cache flush.

---

<div dir="rtl">

## בעברית

תוסף שמטמיע סרטוני אינסטגרם ופייסבוק בוורדפרס בתצוגה מעוצבת ומודרנית — גריד, כרטיסיות, קרוסלה, מייסונרי, ספוטלייט, שורת רילס וסטוריז.

**איך מתחילים**

1. מתקינים ומפעילים את התוסף.
2. **Social Embed ← Add video** — מדביקים קישור לריל או לסרטון פייסבוק ושומרים. לרשימה שלמה בבת אחת: **Bulk import**.
3. מוסיפים גלריה בבלוק *Social Video Gallery*, בווידג'ט, באלמנטור או עם השורטקוד `[ofnoa_social_embed]`.

**מה פתוח לעיצוב** — הכול: מספר עמודות בנפרד לדסקטופ/טאבלט/מובייל, מרווחים, יחס תמונה, פינות, מסגרות, צללים, שכבת אוברליי ועוצמתה, צבעי מותג, ערכת צבעים בהירה/כהה/אוטומטית, גופנים וגדלים, אפקטי hover, אנימציות כניסה, וכל רכיב בכרטיס ניתן לכיבוי או הדלקה.

**תמיכה בעברית ו-RTL** — הפריסה בנויה על תכונות לוגיות, והחצים, כפתור ההפעלה והלייטבוקס מתהפכים נכון באתר בעברית.

**בלי מפתחות API** — ההפעלה עוברת דרך נגני ההטמעה הציבוריים של אינסטגרם ופייסבוק, ולכן שום טוקן לא פג ושובר את הגלריה. פרטי אפליקציית Meta הם אופציונליים לחלוטין ומשמשים רק לשיפור משיכת התמונות הממוזערות.

**מצב פרטיות** — אפשר להפעיל מצב שבו שום iframe של מטא לא נטען עד שהגולש לוחץ Play.

**עדכונים** — התוסף בודק את הריפו הציבורי הזה ומציג עדכון במסך התוספים הרגיל של וורדפרס.

</div>

---

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

Instagram and Facebook are trademarks of Meta Platforms, Inc. This plugin is not affiliated with, endorsed by or sponsored by Meta.
