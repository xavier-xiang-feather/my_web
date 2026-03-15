<?php

$config = require_once __DIR__ . '/db.php';

$dsn = "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}";
try {
    #use pdo to access db
    $pdo = new PDO(
        $dsn,
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
}
catch (PDOException $e){
    exit("DB connection failed:" . $e->getMessage());
}
?>