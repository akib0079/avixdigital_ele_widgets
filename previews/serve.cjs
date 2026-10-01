// Self-contained previews, restricted to this folder and loopback.
const http=require('node:http'),fs=require('node:fs'),path=require('node:path');
const root=__dirname;
const types={'.html':'text/html; charset=utf-8','.css':'text/css; charset=utf-8','.js':'text/javascript; charset=utf-8','.png':'image/png','.webp':'image/webp','.svg':'image/svg+xml','.woff2':'font/woff2'};
http.createServer((request,response)=>{
 let pathname;try{pathname=decodeURIComponent(new URL(request.url,'http://127.0.0.1').pathname);}catch{response.writeHead(400).end();return;}
 if(pathname==='/'){response.writeHead(302,{Location:'/service-benefits/'}).end();return;}
 if(pathname.endsWith('/'))pathname+='index.html';
 const file=path.resolve(root,'.'+pathname);
 if(!file.startsWith(root+path.sep)){response.writeHead(403).end();return;}
 fs.readFile(file,(error,data)=>{
  if(error){response.writeHead(404).end('Not found');return;}
  response.writeHead(200,{'Content-Type':types[path.extname(file)]||'application/octet-stream','Cache-Control':'no-cache'});response.end(data);
 });
}).listen(4173,'127.0.0.1',()=>console.log('Avix previews: http://127.0.0.1:4173/service-benefits/'));
