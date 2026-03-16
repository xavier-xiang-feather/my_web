<?php
require __DIR__ . '/../../includes/auth.php';
require_login();
require __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options; // 新增

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

/* build HTML for PDF - 这里改用干净的模板，只嵌入图片 */
$chart = $data['chart'] ?? '';
$html = "
<div style='text-align:center; font-family:Arial;'>
    <h1>$title</h1>
    <p>Generated at: ".date('Y-m-d H:i:s')."</p>";

if($chart){
    $html .= "<div style='margin-top:20px;'><img src='$chart' style='width:100%'></div>";
}

$html .= "</div>";

/* generate PDF */
$options = new Options();
$options->set('isRemoteEnabled', true); // 必须开启以支持 Base64 图片
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();

$exportDir = __DIR__ . '/../../exports/';
if(!file_exists($exportDir)){
    mkdir($exportDir, 0755, true);
}

$filename = "report_".$reportId."_".time().".pdf";
$filePath = $exportDir . $filename;

file_put_contents($filePath, $dompdf->output());

$url = "/exports/".$filename;
echo json_encode(["status"=>"success","url"=>$url]);