<?php
$data = file_get_contents("php://input");

$currentTimeinSeconds = time(); 

if ($data == '::LONG::') {
    $configs[$currentTimeinSeconds] = '1';
    error_log("111111");

} elseif ($data == '::SHORT::') {
    $configs[$currentTimeinSeconds] = '-1';
    error_log("-----111111");

}

$configs_object = json_encode($configs);
file_put_contents('./log.json', $configs_object);

// include dirname(__FILE__)."/kiteconnect.php";
// $configs_object = file_get_contents('./config.json');
// $configs = json_decode($configs_object, true);

// $kite = new KiteConnect($configs["api_key"]);
// $kite->setAccessToken($configs["access_token"]);
// foreach ($events as $event) {
//   // Here, you now have each event and can process them how you like
//   process_event($event);
// }
?>