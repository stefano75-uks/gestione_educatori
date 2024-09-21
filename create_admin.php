<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $data_nascita = $_POST['data_nascita'];

    // Controlla se l'utente esiste già
    $sql = "SELECT * FROM utenti WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "L'utente esiste già.";
    } else {
        // Crea l'utente con il ruolo di amministratore
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO utenti (username, password, nome, cognome, data_nascita, is_admin) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $hashed_password, $nome, $cognome, $data_nascita);
        if ($stmt->execute()) {
            echo "Utente amministratore creato con successo.";
        } else {
            echo "Errore nella creazione dell'utente amministratore.";
        }
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Crea Amministratore
                </div>
                <div class="card-body">
                    <form action="create_admin.php" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nome utente</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" name="nome" id="nome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="cognome" class="form-label">Cognome</label>
                            <input type="text" name="cognome" id="cognome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="data_nascita" class="form-label">Data di nascita</label>
                            <input type="date" name="data_nascita" id="data_nascita" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Crea Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
