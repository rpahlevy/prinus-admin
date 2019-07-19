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
	$session = \App\Session::getInstance();
	$user_id = $session->user_id;
	if (empty($user_id)) {
		return null;
	}

	$stmt = $container->db->prepare("SELECT * FROM users WHERE id=:id AND is_active=1");
	$stmt->execute([':id' => $user_id]);
	$user = $stmt->fetch();
	return $user ?: null;
});

$container->get('view')->getEnvironment()->addFunction($env);
$container->get('view')->getEnvironment()->addFunction($asset);
// $container->get('view')->getEnvironment()->addFunction($session);
$container->get('view')->getEnvironment()->addFunction($user);