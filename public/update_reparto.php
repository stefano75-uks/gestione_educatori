<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Verifica accesso e permessi
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit();
}

try {
    $repartoOriginal = $_POST['reparto_original'] ?? '';
    $newReparto = $_POST['new_reparto'] ?? '';

    if (empty($repartoOriginal) || empty($newReparto)) {
        throw new Exception('Parametri mancanti');
    }

    // Aggiorna il reparto
    $stmt = $conn->prepare("UPDATE detenuti SET reparto = ? WHERE reparto = ? AND (data_uscita IS NULL OR data_uscita = '0000-00-00')");
    $stmt->bind_param("ss", $newReparto, $repartoOriginal);
    
    if (!$stmt->execute()) {
        throw new Exception('Errore durante l\'aggiornamento');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Reparto aggiornato con successo'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>