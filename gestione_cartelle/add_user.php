<?php
include_once 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi o Modifica Utente</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
   
<div class="container">
    <a href="index.php" class="btn btn-primary mb-3">Torna alla Home</a>
    <h1 class="my-4">Aggiungi o Modifica Utente</h1>

    <!-- Modulo di ricerca -->
    <form action="add_user.php" method="GET">
        <div class="form-group">
            <label for="search">Cerca Utente</label>
            <input type="text" class="form-control" id="search" name="search" placeholder="Inserisci nome o cognome">
        </div>
        <button type="submit" class="btn btn-secondary">Cerca</button> 
    </form>
    <?php
    // Gestisci la ricerca utente
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $sql_search = "SELECT * FROM users WHERE nome LIKE '%$search%' OR cognome LIKE '%$search%'";
        $result = $conn->query($sql_search);

        if ($result->num_rows > 0) {
            echo "<h2 class='my-4'>Risultati della ricerca:</h2>";
            echo "<ul class='list-group'>";
            while($row = $result->fetch_assoc()) {
                echo "<li class='list-group-item'>";
                echo $row['nome'] . " " . $row['cognome'] . " - <a href='add_user.php?edit=" . $row['id'] . "' class='btn btn-sm btn-warning'>Modifica</a>";
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class='alert alert-warning'>Nessun utente trovato.</div>";
        }
    }

    // Funzione per ridimensionare l'immagine
    function resize_image($file, $target_width, $target_height) {
        list($original_width, $original_height) = getimagesize($file);
        $src = imagecreatefromjpeg($file);
        $dst = imagecreatetruecolor($target_width, $target_height);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $target_width, $target_height, $original_width, $original_height);
        return $dst;
    }

    // Gestisci l'aggiornamento dell'utente
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Ottieni i dati dal form
        $id = $_POST['id'] ?? '';
        $nome = $_POST['nome'];
        $cognome = $_POST['cognome'];
        $telefono = $_POST['telefono'];
        $reparto = $_POST['reparto'];
        $matricola = $_POST['matricola'];
        $foto = '';

        // Gestione upload foto
        if (!empty($_FILES['foto']['name'])) {
            $target_dir = "foto_utenti/";
            $imageFileType = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
            $new_filename = $target_dir . uniqid() . '.' . $imageFileType;

            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $new_filename)) {
                // Ridimensiona l'immagine a 50x50 pixel
                $resized_image = resize_image($new_filename, 50, 50);
                imagejpeg($resized_image, $new_filename, 90);
                $foto = basename($new_filename);
            }
        }

        if (!empty($id)) {
            // Aggiorna i dati nel database
            if (!empty($foto)) {
                $sql_update = "UPDATE users SET nome='$nome', cognome='$cognome', telefono='$telefono', reparto='$reparto', matricola='$matricola', foto='$foto' WHERE id='$id'";
            } else {
                $sql_update = "UPDATE users SET nome='$nome', cognome='$cognome', telefono='$telefono', reparto='$reparto', matricola='$reparto' WHERE id='$id'";
            }

            if ($conn->query($sql_update) === TRUE) {
                echo "<div class='alert alert-success'>Utente aggiornato con successo</div>";
            } else {
                echo "<div class='alert alert-danger'>Errore: " . $sql_update . "<br>" . $conn->error . "</div>";
            }
        } else {
            // Inserisci i dati nel database
            $sql_insert = "INSERT INTO users (nome, cognome, telefono, reparto, matricola, foto) VALUES ('$nome', '$cognome', '$telefono', '$reparto', '$matricola', '$foto')";
            if ($conn->query($sql_insert) === TRUE) {
                echo "<div class='alert alert-success'>Utente aggiunto con successo</div>";
            } else {
                echo "<div class='alert alert-danger'>Errore: " . $sql_insert . "<br>" . $conn->error . "</div>";
            }
        }

        $conn->close();
    }

    // Modulo di modifica
    if (isset($_GET['edit'])) {
        $id = $_GET['edit'];
        $sql_edit = "SELECT * FROM users WHERE id='$id'";
        $result_edit = $conn->query($sql_edit);
        $row_edit = $result_edit->fetch_assoc();
    ?>
        <h2 class="my-4">Modifica Utente</h2>
        <form action="add_user.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $row_edit['id']; ?>">
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo $row_edit['nome']; ?>" required>
            </div>
            <div class="form-group">
                <label for="cognome">Cognome</label>
                <input type="text" class="form-control" id="cognome" name="cognome" value="<?php echo $row_edit['cognome']; ?>" required>
            </div>
            <div class="form-group">
                <label for="telefono">Telefono</label>
                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo $row_edit['telefono']; ?>">
            </div>
            <div class="form-group">
                <label for="reparto">Reparto</label>
                <input type="text" class="form-control" id="reparto" name="reparto" value="<?php echo $row_edit['reparto']; ?>" required>
            </div>
            <div class="form-group">
                <label for="matricola">Matricola</label>
                <input type="text" class="form-control" id="matricola" name="matricola" value="<?php echo $row_edit['matricola']; ?>" required>
            </div>
            <div class="form-group">
                <label for="foto">Foto</label>
                <input type="file" class="form-control-file" id="foto" name="foto">
            </div>
            <button type="submit" class="btn btn-primary">Aggiorna</button>
        </form>
        <?php if (!empty($row_edit['foto'])): ?>
            <div class="mt-3">
                <img src="foto_utenti/<?php echo $row_edit['foto']; ?>" alt="Foto di <?php echo $row_edit['nome']; ?>" width="50" height="50">
            </div>
        <?php endif; ?>
    <?php
    } else {
    ?>
        <h2 class="my-4">Aggiungi Utente</h2>
        <form action="add_user.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="cognome">Cognome</label>
                <input type="text" class="form-control" id="cognome" name="cognome" required>
            </div>
            <div class="form-group">
                <label for="telefono">Telefono</label>
                <input type="text" class="form-control" id="telefono" name="telefono">
            </div>
            <div class="form-group">
                <label for="reparto">Reparto</label>
                <input type="text" class="form-control" id="reparto" name="reparto">
            </div>
            <div class="form-group">
                <label for="matricola">Matricola</label>
                <input type="text" class="form-control" id="matricola" name="matricola" required>
            </div>
            <div class="form-group">
                <label for="foto">Foto</label>
                <input type="file" class="form-control-file" id="foto" name="foto">
            </div>
            <button type="submit" class="btn btn-primary">Aggiungi</button>
        </form>
    <?php
    }
    ?>
</div>

</body>
</html>
