<?php
	include dirname(__FILE__)."/kiteconnect.php";
	$configs_object = file_get_contents('./config.json');
    $configs = json_decode($configs_object, true);

		$kite = new KiteConnect($configs["api_key"]);
		$kite->setAccessToken($configs["access_token"]);
		$values = $kite->getLTP(["NFO:BANKNIFTY2131835000PE"]);
print_r($values);

?>
