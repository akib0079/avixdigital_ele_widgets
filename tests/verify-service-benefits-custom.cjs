const {chromium,webkit}=require('C:/Users/MT/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('fs');
const pages=JSON.parse(fs.readFileSync(__dirname+'/service-benefits-elementor-verification.json','utf8')).pages;
(async()=>{
 const report={checks:[],errors:[]};
 const check=(condition,label)=>{report.checks.push({label,passed:!!condition});if(!condition)report.errors.push(label)};
 for(const [name,engine,options] of [['Chrome',chromium,{executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'}],['WebKit',webkit,{}]]){
  const browser=await engine.launch({headless:true,...options});
  try{
   const page=await browser.newPage({viewport:{width:1440,height:1000}});
   page.on('pageerror',error=>report.errors.push(name+': '+error.message));
   const load=async id=>{await page.goto('http://127.0.0.1:4174/?page_id='+id,{waitUntil:'networkidle'});await page.evaluate(()=>document.fonts.ready)};
   await load(pages.default);
   const gap=await page.locator('.avix-benefits__visual').first().evaluate(element=>getComputedStyle(element).marginTop);
   check(gap==='25px',name+': approved image spacing survives Elementor CSS');
   await load(pages.custom);
   const values=await page.evaluate(()=>{
    const style=selector=>getComputedStyle(document.querySelector(selector));
    return {heading:style('.avix-benefits__title').fontSize,radius:style('.avix-benefits__card').borderRadius,gap:style('.avix-benefits__grid').gap,
     background:style('.avix-benefits__card').backgroundColor,imageRadius:style('.avix-benefits__visual').borderRadius,
     cards:document.querySelectorAll('article').length,customAvatar:!!document.querySelector('.avix-benefits__buddy img'),
     librarySource:document.querySelector('.avix-benefits__visual img').getAttribute('srcset'),
     text:document.querySelector('.avix-benefits__card-title').textContent};
   });
   check(values.heading==='46px'&&values.radius==='25px'&&values.gap==='30px'&&values.background==='rgb(250, 251, 252)'&&values.imageRadius==='19px',name+': saved style controls apply '+JSON.stringify(values));
   check(values.cards===4&&values.customAvatar&&values.librarySource&&values.text==='Custom service offering',name+': edited cards, media and avatar render');
   const first=page.locator('.avix-benefits__card a').first();await first.hover();await page.waitForTimeout(300);
   check(await first.evaluate(element=>getComputedStyle(element).color)==='rgb(154, 52, 18)',name+': per-card link hover colour applies');
   check((await first.getAttribute('target'))==='_blank'&&(await first.getAttribute('rel')).includes('noopener'),name+': external link options apply');
   await page.setViewportSize({width:390,height:844});
   check(await page.locator('.avix-benefits__title').evaluate(element=>getComputedStyle(element).fontSize)==='31px',name+': mobile heading override');
   check(await page.locator('.avix-benefits__grid').evaluate(element=>getComputedStyle(element).gap)==='18px',name+': mobile card spacing override');
   check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),name+': custom mobile reflows');
   await page.setViewportSize({width:1440,height:1000});await load(pages.static);
   await page.locator('.avix-benefits__card').first().hover();await page.waitForTimeout(300);
   check(await page.locator('.avix-benefits__card').first().evaluate(element=>getComputedStyle(element).transform)==='none',name+': disabled card motion');
   await page.locator('.avix-benefits__help').hover();
   check(await page.locator('.avix-benefits__arm').evaluate(element=>getComputedStyle(element).animationName)==='none',name+': disabled greeting');
   await load(pages.multiple);
   const ids=await page.locator('[id^="avix-benefits-"]').evaluateAll(elements=>elements.map(element=>element.id));
   check(new Set(ids).size===ids.length&&await page.locator('.avix-benefits').count()===2,name+': multiple instances have unique identifiers');
   const second=page.locator('.avix-benefits').nth(1);await second.locator('.avix-benefits__help').hover();
   check(await second.locator('.avix-benefits__arm').evaluate(element=>getComputedStyle(element).animationName)==='avix-benefits-wave',name+': second instance greeting');
   // Elementor PHP preview updates replace the entire widget element.
   await page.evaluate(()=>{const root=document.querySelector('.elementor-widget-avix-service-benefits');root.replaceWith(root.cloneNode(true));});
   const replacement=page.locator('.avix-benefits').first();await replacement.locator('.avix-benefits__help').hover();
   check(await replacement.locator('.avix-benefits__arm').evaluate(element=>getComputedStyle(element).animationName)==='avix-benefits-wave',name+': replacement markup initializes');
   await page.waitForTimeout(1300);
   check(await replacement.locator('.avix-benefits__arm').evaluate(element=>getComputedStyle(element).animationName)==='none',name+': replaced greeting still finishes');
   await page.emulateMedia({reducedMotion:'reduce'});await replacement.locator('.avix-benefits__help').hover();
   check(await replacement.locator('.avix-benefits__help').evaluate(element=>!element.classList.contains('is-waving')),name+': motion preference change clears greeting');
   await load(pages.no_avatar);await page.setViewportSize({width:390,height:844});
   check(await page.locator('.avix-benefits__buddy').count()===0&&await page.locator('.avix-benefits__link--help').isVisible()&&await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),name+': guide layout without avatar');
   // Basic regression check for the existing About Hero in the same release.
   await page.goto('http://127.0.0.1:4174/?page_id=5',{waitUntil:'networkidle'});
   check(await page.locator('.avix-about__cta').isVisible()&&await page.locator('.avix-about__platform').count()>0,name+': existing About Hero still renders');
  }finally{await browser.close()}
 }
 fs.writeFileSync(__dirname+'/service-benefits-custom-verification.json',JSON.stringify(report,null,2));
 console.log(JSON.stringify({checks:report.checks.length,errors:report.errors},null,2));if(report.errors.length)process.exitCode=1;
})().catch(error=>{console.error(error);process.exitCode=1});
