<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Impostare l'header Content-Type prima di qualsiasi output
header('Content-Type: application/json');

require_once '../config.php';

// Log per debug
error_log("Request to get_documenti.php");
error_log("GET parameters: " . print_r($_GET, true));
error_log("Session: " . print_r($_SESSION, true));

// Verifica accesso
if (!isset($_SESSION['loggedin'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Non autorizzato'
    ]);
    exit();
}

// Verifica parametri
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'ID utente non valido'
    ]);
    exit();
}


$user_id = (int)$_GET['user_id'];

try {
    $sql = "SELECT * FROM documenti WHERE user_id = ?";
    $params = [$user_id];
    $types = "i";

    if (!empty($_GET['data_da'])) {
        $sql .= " AND data_evento >= ?";
        $params[] = $_GET['data_da'];
        $types .= "s";
    }

    if (!empty($_GET['data_a'])) {
        $sql .= " AND data_evento <= ?";
        $params[] = $_GET['data_a'];
        $types .= "s";
    }

    if (!empty($_GET['tipo'])) {
        $sql .= " AND tipo_documento = ?";
        $params[] = $_GET['tipo'];
        $types .= "s";
    }

    $sql .= " ORDER BY data_caricamento DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $documenti = [];
    while ($doc = $result->fetch_assoc()) {
        $documenti[] = $doc;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $documenti,
        'total' => count($documenti)
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Errore nel recupero dei documenti: ' . $e->getMessage()
    ]);
}
?>