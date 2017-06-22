<?php

if (!function_exists('qb')) {
    /**
     * @return \MvcBox\SqlQuery\SqlQueryAbstract
     * @throws Exception
     */
    function qb()
    {
        static $pdo;
        static $driver;
        require_once __DIR__ . '/autoloader.php';

        if (null === $pdo) {
            try {
                $config = require __DIR__ . '/connection.config.php';
                $driver = strtolower(trim($config['driver']));
                $drivers = array(
                    'mysql' => 'MvcBox\SqlQuery\MysqlQuery',
                    'pgsql' => 'MvcBox\SqlQuery\PgsqlQuery',
                    'sqlite' => 'MvcBox\SqlQuery\SqliteQuery',
                    'sqlite2' => 'MvcBox\SqlQuery\SqliteQuery'
                );
                if (!isset($drivers[$driver])) {
                    throw new Exception('Incorrect driver');
                }
                if (!in_array($driver, PDO::getAvailableDrivers())) {
                    throw new Exception('Driver [' . $driver . '] is not supported');
                }
                $dsns = array(
                    'mysql' => "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}",
                    'pgsql' => "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
                    'sqlite' => "sqlite:{$config['dbpath']}",
                    'sqlite2' => "sqlite:{$config['dbpath']}"
                );
                $charsets = array(
                    'pgsql' => "SET NAMES '{$config['charset']}'"
                );
                $pdo = new PDO($dsns[$driver], $config['username'], $config['password'], $config['options']);
                if (isset($charsets[$driver])) {
                    $pdo->exec($charsets[$driver]);
                }
                if (isset($config['callback']) && is_callable($callback = $config['callback'])) {
                    $callback($pdo);
                }
                $driver = $drivers[$driver];
            } catch (Exception $e) {
                exit('Sql Query fatal error: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')');
            }
        }
        return new $driver($pdo);
    }
}
