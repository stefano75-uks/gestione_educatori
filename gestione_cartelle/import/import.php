<?php
require 'vendor/autoload.php';
require 'db_connection.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use setasign\Fpdi\TcpdfFpdi;

if (isset($_POST['submit'])) {
    $file = $_FILES['file']['tmp_name'];
    $fields = $_POST['fields'];

    if (empty($file) || empty($fields)) {
        die("File o campi non selezionati");
    }

    // Connessione al database
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    $fileType = $_FILES['file']['type'];
    if (strpos($fileType, 'spreadsheet') !== false || strpos($fileType, 'excel') !== false) {
        // Lettura del file Excel
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
    } elseif (strpos($fileType, 'pdf') !== false) {
        // Lettura del file PDF
        $pdf = new TcpdfFpdi();
        $pdf->setSourceFile($file);
        $rows = [];
        
        for ($pageNo = 1; $pageNo <= $pdf->setSourceFile($file); $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
            $text = $pdf->getText();
            // Supponiamo che i dati siano separati da una virgola e che ogni riga sia su una nuova riga
            $rows = array_merge($rows, array_map('str_getcsv', explode("\n", $text)));
        }
    } else {
        die("Formato file non supportato. Carica un file Excel o PDF.");
    }

    // Controlla i duplicati e inserisci i dati
    foreach ($rows as $row) {
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $row[array_search($field, $fields)];
        }

        // Verifica se la matricola esiste già
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rubrica WHERE matricola = :matricola");
        $stmt->execute(['matricola' => $data['matricola']]);
        if ($stmt->fetchColumn() == 0) {
            // Inserisci i dati
            $placeholders = array_fill(0, count($fields), '?');
            $sql = "INSERT INTO rubrica (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
        }
    }

    echo "Dati importati con successo!";
}
?>
