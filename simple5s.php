<?php
$data = file_get_contents("php://input");
$currentTimeinSeconds = time(); 

include dirname(__FILE__)."/kiteconnect.php";
$configs_object = file_get_contents('./config.json');
$configs = json_decode($configs_object, true);

$openpos = get_openposition($configs);

$kite = new KiteConnect($configs["api_key"]);
$kite->setAccessToken($configs["access_token"]);
$allpos = $kite->getOrders();

$openord = get_openorders($configs, $allpos);
$openslorder = get_pendingslorders($configs, $allpos);

if($openpos == null) {
    error_log('no order');
    if ($data == 'buy') {
        $ltp = get_ltp($configs, "260105");
        $inst = build_nearest_atm($ltp, 'CALL');
        $ltpopt = get_ltp($configs, "NFO:".$inst);
        error_log('buy '. $inst );
        if($openord != null) {
            $ltpopenord = get_ltp($configs, "NFO:".$openord->tradingsymbol);
            if($ltpopenord - $openord->price > 5 ) {
                $cancel = $kite->cancelOrder($openord->variety, $openord->order_id);
                if($cancel) {
                    buy($inst, $configs, $ltpopt);
                }   
            }
        } else {
            buy($inst, $configs, $ltpopt);
        }

    } elseif ($data == 'sell') {
        $ltp = get_ltp($configs, "260105");
        $inst = build_nearest_atm($ltp, 'PUT');
        $ltpopt = get_ltp($configs, "NFO:".$inst);
        error_log('buy '. $inst, $ltpopt);
        if($openord != null) {
            $ltpopenord = get_ltp($configs, "NFO:".$openord->tradingsymbol);
            if($ltpopenord - $openord->price > 5 ) {
                $cancel = $kite->cancelOrder($openord->variety, $openord->order_id);
                if($cancel) {
                    buy($inst, $configs, $ltpopt);
                }   
            }
        } else {
            buy($inst, $configs, $ltpopt);
        }
    } 
} else {
    if(($openpos->opt == 'CALL' && $data == 'sell') or ($openpos->opt == 'PUT' && $data == 'buy')) {
        // bhago
        $ltpopt = get_ltp($configs, "NFO:".$openpos->tradingsymbol);
        if($openslorder != null) {
            $params["trigger_price"] = $ltpopt-1; 
            $kite->modifyOrder($openslorder->variety, $openslorder->order_id, $params);
        } 
    } elseif (($openpos->opt == 'CALL' && $data == 'buy') or ($openpos->opt == 'PUT' && $data == 'sell')) {
        // Trail
        $ltpopt = get_ltp($configs, "NFO:".$openpos->tradingsymbol);
        if($openslorder != null) {
            
            if($ltpopt - $openslorder->parent_price > 3) {
                $params["trigger_price"] = $openslorder->parent_price + 2;
                $kite->modifyOrder($openslorder->variety, $openslorder->order_id, $params);
            } elseif($ltpopt - $openslorder->trigger_price > 10) {
                $kite->modifyOrder($openslorder->variety, $openslorder->order_id, $params);             
            }
        
        } 
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

function get_openorders($configs, $orders) {

    foreach ($orders as $key => $pos) {
        if ($pos->status == 'OPEN') {
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


function get_pendingslorders($configs, $orders) {

    foreach ($orders as $key => $pos) {
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

    foreach ($orders as $key => $pos) {
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



function buy($inst, $configs, $ltp) {
    $kite = new KiteConnect($configs["api_key"]);
	$kite->setAccessToken($configs["access_token"]);

	$order_id = $kite->placeOrder("co", [
		"tradingsymbol" => $inst,
		"exchange" => "NFO",
		"quantity" => 25,
        //"price" => $ltp,
        "trigger_price" => $ltp - round($ltp/50),
		"transaction_type" => "BUY",
		"order_type" => "MARKET",
		"product" => "NRML"
	])["order_id"];

    return $order_id;
}

function sell($inst, $configs, $quantity, $ltp) {
	$kite = new KiteConnect($configs["api_key"]);
	$kite->setAccessToken($configs["access_token"]);
    $kite->modifyOrder($variety, $order_id, $params);
	$order_id = $kite->placeOrder("regular", [
		"tradingsymbol" => $inst,
		"exchange" => "NFO",
        "trigger_price" => $ltp + 5,
		"quantity" => $quantity,
		"transaction_type" => "SELL",
		"order_type" => "SL-M",
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
    $expiry = '21MAR';

    if ($opt == 'CALL') {
        return 'BANKNIFTY'.$expiry.$cstrike.'CE';
    } elseif($opt == 'PUT') {
        return 'BANKNIFTY'.$expiry.$pstrike.'PE';
    }
}

?>