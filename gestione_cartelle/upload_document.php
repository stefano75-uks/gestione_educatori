<?php
include_once 'db_connection.php';
session_start();
// devo recuperare l'username dell'utente loggato
$username = $_SESSION['username'];
//ora fammi vedere con il debug se l'username è stato recuperato
//echo $username;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carica Documento</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="card">
        <div class="card-body" style="background-color: #2222;">
            <div class="container mt-4">
                <h1 class="my-4">Carica Documento</h1>
                <a href="index.php" class="btn btn-primary mb-3">Torna alla Home</a>

                <?php
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    // Ottieni i dati dal form
                    $user_id = $_POST['user_id'];
                    $tipo_documento = $_POST['tipo_documento'];
                    $data_evento = $_POST['data_evento']; // Nuovo campo
                    $operatore = $_SESSION['username']; // Recupera il valore di username dalla sessione
                    $file_path = '';

                    // Assicurati che la directory di destinazione esista
                    $target_dir = "d:/disciplinari/";
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }


                    //il file_path è sbagliato, devi correggerlo perche deve essere relativo altrimenti il server non lo trova



                    // Gestione upload file
                    if (!empty($_FILES['file']['name'])) {
                        $target_file = $target_dir . basename($_FILES["file"]["name"]);
                        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                            // Memorizza il percorso relativo nel database
                            $file_path = "disciplinari/" . basename($_FILES["file"]["name"]);
                        } else {
                            echo "<div class='alert alert-danger'>Errore durante il caricamento del file.</div>";
                        }
                    }
                    if ($file_path != '') {
                        // Prepara l'inserimento nel database per prevenire SQL injection
                        $stmt = $conn->prepare("INSERT INTO documenti (user_id, tipo_documento, data_evento, file_path, operatore) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param('issss', $user_id, $tipo_documento, $data_evento, $file_path, $operatore);

                        if ($stmt->execute()) {
                            echo "<div class='alert alert-success'>Documento caricato con successo</div>";

                            // Registra l'azione nel log
                            logAction($conn, $user_id, $_SESSION['username'], '', '', 'Caricato un documento');

                            // Redirect a index.php
                            //echo "<a href='index.php' class='btn btn-primary mt-3'>Torna alla Home</a>";
                        } else {
                            echo "<div class='alert alert-danger'>Errore: " . $stmt->error . "</div>";
                        }

                        $stmt->close();
                    }
                }
                ?>

                <form action="upload_document.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo isset($_GET['user_id']) ? htmlspecialchars($_GET['user_id']) : ''; ?>">
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="tipo_documento">Tipo di Documento</label>
                            <select class="form-control" id="tipo_documento" name="tipo_documento" required>
                                <option value="Disciplinare">Disciplinare</option>
                                <option value="Certificato">Certificato</option>
                                <option value="Rapporto">Rapporto</option>
                                <option value="Altro">Altro</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="data_evento">Data dell'Evento</label>
                            <input type="date" class="form-control" id="data_evento" name="data_evento" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="file">Carica Documento</label>
                            <input type="file" class="form-control-file" id="file" name="file">
                        </div>

                    </div>
                    <button type="submit" class="btn btn-primary">Carica</button>
                </form>
            </div>
        </div>
    </div>

</body>

</html>