<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\Logger;

class LoggerController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        $user = $this->app->user;

        if ($user['tenant_id'] > 0)
        {
            $loggers = $this->db->query("SELECT
                    logger.*,
                    COALESCE(tenant.nama, '-') as nama_tenant
                FROM
                    logger
                    LEFT JOIN tenant ON (logger.tenant_id = tenant.id)
                WHERE
                    logger.tenant_id = {$user['tenant_id']}
                ORDER BY
                    tenant.nama,
                    logger.sn
                ")->fetchAll();
        }
        else
        {
            $loggers = $this->db->query("SELECT
                    logger.*,
                    COALESCE(tenant.nama, '-') as nama_tenant
                FROM
                    logger
                    LEFT JOIN tenant ON (logger.tenant_id = tenant.id)
                ORDER BY
                    tenant.nama,
                    logger.sn
                ")->fetchAll();
        }

        return $this->view($response, 'logger/index.html', [
            'loggers' => $loggers
        ]);
    }

    public function add(Request $request, Response $response, $args)
    {
        $user = $this->app->user;

        $logger = [
            'sn' => '',
            'location_id' => '',
            'tenant_id' => $user['tenant_id'] ?: intval($request->getParam('t', 0)),
        ];

        if ($user['tenant_id'] > 0) {
            $tenants = $this->db->query("SELECT * FROM tenant WHERE id={$user['tenant_id']} ORDER BY nama")->fetchAll();
        } else {
            $tenants = $this->db->query("SELECT * FROM tenant ORDER BY nama")->fetchAll();
        }
        $referer = $this->referer($request, $this->route('logger'));

        return $this->view($response, 'logger/edit.html', [
            'mode' => 'Add',
            'logger' => $logger,
            'tenants' => $tenants,
            'referer' => $referer,
        ]);
    }

    public function handleAdd(Request $request, Response $response, $args)
    {
        $logger = $request->getParams();

        $stmt = $this->db->prepare("INSERT INTO logger (sn, location_id, tenant_id) VALUES (:sn, :location_id, :tenant_id)");
        $stmt->bindValue(':sn', $logger['sn']);
        $stmt->bindValue(':location_id', $logger['location_id'] ? $logger['location_id'] : null);
        $stmt->bindValue(':tenant_id', $logger['tenant_id'] ? $logger['tenant_id'] : null);
        $stmt->execute();
        
        $referer = $request->getParam('_referer', $this->route('logger'));
        
        return $this->redirect($response, $referer, [
            'messages' => "Logger {$logger[sn]} telah ditambahkan"
        ]);
    }

    public function edit(Request $request, Response $response, $args)
    {
        $id = isset($args['id']) ? intval($args['id']) : 0;
        $stmt = $this->db->prepare("SELECT * FROM logger WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $logger = $stmt->fetch();
        if (!$logger) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $tenants = $this->db->query("SELECT * FROM tenant ORDER BY nama")->fetchAll();
        $referer = $this->referer($request, $this->route('logger'));

        return $this->view($response, 'logger/edit.html', [
            'mode' => 'Edit',
            'logger' => $logger,
            'tenants' => $tenants,
            'referer' => $referer
        ]);
    }

    public function handleEdit(Request $request, Response $response, $args)
    {
        $id = isset($args['id']) ? intval($args['id']) : 0;
        $stmt = $this->db->prepare("SELECT * FROM logger WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $logger = $stmt->fetch();
        if (!$logger) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $logger['sn'] = $request->getParam('sn', $logger['sn']);
        $logger['location_id'] = $request->getParam('location_id', $logger['location_id']);
        $logger['tenant_id'] = $request->getParam('tenant_id', $logger['tenant_id']);

        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("UPDATE logger set sn=:sn, location_id=:location_id, tenant_id=:tenant_id, modified_at='$now' WHERE id=:id");
        $stmt->bindValue(':sn', $logger['sn']);
        $stmt->bindValue(':location_id', $logger['location_id'] ? $logger['location_id'] : null);
        $stmt->bindValue(':tenant_id', $logger['tenant_id'] ? $logger['tenant_id'] : null);
        $stmt->bindValue(':id', $logger['id']);
        $stmt->execute();

        $referer = $request->getParam('_referer', $this->route('logger'));
        
        return $this->redirect($response, $referer, [
            'messages' => "Perubahan Logger {$logger[sn]} telah disimpan"
        ]);
    }

    public function handleUnlink(Request $request, Response $response, $args)
    {
        $id = isset($args['id']) ? intval($args['id']) : 0;
        $stmt = $this->db->prepare("SELECT * FROM logger WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $logger = $stmt->fetch();
        if (!$logger) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $stmt = $this->db->prepare("UPDATE logger SET tenant_id=null WHERE id=:id");
        $stmt->execute([':id' => $id]);

        $referer = $this->referer($request, $this->route('logger'));
        
        return $this->redirect($response, $referer, [
            'messages' => "Logger {$logger[sn]} telah dipisahkan dari Tenant"
        ]);
    }

    public function handleDelete(Request $request, Response $response, $args)
    {
        $id = isset($args['id']) ? intval($args['id']) : 0;
        $stmt = $this->db->prepare("SELECT * FROM logger WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $logger = $stmt->fetch();
        if (!$logger) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $stmt = $this->db->prepare("DELETE FROM logger WHERE id=:id");
        $stmt->execute([':id' => $id]);

        $referer = $this->referer($request, $this->route('logger'));
        
        return $this->redirect($response, $referer, [
            'messages' => "Logger {$logger[sn]} telah dihapus"
        ]);
    }

    // public function detail(Request $request, Response $response, $args)
    // {
    //     $id = isset($args['id']) ? intval($args['id']) : 0;
    //     $stmt = $this->db->prepare("SELECT * FROM logger WHERE logger.id=:id");
    //     $stmt->execute([':id' => $id]);
    //     $logger = $stmt->fetch();
    //     if (!$logger) {
    //         throw new \Slim\Exception\NotFoundException($request, $response);
    //     }

    //     $stmt_users = $this->db->prepare("SELECT * from users WHERE logger_id=:id");
    //     $stmt_users->execute([':id' => $id]);
    //     $users = $stmt_users->fetchAll();
    //     $logger['jml_user'] = count($users);
        
    //     $stmt_logger = $this->db->prepare("SELECT * from logger WHERE logger_id=:id");
    //     $stmt_logger->execute([':id' => $id]);
    //     $loggers = $stmt_logger->fetchAll();
    //     $logger['jml_logger'] = count($loggers);

    //     return $this->view($response, 'logger/detail.html', [
    //         'logger' => $logger,
    //         'users' => $users,
    //         'loggers' => $loggers,
    //     ]);
    // }
}