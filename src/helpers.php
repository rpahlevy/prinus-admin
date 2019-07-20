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