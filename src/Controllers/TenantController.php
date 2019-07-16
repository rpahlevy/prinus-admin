<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        $tenants = $this->db->query("SELECT * FROM tenant ORDER BY nama")->fetchAll();
        foreach ($tenants as &$tenant) {
            $tenant['jml_user'] = $this->db->query("SELECT COUNT(id) FROM users WHERE tenant_id={$tenant['id']}")->fetchColumn();
            $tenant['jml_logger'] = $this->db->query("SELECT COUNT(id) FROM logger WHERE tenant_id={$tenant['id']}")->fetchColumn();
        }
        unset($tenant);

        return $this->view($response, 'tenant/index.twig', [
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

        return $this->view($response, 'tenant/edit.twig', [
            'mode' => 'Add',
            'tenant' => $tenant,
            'referer' => $referer,
        ]);
    }

    public function handleAdd(Request $request, Response $response, $args)
    {
        $tenant = $request->getParams();

        $stmt = $this->db->prepare("INSERT INTO tenant (nama, slug) VALUES (:nama, :slug)");
        $stmt->bindValue(':nama', $tenant['nama']);
        $stmt->bindValue(':slug', $tenant['slug']);
        $stmt->execute();
        
        $referer = $request->getParam('_referer', $this->route('tenant'));
        
        return $this->redirect($response, $referer, [
            'messages' => "Tenant {$tenant[nama]} telah ditambahkan"
        ]);
    }

    public function edit(Request $request, Response $response, $args)
    {
        $id = isset($args['id']) ? intval($args['id']) : 0;
        $stmt = $this->db->prepare("SELECT * FROM tenant WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $tenant = $stmt->fetch();
        if (!$tenant) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $referer = $this->referer($request, $this->route('tenant'));

        return $this->view($response, 'tenant/edit.twig', [
            'mode' => 'Edit',
            'tenant' => $tenant,
            'referer' => $referer
        ]);
    }

    public function handleEdit(Request $request, Response $response, $args)
    {
        $id = isset($args['id']) ? intval($args['id']) : 0;
        $stmt = $this->db->prepare("SELECT * FROM tenant WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $tenant = $stmt->fetch();
        if (!$tenant) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

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
        $id = isset($args['id']) ? intval($args['id']) : 0;
        // $stmt = $this->db->prepare("SELECT
        //         tenant.*,
        //         COUNT(users.id) as jml_user,
        //         COUNT(logger.id) as jml_logger
        //     FROM
        //         tenant
        //         LEFT JOIN users ON (tenant.id = users.tenant_id)
        //         LEFT JOIN logger ON (tenant.id = logger.tenant_id)
        //     WHERE
        //         tenant.id=:id
        //     GROUP BY
        //         tenant.id");
        $stmt = $this->db->prepare("SELECT * FROM tenant WHERE tenant.id=:id");
        $stmt->execute([':id' => $id]);
        $tenant = $stmt->fetch();
        if (!$tenant) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $stmt_users = $this->db->prepare("SELECT * from users WHERE tenant_id=:id");
        $stmt_users->execute([':id' => $id]);
        $users = $stmt_users->fetchAll();
        $tenant['jml_user'] = count($users);
        
        $stmt_logger = $this->db->prepare("SELECT * from logger WHERE tenant_id=:id");
        $stmt_logger->execute([':id' => $id]);
        $loggers = $stmt_logger->fetchAll();
        $tenant['jml_logger'] = count($loggers);

        return $this->view($response, 'tenant/detail.twig', [
            'tenant' => $tenant,
            'users' => $users,
            'loggers' => $loggers,
        ]);
    }
}