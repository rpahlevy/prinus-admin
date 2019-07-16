<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

class DashboardController extends Controller
{
    public function index($request, $response, $args)
    {
        return $this->view($response, 'dashboard/index.twig');
    }
}