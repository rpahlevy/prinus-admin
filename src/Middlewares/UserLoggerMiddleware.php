<?php

namespace App\Middlewares;

use App\Controllers\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class UserLoggerMiddleware extends Controller
{
    public function __invoke(Request $request, Response $response, $next)
    {
        // before
        $path = explode("/", $request->getUri()->getPath());
        $logger_id = intval($path[count($path)-1]);
        $stmt = $this->db->prepare("SELECT * FROM logger WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $logger = $stmt->fetch();

        $user = $this->app->user;
        if (!$logger || ($user['tenant_id'] > 0 && $user['tenant_id'] != $logger['tenant_id'])) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
        // before

        $response = $next($request, $response);

        // after
        // after

        return $response;
    }
}