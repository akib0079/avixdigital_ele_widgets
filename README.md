# Avix Digital Elementor Widgets

Custom Elementor widgets for [avixdigital.com](https://avixdigital.com). Current release: **1.15.2**.

## Install

1. Download [avix-elementor-widgets-1.15.2.zip](dist/avix-elementor-widgets-1.15.2.zip).
2. In WordPress, use **Plugins → Add New → Upload Plugin**. Replace the existing Avix Digital plugin if it is already installed, then activate it.
3. In Elementor, open the **Avix Digital** category and add a widget to a full-width container with zero padding.

Requires WordPress 6.2+, PHP 7.4+, and Elementor 3.20+. The About Hero and Service Benefits were verified locally with WordPress 7.1.2, Elementor 4.3.3, and PHP 8.4.26.

## Widgets

| Widget | Purpose |
| --- | --- |
| About Hero | Full-width about-page introduction, booking CTA, white brand tile and expertise marquee with check badges. *Clear the fixed header* (on by default) starts it below the Smart Header. |
| Service Benefits | Editable service cards, responsive artwork, plain underline links and the Avix avatar guide. |
| Hero Banner | Homepage introduction and pixel character interactions. |
| Services Showcase | Service presentation. |
| Selected Work Stack | Portfolio cards that stack on scroll. |
| Impact Numbers | Business metrics and count-up animation. |
| Testimonial Stack | Customer review slider. |
| Site Footer | Brand, contact, navigation and CTA. |
| Process Timeline | Project process and animated character. |
| FAQ & Quote | Questions and consultation content. |
| Compare & CEO Quote | Service comparison and CEO message. |
| Client Logos | Client brand presentation. |
| Intro Text | Introductory copy. |
| Smart Header | Site navigation. One **Light mode** switch per header: off for dark heroes (white text, white logo, white notch), on for light pages (dark text, dark logo, dark notch). The notch is a single seamless shape, and the pixel character sits on it saying hi. |
| Team | Portrait cards for the people behind the projects, with a playful tilt on hover, and the pixel character's own card: it looks at whoever is hovered and jumps when its card is hovered. |
| Page Hero | Inner-page hero (Services and the service pages): optional parent breadcrumb (Home › Services › Shopify Plus) and a stat chip on the image in a rounded stage, light or dark, with breadcrumb, two buttons, a proof row and an orbit of platform logos around the pixel character, which looks at whichever logo is hovered. Clears the fixed header. Optional Service structured data (JSON-LD). |
| Service Index | Numbered service rows with platform icons and tags. On desktop a preview image follows the cursor with the pixel character riding on it; on phones the rows become image cards. |
| Story | Manifesto statement whose words fill in as you scroll, with inline image pills, plus a wide photo with a stats panel the pixel character sits on. |
| Founder | Founder note with photo, quote, badges and an accessible video modal (YouTube, Vimeo or MP4, loaded only on play). The pixel character peeks over the photo. |
| Values | "How we work" principles in a bento grid with pixel-art icons. The pixel character hops to whichever card is hovered. |
| Journey | Milestones on a track the pixel character walks along as you scroll, lighting each milestone and planting a flag at the end. Vertical on phones. |
| Careers | Hiring card with perks, optional open roles and a "We're hiring" sign held by the pixel character. |
| Post Grid | Blog listing: header, featured post, category filters, cards with reading time, an in-grid call to action and AJAX "Load more" (works without JavaScript too). |
| Service Tabs | Capability tabs for service pages: accessible tab bar, panels with a checklist, button and image, and the pixel character walking to the chosen tab. Works without JavaScript (panels stack). |
| Ticker | Orange keyword band that loops seamlessly, pauses on hover or with its button, and lists the words for screen readers. The pixel character rides on the band. |
| Case Study Hero | Dark top of a case study: breadcrumb, client logo, headline, facts bar (client, services, platform, year, role, live site) and either a studio render or the live homepage in browser and phone frames. Reads everything from the case study. |
| Case Study Chapter | The challenge, approach and "what we built" chapters, with a sticky label, constraint chips, numbered implementation cards and jump links to every feature. |
| Feature Spotlight | One feature per widget: a real screenshot of the live site in a device frame with numbered pins, callouts, a legend, the problem and what was built, and a full-size lightbox. |
| Case Study Results | Dark impact section: outcome statement, pillars, sourced metrics (a metric without a source never shows), "proof you can check" links and an optional real quote. |
| Case Study Gallery | Mosaic of studio renders and framed desktop and phone screenshots; a swipeable carousel on phones. |
| Case Study Stack | Services and technology used, with observed tools marked as such. |
| Case Study Next | Closing call to action (the only pixel character on case-study pages) and the next case study card. |
| Case Study Grid | The /case-studies/ index: featured card, panel cards, service filters and AJAX "Load more" (works without JavaScript too). |

See [About Hero](ABOUT-HERO.md) and [Service Benefits](SERVICE-BENEFITS.md) for content, dynamic tags, styling controls and interaction details. Available dynamic tag sources depend on the Elementor edition and installed integrations.

## Changelog

### 1.15.2

- Two new case studies: Products for Home and Kampeerwinkel Roermond, first on the index.
- Hero facts bar: columns never shrink below their content, so the website link is no longer cut off at 1025–1280px.
- Next card: client names with one long word step down a size instead of breaking mid-word.
- Results: neutral "The build in numbers" eyebrow and a footnote that fits every source.
- Index intro mentions all five projects.

### 1.15.1

Case-study polish after the first live QA, plus two new case studies.

- Case Study Hero: the client logo now sits above the title, the hero spans the full width with the header's side margins, and a WebGL shader in the brand orange moves behind it and follows the pointer (a static frame for reduced motion, paused off screen, falls back to the CSS glow). The H1 shows from the first paint, so it no longer delays LCP.
- Every case-study widget has a working dark/light Theme switch in the editor; colour pickers start empty so the theme applies. The Results section is light by default, the Next card has a light panel option, and the Grid has a Card panel select (Auto, Light, Dark).
- Gallery: frames no longer collapse under themes that style `figure` as a flex column; below-the-fold images stay lazy.
- SEO: Yoast focus keyphrases from the data files, client names in the H1s, refreshed titles and descriptions, the theme's duplicate Open Graph block removed when Yoast is active.
- New case studies: Products for Home (WordPress, WooCommerce and Bricks with a FunnelKit checkout funnel) and Kampeerwinkel Roermond (Shopify).

### 1.15.0

Case Studies. Existing pages are unchanged; the new code only runs on case studies and the case-studies index.

- New **Case Studies** post type (`/case-studies/<slug>/`) with a "Case study details" meta box (overview, facts, story, results, stack), service and industry terms, admin columns and a **Settings & import** page.
- New posts start from a starter layout (hero, three chapters, three spotlights, results, gallery, stack, next). Every widget fills itself from the case study, so a new case study only needs its details, screenshots and hotspots.
- Eight new widgets (see the table). The pixel character appears once per case study, on the closing call to action; the header and footer characters are hidden on case-study pages only.
- SEO: Yoast breadcrumbs include the index page, a `CreativeWork` schema piece per case study and an `ItemList` on the index; a fallback JSON-LD prints when Yoast is not active.
- Importer for the Rehall, Ovabalance and Flow Storage case studies (content from the portfolio and the live sites) and the index page.
- Service Tabs: the default Shopify intro now links to the internal case studies.
- Owner follow-up: the defaults of the home Selected Work and Client Logos widgets still point to the portfolio; their saved links on the live pages were updated.

### 1.14.0

Dark service-page heroes and service structured data. Both are opt-in: the home page widgets are unchanged, and pages that do not use the new settings render exactly as before.

- Page Hero: new image frame **Product render, dark (orange glow)** for dark device renders on the dark full-width stage. The frame gets a warm near-black fill, an orange glow under and around it and a faint top-lit hairline; the pause button becomes a glass circle with a white icon so it stays visible on a dark render. The stat chip stays a white card and the layout is the same as the Product render frame.
- Page Hero: new **Structured data → Service schema** switch (off by default). It prints one schema.org `Service` per page (`@id` = page URL + `#service`) with name, service type, description, URL and areas served, provided by the site's `#organization` (the Organization Yoast outputs). Name and description default to the headline (without the brackets) and the lead; areas served default to European Union, United Kingdom and United States, one per line. Not printed in the editor.

### 1.13.2

- Service Index: when lined up with the header, the title size follows the width a little more closely (31px on the narrowest phones), so "Explore other services" stays on one line from 360 to 1920px.

### 1.13.1

Fixes from the live QA of the four service pages. The home page widgets are unchanged, and pages that do not use the new settings render exactly as before.

- Page Hero: the image figure no longer inherits the theme's `figure { overflow: hidden }`, so the stat chip, its seal, both label lines and the character above the frame show in full. Before the header script runs, the stage clears the real header height (106/91px with the notch), so it no longer moves down after load. On stacked tablets the image starts on the copy's edge. New colour control: Headline [highlight].
- Focus rings: Page Hero, Service Tabs, Values, Journey, Ticker and Service Index restate their 2px rings with `!important`, because the theme's `body.show_outline` rule replaced them with a 1px dotted outline. A mouse click shows no ring. The Ticker's pause button gets a two-tone ring that stays visible on the orange band.
- New "Side margins → Line up with the header" setting on Page Hero, Values, Journey and Service Index. Service Tabs always uses it: content starts on the header logo's line (5% of the width) at every width.
- Journey: in the centred layout the vertical track (rail and cards) is centred as one block.
- Values: in the side layout the watermark stays inside the text column and is hidden where the column stacks. The Padding control accepts custom values.
- Service Index: a spanning odd card fills its image to the card height. The title uses a fluid size capped at 60px when lined up with the header. New colour control: Title [highlight].
- Service Tabs and Service Index images stay `loading="lazy"` even when the site filters attachment images to eager.

## Project structure

The current plugin source is at the repository root. The older nested plugin source has been consolidated into this layout; its commit history is preserved.

```text
avix-elementor-widgets.php   Plugin bootstrap and widget registry
includes/                   Widget classes and shared helpers
assets/                     Scoped CSS, JavaScript, artwork and licensed fonts
dist/                       Installable release ZIPs
previews/                   Current standalone HTML previews and verification assets
preview/                    Earlier standalone previews retained from the repository
tests/                      Integration/browser scripts, screenshots and reports
```

The ZIP packages only plugin files, assets and widget guides. Local WordPress dependencies, credentials, databases, logs and machine-specific PHP configuration are excluded from Git.

## Preview and verification

Run `node previews/serve.cjs` and open `http://127.0.0.1:4173/`. The default route shows Service Benefits; About Hero is at `/about-hero/`.

Service Benefits passed Chrome and WebKit checks at 11 widths from 320 to 1920px, including overflow, image/font loading, keyboard focus, hover underline, reduced motion, touch targets, embedded containers and JavaScript-disabled content. Actual Elementor editor checks confirmed heading, repeater and style updates, plus avatar initialization after content replacement. Saved custom settings and the existing About Hero were also checked.

The scripts under `tests/` use the development machine's runtime paths and a disposable local WordPress installation; they are retained as verification sources, not a portable one-command test setup. The local installation and its credentials are not included. A final check with the live site's theme and caches is needed after installation.

## Release packaging

With PHP's Zip extension enabled, run `php tests/package-plugin.php` to rebuild the 1.10.0 installable archive. The package script verifies expected widget files and excludes development installations and credentials.
