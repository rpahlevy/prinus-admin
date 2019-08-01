<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        $user = $this->app->user;
        if ($user['tenant_id'] > 0) {
            return $this->redirect($response, $this->route('detailTenant', ['id' => $user['tenant_id']]));
        }
        // $q = "SELECT * FROM tenant ";

        // $user = $this->app->user;
        // if ($user && $user['tenant_id'] > 0) {
        //     $q .= "WHERE id={$user[tenant_id]} ";
        // }

        // $q .= "ORDER BY nama";

        $tenants = $this->db->query("SELECT * FROM tenant ORDER BY nama")->fetchAll();
        foreach ($tenants as &$tenant) {
            $tenant['jml_user'] = $this->db->query("SELECT COUNT(id) FROM users WHERE tenant_id={$tenant['id']}")->fetchColumn();
            $tenant['jml_logger'] = $this->db->query("SELECT COUNT(id) FROM logger WHERE tenant_id={$tenant['id']}")->fetchColumn();
        }
        unset($tenant);

        return $this->view($response, 'tenant/index.html', [
            'tenants' => $tenants
        ]);
    }

    public function add(Request $request, Response $response, $args)
    {
        $tenant = [
            'nama' => '',
            'slug' => ''
        ];
        
        $referer = $this->referer($request, $this->route('logger'));

        return $this->view($response, 'tenant/edit.html', [
            'mode' => 'Add',
            'tenant' => $tenant,
            'referer' => $referer,
        ]);
    }

    public function handleAdd(Request $request, Response $response, $args)
    {
        $tenant = $request->getParams();

        $stmt = $this->db->prepare("INSERT INTO tenant (id, nama, slug) VALUES (nextval('tenant_id_seq'), :nama, :slug)");
        //$stmt->bindValue(':nama', $tenant['nama']);
        //$stmt->bindValue(':slug', $tenant['slug']);
        $stmt->execute(["nama" => $tenant['nama'], "slug" => $tenant['slug']]);
        
        $referer = $request->getParam('_referer', $this->route('tenant'));
        
        return $this->redirect($response, $referer, [
            'messages' => "Tenant {$tenant[nama]} telah ditambahkan"
        ]);
    }

    public function edit(Request $request, Response $response, $args)
    {
        $tenant = $request->getAttribute('tenant');
        $referer = $this->referer($request, $this->route('tenant'));

        return $this->view($response, 'tenant/edit.html', [
            'mode' => 'Edit',
            'tenant' => $tenant,
            'referer' => $referer
        ]);
    }

    public function handleEdit(Request $request, Response $response, $args)
    {
        $tenant = $request->getAttribute('tenant');
        $tenant['nama'] = $request->getParam('nama', $tenant['nama']);
        $tenant['slug'] = $request->getParam('slug', $tenant['slug']);

        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("UPDATE tenant set nama=:nama, slug=:slug, modified_at='$now' WHERE id=:id");
        $stmt->bindValue(':nama', $tenant['nama']);
        $stmt->bindValue(':slug', $tenant['slug']);
        $stmt->bindValue(':id', $tenant['id']);
        $stmt->execute();

        $referer = $request->getParam('_referer', $this->route('tenant'));
        
        return $this->redirect($response, $referer, [
            'messages' => "Perubahan Tenant {$tenant[nama]} telah disimpan"
        ]);
    }

    public function detail(Request $request, Response $response, $args)
    {
        $tenant = $request->getAttribute('tenant');

        $stmt_users = $this->db->prepare("SELECT * from users WHERE tenant_id=:id");
        $stmt_users->execute([':id' => $tenant['id']]);
        $users = $stmt_users->fetchAll();
        $tenant['jml_user'] = count($users);
        
        $stmt_logger = $this->db->prepare("SELECT * from logger WHERE tenant_id=:id");
        $stmt_logger->execute([':id' => $tenant['id']]);
        $loggers = $stmt_logger->fetchAll();
        $tenant['jml_logger'] = count($loggers);

        return $this->view($response, 'tenant/detail.html', [
            'tenant' => $tenant,
            'users' => $users,
            'loggers' => $loggers,
        ]);
    }
}
