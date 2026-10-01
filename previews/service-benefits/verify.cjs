const {chromium,webkit}=require('C:/Users/MT/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('node:fs'),path=require('node:path');
const url='http://127.0.0.1:4173/service-benefits/';
(async()=>{
 const report={layouts:[],interactions:[],errors:[]};
 const assert=(condition,label)=>{if(!condition)report.errors.push(label);};
 for(const [name,engine,options] of [['Chrome',chromium,{executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'}],['WebKit',webkit,{}]]){
  const browser=await engine.launch({headless:true,...options});
  const context=await browser.newContext({deviceScaleFactor:2});
  const page=await context.newPage();
  page.on('pageerror',error=>report.errors.push(name+': '+error.message));
  page.on('console',event=>{if(event.type()==='error')report.errors.push(name+': '+event.text());});
  page.on('response',response=>{if(response.status()>=400)report.errors.push(name+': '+response.status()+' '+response.url());});
  for(const [width,height] of [[1920,1080],[1440,1000],[1024,768],[960,720],[768,1024],[640,900],[600,800],[430,932],[390,844],[360,640],[320,568]]){
   await page.setViewportSize({width,height});await page.goto(url,{waitUntil:'networkidle'});await page.evaluate(()=>document.fonts.ready);
   // Scroll each lazy image into view to verify all supplied artwork, including stacked cards.
   for(const image of await page.locator('.avix-benefits__visual img').all())await image.scrollIntoViewIfNeeded();
   await page.waitForFunction(()=>[...document.images].every(image=>image.complete&&image.naturalWidth));
   const result=await page.evaluate(()=>{
    const cards=[...document.querySelectorAll('.avix-benefits__card')], links=cards.map(card=>card.querySelector('a'));
    const rects=cards.map(card=>card.getBoundingClientRect());
    const columns=new Set(rects.map(rect=>Math.round(rect.left))).size;
    return {width:innerWidth,overflow:document.documentElement.scrollWidth>innerWidth,columns,
     titleSize:getComputedStyle(document.querySelector('h2')).fontSize,
     headingLevels:[...document.querySelectorAll('h1,h2,h3')].map(heading=>heading.tagName),
     images:[...document.images].map(image=>({source:image.currentSrc,naturalWidth:image.naturalWidth,clientWidth:image.clientWidth})),
     cardHeights:rects.map(rect=>rect.height),linkY:links.map(link=>link.getBoundingClientRect().top),
     clippedLinks:links.some(link=>link.scrollWidth>link.clientWidth+1),
     targetHeights:[...document.querySelectorAll('a')].map(link=>link.getBoundingClientRect().height),
     fonts:document.fonts.check('700 40px "Avix Benefits Display"')&&document.fonts.check('400 16px "Avix Benefits Inter"'),
     header:!!document.querySelector('header,nav')};
   });
   report.layouts.push({engine:name,...result});
   assert(!result.overflow&&!result.clippedLinks&&result.fonts&&!result.header,`${name}: responsive layout ${width}`);
   assert(result.targetHeights.every(height=>height>=44),`${name}: touch targets ${width}`);
   assert(result.headingLevels.join(',')==='H2,H3,H3,H3',`${name}: semantic heading hierarchy`);
   if(result.columns===3){assert(Math.max(...result.cardHeights)-Math.min(...result.cardHeights)<1,`${name}: equal card heights ${width}`);assert(Math.max(...result.linkY)-Math.min(...result.linkY)<1,`${name}: aligned service links ${width}`);}
   if(name==='Chrome'&&[1440,768,390].includes(width)){
    await page.mouse.move(0,0);await page.evaluate(()=>scrollTo(0,0));
    await page.screenshot({path:path.join(__dirname,`preview-${width}.png`),fullPage:true});
   }
  }
  await page.setViewportSize({width:1440,height:1000});await page.goto(url,{waitUntil:'networkidle'});
  const first=page.locator('.avix-benefits__card a').first();
  await first.hover();await page.waitForTimeout(300);
  assert(await first.locator('span').evaluate(element=>getComputedStyle(element).backgroundSize)==='100% 1px',`${name}: underline hover`);
  assert(await first.evaluate(element=>getComputedStyle(element).backgroundColor)==='rgba(0, 0, 0, 0)',`${name}: links remain plain`);
  await page.mouse.move(0,0);await page.keyboard.press('Tab');
  // WebKit link tabbing follows host preferences; verify its keyboard focus treatment explicitly.
  if(name==='WebKit')await first.focus();
  assert(await first.evaluate(element=>element.matches(':focus-visible')&&getComputedStyle(element).outlineStyle==='solid'),`${name}: keyboard focus`);
  const hrefs=await page.locator('a').evaluateAll(links=>links.map(link=>link.href));
  assert(hrefs.join('|')==='https://avixdigital.com/service/shopify-plus/|https://avixdigital.com/service/web-development/|https://avixdigital.com/service/uiux-and-brand-design/|https://avixdigital.com/contact/',`${name}: correct destinations`);
  await page.locator('.avix-benefits__help').scrollIntoViewIfNeeded();await page.waitForTimeout(1300);
  assert(await page.locator('.avix-benefits__arm').evaluate(element=>getComputedStyle(element).animationName)==='none',`${name}: greeting finishes without continuous motion`);
  await page.emulateMedia({reducedMotion:'reduce'});await page.locator('.avix-benefits__help').hover();
  assert(await page.locator('.avix-benefits__arm').evaluate(element=>getComputedStyle(element).animationName)==='none',`${name}: reduced motion`);
  await page.locator('.avix-benefits__card').first().hover();
  assert(await page.locator('.avix-benefits__card').first().evaluate(element=>getComputedStyle(element).transform)==='none',`${name}: reduced motion hover`);
  await page.emulateMedia({reducedMotion:'no-preference'});
  // Simulate a narrow Elementor column within a wide desktop canvas.
  for(const width of [360,720]){
   await page.evaluate(width=>{const root=document.querySelector('.avix-benefits');root.style.width=width+'px';root.style.marginInline='auto';},width);
   assert(await page.locator('.avix-benefits').evaluate(element=>element.scrollWidth<=element.clientWidth+1),`${name}: embedded ${width}px container`);
  }
  // Longer custom copy and larger type must grow rather than clip.
  await page.setViewportSize({width:390,height:844});
  await page.evaluate(()=>{const root=document.querySelector('.avix-benefits');root.style.width='100%';root.querySelector('h2').style.fontSize='50px';root.querySelector('h3').textContent='Shopify stores and e-commerce experiences tailored to your customers';});
  assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`${name}: custom copy/type reflow`);
  report.interactions.push({engine:name,passed:['underline hover','plain links','keyboard focus','service destinations','finite avatar greeting','reduced motion','embedded columns','custom text/type']});
  await browser.close();
 }
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 const context=await browser.newContext({javaScriptEnabled:false,viewport:{width:390,height:844}});
 const page=await context.newPage();await page.goto(url);
 assert(await page.getByRole('link').count()===4&&await page.locator('.avix-benefits__buddy').isVisible(),'JavaScript-disabled content, links and avatar');
 await browser.close();
 fs.writeFileSync(path.join(__dirname,'verification.json'),JSON.stringify(report,null,2));
 console.log(JSON.stringify({layouts:report.layouts.length,interactions:report.interactions,errors:report.errors},null,2));
 if(report.errors.length)process.exitCode=1;
})().catch(error=>{console.error(error);process.exitCode=1;});
