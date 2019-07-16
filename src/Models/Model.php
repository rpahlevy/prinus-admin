<?php

namespace App\Models;

class Model
{
    protected $table = 'table';
    protected $primaryKey = 'id';

    public function __construct()
    {
        
    }

    /**
     * @param $fields '*' (default) or array of fields ['id', 'nama']
     * @param $orders '' (default) or associative array of field & mode ['nama' => 'asc', 'id' => 'desc']
     * @return array
     */
    public static function all(\PDO $db, $fields='*', $orders='id')
    {
        $model = new static;
        $fields = static::buildFieldStr($fields);
        $orders = static::buildOrderStr($orders, $model->primaryKey);
        
        return $db->query("SELECT {$fields} FROM {$model->table} ORDER BY {$orders}")->fetchAll();
    }

    /**
     * @param $fields '*' (default) or array of fields ['id', 'nama']
     * @param $orders '' (default) or associative array of field & mode ['nama' => 'asc', 'id' => 'desc']
     * @return array
     */
    public static function find(\PDO $db, $id, $fields='*', $orders='id')
    {
        $model = new static;
        $fields = static::buildFieldStr($fields);
        $orders = static::buildOrderStr($orders, $model->primaryKey);

        $stmt = $db->prepare("SELECT {$fields} FROM {$model->table} WHERE {$model->primaryKey} = :id ORDER BY {$orders}");
        // $stmt->bindValue(':id', $id);
        $stmt->execute([':id' => $id]);
        $arr = $stmt->fetchAll();

        return $arr && count($arr) > 0 ? $arr[0] : null;
    }

    protected static function buildFieldStr($fields)
    {
        $field_str = empty($fields) ? '*' : $fields;
        if (is_array($fields)) {
            $field_str = implode(',', $fields);
        }
        return $field_str;
    }

    protected static function buildOrderStr($orders, $primaryKey)
    {
        $order_str = empty($orders) ? $primaryKey : $orders;
        if (is_array($orders)) {
            foreach ($orders as $field => $mode) {
                $order_str .= "$field $mode,";
            }
        }
        return rtrim($order_str, ",");
    }
}