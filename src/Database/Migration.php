<?php

namespace App\Database;

abstract class Migration
{
    protected $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    abstract public function up();
    abstract public function down();
    
    public function getName(): string
    {
        $className = get_class($this);
        $parts = explode('\\', $className);
        return end($parts);
    }
}
