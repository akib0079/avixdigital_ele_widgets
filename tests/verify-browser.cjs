const { chromium } = require('C:/Users/MT/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs = require('node:fs');
const path = require('node:path');
const base = 'http://127.0.0.1:4174/';
(async () => {
 const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 const page = await browser.newPage();
 const errors=[], checks=[];
 page.on('pageerror', error => errors.push(error.message));
 page.on('console', event => {if(event.type()==='error') errors.push(event.text());});
 const assert=(condition,label)=>{if(!condition) errors.push(label);};
 async function load(id=5) {await page.goto(base+'?page_id='+id,{waitUntil:'networkidle'}); await page.evaluate(()=>document.fonts.ready); await page.locator('.avix-about.is-enhanced').waitFor();}
 for (const [width,height] of [[1920,1080],[1440,900],[1366,768],[1280,720],[1024,768],[768,1024],[600,800],[430,932],[390,844],[375,667],[360,640],[320,568]]) {
  await page.setViewportSize({width,height}); await load();
  const result = await page.evaluate(()=>{
   const q=s=>document.querySelector(s), box=s=>q(s).getBoundingClientRect();
   return {width:innerWidth,height:innerHeight,overflow:document.documentElement.scrollWidth>innerWidth,
    sectionHeight:box('.avix-about').height,sectionWidth:box('.avix-about').width,
    headingSize:getComputedStyle(q('.avix-about__title')).fontSize,
    headingFont:getComputedStyle(q('.avix-about__title')).fontFamily,
    marqueeBottom:box('.avix-about__marquee').bottom,footerBottom:box('.avix-about__rail-footer').bottom,
    logoWhite:getComputedStyle(q('.avix-about__hub')).backgroundColor==='rgb(255, 255, 255)',
    ctaColor:getComputedStyle(q('.avix-about__cta')).color,ctaBackground:getComputedStyle(q('.avix-about__cta')).backgroundColor,
    images:[...document.images].filter(i=>!i.complete||!i.naturalWidth).map(i=>i.src),
    fonts:document.fonts.check('700 40px "Avix About Display"')&&document.fonts.check('400 16px "Avix About Inter"'),
    period:parseFloat(getComputedStyle(q('.avix-about__expertise')).getPropertyValue('--aa-period')),
    groupWidth:box('.avix-about__group').width,hasHeader:!!q('header,nav'),
    trackCopies:q('[data-track]').children.length};
  });
  checks.push(result);
  assert(!result.overflow && result.sectionWidth===width,'Full-width layout at '+width);
  assert(result.sectionHeight<=height+1,'Default hero exceeds viewport at '+width+'x'+height);
  assert(result.marqueeBottom<=height+1,'Marquee exceeds first viewport at '+width+'x'+height);
  assert(result.footerBottom<=height+1,'Section footer exceeds first viewport at '+width+'x'+height);
  assert(result.logoWhite && result.fonts && !result.images.length && !result.hasHeader,'Assets/header at '+width);
  assert(result.period===result.groupWidth && result.trackCopies>=2,'Seamless measured loop at '+width);
  if ([1440,390,320].includes(width)) { await page.getByRole('button',{name:'Pause animation',exact:true}).click(); await page.mouse.move(0,0); await page.screenshot({path:path.join(__dirname,'hero-'+width+'.png'),fullPage:true}); }
 }
 await page.setViewportSize({width:1440,height:900}); await load();
 const track=page.locator('[data-track="incoming"]');
 const initial=await track.evaluate(e=>getComputedStyle(e).transform); await page.waitForTimeout(250);
 assert(initial!==await track.evaluate(e=>getComputedStyle(e).transform),'Animation advances');
 await page.getByRole('button',{name:'Pause animation',exact:true}).click();
 const paused=await track.evaluate(e=>getComputedStyle(e).transform); await page.waitForTimeout(250);
 assert(paused===await track.evaluate(e=>getComputedStyle(e).transform),'Pause stops animation');
 await page.getByRole('button',{name:'Resume animation',exact:true}).click();
 assert(await track.evaluate(e=>getComputedStyle(e).animationPlayState)==='running','Resume restarts animation');
 await page.keyboard.press('Shift+Tab');
 assert(await page.locator('.avix-about__cta').evaluate(e=>getComputedStyle(e).outlineStyle)==='solid','Visible keyboard focus');
 await page.emulateMedia({reducedMotion:'reduce'});
 assert(!await page.locator('.avix-about__marquee').isVisible() && await page.locator('.avix-about__platforms').isVisible(),'Reduced motion fallback');
 await page.emulateMedia({reducedMotion:'no-preference'});
 await load(7);
 assert(!await page.locator('.avix-about__marquee').isVisible() && await page.locator('.avix-about__platforms').isVisible(),'Animation disabled control');
 await load(6);
 const custom=await page.evaluate(()=>{
  const style=s=>getComputedStyle(document.querySelector(s));
  return {title:style('.avix-about__title').fontSize,button:style('.avix-about__cta').backgroundColor,
   tile:style('.avix-about__hub').width,duration:style('[data-track]').animationDuration,
   badge:style('[data-track="outgoing"] path').fill,count:document.querySelector('.avix-about__group').children.length,
   logo:!!document.querySelector('.avix-about__platform-image')};
 });
 assert(custom.title==='48px' && custom.button==='rgb(17, 34, 51)' && custom.tile==='130px' && custom.duration==='12s' && custom.badge==='rgb(25, 135, 84)' && custom.count===1 && custom.logo,'Elementor style controls/custom logo actually apply: '+JSON.stringify(custom));
 await page.locator('.avix-about__expertise').hover();
 assert(await page.locator('[data-track]').first().evaluate(e=>getComputedStyle(e).animationPlayState)==='paused','Hover pause setting');
 await page.setViewportSize({width:390,height:844});
 assert(await page.locator('.avix-about__title').evaluate(e=>getComputedStyle(e).fontSize)==='28px','Elementor mobile typography override');
 // Exercise the editor replacement lifecycle: detached instances must not retain handlers.
 await page.evaluate(()=>{
  const original=document.querySelector('.avix-about');
  const replacement=original.cloneNode(true);
  replacement.querySelectorAll('[data-track]').forEach(track=>{while(track.children.length>1) track.lastElementChild.remove();});
  replacement.classList.remove('is-enhanced','is-paused','is-still');
  original.replaceWith(replacement);
 });
 await page.locator('.avix-about.is-enhanced').waitFor();
 await page.getByRole('button',{name:'Stop motion',exact:true}).click();
 assert(await page.getByRole('button',{name:'Continue motion',exact:true}).count()===1,'Editor replacement initializes once');
 const noJS=await browser.newContext({javaScriptEnabled:false,viewport:{width:390,height:844}});
 const noJSPage=await noJS.newPage(); await noJSPage.goto(base+'?page_id=5');
 assert(await noJSPage.locator('.avix-about__platforms').isVisible(),'No-JavaScript expertise fallback');
 await browser.close();
 const report={checks,custom,errors}; fs.writeFileSync(path.join(__dirname,'browser-verification.json'),JSON.stringify(report,null,2));
 console.log(JSON.stringify(report,null,2)); if(errors.length)process.exitCode=1;
})().catch(error=>{console.error(error);process.exitCode=1;});
