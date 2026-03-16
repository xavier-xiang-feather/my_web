<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){
    echo json_encode(["status"=>"error","message"=>"invalid json"]);
    exit();
}

$reportId = $data['id'] ?? null;
if(!$reportId){
    echo json_encode(["status"=>"error","message"=>"missing report id"]);
    exit();
}

/* report title */
switch($reportId){
    case 1: $title = "Accessed Browser Report"; break;
    case 2: $title = "Mouse Event Report"; break;
    case 3: $title = "Performance Report"; break;
    default:
        echo json_encode(["status"=>"error","message"=>"unknown report"]);
        exit();
}

/* 核心修复：处理 Base64 数据 */
$chart = $data['chart'] ?? '';

// 如果是通过 Chart.js 传过来的，确保没有多余的换行符
$chart = str_replace(["\r", "\n"], '', $chart);

$html = "
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Helvetica, sans-serif; text-align: center; }
        .header { margin-bottom: 30px; }
        .chart-container { width: 100%; }
        img { width: 600px; height: auto; border: 1px solid #eee; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>$title</h1>
        <p>Generated at: " . date('Y-m-d H:i:s') . "</p>
    </div>";

if($chart){
    // 确保 src 属性正确包裹
    $html .= "<div class='chart-container'><img src='$chart'></div>";
}

$html .= "</body></html>";

/* generate PDF */
$options = new Options();
// 关键配置：允许处理远程资源和 Base64
$options->set('isRemoteEnabled', true); 
$options->set('isPhpEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$exportDir = __DIR__ . '/../../exports/';
if(!file_exists($exportDir)){
    mkdir($exportDir, 0755, true);
}

$filename = "report_".$reportId."_".time().".pdf";
$filePath = $exportDir . $filename;

if(file_put_contents($filePath, $dompdf->output())){
    $url = "/exports/".$filename;
    echo json_encode(["status"=>"success","url"=>$url]);
} else {
    echo json_encode(["status"=>"error","message"=>"Write permission denied on exports folder"]);
}