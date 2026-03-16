<?php

require __DIR__ . '/../../includes/auth.php';
require_login();

require __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){
echo json_encode([
"status"=>"error",
"message"=>"invalid json"
]);
exit();
}

$reportId = $data['id'] ?? null;

if(!$reportId){
echo json_encode([
"status"=>"error",
"message"=>"missing report id"
]);
exit();
}

/* report title */

switch($reportId){

case 1:
$title = "Accessed Browser Report";
break;

case 2:
$title = "Mouse Event Report";
break;

case 3:
$title = "Performance Report";
break;

default:
echo json_encode([
"status"=>"error",
"message"=>"unknown report"
]);
exit();
}

/* build HTML for PDF */
ob_start();

switch($reportId){

case 1:
require __DIR__ . "/../../report_pages/browser_report.php";
break;

case 2:
require __DIR__ . "/../../report_pages/mouse_event_report.php";
break;

case 3:
require __DIR__ . "/../../report_pages/performance_report.php";
break;

default:
exit("unknown report");

}

$html = ob_get_clean();

/* insert chart if provided */

$chart = $data['chart'] ?? '';

if($chart){
$html .= "<div style='margin-top:20px;'>
<img src='$chart' style='width:700px'>
</div>";
}

/* generate PDF */

$dompdf = new Dompdf();

$dompdf->loadHtml($html);

$dompdf->setPaper('A4');

$dompdf->render();

/* ensure exports directory exists */

$exportDir = __DIR__ . '/../../exports/';

if(!file_exists($exportDir)){
mkdir($exportDir, 0755, true);
}

/* filename */

$filename = "report_".$reportId."_".time().".pdf";

$filePath = $exportDir . $filename;

/* save */

file_put_contents($filePath, $dompdf->output());

/* accessible URL */

$url = "/exports/".$filename;

/* response */

echo json_encode([
"status"=>"success",
"url"=>$url
]);