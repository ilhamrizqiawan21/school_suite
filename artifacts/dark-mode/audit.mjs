import {createRequire} from 'node:module';
import {readFile,writeFile} from 'node:fs/promises';
import path from 'node:path';
const root=process.cwd();
const require=createRequire(root+'/package.json');
const {chromium}=require('@playwright/test');
const manifest=JSON.parse(await readFile(root+'/public/build/manifest.json','utf8'));
const fixtures=JSON.parse(await readFile(root+(process.argv.includes('--extended')?'/artifacts/dark-mode/extended-fixtures.json':'/artifacts/font-cleanup/page-fixtures.json'),'utf8'));
const browser=await chromium.launch({headless:true});
const evidence=process.argv.includes('--evidence');
const results=[];
for(const [key,fixture] of Object.entries(fixtures)){
 if(fixture.status!==200)continue;
 if(process.argv.includes('--focus')&&key!=='exam/attempt')continue;
 if(process.argv.includes('--focus-pack')&&key!=='teacher/penilaian/paket-soal')continue;
 for(const width of [390,1440]) for(const theme of (process.argv.includes('--extended')&&!process.argv.includes('--after')?['dark']:['light','dark'])){
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
  await page.goto('https://audit.test'+fixture.url,{waitUntil:'domcontentloaded'});
  await page.waitForFunction(()=>document.querySelector('#app')?.children.length>0).catch(async error=>{console.log(JSON.stringify({key,errors,renderFailed:true}));await page.screenshot({path:root+'/artifacts/dark-mode/render-failure.png'});throw error;});
  await page.evaluate(()=>document.fonts.ready);

  await page.waitForTimeout(350);
  const inspect=()=>page.evaluate(()=>{
    const canvas=document.createElement('canvas'),ctx=canvas.getContext('2d');
    function rgb(color){ctx.clearRect(0,0,1,1);ctx.fillStyle=color;ctx.fillRect(0,0,1,1);return [...ctx.getImageData(0,0,1,1).data].map((x,i)=>i===3?x/255:x);}
    function blend(f,b){return f.slice(0,3).map((x,i)=>x*f[3]+b[i]*(1-f[3])).concat(1);}
    function bg(el){const chain=[];for(let e=el;e;e=e.parentElement)chain.unshift(e);let c=[255,255,255,1];for(const e of chain)c=blend(rgb(getComputedStyle(e).backgroundColor),c);return c;}
    function lum(c){return c.slice(0,3).map(x=>{x/=255;return x<=.04045?x/12.92:((x+.055)/1.055)**2.4}).reduce((s,x,i)=>s+x*[.2126,.7152,.0722][i],0);}
    const issues=[],bright=[];let checked=0;
    for(const el of document.querySelectorAll('body *')){
      if(!el.checkVisibility({checkOpacity:true,checkVisibilityCSS:true})||el.closest('[disabled],[aria-disabled=true],svg,script,style'))continue;
      const s=getComputedStyle(el),r=el.getBoundingClientRect();if(!r.width||!r.height||r.right<0||r.left>innerWidth)continue;
      const own=rgb(s.backgroundColor);if(own[3]>.9&&lum(own)>.7&&r.width*r.height>3000)bright.push({tag:el.tagName,cls:String(el.className),color:s.backgroundColor});
      const text=[...el.childNodes].filter(n=>n.nodeType===Node.TEXT_NODE).map(n=>n.textContent.trim()).join(' ').trim();
      if(!text)continue;checked++;
      const b=bg(el),f=blend(rgb(s.color),b),ratio=(Math.max(lum(f),lum(b))+.05)/(Math.min(lum(f),lum(b))+.05),large=parseFloat(s.fontSize)>=24||(parseFloat(s.fontSize)>=18.66&&+s.fontWeight>=700),min=large?3:4.5;
      if(ratio<min-.02)issues.push({text:text.slice(0,90),cls:String(el.className),ratio:+ratio.toFixed(2),min,color:s.color,bg:b,font:s.fontSize});
    }
    return {theme:document.documentElement.dataset.theme,checked,issues,bright};
  });
  const base=await inspect();
  const modalNames={'teacher/penilaian/paket-soal':'+ Buat paket','admin/akademik/siswa':'Tambah siswa','admin/akademik/guru':'Tambah guru','admin/akademik/jadwal':'Tambah Slot Jadwal','admin/pengaturan/pengguna':'Tambah pengguna','teacher/penilaian/bank-soal':'Tambah soal'};
  let opened=null;const label=modalNames[key];
  if(label){const b=page.getByRole('button',{name:label,exact:true});if(await b.count()){await b.click();await page.waitForTimeout(250);opened=await inspect();}}
  if(width===1440&&theme==='dark'&&['admin/akademik/siswa','teacher/penilaian/bank-soal','admin/dashboard','exam/attempt','admin/ujian/monitor','admin/pembelajaran/progress','admin/spmb/seleksi'].includes(key))await page.screenshot({path:root+'/artifacts/dark-mode/'+key.replaceAll('/','-')+'-'+(process.argv.includes('--after')?'after':'before')+'.png'});
  results.push({key,width,requested:theme,errors,...base,opened});
  console.log(JSON.stringify({key,width,theme:base.theme,issues:base.issues.length,modal:opened?.issues.length,bright:base.bright.length}));
  await context.close();
 }
}
await writeFile(root+'/artifacts/dark-mode/'+((process.argv.includes('--extended')?'extended-':'')+(process.argv.includes('--focus-pack')?'pack-final':process.argv.includes('--focus')?'exam-final':process.argv.includes('--after')?'after':'before'))+'.json',JSON.stringify(results,null,2));
await browser.close();
