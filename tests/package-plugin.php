<?php
$root=dirname(__DIR__);
// Version comes from the plugin header, so the file name always matches the release.
preg_match('/^\s*\*\s*Version:\s*([0-9.]+)/m',file_get_contents($root.'/avix-elementor-widgets.php'),$version);
$zip=new ZipArchive();
$destination=$root.'/dist/avix-elementor-widgets-'.$version[1].'.zip';
if(true!==$zip->open($destination,ZipArchive::CREATE|ZipArchive::OVERWRITE))throw new RuntimeException('Cannot create package');
$files=[$root.'/avix-elementor-widgets.php',$root.'/ABOUT-HERO.md',$root.'/SERVICE-BENEFITS.md'];
foreach(['assets','includes'] as $directory){
 $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory,FilesystemIterator::SKIP_DOTS));
 foreach($iterator as $file)if($file->isFile())$files[]=$file->getPathname();
}
foreach($files as $file){$relative=str_replace('\\','/',substr($file,strlen($root)+1));$zip->addFile($file,'avix-elementor-widgets/'.$relative);}
$zip->close();
$check=new ZipArchive();$check->open($destination);
foreach(['includes/widgets/class-team.php','assets/css/team.css','assets/js/team.js','includes/widgets/class-service-benefits.php','assets/css/service-benefits.css','assets/js/service-benefits.js','assets/images/service-benefits/service-3-768.webp','SERVICE-BENEFITS.md'] as $file)if(false===$check->locateName('avix-elementor-widgets/'.$file))throw new RuntimeException('Missing '.$file);
for($i=0;$i<$check->numFiles;$i++)if(preg_match('~/(tests|previews|wordpress|local-credentials)/~',$check->getNameIndex($i)))throw new RuntimeException('Development file in package');
echo json_encode(['package'=>$destination,'files'=>$check->numFiles,'bytes'=>filesize($destination)],JSON_PRETTY_PRINT).PHP_EOL;
$check->close();
