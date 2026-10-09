<?php
/**
 * The article page between the theme's header and footer: hero, table of
 * contents, content, author box, CTA band and related posts.
 *
 * $args is \AvixWidgets\Blog\Article::view(). Every value is escaped here,
 * except the post content (the_content filters), the TOC markup (built and
 * escaped in Toc), the mosaic SVG and the related cards (built and escaped in
 * Article and Post_Cards).
 *
 * @package AvixWidgets
 */

defined( 'ABSPATH' ) || exit;

$avix_a = isset( $args ) && is_array( $args ) ? $args : array();
if ( ! $avix_a ) {
	return;
}

$avix_author   = is_array( $avix_a['author'] ) ? $avix_a['author'] : array();
$avix_service  = $avix_a['service'];
$avix_dates    = $avix_a['dates'];
$avix_settings = $avix_a['settings'];
$avix_proof    = trim( (string) $avix_settings['proof'] );
$avix_proof_2  = trim( (string) $avix_settings['proof_2'] );
// The rail exists for the table of contents. Without one (fewer than three
// headings) the side card would sit next to the first paragraph, so the
// article uses the centred single column instead.
$avix_has_rail = '' !== $avix_a['toc'];

$avix_icon_arrow = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 11l6-6M6 5h5v5"/></svg>';
$avix_icons      = array(
	'linkedin' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9.75h4v11H3zM9.5 9.75h3.8v1.5h.06c.53-1 1.83-2.06 3.77-2.06 4.03 0 4.77 2.65 4.77 6.1v5.46h-4v-4.84c0-1.16-.02-2.64-1.61-2.64-1.61 0-1.86 1.26-1.86 2.56v4.92h-4z"/></svg>',
	'x'        => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M17.75 3h3.07l-6.7 7.66L22 21h-6.17l-4.83-6.32L5.47 21H2.4l7.17-8.2L2 3h6.33l4.37 5.77zm-1.08 16.17h1.7L7.4 4.74H5.58z"/></svg>',
	'email'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm1.4 2L12 12.3 19.6 7zM20 8.9l-8 5.6-8-5.6V17h16z"/></svg>',
	'copy'     => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10.6 13.4a1 1 0 0 1 0-1.4l3.4-3.4a1 1 0 1 1 1.4 1.4L12 13.4a1 1 0 0 1-1.4 0zM8.5 17.6a3 3 0 0 1-2.1-5.1l2.1-2.1 1.4 1.4-2.1 2.1a1 1 0 0 0 1.4 1.4l2.1-2.1 1.4 1.4-2.1 2.1a3 3 0 0 1-2.1.9zm6.4-3.9-1.4-1.4 2.1-2.1a1 1 0 0 0-1.4-1.4l-2.1 2.1-1.4-1.4 2.1-2.1a3 3 0 1 1 4.2 4.2z"/></svg>',
	'star'     => '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M8 1.2l2 4.3 4.7.5-3.5 3.2 1 4.6L8 11.5l-4.2 2.3 1-4.6L1.3 6l4.7-.5z"/></svg>',
);

/**
 * Share buttons: plain links, no scripts from the networks.
 *
 * @param array  $share Share URLs.
 * @param string $label Visible label.
 * @param array  $icons Icons.
 * @param string $extra Extra class.
 */
$avix_share = static function ( array $share, string $label, array $icons, string $extra = '' ) {
	if ( ! $share ) {
		return;
	}
	?>
	<div class="avix-art-share<?php echo '' !== $extra ? ' ' . esc_attr( $extra ) : ''; ?>">
		<p class="avix-art-share__label"><?php echo esc_html( $label ); ?></p>
		<a class="avix-art-share__btn" href="<?php echo esc_url( $share['linkedin'] ); ?>" target="_blank" rel="noopener"><?php echo $icons['linkedin']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span class="avix-art-sr"><?php esc_html_e( 'Share on LinkedIn (opens in a new tab)', 'avix-widgets' ); ?></span></a>
		<a class="avix-art-share__btn" href="<?php echo esc_url( $share['x'] ); ?>" target="_blank" rel="noopener"><?php echo $icons['x']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span class="avix-art-sr"><?php esc_html_e( 'Share on X (opens in a new tab)', 'avix-widgets' ); ?></span></a>
		<a class="avix-art-share__btn" href="<?php echo esc_url( $share['email'], array( 'mailto' ) ); ?>"><?php echo $icons['email']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span class="avix-art-sr"><?php esc_html_e( 'Share by email', 'avix-widgets' ); ?></span></a>
		<button class="avix-art-share__btn" type="button" data-art-copy="<?php echo esc_url( $share['copy'] ); ?>"><?php echo $icons['copy']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span class="avix-art-sr"><?php esc_html_e( 'Copy link', 'avix-widgets' ); ?></span></button>
	</div>
	<?php
};
?>
<main id="avix-article" class="avix-art avix-art--service-<?php echo esc_attr( $avix_service['key'] ); ?>">
	<div class="avix-art-progress" aria-hidden="true"><span class="avix-art-progress__bar" data-art-progress></span></div>

	<article class="avix-art-article">
		<header class="avix-art-hero<?php echo '' === $avix_a['image'] ? ' avix-art-hero--text' : ''; ?>">
			<div class="avix-art-hero__inner">
				<div class="avix-art-hero__copy">
					<?php if ( count( $avix_a['crumbs'] ) > 1 ) : ?>
						<nav class="avix-art-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'avix-widgets' ); ?>">
							<ol>
								<?php foreach ( $avix_a['crumbs'] as $avix_crumb ) : ?>
									<li>
										<?php if ( '' !== $avix_crumb['url'] ) : ?>
											<a href="<?php echo esc_url( $avix_crumb['url'] ); ?>"><?php echo esc_html( $avix_crumb['text'] ); ?></a>
										<?php else : ?>
											<span aria-current="page"><?php echo esc_html( $avix_crumb['text'] ); ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ol>
						</nav>
					<?php endif; ?>
					<?php if ( '' !== $avix_a['chip'] ) : ?>
						<p class="avix-art-chip"><?php echo esc_html( $avix_a['chip'] ); ?></p>
					<?php endif; ?>
					<h1 class="avix-art-title"><?php echo $avix_a['title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Article::title(). ?></h1>
					<?php if ( '' !== $avix_a['dek'] ) : ?>
						<p class="avix-art-dek"><?php echo esc_html( $avix_a['dek'] ); ?></p>
					<?php endif; ?>
				</div>

				<div class="avix-art-hero__media">
					<?php echo $avix_a['mosaic']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from numbers in Article::mosaic(). ?>
					<?php if ( '' !== $avix_a['image'] ) : ?>
						<span class="avix-art-hero__frame"><?php echo $avix_a['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?></span>
					<?php endif; ?>
				</div>

				<div class="avix-art-facts">
					<?php if ( $avix_author ) : ?>
						<?php
						$avix_author_tag   = '' !== $avix_author['url'] ? 'a' : 'span';
						$avix_author_attrs = '' !== $avix_author['url'] ? ' href="' . esc_url( $avix_author['url'] ) . '"' . ( $avix_author['external'] ? ' rel="author noopener" target="_blank"' : ' rel="author"' ) : '';
						?>
						<<?php echo esc_html( $avix_author_tag ); ?> class="avix-art-author"<?php echo $avix_author_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
							<span class="avix-art-avatar avix-art-face"><?php echo \AvixWidgets\Blog\Article::face( $avix_author, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Article::face(). ?></span>
							<span class="avix-art-author__text">
								<span class="avix-art-author__name"><?php echo esc_html( $avix_author['name'] ); ?></span>
								<?php if ( '' !== $avix_author['role'] ) : ?>
									<span class="avix-art-author__role"><?php echo esc_html( $avix_author['role'] ); ?></span>
								<?php endif; ?>
							</span>
							<?php if ( $avix_author['external'] ) : ?>
								<span class="avix-art-sr"><?php esc_html_e( '(opens in a new tab)', 'avix-widgets' ); ?></span>
							<?php endif; ?>
						</<?php echo esc_html( $avix_author_tag ); ?>>
					<?php endif; ?>
					<dl>
						<div>
							<dt><?php esc_html_e( 'Published', 'avix-widgets' ); ?></dt>
							<dd><time datetime="<?php echo esc_attr( $avix_dates['published']['iso'] ); ?>"><?php echo esc_html( $avix_dates['published']['short'] ); ?></time></dd>
						</div>
						<?php if ( $avix_dates['updated'] ) : ?>
							<div>
								<dt><?php esc_html_e( 'Updated', 'avix-widgets' ); ?></dt>
								<dd><time datetime="<?php echo esc_attr( $avix_dates['modified']['iso'] ); ?>"><?php echo esc_html( $avix_dates['modified']['short'] ); ?></time></dd>
							</div>
						<?php endif; ?>
						<div>
							<dt><?php esc_html_e( 'Reading time', 'avix-widgets' ); ?></dt>
							<dd>
								<?php
								/* translators: %d: minutes. */
								echo esc_html( sprintf( _n( '%d min read', '%d min read', (int) $avix_a['minutes'], 'avix-widgets' ), (int) $avix_a['minutes'] ) );
								?>
							</dd>
						</div>
					</dl>
				</div>
			</div>
		</header>

		<div class="avix-art-main">
			<div class="avix-art-grid<?php echo $avix_has_rail ? '' : ' avix-art-grid--solo'; ?>">
				<?php if ( $avix_has_rail ) : ?>
					<aside class="avix-art-rail" aria-label="<?php esc_attr_e( 'Article navigation and contact', 'avix-widgets' ); ?>">
						<a class="avix-art-skip" href="#avix-art-body"><?php esc_html_e( 'Skip to the article', 'avix-widgets' ); ?></a>
						<?php echo $avix_a['toc']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Toc::desktop(). ?>
						<?php if ( ! empty( $avix_settings['rail_cta'] ) ) : ?>
							<div class="avix-art-cta-card">
								<span class="avix-art-cta-card__px" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
								<p class="avix-art-cta-card__eyebrow"><?php echo esc_html( $avix_service['eyebrow'] ); ?></p>
								<p class="avix-art-cta-card__title"><?php echo esc_html( $avix_service['title'] ); ?></p>
								<a class="avix-art-btn" href="<?php echo esc_url( $avix_a['contact'] ); ?>"><?php esc_html_e( 'Talk to the team', 'avix-widgets' ); ?> <span class="avix-art-btn__icon"><?php echo $avix_icon_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span></a>
								<?php if ( '' !== $avix_service['url'] && '' !== $avix_service['link'] ) : ?>
									<a class="avix-art-cta-card__link" href="<?php echo esc_url( $avix_service['url'] ); ?>"><?php echo esc_html( $avix_service['link'] ); ?></a>
								<?php endif; ?>
								<?php if ( '' !== $avix_proof ) : ?>
									<p class="avix-art-cta-card__proof"><?php echo $avix_icons['star']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php echo esc_html( $avix_proof ); ?></p>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						<?php $avix_share( $avix_a['share'], __( 'Share', 'avix-widgets' ), $avix_icons, 'avix-art-share--rail' ); ?>
					</aside>
				<?php endif; ?>

				<div class="avix-art-content">
					<?php echo $avix_a['toc_m']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Toc::mobile(). ?>

					<div class="avix-art-prose" id="avix-art-body" tabindex="-1" data-art-content>
						<?php echo $avix_a['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content. ?>
					</div>
					<?php echo $avix_a['pages']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_link_pages(). ?>
					<p class="avix-art-sr" aria-live="polite" data-art-live data-art-copied="<?php esc_attr_e( 'Link copied', 'avix-widgets' ); ?>"></p>
				</div>
			</div>

			<?php // The end has its own grid (same columns): the sticky rail is bounded by its grid, so it scrolls away after the last section. ?>
			<div class="avix-art-grid avix-art-grid--end<?php echo $avix_has_rail ? '' : ' avix-art-grid--solo'; ?>">
				<footer class="avix-art-end">
					<div class="avix-art-end__row">
						<p class="avix-art-end__updated">
							<?php if ( $avix_dates['updated'] ) : ?>
								<?php esc_html_e( 'Last updated', 'avix-widgets' ); ?> <time datetime="<?php echo esc_attr( $avix_dates['modified']['iso'] ); ?>"><?php echo esc_html( $avix_dates['modified']['long'] ); ?></time>
							<?php else : ?>
								<?php esc_html_e( 'Published', 'avix-widgets' ); ?> <time datetime="<?php echo esc_attr( $avix_dates['published']['iso'] ); ?>"><?php echo esc_html( $avix_dates['published']['long'] ); ?></time>
							<?php endif; ?>
						</p>
						<?php $avix_share( $avix_a['share'], __( 'Share this guide', 'avix-widgets' ), $avix_icons ); ?>
					</div>

					<?php if ( $avix_author ) : ?>
						<section class="avix-art-author-box" aria-label="<?php esc_attr_e( 'About the author', 'avix-widgets' ); ?>">
							<span class="avix-art-author-box__photo avix-art-face"><?php echo \AvixWidgets\Blog\Article::face( $avix_author, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Article::face(). ?></span>
							<div class="avix-art-author-box__body">
								<p class="avix-art-eyebrow"><?php esc_html_e( 'Written by', 'avix-widgets' ); ?></p>
								<h2 class="avix-art-author-box__name"><?php echo esc_html( $avix_author['name'] ); ?></h2>
								<?php if ( '' !== $avix_author['role'] ) : ?>
									<p class="avix-art-author-box__role"><?php echo esc_html( $avix_author['role'] ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== $avix_author['bio'] ) : ?>
									<p class="avix-art-author-box__bio"><?php echo $avix_author['bio']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() in Article::author(). ?></p>
								<?php endif; ?>
								<?php if ( $avix_author['links'] ) : ?>
									<ul class="avix-art-author-box__links">
										<?php foreach ( $avix_author['links'] as $avix_link ) : ?>
											<li>
												<a href="<?php echo esc_url( $avix_link['url'] ); ?>"<?php echo $avix_link['external'] ? ' target="_blank" rel="noopener"' : ''; ?>>
													<?php
													if ( '' !== $avix_link['icon'] && isset( $avix_icons[ $avix_link['icon'] ] ) ) {
														echo $avix_icons[ $avix_link['icon'] ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
													}
													echo esc_html( $avix_link['label'] );
													?>
													<?php if ( $avix_link['external'] ) : ?>
														<span class="avix-art-sr"><?php esc_html_e( '(opens in a new tab)', 'avix-widgets' ); ?></span>
													<?php endif; ?>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>
						</section>
					<?php endif; ?>
				</footer>
			</div>
		</div>
	</article>

	<section class="avix-art-band" aria-labelledby="avix-art-band-title">
		<div class="avix-art-band__stage">
			<div class="avix-art-band__copy">
				<p class="avix-art-eyebrow"><?php esc_html_e( 'Work with us', 'avix-widgets' ); ?></p>
				<h2 class="avix-art-band__title" id="avix-art-band-title"><?php echo esc_html( $avix_service['band_lead'] ); ?> <span class="avix-art-band__accent"><?php echo esc_html( $avix_service['band_accent'] ); ?></span></h2>
				<p class="avix-art-band__text"><?php esc_html_e( 'Send a short brief: your goals, users, essential features and launch window. We’ll review it and suggest a practical next step.', 'avix-widgets' ); ?></p>
			</div>
			<div class="avix-art-band__actions">
				<a class="avix-art-btn" href="<?php echo esc_url( $avix_a['contact'] ); ?>"><?php esc_html_e( 'Share your project brief', 'avix-widgets' ); ?> <span class="avix-art-btn__icon"><?php echo $avix_icon_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span></a>
				<?php if ( '' !== $avix_service['url'] && '' !== $avix_service['link'] ) : ?>
					<a class="avix-art-band__link" href="<?php echo esc_url( $avix_service['url'] ); ?>"><?php echo esc_html( $avix_service['link'] ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $avix_proof || '' !== $avix_proof_2 ) : ?>
					<ul class="avix-art-band__proof">
						<?php if ( '' !== $avix_proof ) : ?>
							<li><?php echo esc_html( $avix_proof ); ?></li>
						<?php endif; ?>
						<?php if ( '' !== $avix_proof_2 ) : ?>
							<li><?php echo esc_html( $avix_proof_2 ); ?></li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ( '' !== $avix_a['related'] ) : ?>
		<section class="avix-art-related" aria-labelledby="avix-art-related-title">
			<div class="avix-art-related__inner">
				<div class="avix-art-related__head">
					<div>
						<p class="avix-art-eyebrow"><?php esc_html_e( 'Keep reading', 'avix-widgets' ); ?></p>
						<h2 class="avix-art-related__title" id="avix-art-related-title"><?php esc_html_e( 'More guides from the team', 'avix-widgets' ); ?></h2>
					</div>
					<a class="avix-art-related__all" href="<?php echo esc_url( $avix_a['blog_url'] ); ?>"><?php esc_html_e( 'All articles', 'avix-widgets' ); ?> <span aria-hidden="true"><?php echo $avix_icon_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span></a>
				</div>
				<div class="avix-art-related__grid">
					<?php echo $avix_a['related']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Post_Cards::card() escapes. ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
</main>
