<?php

namespace App\Middlewares\AuthMiddlewares;

use App\Controllers\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class UserMiddleware extends Controller
{
    public function __invoke(Request $request, Response $response, $next)
    {
        // before
        $user_refresh_time = $this->session->user_refresh_time;
        $now = time();

        if (empty($user_refresh_time) || $user_refresh_time < $now) {
            $this->session->destroy();
            return $this->redirect($response, $this->route('login'), ['errors' => 'Silahkan login untuk melanjutkan']);
        }
        // before

        $this->session->user_refresh_time = strtotime("+1hour");

        $response = $next($request, $response);

        // after
        // after

        return $response;
    }
}