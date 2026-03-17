<?php

require __DIR__ . '/../../includes/auth.php';
require_login();

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){
exit("invalid json");
}

$id = $data['id'] ?? null;

if(!$id){
exit("invalid request");
}


switch($id){

case 1:


$url = "https://collector.mrxijian.site/import.php?token=xxjsld233";

$response = @file_get_contents($url);

if($response === false){
exit("update failed");
}

break;

default:
exit("unknown dataset");

}

echo "success";
