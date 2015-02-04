<?php
$dir = dirname(__FILE__) . '/';
if(!file_exists($dir . "synczip.json")){
   die("\nFile synczip.json is not found");
}
$ini = json_decode(file_get_contents($dir . "synczip.json"), true);

if(empty($ini['files_map'])) {
   var_dump($ini, $dir . "synczip.json");
   die("\nfiles_map is empty - nothing to copy\n");
}

$url = $ini['zip_path']['url'];
$zip = $ini['zip_path']['tmpdir'] . $ini['zip_path']['tmpfile'];
$ch = curl_init($url);

if(strpos($url, 'https') === false) 
{
}
else
{
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
}

curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION , TRUE);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$zipOut = curl_exec($ch);
curl_close($ch);
$fp = fopen($zip, 'w');
fwrite($fp, $zipOut);
fclose($fp); 
unset($zipOut);

//Copy files
$z = new ZipArchive();
if ($z->open($zip)) {
    foreach($ini['files_map'] as $from => $to) {
       $data = $z->getFromName($from);
       if($data){
          $path = explode('/', $to);
          array_splice($path, count($path) - 1, 1);
          $fullPath = $dir;
          foreach($path as $directory){
              if(!file_exists($fullPath.$directory)) {
                 mkdir($fullPath.$directory,0775);
              } 
              $fullPath .= $directory.'/';  
          }
          file_put_contents($dir.$to, $data);
          continue;
       }
       echo "\nWarning! Where is no $from in archive!;";
    }
}
echo "\nSuccess!\n";

//Remove zip
unlink($zip);
?>