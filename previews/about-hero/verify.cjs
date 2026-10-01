const { chromium } = require('C:/Users/MT/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const path = require('node:path');
const fs = require('node:fs');
(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));
  page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
  page.on('response', response => { if (response.status() >= 400) errors.push(`${response.status()} ${response.url()}`); });
  const checks = [];
  for (const width of [1440, 1920, 1024, 768, 390, 320]) {
    await page.setViewportSize({ width, height: width > 1000 ? 960 : 844 });
    await page.goto('http://127.0.0.1:4173/', { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);
    const result = await page.evaluate(() => {
      const heading = document.querySelector('h1');
      const track = document.querySelector('[data-track]');
      const root = document.querySelector('.avix-about');
      return {
        width: innerWidth, overflow: document.documentElement.scrollWidth > innerWidth,
        headline: heading.innerText, headingSize: getComputedStyle(heading).fontSize,
        enhanced: root.classList.contains('is-enhanced'),
        period: getComputedStyle(track).getPropertyValue('--aa-period').trim(),
        actualPeriod: document.querySelector('.avix-about__group').getBoundingClientRect().width,
        imageFailures: [...document.images].filter(image => !image.complete || !image.naturalWidth).length,
        fontsLoaded: document.fonts.check('500 20px "Space Grotesk"') && document.fonts.check('400 16px Inter'),
        pauseVisible: !!document.querySelector('.avix-about__pause').getBoundingClientRect().height,
        sectionLeft: root.getBoundingClientRect().left, sectionWidth: root.getBoundingClientRect().width,
        hasHeader: !!document.querySelector('header, nav'),
        ctaBackground: getComputedStyle(document.querySelector('.avix-about__cta')).backgroundColor,
        badgeFill: getComputedStyle(document.querySelector('[data-track="outgoing"] path')).fill
      };
    });
    checks.push(result);
    if (result.overflow || !result.enhanced || result.imageFailures || !result.fontsLoaded || result.hasHeader || result.sectionLeft !== 0 || result.sectionWidth !== width || parseFloat(result.period) !== result.actualPeriod) errors.push(`Layout check failed at ${width}`);
    if ([1440, 390, 320].includes(width)) {
      await page.evaluate(() => {
        document.querySelectorAll('[data-track]').forEach(el => el.style.animationPlayState = 'paused');
        document.activeElement.blur();
        window.scrollTo(0, 0);
      });
      await page.screenshot({ path: path.join(__dirname, `preview-${width}.png`), fullPage: true });
    }
  }
  await page.setViewportSize({ width: 1440, height: 960 });
  await page.goto('http://127.0.0.1:4173/');
  const track = page.locator('[data-track="incoming"]');
  const initial = await track.evaluate(el => getComputedStyle(el).transform);
  await page.waitForTimeout(300);
  const advanced = await track.evaluate(el => getComputedStyle(el).transform);
  if (initial === advanced) errors.push('Marquee did not animate');
  await page.getByRole('button', { name: 'Pause platform animation' }).click();
  const paused = await track.evaluate(el => getComputedStyle(el).transform);
  await page.waitForTimeout(300);
  if (paused !== await track.evaluate(el => getComputedStyle(el).transform)) errors.push('Pause did not stop motion');
  await page.getByRole('button', { name: 'Resume platform animation' }).click();
  if (await track.evaluate(el => getComputedStyle(el).animationPlayState) !== 'running') errors.push('Resume did not restart motion');
  await page.emulateMedia({ reducedMotion: 'reduce' });
  if (await page.locator('.avix-about__marquee').isVisible()) errors.push('Reduced motion still animating');
  if (!await page.locator('.avix-about__platforms').isVisible()) errors.push('Reduced motion platform list not visible');
  await page.screenshot({ path: path.join(__dirname, 'preview-reduced-motion.png'), fullPage: true });
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } });
  const noJS = await context.newPage();
  await noJS.goto('http://127.0.0.1:4173/');
  if (!await noJS.locator('.avix-about__platforms').isVisible()) errors.push('No-JS expertise missing');
  await browser.close();
  const report = { checks, errors, interactions: 'full-width section, no header, animation, pause, resume, reduced motion, no JavaScript' };
  fs.writeFileSync(path.join(__dirname, 'verification.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  if (errors.length) process.exitCode = 1;
})().catch(error => { console.error(error); process.exitCode = 1; });
