<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$error = '';
$success = '';

// Funzione per ottenere le informazioni dell'utente
function getUserInfo($conn, $username) {
    $sql = "SELECT * FROM utenti WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

// Funzione per ottenere i permessi dell'utente
function getUserPermissions($conn, $user_id) {
    $sql = "SELECT * FROM permissions WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[$row['table_name']] = $row;
    }
    $stmt->close();
    return $permissions;
}

// Funzione per cambiare la password di un utente esistente
function changePassword($conn, $username, $password) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE utenti SET password = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $hashed_password, $username);
    $stmt->execute();
    $stmt->close();
}

// Funzione per aggiungere un nuovo utente
function addUser($conn, $username, $password, $nome, $cognome, $data_nascita) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO utenti (username, password, nome, cognome, data_nascita) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $hashed_password, $nome, $cognome, $data_nascita);
    $stmt->execute();
    $stmt->close();
}

// Funzione per aggiornare i permessi dell'utente
function updatePermissions($conn, $user_id, $table_name, $can_read, $can_write, $can_delete) {
    // Controlla se esiste già un record per questa tabella e utente
    $sql = "SELECT * FROM permissions WHERE user_id = ? AND table_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $table_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        // Aggiorna i permessi esistenti
        $sql = "UPDATE permissions SET can_read = ?, can_write = ?, can_delete = ? WHERE user_id = ? AND table_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiis", $can_read, $can_write, $can_delete, $user_id, $table_name);
    } else {
        // Inserisci nuovi permessi
        $sql = "INSERT INTO permissions (user_id, table_name, can_read, can_write, can_delete) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiii", $user_id, $table_name, $can_read, $can_write, $can_delete);
    }
    $stmt->execute();
    $stmt->close();
}

// Funzione per ottenere i nomi delle tabelle del database
function getTableNames($conn) {
    $tables = [];
    $sql = "SHOW TABLES";
    $result = $conn->query($sql);
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    return $tables;
}

// Variabili per i messaggi di successo e errore
$success = '';
$error = '';

// Se viene inviato il modulo di ricerca utente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_user'])) {
    $username = $_POST['username'];
    $user = getUserInfo($conn, $username);
    if (!$user) {
        $error = "Utente non trovato.";
    } else {
        $permissions = getUserPermissions($conn, $user['id']);
    }
}

// Se viene inviato il modulo di cambiamento password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $username = $_POST['username'];
    $new_password = $_POST['new_password'];
    changePassword($conn, $username, $new_password);
    $success = "Password cambiata con successo.";
}

// Se viene inviato il modulo di creazione utente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_user'])) {
    $new_username = $_POST['new_username'];
    $new_password = $_POST['new_password'];
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $data_nascita = $_POST['data_nascita'];

    // Verifica se l'utente esiste già
    if (getUserInfo($conn, $new_username)) {
        $error = "L'utente esiste già.";
    } else {
        addUser($conn, $new_username, $new_password, $nome, $cognome, $data_nascita);
        $success = "Utente creato con successo.";
    }
}

// Se viene inviato il modulo di aggiornamento dei permessi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_permissions'])) {
    $user_id = $_POST['user_id'];
    foreach ($_POST['permissions'] as $table_name => $perms) {
        $can_read = isset($perms['can_read']) ? 1 : 0;
        $can_write = isset($perms['can_write']) ? 1 : 0;
        $can_delete = isset($perms['can_delete']) ? 1 : 0;
        updatePermissions($conn, $user_id, $table_name, $can_read, $can_write, $can_delete);
    }
    $success = "Permessi aggiornati con successo.";
}

// Ottieni l'elenco delle tabelle
$tables = getTableNames($conn);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2>Amministrazione</h2>
            <p><a href="logout.php" class="btn btn-secondary">Logout</a></p>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Modulo per la ricerca utente -->
            <form method="post" action="admin.php" class="mb-4">
                <h4>Ricerca Utente</h4>
                <div class="mb-3">
                    <label for="username" class="form-label">Nome utente</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                </div>
                <button type="submit" name="search_user" class="btn btn-primary">Cerca</button>
            </form>

            <!-- Modulo per la creazione utente -->
            <form method="post" action="admin.php" class="mb-4">
                <h4>Crea Utente</h4>
                <div class="mb-3">
                    <label for="new_username" class="form-label">Nome utente</label>
                    <input type="text" name="new_username" id="new_username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" required>
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
                <button type="submit" name="create_user" class="btn btn-primary">Crea Utente</button>
            </form>

            <?php if (isset($user)): ?>
                <!-- Modulo per la modifica dei permessi -->
                <form method="post" action="admin.php">
                    <h4>Permessi Utente: <?php echo htmlspecialchars($user['username']); ?></h4>
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <?php foreach ($tables as $table): ?>
                        <div class="mb-3">
                            <label><?php echo htmlspecialchars($table); ?></label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[<?php echo $table; ?>][can_read]" id="<?php echo $table; ?>_can_read" <?php echo isset($permissions[$table]) && $permissions[$table]['can_read'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="<?php echo $table; ?>_can_read">
                                    Leggi
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[<?php echo $table; ?>][can_write]" id="<?php echo $table; ?>_can_write" <?php echo isset($permissions[$table]) && $permissions[$table]['can_write'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="<?php echo $table; ?>_can_write">
                                    Scrivi
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[<?php echo $table; ?>][can_delete]" id="<?php echo $table; ?>_can_delete" <?php echo isset($permissions[$table]) && $permissions[$table]['can_delete'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="<?php echo $table; ?>_can_delete">
                                    Elimina
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="update_permissions" class="btn btn-primary">Aggiorna Permessi</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
