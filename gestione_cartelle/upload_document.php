<?php
include_once 'db_connection.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carica Documento</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>
</head>
<body>
   
    <div class="container mt-4">
        <h1 class="my-4">Carica Documento</h1>
        <a href="index.php" class="btn btn-primary mb-3">Torna alla Home</a>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Ottieni i dati dal form
            $user_id = $_POST['user_id'];
            $tipo_documento = $_POST['tipo_documento'];
            $file_path = '';

            // Assicurati che la directory di destinazione esista
            $target_dir = "uploads/documents/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Gestione upload file
            if (!empty($_FILES['file']['name'])) {
                $target_file = $target_dir . basename($_FILES["file"]["name"]);
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                    $file_path = $target_file;
                } else {
                    echo "<div class='alert alert-danger'>Errore durante il caricamento del file.</div>";
                }
            }

            // Gestione immagine webcam
            if (!empty($_POST['webcam_image'])) {
                $webcam_image = $_POST['webcam_image'];
                $file_path = $target_dir . "webcam_" . time() . ".jpg";
                list($type, $webcam_image) = explode(';', $webcam_image);
                list(, $webcam_image) = explode(',', $webcam_image);
                $webcam_image = base64_decode($webcam_image);
                file_put_contents($file_path, $webcam_image);
            }

            if ($file_path != '') {
                // Prepara l'inserimento nel database per prevenire SQL injection
                $stmt = $conn->prepare("INSERT INTO documenti (user_id, tipo_documento, file_path) VALUES (?, ?, ?)");
                $stmt->bind_param('iss', $user_id, $tipo_documento, $file_path);

                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Documento caricato con successo</div>";

                    // Registra l'azione nel log
                    logAction($conn, $user_id, $_SESSION['username'], '', '', 'Caricato un documento');

                    // Redirect a index.php
                    echo "<a href='index.php' class='btn btn-primary mt-3'>Torna alla Home</a>";
                } else {
                    echo "<div class='alert alert-danger'>Errore: " . $stmt->error . "</div>";
                }

                $stmt->close();
            }
        }
        ?>
        <form action="upload_document.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="user_id" value="<?php echo isset($_GET['user_id']) ? htmlspecialchars($_GET['user_id']) : ''; ?>">

        <div class="form-group">
                <label for="tipo_documento">Tipo di Documento</label>
                <input type="text" class="form-control" id="tipo_documento" name="tipo_documento" required>
            </div>
            <div class="form-group">
                <label for="file">Carica Documento</label>
                <input type="file" class="form-control-file" id="file" name="file">
            </div>
            <div class="form-group">
                <label for="webcam">O scatta una foto con la webcam</label>
                <div id="my_camera"></div>
                <br>
                <input type="button" value="Scatta Foto" onClick="take_snapshot()" class="btn btn-secondary">
                <input type="hidden" name="webcam_image" class="image-tag">
                <div id="results"></div>
            </div>
            <button type="submit" class="btn btn-primary">Carica</button>
        </form>
    </div>

    <script language="JavaScript">
        Webcam.set({
            width: 320,
            height: 240,
            image_format: 'jpeg',
            jpeg_quality: 90
        });
        Webcam.attach('#my_camera');

        function take_snapshot() {
            Webcam.snap(function(data_uri) {
                document.querySelector('.image-tag').value = data_uri;
                document.getElementById('results').innerHTML = '<img src="'+data_uri+'"/>';
            });
        }
    </script>
</body>
</html>
