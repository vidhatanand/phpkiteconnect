<?php
$data = file_get_contents("php://input");
$currentTimeinSeconds = time(); 

include dirname(__FILE__)."/kiteconnect.php";
$configs_object = file_get_contents('./config.json');
$configs = json_decode($configs_object, true);

$openpos = get_openposition($configs);

if($openpos == null) {
    error_log('no order');
    if ($data == '::LONG::') {
        $ltp = get_ltp($configs, "260105");
        $inst = build_nearest_atm($ltp, 'CALL');
        $ltpopt = get_ltp($configs, "NFO:".$inst);
        error_log('buy '. $inst );
        buy($inst, $configs, $ltpopt);
        sl($inst, $configs, $ltpopt);
        
    
    } elseif ($data == '::SHORT::') {
        $ltp = get_ltp($configs, "260105");
        $inst = build_nearest_atm($ltp, 'PUT');
        $ltpopt = get_ltp($configs, "NFO:".$inst);
        error_log('buy '. $inst, $ltpopt);
        buy($inst, $configs, $ltpopt);
        sl($inst, $configs, $ltpopt);
    } 
} else {
    if($openpos->opt == 'CALL' && $data == '::SHORT::') {
        $ltpopt = get_ltp($configs, "NFO:".$openpos->tradingsymbol);
        sell($openpos->tradingsymbol, $configs, $openpos->quantity, $ltpopt);
    } elseif ($openpos->opt == 'PUT' && $data == '::LONG::') {
        $ltpopt = get_ltp($configs, "NFO:".$openpos->tradingsymbol);
        sell($openpos->tradingsymbol, $configs, $openpos->quantity, $ltpopt);
    }
}


function get_openposition($configs) {
    $kite = new KiteConnect($configs["api_key"]);
    $kite->setAccessToken($configs["access_token"]);

	$allpos = $kite->getPositions();
	foreach ($allpos->net as $key => $pos) {
		if ($pos->quantity != 0) {
			$openpos = $pos;
			$opt = substr($pos->tradingsymbol, -2);
			if ($opt == 'CE') {
				$openpos->opt = 'CALL';
			} elseif ($opt == 'PE') {
				$openpos->opt = 'PUT';			
			}	
		}
	}
    if(isset($openpos->opt)) {
        return $openpos;
    } else {
        return null;
    }

}

function buy($inst, $configs, $ltp) {
    $kite = new KiteConnect($configs["api_key"]);
	$kite->setAccessToken($configs["access_token"]);

	$order_id = $kite->placeOrder("regular", [
		"tradingsymbol" => $inst,
		"exchange" => "NFO",
		"quantity" => 100,
        "price" => $ltp - 5,
		"transaction_type" => "BUY",
		"order_type" => "LIMIT",
		"product" => "NRML"
	])["order_id"];

    return $order_id;
}

function sl($inst, $configs, $ltp) {
    $kite = new KiteConnect($configs["api_key"]);
	$kite->setAccessToken($configs["access_token"]);

	$order_id = $kite->placeOrder("regular", [
		"tradingsymbol" => $inst,
		"exchange" => "NFO",
		"quantity" => 100,
        "price" => $ltp - 10,
		"transaction_type" => "SELL",
		"order_type" => "SL-M",
		"product" => "NRML"
	])["order_id"];

    return $order_id;
}

function sell($inst, $configs, $quantity, $ltp) {
	$kite = new KiteConnect($configs["api_key"]);
	$kite->setAccessToken($configs["access_token"]);

	$order_id = $kite->placeOrder("regular", [
		"tradingsymbol" => $inst,
		"exchange" => "NFO",
        "price" => $ltp + 5,
		"quantity" => $quantity,
		"transaction_type" => "SELL",
		"order_type" => "LIMIT",
		"product" => "NRML"
	])["order_id"];
}

function get_ltp($configs, $symbols) {
    $kite = new KiteConnect($configs["api_key"]);
    $kite->setAccessToken($configs["access_token"]);
    $values = $kite->getLTP([$symbols]);

    foreach ($values as $key => $value) {
        $price = $value->last_price;
    }
    return $price;
}

function build_nearest_atm($price, $opt) {
    $rprice = round($price);
    $pstrike = $rprice - ($rprice % 500);
    $cstrike = $rprice - ($rprice % 500) + 500;
    $expiry = '21318';

    if ($opt == 'CALL') {
        return 'BANKNIFTY'.$expiry.$cstrike.'CE';
    } elseif($opt == 'PUT') {
        return 'BANKNIFTY'.$expiry.$pstrike.'PE';
    }
}

?>