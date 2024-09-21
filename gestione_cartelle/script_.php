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

// Funzione per scansionare le directory ricorsivamente
function scanDirectories($dir, &$results = []) {
    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            scanDirectories($path, $results);
        } else {
            if (pathinfo($path, PATHINFO_EXTENSION) == 'pdf') {
                $results[] = $path;
            }
        }
    }

    return $results;
}

// Directory principale
$mainDir = 'C:\xampp\htdocs\move\gestione_cartelle\uploads';

// Scansiona tutte le directory e raccogli i file PDF
$pdfFiles = scanDirectories($mainDir);

foreach ($pdfFiles as $filePath) {
    // Estrai cognome, nome e data evento dal nome del file
    $filename = pathinfo($filePath, PATHINFO_FILENAME);
    $parts = explode(' ', $filename);
    if (count($parts) < 3) continue; // Salta i file con nome non conforme
    $cognome = $parts[0];
    $nome = $parts[1];
    $dataEvento = date('Y-m-d', strtotime(str_replace('.', '-', $parts[2])));

    // Cerca l'utente nel database
    $sql = "SELECT id FROM users WHERE cognome = ? AND nome = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $cognome, $nome);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Utente trovato, prendi l'ID utente
        $row = $result->fetch_assoc();
        $userId = $row['id'];

        // Inserisci il record nella tabella documenti
        $tipoDocumento = 'disciplinare';
        $insertSql = "INSERT INTO documenti (user_id, tipo_documento, file_path, data_evento) VALUES (?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param('isss', $userId, $tipoDocumento, $filePath, $dataEvento);
        $insertStmt->execute();
    }
}

// Chiudi la connessione
$conn->close();
?>
