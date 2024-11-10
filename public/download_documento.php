<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin'])) {
    die('Non autorizzato');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Parametro non valido');
}

// Definisci il percorso base degli upload
define('UPLOAD_BASE_PATH', 'D:/upload');

$doc_id = (int)$_GET['id'];

// Recupera informazioni documento
$stmt = $conn->prepare("
    SELECT d.*, det.cognome, det.nome 
    FROM documenti d 
    JOIN detenuti det ON d.user_id = det.id 
    WHERE d.id = ?
");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$result = $stmt->get_result();
$documento = $result->fetch_assoc();

if (!$documento) {
    die('Documento non trovato');
}

// Converti il percorso relativo in assoluto
$file_path = UPLOAD_BASE_PATH . '/' . $documento['file_path'];

// Verifica esistenza file
if (!file_exists($file_path)) {
    die('File non trovato: ' . $file_path);
}

// Prepara nome file per il download
$download_name = sprintf(
    "%s_%s_%s_%s.pdf",
    preg_replace('/[^A-Za-z0-9\-\_]/', '', $documento['cognome']),
    preg_replace('/[^A-Za-z0-9\-\_]/', '', $documento['nome']),
    preg_replace('/[^A-Za-z0-9\-\_]/', '', $documento['tipo_documento']),
    date('Y-m-d', strtotime($documento['data_evento']))
);

// Invia headers
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private');
header('Pragma: private');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Invia file
readfile($file_path);
exit();
?>