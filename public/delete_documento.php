<?php
session_start();
require_once '../config.php';

// Verifica accesso e permessi
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorizzato']);
    exit();
}

// Verifica parametro
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Parametro non valido']);
    exit();
}

$doc_id = (int)$_GET['id'];

// Recupera informazioni documento
$stmt = $conn->prepare("SELECT file_path, user_id FROM documenti WHERE id = ?");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$result = $stmt->get_result();
$documento = $result->fetch_assoc();

if (!$documento) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Documento non trovato']);
    exit();
}

// Elimina il file
$file_path = "../uploads/documenti/{$documento['user_id']}/{$documento['file_path']}";
if (file_exists($file_path)) {
    unlink($file_path);
}

// Elimina record dal database
$stmt = $conn->prepare("DELETE FROM documenti WHERE id = ?");
$stmt->bind_param("i", $doc_id);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Documento eliminato con successo'
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Errore durante l\'eliminazione del documento']);
}
?>