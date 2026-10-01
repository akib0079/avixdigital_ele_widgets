# Avix Digital — service benefits (HTML review)

Open `index.html` in a browser. For a local server, run `node serve.cjs` from this folder and open http://127.0.0.1:4175. The workspace preview is also available at http://127.0.0.1:4173/service-benefits/.

This is the HTML design stage. The existing Elementor plugin and its completed About Hero widget are unchanged. After design review, this section can be converted into an editable Elementor widget.

## Design and content

The supplied reference provides the centred introduction, three bordered service cards, image panels, short descriptions and service links. Avix's Space Grotesk and Inter fonts, dark ink and orange accent connect the section to the completed About hero.

The cards cover Shopify & e-commerce, web development, and UI/UX & brand design. The copy describes customer benefits and uses service terms naturally, without ranking or revenue promises. It follows the published capabilities on these pages:

- [Shopify development](https://avixdigital.com/service/shopify-plus/): custom Shopify/Shopify Plus stores, product discovery and checkout.
- [Web development](https://avixdigital.com/service/web-development/): Webflow, WordPress and custom builds, responsive layouts and technical SEO.
- [UI/UX & branding](https://avixdigital.com/service/uiux-and-brand-design/): user journeys and visual identity.

The links are ordinary text links with an animated underline on hover and keyboard focus. Touch targets have a 44px minimum height. Desktop cards share a baseline; tablet layouts become image-and-text rows, and phones stack the cards vertically.

The orange pixel character reuses the exact 10-unit SVG geometry from the existing Hero widget. It sits beside a helpful contact prompt and waves briefly when it enters view or the prompt receives interaction. It does not animate continuously. Reduced motion suppresses the wave and hover movement. All content and links work without JavaScript.

## Supplied artwork

- Shopify notification: https://avixdigital.com/wp-content/uploads/2026/10/ChatGPT-Image-Sep-29-2026-01_52_11-PM-3.png
- Performance illustration: https://avixdigital.com/wp-content/uploads/2026/10/ChatGPT-Image-Sep-29-2026-01_52_10-PM-2.png
- Interface icons: https://avixdigital.com/wp-content/uploads/2026/10/ChatGPT-Image-Sep-29-2026-01_52_09-PM-1.png

The artwork is preserved without cropping or redesign. Responsive WebP delivery files at 384, 768 and 1152px reduce transfer size. The three 768px files total about 30KB, compared with about 2.39MB for the supplied PNG files. Images use intrinsic dimensions, lazy loading and asynchronous decoding; local variable fonts and their licences are included.

## Implementation

- `index.html`: semantic H2/H3 structure, service content, native links and the existing character SVG.
- `service-benefits.css`: styles scoped to `.avix-benefits`, CSS tokens, container queries, visible focus and reduced-motion support.
- `service-benefits.js`: the finite character greeting; no runtime dependencies.
- `assets/`: responsive illustrations and self-hosted fonts.

The small page-level body reset and metadata in the preview are for standalone display. Only the section markup and scoped assets are needed for the eventual widget. Multiple widget instances will receive unique heading IDs and editor lifecycle handling during conversion.

## Verification

`verification.json` records layout checks in Chrome and WebKit at 320, 360, 390, 430, 600, 640, 768, 960, 1024, 1440 and 1920px widths. The checks include no horizontal overflow, loaded images/fonts, equal desktop card heights and link baselines, heading hierarchy, touch targets, underline hover, keyboard focus styling, correct destinations, finite greeting, reduced motion, narrow 360/720px embedded containers, longer copy/larger type and JavaScript-disabled content.

Desktop, tablet and phone screenshots are included. These are browser viewport tests; physical-device and live Elementor/theme verification belong to the conversion stage. No live site changes have been published.
