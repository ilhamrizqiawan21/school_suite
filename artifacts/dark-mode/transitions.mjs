import { createRequire } from 'node:module';
import { readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
const root=process.cwd(), require=createRequire(root+'/package.json');
const {chromium,expect}=require('@playwright/test');
const fixtures=JSON.parse(await readFile(root+'/artifacts/font-cleanup/page-fixtures.json','utf8'));
const manifest=JSON.parse(await readFile(root+'/public/build/manifest.json','utf8'));
const blade=await readFile(root+'/resources/views/app.blade.php','utf8');
const bootstrap=blade.match(/<script nonce=.*?>([\s\S]*?)<\/script>/)[1];
const browser=await chromium.launch({headless:true}),results=[];
async function setup(context){
 await context.route('**/*',async route=>{
  const url=new URL(route.request().url());if(url.hostname!=='audit.test'||route.request().method()!=='GET')return route.abort();
  const fixture=Object.values(fixtures).find(f=>f.status===200&&f.url===url.pathname);
  if(fixture){if(route.request().headers()['x-inertia'])return route.fulfill({contentType:'application/json',headers:{'X-Inertia':'true'},body:JSON.stringify({...fixture.page,url:fixture.url})});const entry=manifest['resources/js/app.ts'],style=manifest['resources/css/app.css'];
   const boot=bootstrap.replace("@json($page['component'] ?? '')",JSON.stringify(fixture.page.component));
   const html=`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><script>${boot};window.__initialTheme=document.documentElement.dataset.theme;</script><link rel="stylesheet" href="/build/${style.file}">${(entry.css||[]).map(x=>`<link rel="stylesheet" href="/build/${x}">`).join('')}</head><body><script data-page="app" type="application/json">${JSON.stringify({...fixture.page,url:fixture.url}).replaceAll('<','\\u003c')}</script><div id="app"></div><script type="module" src="/build/${entry.file}"></script></body></html>`;
   return route.fulfill({contentType:'text/html',body:html});
  }
  try{return route.fulfill({body:await readFile(path.join(root,'public',url.pathname)),contentType:({'.js':'text/javascript','.css':'text/css','.png':'image/png','.svg':'image/svg+xml','.woff2':'font/woff2'})[path.extname(url.pathname)]||'application/octet-stream'});}catch{return route.fulfill({status:404,body:''});}
 });
}
async function load(page,key){await page.goto('http://audit.test'+fixtures[key].url);await page.waitForFunction(()=>document.querySelector('#app')?.children.length);}
async function theme(page,value){await expect(page.locator('html')).toHaveAttribute('data-theme',value);}
for(const width of [320,390,1440])for(const mode of ['snapshot','reduced','fallback']){
 console.log('Testing',width,mode);
 const context=await browser.newContext({viewport:{width,height:900},colorScheme:'dark',reducedMotion:mode==='reduced'?'reduce':'no-preference'});await setup(context);
 await context.addInitScript(mode=>{if(!localStorage.getItem('school-suite-theme'))localStorage.setItem('school-suite-theme','light');if(mode==='fallback')document.startViewTransition=undefined;window.__transitionCalls=0;const original=document.startViewTransition?.bind(document);if(original)document.startViewTransition=(...args)=>{window.__transitionCalls++;return original(...args);};},mode);
 const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));await load(page,'admin/dashboard');await theme(page,'light');expect(await page.evaluate(()=>window.__initialTheme)).toBe('light');
 const overflow=await page.locator('.topbar').evaluate(el=>[...el.querySelectorAll('button,a')].filter(x=>x.checkVisibility()).some(x=>x.getBoundingClientRect().right>innerWidth));expect(overflow).toBe(false);
 await page.getByRole('button',{name:'Gunakan tema gelap',exact:true}).click();await theme(page,'dark');await page.waitForTimeout(300);
 await expect(page.getByRole('button',{name:'Gunakan tema terang',exact:true})).toHaveAttribute('aria-pressed','true');
 expect(await page.evaluate(()=>localStorage.getItem('school-suite-theme'))).toBe('dark');
 const calls=await page.evaluate(()=>window.__transitionCalls);expect(calls).toBe(mode==='snapshot'?1:0);
 expect(await page.evaluate(()=>document.documentElement.classList.contains('theme-changing'))).toBe(false);
 if(width===390&&mode==='snapshot')await page.screenshot({path:root+'/artifacts/dark-mode/dashboard-mobile-dark.png'});
 await page.reload();await theme(page,'dark');expect(await page.evaluate(()=>window.__initialTheme)).toBe('dark');
 // Native clicks exercise cancellation while a previous snapshot is still pending.
 await page.getByRole('button',{name:'Gunakan tema terang',exact:true}).evaluate(el=>{for(let i=0;i<5;i++)el.click();});await theme(page,'light');await page.waitForTimeout(300);
 await page.getByRole('button',{name:'Gunakan tema gelap',exact:true}).click();await theme(page,'dark');await page.waitForTimeout(300);
 const second=await context.newPage();await load(second,'admin/dashboard');await second.evaluate(()=>localStorage.setItem('school-suite-theme','light'));await theme(page,'light');await expect(page.getByRole('button',{name:'Gunakan tema gelap',exact:true})).toHaveAttribute('aria-pressed','false');
 await second.evaluate(()=>localStorage.setItem('school-suite-theme','dark'));await theme(page,'dark');await second.close();
 for(const key of ['public/','public/login']){await load(page,key);await theme(page,'light');expect(await page.evaluate(()=>window.__initialTheme)).toBe('light');expect(await page.evaluate(()=>localStorage.getItem('school-suite-theme'))).toBe('dark');}
 await load(page,'admin/dashboard');await theme(page,'dark');expect(errors).toEqual([]);
 results.push({width,mode,toggle:true,initialPaint:true,reload:true,rapidToggle:true,crossTab:true,publicRoundtrip:true,errors});await context.close();
}
for(const blocked of [false,true]){
 const context=await browser.newContext({colorScheme:'dark'});await setup(context);
 if(blocked)await context.addInitScript(()=>Object.defineProperty(window,'localStorage',{get(){throw new DOMException('Storage blocked','SecurityError');}}));
 const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));await load(page,'admin/dashboard');await theme(page,'dark');expect(await page.evaluate(()=>window.__initialTheme)).toBe('dark');
 if(!blocked){await page.emulateMedia({colorScheme:'light'});await theme(page,'light');await page.emulateMedia({colorScheme:'dark'});await theme(page,'dark');}
 await page.getByRole('button',{name:'Gunakan tema terang',exact:true}).click();await theme(page,'light');expect(errors).toEqual([]);results.push({systemDefault:true,blockedStorage:blocked,toggle:true,errors});await context.close();
}
await browser.close();await writeFile(root+'/artifacts/dark-mode/transitions.json',JSON.stringify(results,null,2));console.log(JSON.stringify(results,null,2));
