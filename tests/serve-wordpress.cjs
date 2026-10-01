// Serve static assets directly and distribute dynamic requests across PHP workers.
// Windows PHP's built-in single worker otherwise delays Elementor's many editor requests.
const http=require('http'),fs=require('fs'),path=require('path'),{spawn}=require('child_process');
const root=path.join(__dirname,'wordpress'),ports=[4176,4177,4178,4179],children=[];
let index=0;
for(const port of ports){
 const log=fs.openSync(path.join(__dirname,'php-worker-'+port+'.log'),'w');
 children.push(spawn('C:/Users/MT/AppData/Local/Temp/avix-php-runtime/php.exe',['-c',path.join(__dirname,'php.ini'),'-S','127.0.0.1:'+port,'-t',root],{stdio:['ignore','ignore',log],windowsHide:true}));
}
const types={'.css':'text/css','.js':'application/javascript','.png':'image/png','.gif':'image/gif','.svg':'image/svg+xml','.webp':'image/webp','.jpg':'image/jpeg','.woff':'font/woff','.woff2':'font/woff2','.ttf':'font/ttf','.ico':'image/x-icon','.map':'application/json'};
const server=http.createServer((req,res)=>{
 const url=new URL(req.url,'http://127.0.0.1:4174');
 const filename=path.resolve(root,'.'+decodeURIComponent(url.pathname)),type=types[path.extname(filename).toLowerCase()];
 if(filename.startsWith(root+path.sep)&&type&&fs.existsSync(filename)&&fs.statSync(filename).isFile()){
  res.writeHead(200,{'Content-Type':type});fs.createReadStream(filename).pipe(res);return;
 }
 const proxy=http.request({hostname:'127.0.0.1',port:ports[index++%ports.length],method:req.method,path:req.url,headers:req.headers},response=>{res.writeHead(response.statusCode,response.headers);response.pipe(res)});
 proxy.on('error',error=>{res.writeHead(502);res.end(error.message)});req.pipe(proxy);
});
server.listen(4174,'127.0.0.1',()=>console.log('Local WordPress integration server: http://127.0.0.1:4174/'));
const stop=()=>{children.forEach(child=>child.kill());server.close();process.exit()};
process.on('SIGINT',stop);process.on('SIGTERM',stop);process.on('exit',()=>children.forEach(child=>child.kill()));
