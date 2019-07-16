<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

class Controller
{
	/**
	 * @var \Slim\App;
	 */
	protected $app;
	protected $view;

	/**
	 * @var \Slim\Flash\Messages
	 */
	protected $flash;

	/**
	 * @var \PDO
	 */
	protected $db;
	
	public function __construct($app)
	{
		$this->app = $app;
		$this->view = $app->view;
		$this->flash = $app->flash;
		$this->db = $app->db;
	}
	
	protected function view($response, $template, array $data=[])
	{
		return $this->view->render($response, $template, $data);
	}

	protected function referer(Request $request, $default='/')
	{
		$refererHeader = $request->getHeader('HTTP_REFERER');
		return $refererHeader ? array_shift($refererHeader) : $default;
	}

	protected function route($name, array $params=[])
	{
		return $this->app->get('router')->pathFor($name, $params);
	}

	protected function redirect($response, $route, array $messages=[])
	{
		foreach ($messages as $key => $value) {
			$this->flash->addMessage($key, $value);
		}
		return $response->withRedirect($route);
	}
}