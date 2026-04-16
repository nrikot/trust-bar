=== Trust Bar ===
Contributors: N Riko Trihendrawan
Tags: logo, trust bar, partner logos, carousel, brand
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

A WordPress plugin to display logo of companies that support the website displayed in a beautiful, configurable logo/trust bar with carousels, logo appearance color modes, shortcodes, Gutenberg block and widget support.

== Description ==

**Trust Bar** lets you create logo strips (trust bars, partner bars, client logos) anywhere on your WordPress site.

= Key Features =

**Logo Management**
* Upload new logo images via the WordPress Media Library
* Auto-trim whitespace borders from logos
* Auto-normalize/crop logos to consistent dimensions (contain, cover, or fill modes)
* Drag-and-drop reordering
* Toggle logos active/inactive without deleting
* Custom title text, alt text, link URL, and link target per logo

**Display Options**
* 1, 2, 3, 4, or 5 rows of logos
* Configurable logo height, max-width, gap, and padding (horizontal + vertical)
* Color modes: Full Color, Grayscale, Monochrome, Dimmed
* Hover effects: Reveal color, Scale up, or None
* Optional heading text with configurable HTML tag
* Optional title label per logo (below, above, or tooltip)
* Background color, border, and border-radius

**Carousel**
* Auto carousel when logos overflow the container
* Configurable auto-play interval and transition duration
* Previous/Next arrows with keyboard accessibility
* Dot navigation
* Pauses on hover and focus
* Fully responsive — recalculates pages on window resize

**Placement**
* **Shortcode**: `[trust_bar id="1"]` — works in any post, page, or text widget
* **Gutenberg Block**: "Trust Bar" block in the Widgets category
* **Classic Widget**: Available in Appearance → Widgets
* **PHP Template Tag**: `<?php echo do_shortcode('[trust_bar id="1"]'); ?>`
* **Multiple Groups**: Create unlimited named groups, each with independent settings

= Shortcode Attributes =

    [trust_bar id="1" color_mode="grayscale" rows="2" carousel="true" heading="Trusted by" class="my-custom-class"]

| Attribute    | Default             | Options                              |
|-------------|---------------------|--------------------------------------|
| id          | 1                   | Group ID from admin                  |
| color_mode  | (group setting)     | full, grayscale, mono, dimmed        |
| rows        | (group setting)     | 1–5                                  |
| carousel    | (group setting)     | true, false                          |
| heading     | (group setting)     | Any text string                      |
| class       | (empty)             | Any CSS class names                  |

== Installation ==

1. Upload the `trust-bar` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Trust Bar** in the admin menu
4. Create a group, upload your logos, configure settings
5. Copy the shortcode or use the Gutenberg block

== Frequently Asked Questions ==

= How do I add the trust bar to a specific page only? =

Use the shortcode `[trust_bar id="1"]` in the page editor. It will only appear on that page.

= Can I display different color modes on different pages? =

Yes! Override via shortcode attribute: `[trust_bar id="1" color_mode="grayscale"]`

= Does it work with page builders? =

Yes — any page builder that supports WordPress shortcodes will work. Most builders also have a "Shortcode" block/element where you can paste `[trust_bar id="1"]`.

= How do I use it in a PHP template? =

```php
echo do_shortcode('[trust_bar id="1"]');
```

Or render directly:
```php
$logos   = Trust_Bar_DB::get_logos(1);
$group   = Trust_Bar_DB::get_group(1);
echo Trust_Bar_Shortcode::render_html($logos, $group->settings, 1);
```

== Changelog ==

= 1.0.0 =
* Initial release

== Screenshots ==

1. Admin panel with logo management and live preview
2. Trust bar in full color mode
3. Trust bar in grayscale mode with hover color reveal
4. Settings panel showing all options
5. Gutenberg block inspector
