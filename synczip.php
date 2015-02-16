<?php
function setChmod($file, $chmod){
   $chmod = intval($chmod, 8);
   chmod($file, $chmod);
}

function sendMail($to, $subject, $body, $die = false){
   mail($to, $subject, $body);
   if($die){
      die();   
   }
}

function verifyXHubSignature($secret = null){
   if(empty($secret)){
      return true;   
   }
   if(!isset($_SERVER[ 'CONTENT_TYPE' ]) || !isset($_SERVER[ 'HTTP_X_HUB_SIGNATURE' ])){
      return false;   
   }
   $hubSignature = $_SERVER['HTTP_X_HUB_SIGNATURE'];
   list($algo, $hash) = explode('=', $hubSignature, 2);
   $contentType = $_SERVER[ 'CONTENT_TYPE' ];
	if( $contentType === 'application/x-www-form-urlencoded' )
	{
		$payload = filter_input( INPUT_POST, 'payload' );
	}
	else if( $contentType === 'application/json' )
	{
		$payload = file_get_contents( 'php://input' );
	}
	else {
      return false;	
	}
   $payloadHash = hash_hmac($algo, $payload, $secret);
   
   return $algo . '=' . $payloadHash === $_SERVER[ 'HTTP_X_HUB_SIGNATURE' ];
}


//++++++++++++ INIT SETTINGS +++++++++++++++

$host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
$dir = dirname(__FILE__) . '/';
if(!file_exists($dir . "synczip.json")){
   sendMail($ini['mailto'], 'File synczip.json is not found on '. $host, 'File synczip.json is not found on '. $host, true);
}
$ini = json_decode(file_get_contents($dir . "synczip.json"), true);

if(empty($ini['files_map'])) {
   sendMail($ini['mailto'], 'Files_map is empty - nothing to copy on '. $host, 'Files_map is empty - nothing to copy on '. $host, true);
}
$url = $ini['zip_path']['url'];
$zip = $ini['zip_path']['tmpdir'] . $ini['zip_path']['tmpfile'];


//++++++++++++ FIREWALL +++++++++++++++++++
if(!verifyXHubSignature($ini['X_Hub_Secret'])){
   sendMail($ini['mailto'], 'Error while sync on '. $host, 'Wrong signature on '. $host, true);
}


//++++++++++++ LOAD ARCHIVE +++++++++++++++

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


//++++++++++++ COPY FILES +++++++++++++++

$warnings = array();
$z = new ZipArchive();
if ($z->open($zip)) {
    foreach($ini['files_map'] as $from => $dist) {
       $chmod = $dist['chmod'];
       $to = $dist['saveto'];
       $data = $z->getFromName($from);
       if($data){
          $path = explode('/', $to);
          array_splice($path, count($path) - 1, 1);
          $fullPath = $dir;
          foreach($path as $directory){
              if(!file_exists($fullPath.$directory)) {
                 mkdir($fullPath.$directory,intval($ini['mkdir_chmod'], 8));
              }
              else {
                  setChmod($fullPath.$directory, $ini['mkdir_chmod']);             
              }
              $fullPath .= $directory.'/';  
          }
          file_put_contents($dir.$to, $data);
          setChmod($dir.$to, $chmod);
          continue;
       }
       $warnings[] = "Warning! Where is no ". $from ." in archive!;";
    }
}
$body = 'Synced with synczip successfully on '.$host;
$body .= empty($warnings) ? '' : "\nBut there are some problems:\n\n" . implode('\n', $warnings);
sendMail($ini['mailto'], 'Synced with synczip successfully on '.$host, $body);


//++++++++++++ CLEAN UP +++++++++++++++

unlink($zip);
?>