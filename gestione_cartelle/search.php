<?php
include 'db_connection.php';

$searchCognome = isset($_GET['searchCognome']) ? $_GET['searchCognome'] : '';
$searchMatricola = isset($_GET['searchMatricola']) ? $_GET['searchMatricola'] : '';
$searchDataEvento = isset($_GET['searchDataEvento']) ? $_GET['searchDataEvento'] : '';

// Creare una query per cercare nella tabella utenti e documenti
$sql = "
    SELECT DISTINCT u.id 
    FROM users u 
    LEFT JOIN documenti d ON u.id = d.user_id 
    WHERE u.cognome LIKE ? 
    AND (u.matricola LIKE ? OR d.data_evento LIKE ?)
";

// Preparare e eseguire la query
$stmt = $conn->prepare($sql);
$searchCognomeLike = "%$searchCognome%";
$searchMatricolaLike = "%$searchMatricola%";
$searchDataEventoLike = "%$searchDataEvento%";
$stmt->bind_param('sss', $searchCognomeLike, $searchMatricolaLike, $searchDataEventoLike);
$stmt->execute();
$result = $stmt->get_result();

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
