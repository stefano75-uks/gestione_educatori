<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit();
}

try {
    $reparto = $_GET['reparto'] ?? '';
    
    if (empty($reparto)) {
        throw new Exception('Parametro reparto mancante');
    }

    $sql = "SELECT id, matricola, nome, cognome, reparto 
            FROM detenuti 
            WHERE UPPER(LEFT(reparto, 3)) = ? 
            AND (data_uscita IS NULL OR data_uscita = '0000-00-00')
            ORDER BY cognome, nome";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $reparto);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $detenuti = [];
    while ($row = $result->fetch_assoc()) {
        $detenuti[] = $row;
    }

    echo json_encode([
        'success' => true,
        'detenuti' => $detenuti
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}