<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connection.php';

// Esegui la query per recuperare tutti gli utenti
$sql = "SELECT id, nome, cognome, email, telefono, matricola FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rubrica</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
       <h1 class="my-4">Rubrica</h1>
        <a href="add_user.php" class="btn btn-primary mb-3">Aggiungi Utente</a>
        <form action="search.php" method="GET">
            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="Cerca per Cognome" name="searchCognome">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Cerca</button>
                </div>
            </div>
        </form>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Cognome</th>
                    <th>Email</th>
                    <th>Telefono</th>
                    <th>Matricola</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Esegui la query per contare il numero di allegati per ogni utente
                        $user_id = $row['id'];
                        $count_attachments_sql = "SELECT COUNT(*) AS count FROM documenti WHERE user_id = $user_id";
                        $count_result = $conn->query($count_attachments_sql);
                        $attachments_count = $count_result->fetch_assoc()['count'];

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['cognome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['telefono']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['matricola']) . "</td>";
                        echo "<td>";
                        echo "<a href='view_user.php?id=" . $row['id'] . "' class='btn btn-primary btn-sm'>Allegati <span class='badge badge-light'>$attachments_count</span></a>"; // Badge incluso nel pulsante "Allegati"
                        echo "<a href='upload_document.php?user_id=" . $row['id'] . "' class='btn btn-secondary btn-sm'>Carica Documento</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Nessun utente trovato</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
