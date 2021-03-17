<?php
	include dirname(__FILE__)."/kiteconnect.php";
	$configs_object = file_get_contents('./config.json');
    $configs = json_decode($configs_object, true);

	function get_openorders($configs) {
		$kite = new KiteConnect($configs["api_key"]);
		$kite->setAccessToken($configs["access_token"]);
	
		$allpos = $kite->getOrders();

		$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
		$txt = print_r($allpos, true);
		fwrite($myfile, $txt);
		fclose($myfile);

		foreach ($allpos as $key => $pos) {
			if ($pos->status == 'TRIGGER PENDING') {
				$openpos = $pos;
				$opt = substr($pos->tradingsymbol, -2);
				if ($opt == 'CE') {
					$openpos->opt = 'CALL';
				} elseif ($opt == 'PE') {
					$openpos->opt = 'PUT';			
				}	
			}
		}

		foreach ($allpos as $key => $pos) {
			if ($openpos->parent_order_id == $pos->order_id) {
				$openpos->parent_price = $pos->price;
			}
		}
		if(isset($openpos->opt)) {
			return $openpos;
		} else {
			return null;
		}
	
	}


	$or = get_openorders($configs);
	print_r($or);
?>
