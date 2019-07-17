<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

// $app->get('[/]', '\App\Controllers\TestController:test');
$app->redirect('[/]', $_ENV['APP_URL'].'/tenant');
$app->get('/login', '\App\Controllers\AuthControllers\LoginController:login')->setName('login');
$app->get('/logout', '\App\Controllers\AuthControllers\LoginController:logout')->setName('logout');

$app->redirect('/dashboard', $_ENV['APP_URL'].'/tenant');
$app->redirect('/home', $_ENV['APP_URL'].'/tenant');
// $app->get('/dashboard', '\App\Controllers\DashboardController:index')->setName('dashboard');

$app->group('/tenant', function() {

    $this->get('', '\App\Controllers\TenantController:index')->setName('tenant');

    $this->get('/add', '\App\Controllers\TenantController:add')->setName('addTenant');
    $this->post('/add', '\App\Controllers\TenantController:handleAdd');

    $this->group('/{id}', function() {

        $this->get('', '\App\Controllers\TenantController:detail')->setName('detailTenant');

        $this->get('/edit', '\App\Controllers\TenantController:edit')->setName('editTenant');
        $this->post('/edit', '\App\Controllers\TenantController:handleEdit');
    });
});

$app->group('/user', function() {

    $this->get('', '\App\Controllers\UsersController:index')->setName('users');

    $this->get('/add', '\App\Controllers\UsersController:add')->setName('addUser');
    $this->post('/add', '\App\Controllers\UsersController:handleAdd');

    $this->group('/{id}', function() {

        $this->get('', '\App\Controllers\UsersController:detail')->setName('detailUser');

        $this->get('/edit', '\App\Controllers\UsersController:edit')->setName('editUser');
        $this->post('/edit', '\App\Controllers\UsersController:handleEdit');

        $this->post('/unlink', '\App\Controllers\UsersController:handleUnlink')->setName('unlinkUser');

        $this->post('/enable', '\App\Controllers\UsersController:handleEnable')->setName('enableUser');
    });
});

$app->group('/logger', function() {

    $this->get('', '\App\Controllers\LoggerController:index')->setName('logger');

    $this->get('/add', '\App\Controllers\LoggerController:add')->setName('addLogger');
    $this->post('/add', '\App\Controllers\LoggerController:handleAdd');

    $this->group('/{id}', function() {

        // $this->get('', '\App\Controllers\LoggerController:detail')->setName('detailLogger');

        $this->get('/edit', '\App\Controllers\LoggerController:edit')->setName('editLogger');
        $this->post('/edit', '\App\Controllers\LoggerController:handleEdit');

        $this->post('/unlink', '\App\Controllers\LoggerController:handleUnlink')->setName('unlinkLogger');

        $this->post('/delete', '\App\Controllers\LoggerController:handleDelete')->setName('deleteLogger');
    });
});
