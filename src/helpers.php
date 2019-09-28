<?php

$container = $app->getContainer();

$env = new Twig_SimpleFunction('env', function ($key, $default) {
	return isset($_ENV[$key]) ? $_ENV[$key] : $default;
});
$asset = new Twig_SimpleFunction('asset', function ($path) {
	return $_ENV['APP_URL'] .'/'. $path;
});
// $session = new Twig_SimpleFunction('session', function () {
// 	return \App\Session::getInstance();
// });
$user = new Twig_SimpleFunction('user', function () use ($container) {
	return $container->get('user');
});

$container->get('view')->getEnvironment()->addFunction($env);
$container->get('view')->getEnvironment()->addFunction($asset);
// $container->get('view')->getEnvironment()->addFunction($session);
$container->get('view')->getEnvironment()->addFunction($user);


// curl
// https://stackoverflow.com/questions/28858351/php-ssl-certificate-error-unable-to-get-local-issuer-certificate
function curl ($url, $method="GET", $headers=[], $postFields="")
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $postFields,
		CURLOPT_HTTPHEADER => $headers,
	));
	
	// ob_start();  
	// $out = fopen('php://output', 'w');
	// curl_setopt($curl, CURLOPT_VERBOSE, true);  
	// curl_setopt($curl, CURLOPT_STDERR, $out);  

    $response = curl_exec($curl);
    $err = curl_error($curl);

	curl_close($curl);
	
	// fclose($out);  
	// $debug = ob_get_clean();
	if ($err) {
		return $err;
	}
	// } else if ($debug) {
	// 	return $debug;
	// }

    return $response;
};