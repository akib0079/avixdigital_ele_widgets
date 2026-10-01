const {firefox,webkit}=require('C:/Users/MT/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('node:fs');
(async()=>{
 const checks=[],errors=[],unavailable=[];
 for(const [name,engine] of [['Firefox',firefox],['WebKit',webkit]]){
  let browser; try { browser=await engine.launch({headless:true}); } catch(error) { unavailable.push(name+': launch unavailable ('+error.message.split('\n')[0]+')'); continue; }
  const page=await browser.newPage();
  page.on('pageerror',error=>errors.push(name+': '+error.message));
  for(const [width,height] of [[1440,900],[1366,768],[768,1024],[390,844],[375,667],[320,568],[844,390]]){
   await page.setViewportSize({width,height});
   await page.goto('http://127.0.0.1:4174/?page_id=5',{waitUntil:'networkidle'});
   await page.evaluate(()=>document.fonts.ready);
   await page.locator('.avix-about.is-enhanced').waitFor();
   const result=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,marqueeBottom:document.querySelector('.avix-about__marquee').getBoundingClientRect().bottom,width:document.querySelector('.avix-about').getBoundingClientRect().width,heading:getComputedStyle(document.querySelector('.avix-about__title')).fontSize,brokenImages:[...document.images].filter(i=>!i.complete||!i.naturalWidth).length}));
   checks.push({engine:name,width,height,...result});
   if(result.overflow||result.width!==width||result.brokenImages||(height>=568&&result.marqueeBottom>height+1))errors.push(`${name} layout ${width}×${height}`);
   await page.getByRole('button',{name:'Pause animation',exact:true}).click();
   const track=page.locator('[data-track="incoming"]');
   const paused=await track.evaluate(e=>getComputedStyle(e).transform);await page.waitForTimeout(200);
   if(paused!==await track.evaluate(e=>getComputedStyle(e).transform))errors.push(`${name} pause failed`);
   await page.getByRole('button',{name:'Resume animation',exact:true}).click();
  }
  await page.emulateMedia({reducedMotion:'reduce'});
  if(await page.locator('.avix-about__marquee').isVisible()||!await page.locator('.avix-about__platforms').isVisible())errors.push(`${name} reduced motion`);
  await browser.close();
 }
 const report={checks,errors,unavailable};fs.writeFileSync(__dirname+'/engine-verification.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report,null,2));if(errors.length)process.exitCode=1;
})().catch(error=>{console.error(error);process.exitCode=1;});
