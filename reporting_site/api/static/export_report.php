<?php
require __DIR__ . '/../../includes/auth.php';
require_login();

require __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "invalid json"]);
    exit();
}

$reportId = $data['id'] ?? null;
$chartBase64 = $data['chart'] ?? null;

if (!$reportId) {
    echo json_encode(["status" => "error", "message" => "missing report id"]);
    exit();
}

// 设定标题
$titles = [
    1 => "Accessed Browser Report",
    2 => "Mouse Event Report",
    3 => "Performance Report"
];

if (!isset($titles[$reportId])) {
    echo json_encode(["status" => "error", "message" => "unknown report"]);
    exit();
}

$title = $titles[$reportId];

/* 构造专供 PDF 使用的简洁 HTML */
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 30px; }
        .title { font-size: 24px; font-weight: bold; color: #1e40af; }
        .meta { font-size: 12px; color: #666; }
        .content { text-align: center; }
        .chart-img { width: 100%; max-width: 650px; margin-top: 20px; border: 1px solid #ddd; padding: 10px; }
        .footer { margin-top: 50px; font-size: 10px; text-align: center; color: #999; }
    </style>
</head>
<body>
    <div class='header'>
        <div class='title'>$title</div>
        <div class='meta'>Report Generated at: " . date('Y-m-d H:i:s') . "</div>
    </div>
    
    <div class='content'>
        <p>This report displays the statistical distribution of events captured in the system.</p>";

if ($chartBase64) {
    $html .= "<img src='$chartBase64' class='chart-img'>";
} else {
    $html .= "<p style='color:red;'>Chart data was not provided.</p>";
}

$html .= "
    </div>
    <div class='footer'>
        &copy; " . date('Y') . " Your System Name. Confidential.
    </div>
</body>
</html>";

/* 配置 Dompdf */
$options = new Options();
$options->set('isRemoteEnabled', true); // 允许加载远程图片或 base64
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

/* 保存文件 */
$exportDir = __DIR__ . '/../../exports/';
if (!file_exists($exportDir)) {
    mkdir($exportDir, 0755, true);
}

$filename = "report_" . $reportId . "_" . time() . ".pdf";
$filePath = $exportDir . $filename;

if (file_put_contents($filePath, $dompdf->output())) {
    // 这里注意：URL 路径要根据你实际的 Web 根目录调整
    $url = "/exports/" . $filename; 
    echo json_encode(["status" => "success", "url" => $url]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to save file"]);
}