import {createRequire} from 'node:module';
import {readFile,writeFile} from 'node:fs/promises';
import path from 'node:path';
const root='/home/ilhamzp/Projects/school_suite';
const require=createRequire(root+'/package.json');
const {chromium}=require('@playwright/test');
const manifest=JSON.parse(await readFile(root+'/public/build/manifest.json','utf8'));
const fixtures=JSON.parse(await readFile(root+'/artifacts/font-cleanup/page-fixtures.json','utf8'));
const browser=await chromium.launch({headless:true});
const evidence=process.argv.includes('--evidence');
const results=evidence?[]:JSON.parse(await readFile(root+'/artifacts/button-audit/runtime-results.json','utf8').catch(()=>'[]'));
for(const [key,fixture] of Object.entries(fixtures)){
 if(fixture.status!==200)continue;
 for(const width of [390,1440]) for(const theme of ['light','dark']){
  if(evidence&&(!['admin/akademik/siswa','admin/akademik/guru','admin/akademik/jadwal'].includes(key)||width!==390||theme!=='light'))continue;
  if(results.some(r=>r.key===key&&r.width===width&&r.themeRequested===theme))continue;
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
   if(route.request().method()!=='GET')return route.abort();
   if(url.pathname===fixture.url)return route.fulfill({contentType:'text/html',body:html});
   try{const body=await readFile(path.join(root,'public',url.pathname));return route.fulfill({body,contentType:({'.js':'text/javascript','.css':'text/css','.png':'image/png','.woff2':'font/woff2','.jpg':'image/jpeg','.svg':'image/svg+xml'})[path.extname(url.pathname)]||'application/octet-stream'});}catch{return route.fulfill({status:404,body:''});}
  });
  await page.goto('http://audit.test'+fixture.url,{waitUntil:'domcontentloaded'});
  await page.waitForFunction(()=>document.querySelector('#app')?.children.length>0);
  await page.evaluate(()=>document.fonts.ready);
  await page.addStyleTag({content:'*,*::before,*::after{animation:none!important;transition:none!important}'});
  await page.evaluate(()=>new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve))));
  const inspect=()=>page.evaluate(()=>{
   const all=[...document.querySelectorAll('button,a,input[type=submit],input[type=button]')].filter(el=>{
    if(el.tagName==='A'&&!/button|btn|action|tab|pill|chip/.test(el.className))return false;
    const r=el.getBoundingClientRect();return el.checkVisibility({checkOpacity:true,checkVisibilityCSS:true})&&r.width>0&&r.height>0&&r.right>0&&r.left<innerWidth&&r.bottom>0;
   });
   const controls=all.map(el=>{const r=el.getBoundingClientRect(),s=getComputedStyle(el),svg=[...el.querySelectorAll('svg')].map(x=>({width:x.getBoundingClientRect().width,height:x.getBoundingClientRect().height,stroke:getComputedStyle(x).strokeWidth})),before=getComputedStyle(el,'::before'),text=(el.innerText||el.value||'').replace(/\s+/g,' ').trim();return {tag:el.tagName,name:el.getAttribute('aria-label')||el.title||text,text,classes:el.className,width:+r.width.toFixed(2),height:+r.height.toFixed(2),minWidth:s.minWidth,minHeight:s.minHeight,padding:[s.paddingTop,s.paddingRight,s.paddingBottom,s.paddingLeft],radius:s.borderRadius,fontSize:s.fontSize,fontWeight:s.fontWeight,lineHeight:s.lineHeight,background:s.backgroundColor,color:s.color,border:s.border,display:s.display,svg,pseudo:before.content!=='none'&&before.content!=='normal'?{text:before.content,width:before.width,height:before.height}:null,disabled:el.matches(':disabled')||el.getAttribute('aria-disabled')==='true',type:el.getAttribute('type'),parent:el.parentElement.className,context:el.closest('td')?'table':el.closest('header')?'header':el.closest('form')?'form':el.closest('nav')?'navigation':'content',iconOnly:!text&&svg.length>0,hasName:Boolean(el.getAttribute('aria-label')||el.title||text),rect:{x:r.x,y:r.y,right:r.right,bottom:r.bottom}};});
   return {theme:document.documentElement.dataset.theme||'light',controls};
  });
  const base=await inspect();
  const states=[];
  const primary=page.locator('button.primary-button,button.student-action-add,button.teacher-action-add').first();
  if(await primary.count()&&await primary.isVisible()){
   await primary.hover();states.push({state:'hover',...await primary.evaluate(el=>{const s=getComputedStyle(el);return {name:el.textContent.trim(),color:s.color,background:s.backgroundColor,outline:s.outline}})});
   await primary.focus();states.push({state:'focus',...await primary.evaluate(el=>{const s=getComputedStyle(el);return {name:el.textContent.trim(),color:s.color,background:s.backgroundColor,outline:s.outline}})});
  }
  const modalNames={'admin/akademik/siswa':'Tambah siswa','admin/akademik/guru':'Tambah guru','admin/akademik/jadwal':'Tambah Slot Jadwal','admin/pengaturan/pengguna':'Tambah pengguna','teacher/penilaian/bank-soal':'+ Tambah soal'};
  let opened=null;
  const label=modalNames[key];
  if(label){const trigger=page.getByRole('button',{name:label,exact:true});if(await trigger.count()){
   if(evidence)await trigger.locator('..').screenshot({path:root+'/artifacts/button-audit/'+key.replaceAll('/','-')+'-header.png'});
   await trigger.click();await page.evaluate(()=>new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r))));opened=await inspect();
   if(width===390&&theme==='light')await page.screenshot({path:root+'/artifacts/button-audit/'+key.replaceAll('/','-')+'-modal.png',fullPage:false});
  }}
  if(evidence&&opened){const name={'admin/akademik/siswa':'Simpan siswa','admin/akademik/guru':'Simpan Data','admin/akademik/jadwal':'Simpan Jadwal'}[key];const save=page.getByRole('button',{name,exact:true});if(await save.count())await save.locator('..').screenshot({path:root+'/artifacts/button-audit/'+key.replaceAll('/','-')+'-footer.png'});}
  const result={key,width,themeRequested:theme,component:fixture.page.component,errors,...base,states,opened};
  results.push(result);
  await writeFile(root+'/artifacts/button-audit/'+(evidence?'evidence-results.json':'runtime-results.json'),JSON.stringify(results,null,2));
  console.log(JSON.stringify({key,width,theme:base.theme,buttons:base.controls.length,modalButtons:opened?.controls.length,errors}));
  await context.close();
 }
}
await browser.close();
