<?php
// Configurazione database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login_system";

// Crea la connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla la connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Percorso di base per il file path nel database
$oldBasePath = 'C:\\xampp\\htdocs\\move\\gestione_cartelle\\uploads\\documents';
$newBasePath = 'http://localhost/move/gestione_cartelle/uploads/documents';

// Aggiorna i percorsi dei file nel database
$sql = "UPDATE documenti SET file_path = REPLACE(file_path, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $oldBasePath, $newBasePath);

if ($stmt->execute()) {
    echo "Percorsi aggiornati con successo.";
} else {
    echo "Errore durante l'aggiornamento dei percorsi: " . $stmt->error;
}

// Chiudi la connessione
$conn->close();
?>
