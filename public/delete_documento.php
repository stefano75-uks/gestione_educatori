<?php
session_start();
require_once '../config.php';

// Imposta header JSON
header('Content-Type: application/json');

try {
    // Verifica accesso e permessi
    if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Non autorizzato');
    }

    // Leggi i dati JSON dalla richiesta POST
    $data = json_decode(file_get_contents('php://input'), true);
    $doc_id = isset($data['id']) ? (int)$data['id'] : 0;
    
    if (!$doc_id) {
        throw new Exception('Parametro non valido');
    }

    // Recupera informazioni documento
    $stmt = $conn->prepare("SELECT file_path, user_id FROM documenti WHERE id = ?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $documento = $result->fetch_assoc();

    if (!$documento) {
        throw new Exception('Documento non trovato');
    }

    // Elimina il file
    $file_path = "../uploads/documenti/{$documento['user_id']}/{$documento['file_path']}";
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Elimina record dal database
    $stmt = $conn->prepare("DELETE FROM documenti WHERE id = ?");
    $stmt->bind_param("i", $doc_id);

    if (!$stmt->execute()) {
        throw new Exception('Errore durante l\'eliminazione del documento');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Documento eliminato con successo'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>