<?php
//session_start();
$user_id = $_SESSION['user_id'];

include 'db_connection.php'; 

// Recupera i permessi dell'utente
$sql = "SELECT can_write, can_read, can_delete FROM permissions WHERE user_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $permissions = $result->fetch_assoc();

    $stmt->close();
} else {
    echo "Errore nella preparazione della query: " . $conn->error;
}

//$conn->close();

// Debug: stampa i permessi recuperati
//echo "<pre>";
//print_r($permissions);
//echo "</pre>";

