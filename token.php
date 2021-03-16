<?php
	include dirname(__FILE__)."/kiteconnect.php";
    $configs_object = file_get_contents('./config.json');
    $configs = json_decode($configs_object, true);
	// Initialise.
	$kite = new KiteConnect($configs['api_key']);

    // Assuming you have obtained the `request_token`
	// after the auth flow redirect by redirecting the
	// user to $kite->login_url()
	try {
		$user = $kite->generateSession($configs['request_token'], $configs['secret_key']);

		echo "Authentication successful. \n";
		print_r($user);
        
		//$kite->setAccessToken($user->access_token);
	} catch(Exception $e) {
		echo "Authentication failed: ".$e->getMessage();
		throw $e;
	}

	echo $user->user_id." has logged in with access token ";
    echo $user->access_token;
    $configs['access_token'] = $user->access_token;
    $configs_object = json_encode($configs);
    file_put_contents('./config.json', $configs_object);
    
    ?>