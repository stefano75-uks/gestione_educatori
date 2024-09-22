<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'db_connection.php';
include 'recupero_permessi.php'; 

// Controlla se l'ID dell'utente è stato passato tramite la query string
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    // Recupera i dati dell'utente
    $sql = "SELECT id, nome, cognome, matricola, telefono FROM users WHERE id = $user_id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $username = $_SESSION['username']; // Assicurati che questa variabile sia impostata nella sessione
        $nome = $user['nome'];
        $cognome = $user['cognome'];
    } else {
        echo "Utente non trovato.";
        exit();
    }
    
    // Esegui la query per recuperare i documenti dell'utente
    $sql = "SELECT * FROM documenti WHERE user_id = $user_id";
    $doc_result = $conn->query($sql);

    // Debug per verificare se i documenti vengono trovati
    if ($doc_result) {
        if ($doc_result->num_rows > 0) {
            echo "Documenti trovati: " . $doc_result->num_rows;
        } else {
            echo "Nessun documento trovato per l'utente.";
        }
    } else {
        echo "Errore nella query dei documenti: " . $conn->error;
    }
} else {
    echo "ID utente non fornito.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettagli Utente</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function confirmDelete() {
            return confirm("Sei sicuro di voler eliminare questo documento?");
        }
    </script>
</head>
<body>
<div class="container mt-4">
    <h1 class="my-4">Dettagli Utente</h1>
    <a href="index.php" class="btn btn-primary mb-3">Torna alla Home</a>
    
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($user['nome']) . " " . htmlspecialchars($user['cognome']); ?></h5>
            <p class="card-text">Matricola: <?php echo htmlspecialchars($user['matricola']); ?></p>
            <p class="card-text">Telefono: <?php echo htmlspecialchars($user['telefono'] ?? ''); ?></p>
            <?php if (isset($user['id'])) { ?>
                <a href="upload_document.php?user_id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-secondary">Carica Documento</a>
            <?php } else { ?>
                <p>Errore: ID utente non disponibile.</p>
            <?php } ?>
        </div>
    </div>
    <h2>Documenti Caricati</h2>
    <div class="row">
        <?php
        if ($doc_result->num_rows > 0) {
            echo "<ul class='list-group w-100'>";
            while ($doc = $doc_result->fetch_assoc()) {
                echo "<li class='list-group-item'>";
                echo "<h5 class='card-title'>" . htmlspecialchars($doc['tipo_documento']) . "</h5>";
                echo "<p>" . htmlspecialchars($doc['descrizione'] ?? '') . "</p>";
                echo "<p><strong>Data Evento:</strong> " . htmlspecialchars($doc['data_evento'] ?? '') . "</p>";
                
                // Usa il percorso relativo salvato nel database per visualizzare il documento
                echo "<a href='/" . htmlspecialchars($doc['file_path']) . "' class='btn btn-info mr-2' target='_blank'>Visualizza Documento</a>";
                echo "<a href='/" . htmlspecialchars($doc['file_path']) . "' class='btn btn-secondary mr-2' download>Scarica Documento</a>";
                
                echo "<form method='POST' class='d-inline' onsubmit='return confirmDelete();'>";
                echo "<input type='hidden' name='doc_id' value='" . htmlspecialchars($doc['id']) . "'>";
                echo "<button type='submit' name='delete_doc' class='btn btn-danger'";
                if (!$permissions['can_delete']) {
                    echo " disabled";
                }
                echo ">Elimina Documento</button>"; 
                echo "</form>";
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>Nessun documento trovato</p>";
        }
        ?>
    </div>
</div>
</body>
</html>

<?php
$conn->close();
?>


<!--
Alias /disciplinari "D:/disciplinari/"

<Directory "D:/disciplinari/">
    Options Indexes FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>
-->
