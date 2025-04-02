<?php

namespace App\Database;

interface DatabaseInterface
{
    public function prepare($sql);
    public function execute($sql, $params = []);
    public function query($sql);
    public function lastInsertId();
    public function beginTransaction();
    public function commit();
    public function rollback();
}
