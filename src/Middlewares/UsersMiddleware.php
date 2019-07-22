<?php

namespace App\Middlewares;

use App\Controllers\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class UsersMiddleware extends Controller
{
    public function __invoke(Request $request, Response $response, $next)
    {
        // before
        $args = $request->getAttribute('routeInfo')[2];
        $user_id = intval($args['id']);
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id=:id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
        
        $request = $request->withAttribute('user', $user);
        // before

        $response = $next($request, $response);

        // after
        // after

        return $response;
    }
}