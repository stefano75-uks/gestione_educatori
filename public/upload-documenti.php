<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

header('Content-Type: application/json');

// Log per debug
error_log('Richiesta di upload ricevuta');
error_log('POST: ' . print_r($_POST, true));
error_log('FILES: ' . print_r($_FILES, true));

// Verifica accesso
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

if (!isset($_POST['user_id']) || !isset($_FILES['documento'])) {
    echo json_encode(['success' => false, 'error' => 'Dati mancanti']);
    exit;
}

$user_id = (int)$_POST['user_id'];
$file = $_FILES['documento'];
$tipo_documento = $_POST['tipo_documento'] ?? '';
$data_evento = $_POST['data_evento'] ?? date('Y-m-d');
$operatore = $_SESSION['username'];

// Verifica estensione
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    echo json_encode(['success' => false, 'error' => 'Solo file PDF sono permessi']);
    exit;
}

// Struttura cartelle
$upload_base = "D:/upload/documenti";
$year = date('Y');
$month = date('m');
$upload_path = "{$upload_base}/{$year}/{$month}/{$user_id}";
$relative_path = "documenti/{$year}/{$month}/{$user_id}";

// Crea directory se non esiste
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0777, true);
}

// Nome file univoco
$filename = date('Ymd_His') . '_' . uniqid() . '.pdf';
$filepath = $upload_path . '/' . $filename;
$db_path = $relative_path . '/' . $filename;

try {
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Errore nel caricamento del file');
    }

    // Inserimento nel database
    $stmt = $conn->prepare("INSERT INTO documenti (user_id, tipo_documento, data_evento, operatore, file_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $tipo_documento, $data_evento, $operatore, $db_path);

    if (!$stmt->execute()) {
        unlink($filepath);
        throw new Exception('Errore nel salvataggio nel database');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Documento caricato con successo'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}