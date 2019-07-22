<?php

namespace App\Middlewares;

use App\Controllers\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class LoggerMiddleware extends Controller
{
    public function __invoke(Request $request, Response $response, $next)
    {
        // before
        $args = $request->getAttribute('routeInfo')[2];
        $logger_id = intval($args['id']);
        $stmt = $this->db->prepare("SELECT * FROM logger WHERE id=:id");
        $stmt->execute([':id' => $logger_id]);
        $logger = $stmt->fetch();

        $user = $this->app->user;
        if (!$logger || ($user['tenant_id'] > 0 && $user['tenant_id'] != $logger['tenant_id'])) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $request = $request->withAttribute('logger', $logger);
        // before

        $response = $next($request, $response);

        // after
        // after

        return $response;
    }
}