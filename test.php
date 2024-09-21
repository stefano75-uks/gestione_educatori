<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $spreadsheet = IOFactory::load('test.xlsx'); // Sostituisci con un file Excel di test
    echo "PHPExcel caricato correttamente!";
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage();
}
