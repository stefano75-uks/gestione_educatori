<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function copyDocuments($sourceBaseDir, $destDir) {
    for ($year = 2000; $year <= 2024; $year++) {
        $baseDir = $sourceBaseDir . DIRECTORY_SEPARATOR . "ANNO $year   DATA DECISIONE";
        for ($char = 'A'; $char <= 'Z'; $char++) {
            $sourceDir = $baseDir . DIRECTORY_SEPARATOR . $char;
            if (is_dir($sourceDir)) {
                $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir));
                foreach ($rii as $file) {
                    if ($file->isDir()) {
                        continue;
                    }
                    $filePath = $file->getPathname();
                    $fileName = $file->getFilename();
                    $newFilePath = $destDir . DIRECTORY_SEPARATOR . $fileName;
                    if (copy($filePath, $newFilePath)) {
                        echo "File copiato: $fileName<br>";
                    } else {
                        echo "Errore nella copia del file: $fileName<br>";
                    }
                }
            } else {
                echo "Directory $sourceDir non trovata.<br>";
            }
        }
    }
}

$sourceBaseDir = 'D:\\RAPPORTI DISCIPLINARI';
$destDir = 'C:\\xampp\\htdocs\\move\\gestione_cartelle\\uploads\\documents';
copyDocuments($sourceBaseDir, $destDir);
?>

