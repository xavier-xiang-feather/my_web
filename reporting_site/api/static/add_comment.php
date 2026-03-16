<?php

require __DIR__ . '/../../includes/auth.php';
require_login();

$dbConfig = require __DIR__ . '/../../includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$report_id = $data['report_id'] ?? null;
$category = $data['category'] ?? '';
$comment = $data['comment'] ?? '';

$role = $_SESSION['role'];
$author = $_SESSION['username'] ?? 'unknown';

if(!$report_id || !$comment){
    echo "Invalid input";
    exit();
}

/* -------- 权限检查 -------- */

function canAccess($category, $role){

    if($role === 'superadmin'){
        return true;
    }

    if(str_contains($category,'browser') && $role === 'analytics_browser'){
        return true;
    }

    if(str_contains($category,'behavior') && $role === 'analytics_behavior'){
        return true;
    }

    if(str_contains($category,'performance') && $role === 'analytics_performance'){
        return true;
    }

    return false;
}

if(!canAccess($category,$role)){
    echo "Permission denied";
    exit();
}

/* -------- 写入数据库 -------- */

try{

    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset={$dbConfig['charset']}";

    $pdo = new PDO(
        $dsn,
        $dbConfig['user'],
        $dbConfig['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $stmt = $pdo->prepare(
        "INSERT INTO comments (report_id, report_category, comment, author)
         VALUES (?, ?, ?, ?)"
    );

    $stmt->execute([
        $report_id,
        $category,
        $comment,
        $author
    ]);

    echo "success";

}catch(Exception $e){

    echo "Database error";

}