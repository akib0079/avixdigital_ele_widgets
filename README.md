# Avix Digital Elementor Widgets

Custom Elementor widgets for avixdigital.com. Tested on WordPress 7.1 + Elementor 4.1.4 (your live version), PHP 8.3.

| Widget | What it is |
| --- | --- |
| **Hero Banner** | The homepage welcome: parallax background with rounded glass pixels and a pixel trail under the cursor, a headline with the tilted orange highlight and round icon, and the Avix pixel character sitting on the headline, waving hello and chilling. |
| **Selected Work Stack** | Sticky scroll stack of case studies. Each card slides over the last one, which tilts back and dims. Includes a live `01 — 03` progress readout and an optional cursor bubble. |
| **Testimonial Stack** | Review slider where each new card drops onto a pile. Real reviews, verifiable Fiverr/Upwork badges, autoplay with a progress bar. |
| **Site Footer** | Rounded footer in dark or light style: big CTA with a tilted pill, brand + socials, two link columns, direct contact with copy-to-clipboard, partner badges. |
| **Process Timeline** | "Our Methodology" section: a line draws itself as you scroll, the Avix pixel character walks it, lights up each step and sits down at the end of the line. |
| **Impact Numbers** | "Numbers behind the work": minimal centered pill + 3 numbers with hairline dividers and a count-up. An optional editorial layout adds a heading and rules. Light, warm-grey or dark theme. |

No jQuery, GSAP or other libraries. Assets load only on pages that use the widget: about 9 KB gzipped for the stack, 4 KB for the numbers.

## Install

1. WordPress → Plugins → Add New → Upload → `dist/avix-elementor-widgets.zip` → Activate.
2. In Elementor, search **Avix** or open the **Avix Digital** category.
3. Put each widget in a **full-width container with 0 padding**. Each widget handles its own content width (1200px) and side spacing.

The files in `preview/` are standalone copies of the rendered output (e.g. `preview/hero.html`), handy for sharing or quick checks.

## Hero Banner

Replaces the HTML hero. Every text, link, image, icon and colour is editable, and text fields take dynamic tags.

- **Headline** is plain text with two shortcuts: `[words]` get the tilted orange highlight and `{icon}` places the round icon (built-in line icons or any Elementor icon). Enter starts a new line. Default: `Leading [Creative]` / `Web Design {icon} Agency.`
- **Pixel avatar**: the Avix character drops onto the headline (on "Leading" by default), waves and says hi in a speech bubble, then chills: legs swinging, watching the cursor.
  - Hover or tap it for the next greeting; hovering or focusing the button makes it say the button line ("Good choice! 🙌").
  - **Greetings** are one per line. The first plays on arrival, the rest follow every 12 s (adjustable, 0 = off) while the banner is on screen, each said once, then it just chills.
  - It sits on the letter tops, whether they're capitals or lowercase, and turns dark on the orange highlight so it stays visible. Pick the word, position, size and height under **Pixel Avatar**.
  - The bubble sits beside its head in the gap under the badge, so it never covers the headline or the badge, and flips sides near the screen edge. Its text is drawn with CSS, so greetings never become part of the H1 that search engines read.
- **Floating cursor tags** sit just outside the first and last line of the headline. A tag that doesn't fit is hidden rather than cut off (e.g. on 1280px laptops). Wide screens only.
- **Welcome badge**: Avix mark, a pulsing "available" dot, any Elementor icon, or none; optional link.
- **Buttons**: the main CTA plus an optional quieter second link (e.g. "See our work" → `#work`).
- **Trust dock**: add, remove or reorder items, each with an icon and an optional proof link (your Fiverr or partner profile).
- **Background**: a real `<img>` with responsive sizes that loads first, since it's the biggest thing on the first screen. Turn off **Load image first** if the banner isn't at the top of the page. Focus point, overlay colours, slow zoom, glass pixels, pixel trail (square size, radius, strength), mouse parallax and the entrance animation are all switchable.
- **Performance**: effects share one animation loop that stops when nothing moves, and everything, CSS animations included, pauses once the banner is scrolled past or the tab is hidden. Measured in Chrome against the HTML version: script time while idle 5.5 → 0.04 ms per second; after scrolling past, 0.
- Reduced-motion visitors get a still banner (the avatar still says hi). The entrance doesn't replay on every change in the Elementor editor. On phones the tags hide, the dock stacks, glass blur is dropped (costly on phone GPUs) and a tap sends a small pixel ripple.

## Selected Work Stack

**Content → Projects**
- *Manual list*: name, image + focus point, tags (comma separated), description, outcome label/value, meta text (year or domain), link, button text. Every field accepts Elementor dynamic tags (ACF, post fields…).
- *Posts / custom post type*: pulls title, featured image, excerpt and permalink. Map extra fields by meta key / ACF field name: outcome, outcome label, external case-study URL (falls back to the permalink) and year. Tags come from any taxonomy.

**Content → Platforms Strip**
- Small "We build with" line under the stack: Shopify, WordPress, Webflow, React, Next.js and Node.js marks in their real brand colours (official Simple Icons shapes). Switch to grey or grey-with-colour-on-hover under Style. Add, remove, reorder or link each platform, or upload your own logo.

**Content → Motion & Behaviour**
- 3D motion on/off, intensity, image parallax, dimming of covered cards.
- Info panel on the right, left or alternating.
- **Fixed header clearance**: defaults to `#wpadminbar, #avix-smart-header`. The stack follows your hide-on-scroll header, dropping below it when it slides in and moving back up when it hides. Add selectors if the header markup changes.
- **Auto-fix parent overflow**: sticky scrolling fails inside containers set to *Overflow: Hidden*. This switches those Elementor containers to `clip`, which looks identical. Theme wrappers are only reported in the browser console.

**Layout rules**
- The widget switches to a stacked card (image on top, text below) when **it** is narrower than 900px. This uses container queries, so it also adapts inside narrow columns, not only on phones.
- Visitors with "reduce motion" enabled get a still, flat stack; on narrow screens the cards simply scroll.

## Impact Numbers

- **Layout**: *Minimal* (default) or *Editorial*. Title and text are optional in both; leave them empty for the minimal look.
- Numbers accept decimals (`5.0`) plus a prefix/suffix; the suffix can be full size (`+`) or small (`/5`).
- The final numbers are in the HTML, so search engines, screen readers and no-JS visitors see real values. The count-up is only visual.

## Testimonial Stack

- Ships with the six reviews already published on avixdigital.com, word for word. Cards are minimal by default (name, line, review); switch on **Slider → Stars & source badge** to add a rating and a "Verified on Fiverr ↗" link per card.
- **Review source** per card: Fiverr, Upwork, or "Verified client" (no platform badge). Only pick Fiverr/Upwork for reviews that actually came from there.
- **Client photo**: only real photos of that client, with permission. Without one the card shows an Avix-branded monogram. Upload 4:5 (1600×2000 works well) and use **Photo focus** if the face gets cropped on phones. **Slider → Client photo style**: *As uploaded* for portraits you've already styled, or *Auto brand look* to turn a normal photo black & white under the orange glow. **Client logo**: shown above the name.
- Autoplay (7s) pauses on hover, keyboard focus, when scrolled off screen or the tab is hidden, and is off for reduced-motion visitors and inside the editor. Visitors can also swipe/drag, use arrow keys, click the progress segments, or pause.

## Site Footer

- Holds everything from the old footer: CTA (Book a Free Audit, Client Portal), logo + description, Services and Navigation links, WhatsApp / call / both emails (with copy buttons), Fiverr, Upwork, Facebook, Instagram, Threads, copyright.
- **Style → Card → Style**: Dark or Light. Each style has its own logo slot (white logo for dark, dark logo for light). Any colour you set in the Style tab overrides the chosen style.
- **Pixel buddy**: the Avix pixel character sits on the headline (on the word "Got" by default), swings its legs, glances around, drops in with a bounce the first time the footer is seen, and waves when the CTA is hovered. Change the word, its position and height under Call to Action; switch it off there too. Still for reduced-motion visitors.
- The oversized wordmark is still available (Wordmark, Badges & Bottom Bar → Big wordmark) but off by default.
- **Link columns** can use custom links or a **WordPress menu** (Appearance → Menus), so pages can be managed without opening Elementor. The link for the page you're on is highlighted in orange.
- **Contact items**: pick a type and phone/WhatsApp/email links are built automatically.
- **Partner badges**: Meta, Google, Shopify and WordPress by default. Add a **Proof link** to each (your public partner directory profile) so visitors can verify it, and use **Zoom** for images with lots of empty space.
- `{year}` in the copyright updates itself every January.
- To use it site-wide: place it in your footer template (ThemeREX Layouts or Elementor Theme Builder) inside a full-width container with no padding.

## Process Timeline

- Replaces the HTML methodology section. Steps are a list: add, remove or reorder them and the line grows one loop per step (3–5 read best).
- The character walks the line while it draws (legs stepping), lights up each step it reaches, and at the end **sits on the end of the line and chills**: legs dangling and swinging, arms resting, glancing around. A small "Let's start yours →" bubble appears next to it (text and link editable, or switch it off).
- On narrow screens the line becomes a straight rail with a short ledge at the bottom for the character to sit on.
- Steps are rendered once for all screen sizes (the old HTML duplicated them for mobile).

## Fonts & colours

The widgets default to the brand system: Space Grotesk for display, Inter for text, `#FB6007` accent, `#1A1A1A` ink, and a darker `#C64700` for small orange text so it stays readable on white. The live site already loads both fonts. Every colour and font can be overridden in each widget's Style tab.

## Structure

```
avix-elementor-widgets/
├── avix-elementor-widgets.php          bootstrap: checks Elementor, registers category, widgets, assets
├── includes/widgets/class-<slug>.php   one class per widget: hero, selected-work, impact-numbers,
│                                       testimonial-stack, site-footer, process-timeline
└── assets/
    ├── css/<slug>.css
    └── js/<slug>.js
```

To add the next section (logos, CEO quote, pricing, marquee, reviews): add a widget class under `includes/widgets/`, its CSS/JS under `assets/` with the same slug, and one line in the `$widgets` list in `avix-elementor-widgets.php`.
