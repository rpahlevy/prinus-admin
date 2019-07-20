<?php

namespace App\Middlewares;

use App\Controllers\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class UserTenantMiddleware extends Controller
{
    public function __invoke(Request $request, Response $response, $next)
    {
        // before
        $path = explode("/", $request->getUri()->getPath());
        $tenant_id = intval($path[count($path)-1]);

        $user = $this->app->user;
        if ($user['tenant_id'] > 0 && $user['tenant_id'] != $tenant_id) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
        // before

        $response = $next($request, $response);

        // after
        // after

        return $response;
    }
}