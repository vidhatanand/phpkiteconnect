<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);
$data = file_get_contents("php://input");
$currentTimeinSeconds = time(); 

include dirname(__FILE__)."/kiteconnect.php";
$configs_object = file_get_contents('./config.json');
$configs = json_decode($configs_object, true);

set_time_limit(0);//Run infinitely 
 
while (true) 
{ 
  //==do your rest of works here.

  $openpos = get_openposition($configs);

  $kite = new KiteConnect($configs["api_key"]);
  $kite->setAccessToken($configs["access_token"]);
  $allpos = $kite->getOrders();
    
  $openord = get_openorders($configs, $allpos);
  $openslorder = get_pendingslorders($configs, $allpos);
  
  
  if ($openslorder != null && $openpos != null) {
      echo 'open sl order found'.PHP_EOL; 
      $ltpopt = get_ltp($configs, "NFO:".$openslorder->tradingsymbol);
      
      if ($ltpopt > $openslorder->parent_price) {
          if($ltpopt - $openslorder->parent_price > 10 && ($openslorder->parent_price > $openslorder->trigger_price) && ($openslorder->trail0 != true)) {
              echo 'Trail 0 -- ';
              echo ' - LTP: '.$ltpopt;
              echo ' - Parent_price: '.$openslorder->parent_price;
              echo ' - Trigger_price: '.$openslorder->trigger_price;
              $params["trigger_price"] = round($openslorder->parent_price) + 2;
              $out = $kite->modifyOrder($openslorder->variety, $openslorder->order_id, $params);
              if (isset($out->order_id)) {
                $openslorder->trail0 = true;
              }
          } elseif(($ltpopt - $openslorder->parent_price > 30) && ($ltpopt > $openslorder->parent_price) && ($openslorder->parent_price < $openslorder->trigger_price) && ($openslorder->trail1 != true)) {
            echo 'Trail 1 -- ';
            echo ' - LTP: '.$ltpopt;
            echo ' - Parent_price: '.$openslorder->parent_price;
            echo ' - Trigger_price: '.$openslorder->trigger_price;
              $params["trigger_price"] = ($openslorder->parent_price) + 20;
              $out = $kite->modifyOrder($openslorder->variety, $openslorder->order_id, $params);     
              if (isset($out->order_id)) {
                $openslorder->trail1 = true;
              }        
          }elseif (($ltpopt - $openslorder->parent_price > 70) && ($openslorder->parent_price < $openslorder->trigger_price) && (round($openslorder->trigger_price - $openslorder->parent_price) == 20) && ($openslorder->trail2 != true)) {
            echo 'Trail 2 -- ';
            echo ' - LTP: '.$ltpopt;
            echo ' - Parent_price: '.$openslorder->parent_price;
            echo ' - Trigger_price: '.$openslorder->trigger_price;
              $params["trigger_price"] = $ltpopt - 15;
              $out = $kite->modifyOrder($openslorder->variety, $openslorder->order_id, $params);
              if (isset($out->order_id)) {
                $openslorder->trail2 = true;
              }
          } elseif(($ltpopt - $openslorder->trigger_price > 100) and ($openslorder->trigger_price - $openslorder->parent_price) > 20) {
            echo 'Trail 3 -- ';
            echo ' - LTP: '.$ltpopt;
            echo ' - Parent_price: '.$openslorder->parent_price;
            echo ' - Trigger_price: '.$openslorder->trigger_price;
              $params["trigger_price"] = $ltpopt - 20;
              $out = $kite->modifyOrder($openslorder->variety, $openslorder->order_id, $params);
          }
        print_r($out->order_id);
        }
        echo 'Waiting -- ';
        echo ' - LTP: '.$ltpopt;
        echo ' - Parent_price: '.$openslorder->parent_price;
        echo ' - Trigger_price: '.$openslorder->trigger_price;
  } elseif($openslorder == null && $openpos != null) {
    echo 'Position without SL found'.PHP_EOL; 
    print_r($openpos); die;

  }




  flush();//buffer output 
  sleep(3);//for wait 10 seconds 
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

    $openpos = array();
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
    
    if ($openpos != null){
        foreach ($orders as $key => $poss) {
            if ($openpos->parent_order_id == $poss->order_id) {
                $openpos->parent_price = $poss->average_price;
            }
        }   
    }

    if(isset($openpos->opt)) {
        return $openpos;
    } else {
        return null;
    }

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