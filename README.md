# Avix Digital Elementor Widgets

Custom Elementor widgets for avixdigital.com. Tested on WordPress 7.1 + Elementor 4.1.4 (your live version), PHP 8.3.

| Widget | What it is |
| --- | --- |
| **Hero Banner** | The homepage welcome: parallax background with rounded glass pixels and a pixel trail under the cursor, a headline with the tilted orange highlight and round icon, and the Avix pixel character sitting on the headline, waving hello and chilling. |
| **Services Showcase** | Service list that expands on hover or tap, a 3D preview card in a browser frame that swaps per service, and the Avix pixel character flying on rocket boots to the service being viewed. |
| **Selected Work Stack** | Sticky scroll stack of case studies. Each card slides over the last one, which tilts back and dims. Includes a live `01 — 03` progress readout and an optional cursor bubble. |
| **Testimonial Stack** | Review slider where each new card drops onto a pile. Real reviews, verifiable Fiverr/Upwork badges, autoplay with a progress bar. |
| **Site Footer** | Rounded footer in dark or light style: big CTA with a tilted pill, brand + socials, two link columns, direct contact with copy-to-clipboard, partner badges. |
| **Process Timeline** | "Our Methodology" section: a line draws itself as you scroll, the Avix pixel character walks it, lights up each step and sits down at the end of the line. A team row under the title puts real faces next to the process. |
| **FAQ & Quote** | Minimal FAQ accordion for just above the footer, with FAQPage structured data and a small "Request a quote" card the pixel character sits on. |
| **Compare & CEO Quote** | "Same budget. Better outcome.": a compact typical-agency-vs-Avix face-off and the CEO quote card. Pixel Akib flies between them on rocket boots, zaps the old habits, cheers the Avix side and perches on the real photo. |
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
- **Background**: a real `<img>` with responsive sizes that loads first, since it's the biggest thing on the first screen. Turn off **Load image first** if the banner isn't at the top of the page. An image given only as a URL is matched to your media library, so it still gets responsive sizes. Focus point, overlay colours, slow zoom, glass pixels, pixel trail (square size, radius, strength), mouse parallax and the entrance animation are all switchable.
- **Performance**: effects share one animation loop that stops when nothing moves, and everything, CSS animations included, pauses once the banner is scrolled past or the tab is hidden. Measured in Chrome against the HTML version: script time while idle 5.5 → 0.04 ms per second; after scrolling past, 0. Since 1.3.0 the character's glow pulses on the GPU, which cut the remaining idle main-thread work from about 32 to under 1 ms per second.
- Reduced-motion visitors get a still banner (the avatar still says hi). The entrance doesn't replay on every change in the Elementor editor. On phones the tags hide, the dock stacks, glass blur is dropped (costly on phone GPUs) and a tap sends a small pixel ripple.

## Services Showcase

Replaces the HTML services section.

- **Services** come from a manual list (name, description, link + link text, preview image, optional card label) or straight from a post type: your Services pages if the theme registers them (picked automatically), using title, excerpt, featured image and permalink. `[words]` in a description or the section title are highlighted orange.
- **Pixel guide**: the Avix character hovers in the left rail on flickering rocket boots and points at the open service. When another opens it flies there on a spring: it stretches with speed, thrusts on the way up, lifts its arms on the way down, trails exhaust sparks and afterimages, bounces once and lands with a puff. The rail lights up behind it. Switch the boots or the whole guide off under **Pixel Avatar**.
- **Names** are outlined when closed; the open one fills with white ink from left to right, with the ↗ riding on its last word. Choose *Dimmed text* instead of outlines under Style.
- **Preview card**: tilts toward the mouse with a light glare, swaps up or down depending on direction, and shows the page address in a browser frame (or a white border, or no frame). Missing images get a branded placeholder.
- **Opening**: hover on desktop (a short hover-intent delay means a mouse passing over doesn't flicker through every row), tap on touch screens, or click only. Arrow keys, Home and End move between services; each name is a real button inside a heading, with `aria-expanded`, and closed panels are skipped by Tab. Without JavaScript every description stays open.
- **Stable height**: the list reserves room for the longest description, so the page never jumps while visitors hover through the services, and the card stays put.
- **Phones & tablets**: the preview moves inside the open service (beside the text on tablets, above it on phones), the opened service is kept in view, and the guide keeps flying in a narrower rail.
- **Performance**: no blur filters; the background glows only move with `transform`. One animation loop runs only while the avatar flies or the card tilts, and everything pauses when the section is off screen. In a test browser without GPU acceleration the HTML version rendered at 22 fps; this one holds 60.
- **Images** load lazily with responsive sizes; each one is downloaded once, and hidden copies are never fetched. Images given only as a URL (like the defaults) are matched to your media library, so phones still get a smaller copy instead of the 2048px original.

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
- **Width matches the header** (Style → Card → *Content width*, default *Match the site header*): the footer measures where your header's logo starts and its last button ends, and lines its own logo and right-hand content up with them, at every screen size. It re-checks on resize and when the footer scrolls into view (for headers that shrink on scroll). The header is found by *Header selector* (`#avix-smart-header` by default), with common theme headers as a fallback; if none is found it uses *Max width*. Choose *Fixed max width* for the old boxed look.
- **Performance** (1.4.0): the character's glow pulses on the GPU instead of repainting every frame, and its loops pause off screen. Idle main-thread time while the footer is visible: about 36 → under 1 ms per second, on every page.

## Process Timeline

- Replaces the HTML methodology section. Steps are a list: add, remove or reorder them and the line grows one loop per step (3–5 read best).
- The character walks the line while it draws (legs stepping), lights up each step it reaches, and at the end **sits on the end of the line and chills**: legs dangling and swinging, arms resting, glancing around. A small "Let's start yours →" bubble appears next to it (text and link editable, or switch it off).
- On narrow screens the line becomes a straight rail with a short ledge at the bottom for the character to sit on.
- Steps are rendered once for all screen sizes (the old HTML duplicated them for mobile).
- **Title highlight**: wrap words in `[square brackets]` to colour them (default: `[high-converting]`). Colour under Style → *Title [highlight]*. The word-by-word reveal keeps the highlight.
- **Team row** (Content → **Team**): add each person's photo, name, role and an optional profile link (LinkedIn or a bio page). The row appears under the title once a member is added; in the editor, dashed placeholders show where the photos go.
  - Names and roles show in a tooltip on hover, or always under the photos (*Names & roles*). The image alt text always carries them.
  - No photo yet? The person's initials are shown instead.
  - The Avix pixel character gets its own tile in the row: standing on a little orange ledge, it waves every few seconds and waves non-stop when hovered. Switch it off under *Pixel character tile*.
  - Short text beside the photos plus an optional link (e.g. "Meet the team" → About page).
  - **SEO**: Person structured data (JSON-LD) for each named member: name, role, photo, profile link and your site as employer. Photos load lazily with responsive sizes.
- **Performance**: the character's breathing glow now pulses on the GPU instead of repainting every frame (idle main-thread time on screen: about 32 → 1 ms per second), and all loops pause once the section is off screen.

## FAQ & Quote

- Put it **just above the footer** in a full-width container with no padding. Dark or light style (Style → Section → *Style*), and every colour can be overridden.
- **Questions** are a list: question, answer (rich text, so links to your service pages work) and an optional **Anchor**. Linking to `/page/#pricing` opens that answer and scrolls to it. Without an anchor, one is made from the question (e.g. `#faq-how-much-does-a-project-cost`).
- Built on native `<details>`: works without JavaScript, answers stay in the HTML for search engines, and the browser's find-in-page opens the matching answer. One answer open at a time (switchable), the first open by default, smooth open/close in browsers that support it.
- **SEO**: section heading (H2), each question an H3 (changeable), and FAQPage structured data printed once per page. Honest caveat: since 2023 Google only shows FAQ rich results for well-known government and health sites, so don't expect the dropdowns in Google results. The markup still helps Bing and AI answer engines understand the page, and the content itself ranks like any other text.
- **Quote card**: title, one line of text, a **Request a quote** button (full width on phones) and small print. The default copy ("Still have questions?") is deliberately different from the footer's CTA so the two don't repeat each other. Leave any text empty to hide it, or switch the whole card off. On wide screens the card stays in view while visitors read long answers.
- **Pixel character** sits on the card's top edge, legs dangling over it, glancing around; it waves when the card is hovered or the button is focused. It pops in the first time the section is seen. Still for reduced-motion visitors.
- Default questions are general agency questions (pricing, timelines, Shopify Plus/Webflow, migrations, support). Edit them to match how you actually work.

## Compare & CEO Quote

- Replaces the CEO quote section and adds a compact comparison above it. Title takes `[accent]` words and line breaks (default `Same budget.` / `[Better outcome.]`).
- **Comparison** (Content → Comparison): column names and rows, each row one typical-agency habit facing your answer. On wide screens the two sides face each other across a centre seam with a VS badge; on phones each row stacks (struck-through habit, then the Avix line) and the seam becomes a rail on the left.
- **Pixel Akib** (Content → Pixel Akib), the same pixel alter ego that says "Hi, I'm Akib" in the hero, now in cream rocket boots:
  - Sits on the VS badge, legs swinging, watching the cursor.
  - The first time the comparison is seen it flies to the first row, zaps it and comes back to explain: "Hover a row. I'll fix it." (or "Tap a row" on touch screens).
  - Hover (or tap) any row: it rockets over on a spring (boots firing, body tilting and stretching, exhaust sparks), blasts the typical-agency habit (beam, sparks, the line gets struck through) then turns and cheers the Avix side as its ✓ pops. Fixed rows stay fixed.
  - Fix every row and it celebrates: a flip, a burst of pixels and "All fixed. Same budget, zero compromises."
  - Scroll down to the quote and it flies down to sit on your photo: "That's me! The high-res version." Scroll back up and it returns to the badge.
  - Click it (or focus it and press Enter) for a line; every fourth click it does a flip. All lines are editable.
- **CEO quote**: photo, quote (with `[accent]` words), name, role and an optional profile link (LinkedIn/About). Animated GIFs are shown as uploaded: WordPress's resized copies of a GIF are usually still images, so the widget never swaps them in.
- **Tip: use a video instead of the GIF.** Add an MP4/WebM of the same loop under *Looping video*: it's typically 5–10× smaller than a GIF, only downloads and plays while on screen, and uses the photo as its poster.
- **SEO & accessibility**: rows are a real list, and each reads as "The typical agency: … Avix Digital: …" to screen readers and search engines. The quote is a `figure` with `blockquote` and `cite`. Person structured data for the CEO shares its `@id` with the Process Timeline team row, so search engines see one person. The character is a labelled button; its lines are announced politely. Without JavaScript everything is visible and the character stays hidden.
- **Performance**: one spring animation loop that only runs while it moves, no scroll-driven work except a cheap check of where the quote is, idle fidgets driven by a timer twice a second instead of CSS loops (measured: 1–3 ms per second of main-thread time while visible), and everything pauses off screen. Reduced-motion visitors get instant moves, no flight, no tour.

## After updating the plugin: clear the caches

Your site strips `?ver=` from asset URLs (a theme or optimiser setting), and Hostinger's CDN caches CSS/JS for 7 days. Since v1.4.1 the plugin adds its own `?avixv=` stamp that changes whenever a file changes, so updates reach visitors. After uploading a new version, still purge the cache once: hPanel → Websites → your site → Performance → CDN → **Flush cache** (and your WordPress cache plugin, if any).

## Fonts & colours

The widgets default to the brand system: Space Grotesk for display, Inter for text, `#FB6007` accent, `#1A1A1A` ink, and a darker `#C64700` for small orange text so it stays readable on white. The live site already loads both fonts. Every colour and font can be overridden in each widget's Style tab.

## Structure

```
avix-elementor-widgets/
├── avix-elementor-widgets.php          bootstrap: checks Elementor, registers category, widgets, assets
├── includes/trait-media.php            shared helper: matches URL-only images to the media library
├── includes/widgets/class-<slug>.php   one class per widget: hero, services, selected-work,
│                                       impact-numbers, testimonial-stack, site-footer, process-timeline, faq,
│                                       compare-quote
└── assets/
    ├── css/<slug>.css
    └── js/<slug>.js
```

To add the next section (logos, CEO quote, pricing, marquee, reviews): add a widget class under `includes/widgets/`, its CSS/JS under `assets/` with the same slug, and one line in the `$widgets` list in `avix-elementor-widgets.php`.
