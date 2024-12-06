<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['documento'])) {
        $file = $_FILES['documento'];

        // Definisci il percorso di upload
        $upload_path = 'D:/upload/documenti/'; // Directory dove salvare i file

        // Crea la directory se non esiste
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        // Imposta il percorso completo del file
        $filepath = $upload_path . basename($file['name']);

        // Prova a spostare il file dalla directory temporanea a quella di destinazione
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            echo "File caricato con successo: <strong>$filepath</strong>";
        } else {
            echo "Errore nel caricamento del file.";
        }
    } else {
        echo "Nessun file ricevuto.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Upload</title>
</head>
<body>
    <h1>Test Upload File</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="documento" required>
        <button type="submit">Carica</button>
    </form>
</body>
</html>
