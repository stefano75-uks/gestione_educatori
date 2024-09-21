<?php
include 'db_connection.php';

// Verifica se l'ID dell'utente è stato passato tramite la query string
if(isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    
    // Esegui la query per recuperare i dati dell'utente
    $sql = "SELECT nome, cognome, matricola FROM users WHERE id = $user_id";
    $result = $conn->query($sql);

    // Verifica se l'utente esiste nel database
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        // Messaggio di errore se l'utente non esiste
        echo "Utente non trovato.";
    }
} else {
    // Messaggio di errore se l'ID dell'utente non è stato fornito
    echo "ID utente non fornito.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allegati</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1 class="my-4">Allegati</h1>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($user['nome']) . " " . htmlspecialchars($user['cognome']); ?></h5>
            <p class="card-text">Matricola: <?php echo htmlspecialchars($user['matricola']); ?></p>
        </div>
    </div>
    <!-- Qui puoi inserire il codice per visualizzare gli allegati -->
</div>
</body>
</html>
