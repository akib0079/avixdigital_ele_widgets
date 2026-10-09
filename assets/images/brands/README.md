# Brand logos for the Case Study Stack

Official logos of the tools named in case studies, drawn by the Case Study Stack
widget (`Case_Study_Stack::LOGOS` in `includes/widgets/class-case-study-stack.php`).
Each logo is the brand's own artwork, taken from its own site, brand kit or app
listing. Nothing was redrawn or recoloured. Edits were limited to cropping to the
symbol (wordmark paths dropped), trimming the viewBox, removing scripts, styles
and ids, and resizing raster icons to WebP: 144 px for the app icons that fill
the 36 px tile (sharp on 3x screens), 96 px for the smaller glyphs. The logos
are trademarks of their owners and are shown only to name the tools used on a
project.

Platform marks for Shopify, WordPress, Webflow, React, Next.js, Node.js,
Elementor and Figma are not stored here. They are drawn inline by
`includes/brand-icons.php`.

| File | Brand | Mode | Source |
| --- | --- | --- | --- |
| bol.webp | bol | tile | Apple App Store icon of the "bol" app (bol.com bv), 512 px |
| bricks.webp | Bricks Builder | tile | bricksbuilder.io header logo / favicon (same image as in the Bricks brand assets zip) |
| checkout-blocks.webp | Shopify Checkout Blocks | tile | apps.shopify.com/checkout-blocks app icon |
| cookieyes.svg | CookieYes | glyph | assets.cookieyes.com/cy_04d4ebb261.svg (cookieyes.com) |
| elfsight.svg | Elfsight | glyph | elfsight.com header logo, symbol only |
| flyingpress.svg | FlyingPress | glyph | flyingpress.com/brand-assets/ icon SVG |
| funnelkit.svg | FunnelKit | glyph | funnelkit.com header logo (FunnelKit-Logo.svg), symbol only |
| globo-filters.webp | Smart Product Filter & Search (Globo) | tile | apps.shopify.com/product-filter-and-search app icon |
| googlepay.svg | Google Pay | pill | Google Pay brand guidelines, Google-Pay-Acceptance.zip (google-pay-mark_800.svg), unaltered paths |
| gorgias.svg | Gorgias | glyph | gorgias.com header logo ("Gorgias Logo - Black"), symbol only |
| gsap.webp | GSAP | glyph | gsap.com site icon (android-chrome-192x192.png) |
| javascript.svg | JavaScript | tile | logo.js (github.com/voodootikigod/logo.js), MIT licence kept in the file |
| judgeme.svg | Judge.me | tile | judge.me/media-kit, "Judge.me symbol" (Keppel) |
| juo.svg | Juo | glyph | juo.com/favicon.svg, white backing circle removed |
| klaviyo.svg | Klaviyo | glyph | klaviyo.com header logo, flag mark only |
| liquid.webp | Liquid | glyph | shopify.github.io/liquid site icon (water-drop-128x.png). Liquid's only official mark: a thin light-grey outline (#AEB0B3, about 2:1 on the tile), kept as is and drawn in a larger 28 px box so it reads |
| litespeed.svg | LiteSpeed | glyph | litespeedtech.com header logo, symbol only |
| rebuy.svg | Rebuy | glyph | rebuyengine.com/media-kit, "Rebuy logo mark" (black) |
| returnista.webp | Returnista | tile | returnista.nl apple-touch-icon |
| shoppay.svg | Shop Pay (Shop) | glyph | shop.app header mark (Shop swirl, #5433EB) |
| smile.webp | Smile.io | tile | apps.shopify.com/smile-io app icon |
| swiper.svg | Swiper | glyph | swiperjs.com/images/swiper-logo.svg |
| trustpilot.svg | Trustpilot | glyph | cdn.trustpilot.net/brand-assets/5.3.0/logo-black.svg, star only |
| webwinkelkeur.svg | WebwinkelKeur | glyph | webwinkelkeur.nl header logo, symbol only |
| woo-discount-rules.webp | Discount Rules for WooCommerce (Flycart) | glyph | flycart.org product icon, white background made transparent |
| woocommerce.webp | WooCommerce | glyph | woocommerce.com site icon (cropped-logo-w-favicon.png) |
| wployalty.webp | WPLoyalty | glyph | wployalty.net site icon (wployalty-icon.png) |

Modes: a **glyph** sits on the light icon tile; a **tile** is a square app icon
that fills the tile (a knock-out in it, like the Judge.me check, shows white, as
in the brand's app icon); a **pill** (Google Pay) is a wide mark with its own
outline.
To add a logo, put `<key>.svg` or `<key>.webp` here, add the key to `LOGOS` and a
name rule to `MARKS`, add a row above, then run `php tests/case-study-stack-marks.php`.
