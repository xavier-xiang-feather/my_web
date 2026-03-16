<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
if(!$data) {
    echo json_encode(["status"=>"error","message"=>"invalid json"]);
    exit;
}

$reportId = $data['id'] ?? null;
$chart = $data['chart'] ?? '';

switch($reportId){
    case 1: $title = "Accessed Browser Report"; break;
    case 2: $title = "Mouse Event Report"; break;
    case 3: $title = "Performance Report"; break;
    default: $title = "System Report";
}

// 构造 PDF 的 HTML 内容
$html = "
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; text-align: center; color: #333; }
        .header { margin-bottom: 30px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .chart-box { width: 100%; margin-top: 20px; }
        img { width: 600px; height: auto; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>$title</h1>
        <p>Generated at: " . date('Y-m-d H:i:s') . "</p>
    </div>
    <div class='chart-box'>";

if ($chart && strpos($chart, 'data:image/png;base64,') === 0) {
    $html .= "<img src='$chart'>";
} else {
    $html .= "<p style='color:red;'>Chart data missing or invalid.</p>";
}

$html .= "
    </div>
</body>
</html>";

// 配置 Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$exportDir = __DIR__ . '/../../exports/';
if(!file_exists($exportDir)) {
    mkdir($exportDir, 0755, true);
}

$filename = "report_".$reportId."_".time().".pdf";
file_put_contents($exportDir . $filename, $dompdf->output());

echo json_encode([
    "status" => "success",
    "url" => "/exports/" . $filename
]);