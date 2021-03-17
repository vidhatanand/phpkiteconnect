<?php
$data = file_get_contents("php://input");
$currentTimeinSeconds = time(); 

include dirname(__FILE__)."/kiteconnect.php";
$configs_object = file_get_contents('./config.json');
$configs = json_decode($configs_object, true);

$openpos = get_openposition($configs);

if($openpos == null) {
    if ($data == '::LONG::') {
        $ltp = get_ltp($configs);
        $inst = build_nearest_atm($ltp, 'CALL');
        buy($inst, $configs);
    
    
    } elseif ($data == '::SHORT::') {
        $ltp = get_ltp($configs);
        $inst = build_nearest_atm($ltp, 'PUT');
        buy($inst, $configs);
    } 
} else {
    if($openpos->opt == 'CALL' && $data == '::SHORT::') {

    } elseif ($openpos->opt == 'PUT' && $data == '::LONG::') {

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
			if ($opt == CE) {
				$openpos->opt = 'CALL';
			} elseif ($opt == PE) {
				$openpos->opt = 'PUT';			
			}
		
		}
	}
    if(count($openpos) != 0) {
        return $openpos;
    } else {
        return null;
    }

}

function buy($inst, $configs) {
    $kite = new KiteConnect($configs["api_key"]);
	$kite->setAccessToken($configs["access_token"]);

	$order_id = $kite->placeOrder("regular", [
		"tradingsymbol" => $inst,
		"exchange" => "NFO",
		"quantity" => 1000,
		"transaction_type" => "BUY",
		"order_type" => "MARKET",
		"product" => "NRML"
	])["order_id"];

    return $order_id;
}

function sell($inst, $configs) {
	$kite = new KiteConnect($configs["api_key"]);
	$kite->setAccessToken($configs["access_token"]);

	$order_id = $kite->placeOrder("regular", [
		"tradingsymbol" => "INFY",
		"exchange" => "NSE",
		"quantity" => 1,
		"transaction_type" => "BUY",
		"order_type" => "MARKET",
		"product" => "NRML"
	])["order_id"];
}

function get_ltp($configs) {
    $kite = new KiteConnect($configs["api_key"]);
    $kite->setAccessToken($configs["access_token"]);
    $values = $kite->getLTP(["260105"]);

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