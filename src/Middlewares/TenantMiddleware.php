<?php

namespace App\Middlewares;

use App\Controllers\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class TenantMiddleware extends Controller
{
    public function __invoke(Request $request, Response $response, $next)
    {
        // before
        $args = $request->getAttribute('routeInfo')[2];
        $tenant_id = intval($args['id']);
        $stmt = $this->db->prepare("SELECT * FROM tenant WHERE id=:id");
        $stmt->execute([':id' => $tenant_id]);
        $tenant = $stmt->fetch();

        $user = $this->app->user;
        if (!$tenant || ($user['tenant_id'] > 0 && $user['tenant_id'] != $tenant_id)) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
        
        $request = $request->withAttribute('tenant', $tenant);
        // before

        $response = $next($request, $response);

        // after
        // after

        return $response;
    }
}