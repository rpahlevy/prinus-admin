<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\Users;
use DateTimeZone;

class UsersController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        $users = $this->db->query("SELECT
                users.*,
                COALESCE(tenant.nama, 'Admin') as nama_tenant
            FROM
                users
                LEFT JOIN tenant ON (users.tenant_id = tenant.id)
            ORDER BY
                users.is_active DESC,
                nama_tenant,
                users.username
            ")->fetchAll();

        return $this->view($response, 'user/index.html', [
            'users' => $users
        ]);
    }

    public function add(Request $request, Response $response, $args)
    {
        $user = [
            'username' => '',
            'is_active' => 1,
            'tenant_id' => intval($request->getParam('t', 0)),
            'email' => '',
            'tz' => 'Asia/Jakarta'
        ];

        $tenants = $this->db->query("SELECT * FROM tenant ORDER BY nama")->fetchAll();
        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $referer = $this->referer($request, $this->route('users'));

        return $this->view($response, 'user/edit.html', [
            'mode' => 'Add',
            'user' => $user,
            'tenants' => $tenants,
            'timezones' => $timezones,
            'referer' => $referer,
        ]);
    }

    public function handleAdd(Request $request, Response $response, $args)
    {
        $user = $request->getParams();

        if (strlen($user['username']) > 12) {
            return $this->redirect($response, $this->route('addUser'), ['errors' => 'Username terlalu panjang, max. 12 karakter']);
        }

        $stmt = $this->db->prepare("INSERT INTO users (username, is_active, tenant_id, email, tz) VALUES (:username, :is_active, :tenant_id, :email, :tz)");
        $stmt->bindValue(':username', $user['username']);
        $stmt->bindValue(':is_active', $user['is_active']);
        $stmt->bindValue(':tenant_id', $user['tenant_id'] ? $user['tenant_id'] : null);
        $stmt->bindValue(':email', $user['email'] ? $user['email'] : null);
        $stmt->bindValue(':tz', $user['tz'] ? $user['tz'] : 'Asia/Jakarta');
        $stmt->execute();

        $id = $this->db->lastInsertId();
        if (strlen($user['password']) > 0 && strlen($user['password_repeat']) > 0) {
            $valid = true;

            if (strlen($user['password']) < 6) {
                $this->flash->addMessage('errors', 'Panjang password minimal 6 karakter');
                $valid = false;
            }

            if ($user['password'] != $user['password_repeat']) {
                $this->flash->addMessage('errors', 'Password tidak sesuai');
                $valid = false;
            }

            if (!$valid) {
                return $this->redirect($response, $this->route('editUser', ['id' => $id]));
            }

            $stmt = $this->db->prepare("UPDATE users set password=:password WHERE id=:id");
            $stmt->bindValue(':password', password_hash($user['password'], PASSWORD_DEFAULT));
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        }
        
        $referer = $request->getParam('_referer', $this->route('users'));
        
        return $this->redirect($response, $referer, [
            'messages' => "User {$user[username]} telah ditambahkan"
        ]);
    }

    public function edit(Request $request, Response $response, $args)
    {
        $id = isset($args['id']) ? intval($args['id']) : 0;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $tenants = $this->db->query("SELECT * FROM tenant ORDER BY nama")->fetchAll();
        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $referer = $this->referer($request, $this->route('users'));

        return $this->view($response, 'user/edit.html', [
            'mode' => 'Edit',
            'user' => $user,
            'tenants' => $tenants,
            'timezones' => $timezones,
            'referer' => $referer
        ]);
    }

    public function handleEdit(Request $request, Response $response, $args)
    {
        $id = isset($args['id']) ? intval($args['id']) : 0;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $user['username'] = $request->getParam('username', $user['username']);
        $user['is_active'] = $request->getParam('is_active', $user['is_active']);
        $user['tenant_id'] = $request->getParam('tenant_id', $user['tenant_id']);
        $user['email'] = $request->getParam('email', $user['email']);
        $user['tz'] = $request->getParam('tz', $user['tz']);
        $user['password'] = $request->getParam('password', '');
        $user['password_repeat'] = $request->getParam('password_repeat', '');

        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("UPDATE users set username=:username, is_active=:is_active, tenant_id=:tenant_id, email=:email, tz=:tz, modified='$now' WHERE id=:id");
        $stmt->bindValue(':username', $user['username']);
        $stmt->bindValue(':is_active', $user['is_active']);
        $stmt->bindValue(':tenant_id', $user['tenant_id'] ? $user['tenant_id'] : null);
        $stmt->bindValue(':email', $user['email'] ? $user['email'] : null);
        $stmt->bindValue(':tz', $user['tz'] ? $user['tz'] : 'Asia/Jakarta');
        $stmt->bindValue(':id', $user['id']);
        $stmt->execute();
        
        if (strlen($user['password']) > 0 && strlen($user['password_repeat']) > 0) {
            $valid = true;

            if (strlen($user['password']) < 6) {
                $this->flash->addMessage('errors', 'Panjang password minimal 6 karakter');
                $valid = false;
            }

            if ($user['password'] != $user['password_repeat']) {
                $this->flash->addMessage('errors', 'Password tidak sesuai');
                $valid = false;
            }

            if (!$valid) {
                return $this->redirect($response, $this->route('editUser', ['id' => $id]));
            }

            $stmt = $this->db->prepare("UPDATE users set password=:password WHERE id=:id");
            $stmt->bindValue(':password', password_hash($user['password'], PASSWORD_DEFAULT));
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        }

        $referer = $request->getParam('_referer', $this->route('users'));
        
        return $this->redirect($response, $referer, [
            'messages' => "Perubahan User {$user[username]} telah disimpan"
        ]);
    }

    public function handleUnlink(Request $request, Response $response, $args)
    {
        $id = isset($args['id']) ? intval($args['id']) : 0;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $stmt = $this->db->prepare("UPDATE users SET tenant_id=null WHERE id=:id");
        $stmt->execute([':id' => $id]);

        $referer = $this->referer($request, $this->route('users'));
        
        return $this->redirect($response, $referer, [
            'messages' => "User {$user[sn]} telah dipisahkan dari Tenant"
        ]);
    }

    public function handleEnable(Request $request, Response $response, $args)
    {
        $id = isset($args['id']) ? intval($args['id']) : 0;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $user['is_active'] = $request->getParam('is_active', $user['is_active']);

        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("UPDATE users set is_active=:is_active, modified='$now' WHERE id=:id");
        $stmt->bindValue(':is_active', $user['is_active']);
        $stmt->bindValue(':id', $user['id']);
        $stmt->execute();

        $referer = $this->referer($request, $this->route('users'));
        
        return $this->redirect($response, $referer, [
            'messages' => "Users {$user[username]} ". ($user['is_active'] == 0 ? 'DISABLED' : 'ENABLED')
        ]);
    }

    // public function detail(Request $request, Response $response, $args)
    // {
    //     $id = isset($args['id']) ? intval($args['id']) : 0;
    //     $stmt = $this->db->prepare("SELECT * FROM users WHERE users.id=:id");
    //     $stmt->execute([':id' => $id]);
    //     $user = $stmt->fetch();
    //     if (!$user) {
    //         throw new \Slim\Exception\NotFoundException($request, $response);
    //     }

    //     $stmt_users = $this->db->prepare("SELECT * from users WHERE user_id=:id");
    //     $stmt_users->execute([':id' => $id]);
    //     $users = $stmt_users->fetchAll();
    //     $user['jml_user'] = count($users);
        
    //     $stmt_user = $this->db->prepare("SELECT * FROM users WHERE user_id=:id");
    //     $stmt_user->execute([':id' => $id]);
    //     $users = $stmt_user->fetchAll();
    //     $user['jml_user'] = count($users);

    //     return $this->view($response, 'user/detail.html', [
    //         'user' => $user,
    //         'users' => $users,
    //         'users' => $users,
    //     ]);
    // }
}