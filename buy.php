<?php
	include dirname(__FILE__)."/kiteconnect.php";
	$configs_object = file_get_contents('./config.json');
    $configs = json_decode($configs_object, true);
    
	$kite = new KiteConnect($configs["api_key"]);
	$kite->setAccessToken($configs["access_token"]);

	// Get the list of positions.
	echo "Positions: \n";
	$allpos = $kite->getPositions();
	foreach ($allpos->net as $key => $pos) {
		if ($pos->quantity != 0) {
			$openpos = $pos;
			$opt = substr($pos->tradingsymbol, -2);
			if ($opt == CE) {
				$openpos->opt = 'CALL';
			} elseif ($opt == PE) {
				$openpos->opt = 'PUT';			
			}
		
		}
	}
	print_r($openpos);
	die;

	// Retrieve quote and market depth for list of instruments.
	echo "Quote: \n";
	print_r();

	// Place order.
	$order_id = $kite->placeOrder("regular", [
		"tradingsymbol" => "INFY",
		"exchange" => "NSE",
		"quantity" => 1,
		"transaction_type" => "BUY",
		"order_type" => "MARKET",
		"product" => "NRML"
	])["order_id"];

	echo "Order id is ".$order_id;

?>
