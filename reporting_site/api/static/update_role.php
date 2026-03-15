<?php
require __DIR__ . '/../../includes/auth.php';
require_login();


require __DIR__ . '/../../includes/connect_db.php';



$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    exit("invalid json");
}

$id = $data['id'] ?? null;
$role = $data['role'] ?? null;

$allowed_roles = ['viewer','analytics','superadmin'];

if($_SESSION['role'] !== 'superadmin'){
    exit("access denied");
}

if(!$id || !in_array($role,$allowed_roles)){
    exit("invalid request");
}

/* don't modify the root superadmin */
if($id == 1){
    exit("cannot modify superadmin");
}

$stmt = $pdo->prepare("
UPDATE users
SET role = ?
WHERE id = ?
");

$stmt->execute([$role,$id]);

echo "success";