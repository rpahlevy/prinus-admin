<?php

namespace App\Middlewares;

use App\Controllers\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminMiddleware extends Controller
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

        $user = $this->app->user;
        if ($user['tenant_id'] > 0) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
        // before

        $response = $next($request, $response);

        // after
        // after

        return $response;
    }
}