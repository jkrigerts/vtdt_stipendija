<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'C:/Users/mikst/Downloads/Eklase_export_(2025-2026).xlsx';
$wb = IOFactory::load($file);
$sheet = $wb->getSheet(0);
for ($r=1; $r<=12; $r++) {
  $vals=[];
  for ($c=1; $c<=14; $c++) {
    $v = $sheet->getCellByColumnAndRow($c,$r)->getFormattedValue();
    $vals[] = $v;
  }
  echo "ROW $r\n";
  foreach ($vals as $i=>$v) {
    if ($v!=='') echo ($i+1).":[$v] ";
  }
  echo "\n\n";
}
