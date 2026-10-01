# Avix Digital — About Hero

Included in Avix Digital Elementor Widgets 1.9.0. The existing widgets are included in the same update.

## Install and place

1. In WordPress, open **Plugins → Add New → Upload Plugin** and upload `avix-elementor-widgets-1.9.0.zip`. If the Avix plugin is already installed, use **Replace current with uploaded**.
2. Edit the About page with Elementor. Add **Avix Digital → About Hero**.
3. Set the parent container to **Full Width**, width **100%**, zero padding, and zero gap. Remove any page template title above the section. Use your normal site header separately; this widget contains no header.
4. Publish or preview the page. If cached styles persist, regenerate Elementor CSS and clear the page/CDN cache.

## Content controls

- Edit the eyebrow, headline, description, booking button label/link, supporting note, logo, brand label, and marquee captions.
- Use `[brackets]` around headline or caption text to apply its accent colour. New lines become desktop breaks; narrow containers reflow naturally.
- Text, links, media, and repeater text/media support Elementor dynamic tags. The available dynamic sources depend on your Elementor edition and other installed integrations.
- Add, remove, or reorder platforms. Choose a built-in platform mark or upload an image. Optional per-item mark colours override built-in SVG colours; uploaded images retain their artwork colours.
- Upload an optional replacement check image, or leave it empty to use the supplied scalloped SVG. Change its colour under **Style → Marquee & captions**.
- Enable/disable animation, labels, the phone platform list, or the entire expertise area. Adjust responsive loop duration and optional hover/focus pausing. Pause and resume labels are editable.

## Style controls

The Style tab provides background/gradient, minimum height, content widths, responsive spacing, typography, text/accent colours, normal/hover button colours, button padding and radius, arrow circle size, shadows, logo tile dimensions, border/radius/background, brand mark size, platform spacing/sizes, check colour, and caption styles.

Leave a control unset to retain the responsive defaults. The desktop heading caps at **62px**, with **26–36px** defaults on phones. Fonts are bundled locally: Space Grotesk for the headline and Inter for supporting text. Their licences are included under `assets/fonts/`.

The section uses a **100svh** minimum height so the full default hero fits within the stable mobile viewport. On screens at most 740px tall, it compacts spacing and the logo tile, and hides the optional note and captions. The moving expertise row and booking button remain visible. Longer replacement content, large type overrides, browser zoom, or an additional site header can require scrolling; the section grows instead of clipping content. If the site header must share the first screen, reduce the minimum height or spacing accordingly.

## Behaviour and accessibility

- Each widget measures its actual platform group and creates enough copies for a seamless loop. It recalculates after font loading, resizing, and Elementor style changes.
- Editor replacement markup initializes automatically. Multiple widget instances have independent pause controls.
- A keyboard-accessible pause/resume control is present during animation. Motion pauses offscreen and while the browser tab is hidden.
- Reduced motion and disabled JavaScript show the static platform list. The same list supplies readable platform names to assistive technology while moving duplicates remain decorative.
- Uploaded logos are rendered through WordPress attachment helpers when an attachment ID is available. Text is escaped and the headline tag is allowlisted.

## Verification

The widget was exercised in local WordPress 7.1.2 with Elementor 4.3.3 and PHP 8.4.26. Checks include real widget/control registration, rendered default/custom settings, responsive typography and CSS controls, custom logo uploads, safe text/URLs, hidden sections, animation/pause/resume, reduced motion, disabled animation, keyboard focus, editor markup replacement, and JavaScript-disabled rendering.

Browser checks passed in Chrome and WebKit. WebKit also covers a short landscape window; the hero grows without clipping there. Firefox could not be launched on this machine and is not marked as verified. These are automated browser viewport checks, not tests on physical devices.

Default layout checks cover 320×568, 360×640, 375×667, 390×844, 430×932, 600×800, 768×1024, 1024×768, 1280×720, 1366×768, 1440×900, and 1920×1080. The marquee stays within the first screen at these tested sizes without horizontal overflow.

Results and screenshots are kept in the development workspace under `tests/`; WordPress test installations, credentials, test scripts, and previews are excluded from the installable ZIP. The live site's theme and cache integration still need to be checked after installing the update.
