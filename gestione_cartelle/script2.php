<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'db_connection.php';

function associateDocuments($destDir, $conn) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destDir));

    foreach ($rii as $file) {
        if ($file->isDir()){ 
            continue;
        }

        $filePath = $file->getPathname();
        $fileName = $file->getFilename();

        if (preg_match('/^([^ ]+) ([^ ]+) ([0-9]{2}\.[0-9]{2}\.[0-9]{2})\.pdf$/i', $fileName, $matches)) {
            $cognome = $matches[1];
            $nome = $matches[2];
            $dataEvento = DateTime::createFromFormat('d.m.y', $matches[3])->format('Y-m-d');

            $userSql = "SELECT id FROM users WHERE cognome = ? AND nome = ?";
            $stmt = $conn->prepare($userSql);
            $stmt->bind_param('ss', $cognome, $nome);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $userId = $user['id'];

                $docSql = "INSERT INTO documenti (user_id, file_path, tipo_documento, data_evento) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($docSql);
                $tipoDocumento = "Rapporto Disciplinare";
                $stmt->bind_param('isss', $userId, $filePath, $tipoDocumento, $dataEvento);
                if ($stmt->execute()) {
                    echo "Documento $fileName associato e registrato con successo.<br>";
                } else {
                    echo "Errore nella registrazione del documento $fileName nel database: " . $stmt->error . "<br>";
                }
            } else {
                echo "Utente non trovato per il file $fileName<br>";
            }
        } else {
            echo "Nome file non conforme: $fileName<br>";
        }
    }
}

$destDir = 'C:\\xampp\\htdocs\\move\\gestione_cartelle\\uploads\\documents';
associateDocuments($destDir, $conn);

$conn->close();
?>
