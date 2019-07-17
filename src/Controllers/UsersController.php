<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\Users;

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
            'sn' => '',
            'location_id' => '',
            'tenant_id' => intval($request->getParam('t', 0)),
        ];

        $tenants = $this->db->query("SELECT * FROM tenant ORDER BY nama")->fetchAll();
        $referer = $this->referer($request, $this->route('users'));

        return $this->view($response, 'user/edit.html', [
            'mode' => 'Add',
            'user' => $user,
            'tenants' => $tenants,
            'referer' => $referer,
        ]);
    }

    public function handleAdd(Request $request, Response $response, $args)
    {
        $user = $request->getParams();

        $stmt = $this->db->prepare("INSERT INTO users (sn, location_id, tenant_id) VALUES (:sn, :location_id, :tenant_id)");
        $stmt->bindValue(':sn', $user['sn']);
        $stmt->bindValue(':location_id', $user['location_id'] ? $user['location_id'] : null);
        $stmt->bindValue(':tenant_id', $user['tenant_id'] ? $user['tenant_id'] : null);
        $stmt->execute();
        
        $referer = $request->getParam('_referer', $this->route('users'));
        
        return $this->redirect($response, $referer, [
            'messages' => "Users {$user[sn]} telah ditambahkan"
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
        $referer = $this->referer($request, $this->route('users'));

        return $this->view($response, 'user/edit.html', [
            'mode' => 'Edit',
            'user' => $user,
            'tenants' => $tenants,
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

        $user['sn'] = $request->getParam('sn', $user['sn']);
        $user['location_id'] = $request->getParam('location_id', $user['location_id']);
        $user['tenant_id'] = $request->getParam('tenant_id', $user['tenant_id']);

        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("UPDATE users set sn=:sn, location_id=:location_id, tenant_id=:tenant_id, modified='$now' WHERE id=:id");
        $stmt->bindValue(':sn', $user['sn']);
        $stmt->bindValue(':location_id', $user['location_id'] ? $user['location_id'] : null);
        $stmt->bindValue(':tenant_id', $user['tenant_id'] ? $user['tenant_id'] : null);
        $stmt->bindValue(':id', $user['id']);
        $stmt->execute();

        $referer = $request->getParam('_referer', $this->route('users'));
        
        return $this->redirect($response, $referer, [
            'messages' => "Perubahan Users {$user[sn]} telah disimpan"
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