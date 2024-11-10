<?php
session_start();
require_once 'config.php';

// Verifica accesso e ruolo
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Funzione per ottenere tutti gli utenti
function getUsers($conn) {
    $sql = "SELECT u.*, r.name as role_name 
            FROM utenti u 
            LEFT JOIN roles r ON u.role_id = r.id 
            ORDER BY u.username";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Funzione per ottenere tutti i ruoli
function getRoles($conn) {
    $sql = "SELECT * FROM roles ORDER BY name";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Gestione creazione nuovo utente
if (isset($_POST['create_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $role_id = $_POST['role_id'];
    
    // Verifica se l'utente esiste già
    $check = $conn->prepare("SELECT id FROM utenti WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Username già in uso";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO utenti (username, password, nome, cognome, role_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $username, $hashed_password, $nome, $cognome, $role_id);
        
        if ($stmt->execute()) {
            $success = "Utente creato con successo";
        } else {
            $error = "Errore durante la creazione dell'utente";
        }
    }
}

// Gestione modifica password
if (isset($_POST['change_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = trim($_POST['new_password']);
    
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE utenti SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        $success = "Password modificata con successo";
    } else {
        $error = "Errore durante la modifica della password";
    }
}

// Gestione eliminazione utente
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    $stmt = $conn->prepare("DELETE FROM utenti WHERE id = ? AND username != 'admin'");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $success = "Utente eliminato con successo";
    } else {
        $error = "Errore durante l'eliminazione dell'utente";
    }
}

// Ottieni lista utenti e ruoli
$users = getUsers($conn);
$roles = getRoles($conn);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello Amministrazione</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Pannello Amministrazione</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="public/start.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin.php">Gestione Utenti</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        <span class="badge bg-light text-primary ms-2">Admin</span>
                    </span>
                    <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenuto principale -->
    <div class="container my-4">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Creazione nuovo utente -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Crea Nuovo Utente</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="col-md-4">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="col-md-4">
                        <label for="cognome" class="form-label">Cognome</label>
                        <input type="text" class="form-control" id="cognome" name="cognome" required>
                    </div>
                    <div class="col-md-4">
                        <label for="role_id" class="form-label">Ruolo</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>">
                                    <?php echo htmlspecialchars($role['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="create_user" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Crea Utente
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista utenti -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Gestione Utenti</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Ruolo</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                <td><?php echo htmlspecialchars($user['cognome']); ?></td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($user['role_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#changePasswordModal<?php echo $user['id']; ?>">
                                        <i class="bi bi-key"></i> Cambia Password
                                    </button>
                                    
                                    <?php if ($user['username'] !== 'admin'): ?>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteUserModal<?php echo $user['id']; ?>">
                                        <i class="bi bi-trash"></i> Elimina
                                    </button>
                                    <?php endif; ?>

                                    <!-- Modal Cambio Password -->
                                    <div class="modal fade" id="changePasswordModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Cambia Password - <?php echo htmlspecialchars($user['username']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <div class="mb-3">
                                                            <label for="new_password" class="form-label">Nuova Password</label>
                                                            <input type="password" class="form-control" name="new_password" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                                        <button type="submit" name="change_password" class="btn btn-primary">Salva</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Elimina Utente -->
                                    <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Conferma Eliminazione</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Sei sicuro di voler eliminare l'utente <strong><?php echo htmlspecialchars($user['username']); ?></strong>?
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-footer">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                                        <button type="submit" name="delete_user" class="btn btn-danger">Elimina</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>