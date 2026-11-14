const fs=require('fs'),path=require('path');
const req=require('module').createRequire(process.cwd()+'/package.json');
const postcss=req('postcss');
const {parse}=req('@vue/compiler-sfc');
const walk=d=>fs.readdirSync(d,{withFileTypes:true}).flatMap(e=>e.isDirectory()?walk(path.join(d,e.name)):[path.join(d,e.name)]);
const files=walk('resources').filter(f=>/\.(css|vue|blade\.php|js|ts)$/.test(f)&&!f.endsWith('welcome.blade.php'));
let records=[],errors=[],inline=[],utilities=[];
for(const file of files){
 const source=fs.readFileSync(file,'utf8');
 let blocks=[];
 if(file.endsWith('.vue')){const sfc=parse(source); blocks=sfc.descriptor.styles.map(b=>({css:b.content,offset:b.loc.start.line-1}));}
 else if(file.endsWith('.css')) blocks=[{css:source,offset:0}];
 else if(file.endsWith('.blade.php')) blocks=[...source.matchAll(/<style[^>]*>([\s\S]*?)<\/style>/g)].map(m=>({css:m[1],offset:source.slice(0,m.index).split('\n').length-1}));
 for(const b of blocks) try{postcss.parse(b.css).walkDecls(d=>{if(/^(font($|-)|line-height$|letter-spacing$|word-spacing$|text-transform$)/.test(d.prop)||d.prop.startsWith('--type-')||d.prop.startsWith('--font-')){let media=[];for(let x=d.parent;x;x=x.parent)if(x.type==='atrule')media.push('@'+x.name+' '+x.params);records.push({file,line:d.source.start.line+b.offset,selector:d.parent.selector||'@'+d.parent.name,property:d.prop,value:d.value,important:d.important||false,context:media});}});}catch(e){errors.push({file,message:e.reason});}
 if(file.endsWith('.vue')){
 for(const m of source.matchAll(/style="([^"]*(?:font-size|font-weight|font-family|line-height)[^"]*)"/g))inline.push({file,line:source.slice(0,m.index).split('\n').length,value:m[1]});
 for(const m of source.matchAll(/\b(?:text-(?:xs|sm|base|lg|xl|\d+xl|\[[^\]]+\])|font-(?:normal|medium|semibold|bold|extrabold|black))\b/g))utilities.push({file,line:source.slice(0,m.index).split('\n').length,value:m[0]});
 }
}
const screen=records.filter(r=>!r.file.includes('/views/')&&!r.context.some(c=>c.includes('print')));
const sizes=screen.filter(r=>r.property==='font-size');
const literals=sizes.filter(r=>/^\s*[\d.]+(?:px|rem|em)\s*$/.test(r.value));
const px=r=>{const m=r.value.match(/^([\d.]+)(px|rem|em)$/);return m?+m[1]*(m[2]==='px'?1:16):null};
const small=literals.filter(r=>px(r)<12);
const byFile={};for(const r of small)byFile[r.file]=(byFile[r.file]||0)+1;
const countBy=(arr,key)=>arr.reduce((o,r)=>(o[r[key]]=(o[r[key]]||0)+1,o),{});
const summary={scannedFiles:files.length,vuePages:files.filter(f=>f.startsWith('resources/js/Pages/')&&f.endsWith('.vue')).length,screenFontSizeDeclarations:sizes.length,tokenFontSizeDeclarations:sizes.filter(r=>r.value.includes('var(--type-')).length,literalFontSizeDeclarations:literals.length,literalBelow12At16Root:small.length,filesBelow12:new Set(small.map(r=>r.file)).size,uniqueSizeValues:new Set(sizes.map(r=>r.value)).size,fontWeightValues:countBy(screen.filter(r=>r.property==='font-weight'),'value'),fontFamilies:countBy(screen.filter(r=>r.property==='font-family'),'value'),topSmallFiles:Object.entries(byFile).sort((a,b)=>b[1]-a[1]).slice(0,15),inlineTypography: inline.length,utilityTypography:utilities.length,parseErrors:errors};
fs.writeFileSync('artifacts/font-audit/source-inventory.json',JSON.stringify({summary,records,small,inline,utilities},null,2));
console.log(JSON.stringify(summary,null,2));
