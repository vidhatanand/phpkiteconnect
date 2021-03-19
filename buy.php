<?php
	include dirname(__FILE__)."/kiteconnect.php";
	$configs_object = file_get_contents('./config.json');
    $configs = json_decode($configs_object, true);


	$op = build_nearest_atm(34600, 'PUT');

	echo $op;
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
