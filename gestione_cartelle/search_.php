<?php
include 'db_connection.php';

$searchCognome = isset($_GET['searchCognome']) ? $_GET['searchCognome'] : '';

$sql = "SELECT * FROM users WHERE cognome LIKE '%$searchCognome%'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // Se troviamo almeno un risultato, restituiamo direttamente la pagina con il record trovato
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    header("Location: view_user.php?id=$user_id");
    exit();
} else {
    // Se non ci sono risultati, restituiamo un messaggio di nessun utente trovato
    echo "Nessun utente trovato.";
}
?>
