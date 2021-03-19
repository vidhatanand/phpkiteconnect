<?php
	include dirname(__FILE__)."/kiteconnect.php";
	$configs_object = file_get_contents('./config.json');
    $configs = json_decode($configs_object, true);


	// $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
	// $txt = print_r($allpos, true);
	// fwrite($myfile, $txt);
	// fclose($myfile);

	$openpos = get_openposition($configs);
	print_r($openpos); 
	echo $openpos->tradingsymbol;

	

	

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




?>
