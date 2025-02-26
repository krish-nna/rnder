<?php
// download_template.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Enable error reporting (for development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Define column headers
$headers = ['student_id', 'name', 'class', 'phno', 'division', 'rollno', 'email', 'rank_status'];
$col = 'A';

// Set headers in the first row
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// Format headers: Bold, Centered, Auto-size
$sheet->getStyle('A1:H1')->getFont()->setBold(true);
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// Clean output buffer before sending file
ob_clean();
flush();

// Set headers for file download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="student_template.xlsx"');
header('Cache-Control: max-age=0');
header('Pragma: public');
header('Expires: 0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
