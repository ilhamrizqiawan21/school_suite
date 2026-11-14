import {createRequire} from 'node:module';
import {readFile,writeFile} from 'node:fs/promises';
import path from 'node:path';
const root='/home/ilhamzp/Projects/school_suite';
const require=createRequire(root+'/package.json');
const {chromium}=require('@playwright/test');
const manifest=JSON.parse(await readFile(root+'/public/build/manifest.json','utf8'));
const fixtures=JSON.parse(await readFile(root+'/artifacts/font-cleanup/page-fixtures.json','utf8'));
const browser=await chromium.launch({headless:true});
const results=JSON.parse(await readFile(root+'/artifacts/font-cleanup/runtime-results.json','utf8').catch(()=>'[]'));
for(const [key,fixture] of Object.entries(fixtures)){
 if(fixture.status!==200)continue;
 for(const width of [390,1440]) for(const theme of ['light','dark']){
  if(results.some(r=>r.key===key&&r.width===width&&r.requestedTheme===theme))continue;
  const context=await browser.newContext({viewport:{width,height:900}});
  await context.addInitScript(theme=>localStorage.setItem('school-suite-theme',theme),theme);
  const page=await context.newPage(); const errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  const entry=manifest['resources/js/app.ts'],style=manifest['resources/css/app.css'];
  const payload={...fixture.page,url:fixture.url};
  const html=`<!doctype html><html lang="id" data-theme="${theme}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="app-base-path" content=""><link rel="stylesheet" href="/build/${style.file}">${(entry.css||[]).map(x=>`<link rel="stylesheet" href="/build/${x}">`).join('')}</head><body><script data-page="app" type="application/json">${JSON.stringify(payload).replaceAll('<','\\u003c')}</script><div id="app"></div><script type="module" src="/build/${entry.file}"></script></body></html>`;
  await page.route('**/*',async route=>{
   const url=new URL(route.request().url());
   if(url.hostname!=='audit.test')return route.abort();
   if(url.pathname===fixture.url)return route.fulfill({contentType:'text/html',body:html});
   try{const body=await readFile(path.join(root,'public',url.pathname));return route.fulfill({body,contentType:({'.js':'text/javascript','.css':'text/css','.png':'image/png','.woff2':'font/woff2','.jpg':'image/jpeg','.svg':'image/svg+xml'})[path.extname(url.pathname)]||'application/octet-stream'});}catch{return route.fulfill({status:404,body:''});}
  });
  await page.goto('http://audit.test'+fixture.url,{waitUntil:'domcontentloaded'});
  await page.waitForFunction(()=>document.querySelector('#app')?.children.length>0);
  await page.evaluate(()=>document.fonts.ready);
  await page.addStyleTag({content:'*,*::before,*::after{animation:none!important;transition:none!important}'});
  await page.evaluate(()=>new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve))));
  const data=await page.evaluate(()=>{
   const out=[]; const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);
   const canvas=document.createElement('canvas');canvas.width=canvas.height=1;const ctx=canvas.getContext('2d',{willReadFrequently:true});
   const rgba=color=>{ctx.clearRect(0,0,1,1);ctx.fillStyle=color;ctx.fillRect(0,0,1,1);return [...ctx.getImageData(0,0,1,1).data].map((v,i)=>i===3?v/255:v);};
   const blend=(a,b)=>a.slice(0,3).map((v,i)=>v*a[3]+b[i]*(1-a[3])).concat([1]);
   const luminance=a=>a.slice(0,3).map(x=>{x/=255;return x<=.04045?x/12.92:((x+.055)/1.055)**2.4}).reduce((s,x,i)=>s+x*[.2126,.7152,.0722][i],0);
   for(let n=walker.nextNode();n;n=walker.nextNode()){
    const text=n.textContent.trim().replace(/\s+/g,' '),el=n.parentElement;
    if(text.length<3||!el||el.closest('script,style,svg,.sr-only,[aria-hidden=true]')||!el.checkVisibility({checkOpacity:true,checkVisibilityCSS:true})||!el.getClientRects().length)continue;
    const rect=el.getBoundingClientRect(); if(rect.right<=0||rect.left>=innerWidth||rect.bottom<=0)continue;
    const cs=getComputedStyle(el),size=parseFloat(cs.fontSize),weight=parseFloat(cs.fontWeight);
    const ancestors=[];for(let p=el;p;p=p.parentElement)ancestors.unshift(p);
    let bg=[255,255,255,1],skip=false;for(const p of ancestors){const s=getComputedStyle(p);if(s.backgroundImage!=='none'||parseFloat(s.opacity)<1){skip=true;break;}bg=blend(rgba(s.backgroundColor),bg);}
    const color=blend(rgba(cs.color),bg),l1=luminance(color),l2=luminance(bg),ratio=(Math.max(l1,l2)+.05)/(Math.min(l1,l2)+.05);
    out.push({tag:el.tagName,selector:el.tagName.toLowerCase()+(typeof el.className==='string'?'.'+el.className.trim().split(/\s+/).join('.'):''),text:text.slice(0,130),size,weight,family:cs.fontFamily,lineHeight:cs.lineHeight,letterSpacing:cs.letterSpacing,color:cs.color,background:bg.slice(0,3).map(Math.round),contrast:skip?null:+ratio.toFixed(2),contrastThreshold:size>=24||(size>=18.6667&&weight>=700)?3:4.5,clipped:['hidden','clip'].includes(cs.overflowX)&&el.scrollWidth>el.clientWidth+2,nowrap:cs.whiteSpace==='nowrap'});
   }
   const familyCounts={};for(const r of out)familyCounts[r.family]=(familyCounts[r.family]||0)+1;
   return {theme:document.documentElement.dataset.theme||'light',rootFont:getComputedStyle(document.documentElement).fontSize,bodyFont:getComputedStyle(document.body).fontSize,total:out.length,familyCounts,small:out.filter(r=>r.size<12),controls:[...document.querySelectorAll('input:not([type=checkbox]):not([type=radio]),select,textarea')].filter(el=>el.checkVisibility()).map(el=>({tag:el.tagName,type:el.type,size:parseFloat(getComputedStyle(el).fontSize),label:el.placeholder||el.name})),contrastCandidates:out.filter(r=>r.contrast!==null&&r.contrast<r.contrastThreshold),headings:out.filter(r=>/^H[1-6]$/.test(r.tag)),clipped:out.filter(r=>r.clipped),overflow:document.documentElement.scrollWidth>innerWidth,overflowElements:[...document.querySelectorAll('body *')].filter(el=>el.getBoundingClientRect().right>innerWidth+2).slice(0,12).map(el=>({tag:el.tagName,cls:String(el.className),width:el.getBoundingClientRect().width,right:el.getBoundingClientRect().right}))};
  });
  const name=key.replaceAll('/','-')+'-'+width+'-'+theme;
  if(width===390&&theme==='light') await page.screenshot({path:root+'/artifacts/font-cleanup/'+name+'.png',fullPage:true});
  let stress=null;
  let examScale=null;
  if(fixture.page.component==='Assessment/Exams/Attempt'){
   const zoom=page.getByRole('button',{name:/Ukuran teks soal/});
   const before=await page.locator('.question-card h2').evaluate(el=>parseFloat(getComputedStyle(el).fontSize));
   const answerBefore=await page.locator('.options b').first().evaluate(el=>parseFloat(getComputedStyle(el).fontSize));
   for(let i=0;i<4;i++) await zoom.click();
   examScale=await zoom.textContent();
   if(!examScale.includes('200%'))throw new Error('Exam cannot reach 200%');
   const after=await page.locator('.question-card h2').evaluate(el=>parseFloat(getComputedStyle(el).fontSize));
   const answerAfter=await page.locator('.options b').first().evaluate(el=>parseFloat(getComputedStyle(el).fontSize));
   if(Math.abs(after/before-2)>.01||Math.abs(answerAfter/answerBefore-2)>.01)throw new Error('Question and answers must double in size');
   examScale={label:examScale,promptBefore:before,promptAfter:after,answerBefore,answerAfter};
   await page.getByRole('button',{name:/Soal 2/}).click();
   const arabic=await page.locator('.question-card h2').getAttribute('class');
   if(!arabic?.includes('arabic-copy'))throw new Error('Arabic styling lost');
   await page.screenshot({path:root+'/artifacts/font-cleanup/exam-'+width+'-'+theme+'-200.png',fullPage:true});
   await page.getByRole('button',{name:/Soal 3/}).click();
   const matchSize=await page.locator('.matches select').evaluate(el=>parseFloat(getComputedStyle(el).fontSize));
   if(matchSize<32)throw new Error('Matching answers must scale too');
   if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw new Error('Exam overflows at 200%');
   examScale.matchSize=matchSize;
  }
  if(theme==='light'&&width===390&&fixture.page.component!=='Assessment/Reports/Show'){
   await page.setViewportSize({width:320,height:900});
   stress={reflow320:await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,width:document.documentElement.scrollWidth}))};
   await page.setViewportSize({width:390,height:900});
   await page.addStyleTag({content:'body *:not(svg):not(svg *) {line-height:1.5!important;letter-spacing:.12em!important;word-spacing:.16em!important} p{margin-bottom:2em!important}'});
   stress.spacing=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,clipped:[...document.querySelectorAll('a,button,input,select,label,h1,h2,h3')].filter(el=>{const s=getComputedStyle(el);return el.checkVisibility()&&['hidden','clip'].includes(s.overflowX)&&el.scrollWidth>el.clientWidth+2}).map(el=>({tag:el.tagName,text:(el.innerText||el.placeholder||'').slice(0,80)}))}));
   await page.screenshot({path:root+'/artifacts/font-cleanup/'+name+'-spacing.png',fullPage:true});
  }
  results.push({key,width,requestedTheme:theme,component:fixture.page.component,errors,...data,stress,examScale});
  await writeFile(root+'/artifacts/font-cleanup/runtime-results.json',JSON.stringify(results,null,2));
  console.log(JSON.stringify({key,width,theme:data.theme,total:data.total,small:data.small.length,contrastCandidates:data.contrastCandidates.length,errors,stress}));
  await context.close();
 }
}
await browser.close();
await writeFile(root+'/artifacts/font-cleanup/runtime-results.json',JSON.stringify(results,null,2));
