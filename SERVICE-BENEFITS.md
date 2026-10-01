# Avix Digital — Service Benefits

Included in Avix Digital Elementor Widgets **1.10.0**. This update includes the existing widgets, including About Hero.

## Install and place

1. Upload `avix-elementor-widgets-1.10.0.zip` under **WordPress → Plugins → Add New → Upload Plugin**. Choose **Replace current with uploaded** if Avix Digital Elementor Widgets is already installed.
2. Edit the About page with Elementor. Find **Avix Digital → Service Benefits** and drag it below the hero.
3. Use a **Full Width** parent container with width **100%**, zero padding and zero gap. The widget handles its own content width and spacing. It contains no header.
4. Clear the site's page/CDN cache after updating. If needed, use Elementor's regenerate files/data tool.

## Edit content

- Change both eyebrows, the heading and description. Use `[brackets]` for the heading's accent colour and new lines for desktop breaks. Smaller containers reflow the text automatically.
- Add, remove or reorder service cards. Every card has an editable title, description, artwork, alternative text, link label, destination, external-link and nofollow options. An optional per-card hover colour overrides the shared link colour.
- Replace the artwork through the WordPress media library. Bundled artwork has three responsive WebP sizes; library images use WordPress attachment metadata and responsive sources. With the card alternative-text field blank, a library image uses its saved alternative text.
- Text, images and URLs support Elementor dynamic tags. The sources available in the editor depend on your Elementor edition and installed integrations; the widget works with Elementor Free using ordinary controls.
- Edit or hide the team guide and avatar. Leave custom avatar media empty to use the existing Avix pixel character. Custom avatar images remain static.
- The heading tags are selectable; the default structure is H2 followed by H3 service titles.

## Style controls

The Style tab includes section background/gradient, responsive padding, content/intro/description widths, spacing before cards and guide, card gaps, desktop column count, typography, text/accent/link colours, card padding/background/border/shadow/radius, hover border/shadow, artwork background/radius/spacing/aspect ratio/fit, and avatar size.

Unset controls preserve the approved defaults. Fonts are bundled locally with licences under `assets/fonts/`. Responsive typography overrides use Elementor's active breakpoints; structural card layouts adapt to the actual available container width.

Desktop defaults to three equal-height cards with aligned links. Medium-width containers use horizontal cards stacked vertically. Narrow containers use vertical cards in a single column. Desktop column selection does not force multiple columns on tablet or mobile. Longer copy and more cards grow the section instead of clipping it.

## Interaction

- Plain service links reveal an underline and move their arrow slightly on hover. Keyboard focus uses a visible outline and underline. Link targets have a minimum height of 44px.
- A restrained 3px card lift and 2.5% artwork zoom occur on hover-capable devices. These can be disabled under **Content → Team guide & interaction**.
- The built-in avatar greets once when the guide enters view, then briefly on pointer entry or keyboard focus. Each greeting ends after about 1.14 seconds. It does not animate continuously.
- Reduced-motion preferences suppress the lift, zoom, arrow motion and greeting. Changes to that preference clear active greetings.
- Content, links and avatar remain available without JavaScript. Multiple widgets initialize independently; Elementor preview replacements initialize automatically and removed widgets clean up listeners/observers.

## Verification

Tested locally with **WordPress 7.1.2, Elementor 4.3.3 and PHP 8.4.26**. Real Elementor tests cover registration, dynamic tag resolution, repeater fields, default/custom rendering, media metadata, escaped text, safe URLs, heading tags, empty sections and link options.

Chrome and WebKit layout checks cover widths **320, 360, 390, 430, 600, 640, 768, 960, 1024, 1440 and 1920px**. Checks include overflow, loaded artwork/fonts, touch targets, card/link alignment, hover underline, keyboard focus, reduced motion, narrow embedded containers, custom copy, and JavaScript-disabled rendering. Additional checks verify saved responsive style overrides, uploaded artwork, four-card content, per-card colours, custom/no avatar layouts, disabled motion, multiple instances, preview markup replacement and the existing About Hero.

These are browser viewport checks, not physical-device testing. The live site's theme, cache and any third-party Elementor extensions still need a final check after installation.

The actual Elementor editor was also exercised: heading edits, a service repeater edit, and card radius changes updated the preview successfully. The avatar initialized after Elementor's real content replacement and its greeting ended normally.

Development test files, credentials and local WordPress files are excluded from the installable ZIP.
