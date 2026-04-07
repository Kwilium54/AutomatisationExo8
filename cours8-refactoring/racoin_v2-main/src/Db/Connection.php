<?php

namespace App\Db;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class Connection
{
    public static function createConn(): void
    {
        $capsule = new DB;
        $capsule->addConnection(parse_ini_file(__DIR__ . '/../../config/config.ini'));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}
