<?php

namespace App\Controllers\AuthControllers;

use App\Controllers\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class LoginController extends Controller
{
    public function login($request, $response, $args)
    {
        return $this->view($response, 'auth/login.html');
    }

    public function handleLogin($request, $response, $args)
    {
        $credentials = $request->getParams();
        if (empty($credentials['username']) || empty($credentials['password'])) {
            return $this->redirect($response, $this->route('login'), ['errors' => 'Masukkan username dan password']);
        }
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username=:username AND is_active=true");
        $stmt->execute([':username' => $credentials['username']]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($credentials['password'], $user['password'])) {
            return $this->redirect($response, $this->route('login'), ['errors' => 'Username / password salah']);
        }

        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $password_rehash = password_hash($credentials['password'], PASSWORD_DEFAULT);
            $stmt = $this->db->query("UPDATE users set password='{$password_rehash}' WHERE id={$user[id]}");
            $stmt->execute();
        }

        $this->session->user_id = $user['id'];
        $this->session->user_refresh_time = strtotime("+1hour");
        $this->session->user_basic_auth = base64_encode($credentials['username'] .":". $credentials['password']);

        if ($user['tenant_id'] == 0) {
            return $this->redirect($response, $this->route('dashboard'));
        } else {
            return $this->redirect($response, $this->route('logger'));
        }
    }

    public function logout($request, $response, $args)
    {
        $this->session->destroy();
        return $this->redirect($response, $this->route('login'));
    }
}