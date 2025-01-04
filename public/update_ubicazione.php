<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $reparto = $data['reparto'] ?? '';

    if (!$id || empty($reparto)) {
        throw new Exception('Parametri mancanti');
    }

    $stmt = $conn->prepare("UPDATE detenuti SET reparto = ? WHERE id = ?");
    $stmt->bind_param("si", $reparto, $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Errore durante l\'aggiornamento');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Ubicazione aggiornata con successo'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}