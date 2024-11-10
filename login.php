<?php
session_start();
require_once 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';

// Se c'è una richiesta POST di login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $sql = "SELECT u.*, r.name as role_name 
            FROM utenti u 
            JOIN roles r ON u.role_id = r.id 
            WHERE username = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role_name'];
        
        if ($user['role_name'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: public/start.php");
        }
        exit();
    } else {
        $error = 'Nome utente o password non validi.';
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Archivio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #fff;
            border-bottom: 2px solid #f0f0f0;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-center mb-0">Accesso al Sistema</h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nome utente</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary">Accedi</button>
                            </div>
                        </form>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger mt-3">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>