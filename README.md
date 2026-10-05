# Avix Digital Elementor Widgets

Custom Elementor widgets for [avixdigital.com](https://avixdigital.com). Current release: **1.11.0**.

## Install

1. Download [avix-elementor-widgets-1.11.0.zip](dist/avix-elementor-widgets-1.11.0.zip).
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

See [About Hero](ABOUT-HERO.md) and [Service Benefits](SERVICE-BENEFITS.md) for content, dynamic tags, styling controls and interaction details. Available dynamic tag sources depend on the Elementor edition and installed integrations.

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
