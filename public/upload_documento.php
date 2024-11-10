<?php
session_start();
require_once '../config.php';

// Verifica accesso e permessi
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'operator'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorizzato']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id']) || !isset($_FILES['documento'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Dati mancanti']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$file = $_FILES['documento'];
$tipo_documento = $_POST['tipo_documento'] ?? '';
$data_evento = $_POST['data_evento'] ?? date('Y-m-d');
$operatore = $_SESSION['username'];

// Verifica estensione
if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Sono permessi solo file PDF']);
    exit();
}

// Struttura cartelle
$year = date('Y');
$month = date('m');
$base_path = "D:/upload/documenti";
$relative_path = "documenti/{$year}/{$month}/{$user_id}";
$upload_path = "{$base_path}/{$year}/{$month}/{$user_id}";

// Crea cartelle se non esistono
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0777, true);
}

// Nome file univoco
$file_name = date('Ymd_His') . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9\-\_]/', '', $tipo_documento) . '.pdf';
$file_full_path = $upload_path . '/' . $file_name;
$db_path = $relative_path . '/' . $file_name;

try {
    // Tenta di spostare il file
    if (!move_uploaded_file($file['tmp_name'], $file_full_path)) {
        throw new Exception('Errore durante il caricamento del file');
    }

    // Inserimento nel database
    $sql = "INSERT INTO documenti (user_id, tipo_documento, data_evento, operatore, file_path) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $tipo_documento, $data_evento, $operatore, $db_path);

    if (!$stmt->execute()) {
        unlink($file_full_path);
        throw new Exception('Errore durante il salvataggio nel database');
    }

    // Query per ottenere il conteggio aggiornato
    $count_sql = "SELECT COUNT(*) as count FROM documenti WHERE user_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $doc_count = $count_result->fetch_assoc()['count'];

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Documento caricato con successo',
        'doc_count' => $doc_count
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>