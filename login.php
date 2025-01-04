<?php session_start(); 
require_once 'config.php'; 
error_reporting(E_ALL); 
ini_set('display_errors', 1); 

$error = ''; 

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
    <title>Login - Sistema Gestione Educatori</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />

    <link href="public/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="fontawesome/css/all.min.css" rel="stylesheet">    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .card-header {
            background-color: transparent;
            border-bottom: 2px solid #f0f0f0;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
        .btn-primary {
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        .input-group-text {
            background-color: transparent;
            border-right: none;
        }
        .form-control.with-icon {
            border-left: none;
        }
        .alert {
            border-radius: 8px;
        }
        .login-container {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .login-illustration {
            display: none;
        }
        @media (min-width: 992px) {
            .login-illustration {
                display: block;
                flex: 0 0 auto;
                padding: 2rem;
            }
            .login-form {
                flex: 1;
            }
        }
        .svg-container {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="login-container">
                        <div class="login-illustration">
                            <div class="svg-container">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 500" style="width: 250px; height: 312.5px;">
                                    <!-- Corpo Classificatore -->
                                    <rect x="50" y="50" width="300" height="400" fill="#dedede" stroke="#999" stroke-width="2"/>
                                    
                                    <!-- Cassetti -->
                                    <g id="cassetto1">
                                        <rect x="60" y="70" width="280" height="90" fill="#fff" stroke="#999"/>
                                        <rect x="80" y="100" width="180" height="25" fill="#f0f0f0" stroke="#999"/>
                                        <circle cx="290" cy="115" r="10" fill="#888"/>
                                    </g>
                                    
                                    <g id="cassetto2">
                                        <rect x="60" y="170" width="280" height="90" fill="#fff" stroke="#999"/>
                                        <rect x="80" y="200" width="180" height="25" fill="#f0f0f0" stroke="#999"/>
                                        <circle cx="290" cy="215" r="10" fill="#888"/>
                                    </g>
                                    
                                    <g id="cassetto3">
                                        <rect x="60" y="270" width="280" height="90" fill="#fff" stroke="#999"/>
                                        <rect x="80" y="300" width="180" height="25" fill="#f0f0f0" stroke="#999"/>
                                        <circle cx="290" cy="315" r="10" fill="#888"/>
                                    </g>
                                    
                                    <g id="cassetto4">
                                        <rect x="60" y="370" width="280" height="90" fill="#fff" stroke="#999"/>
                                        <rect x="80" y="400" width="180" height="25" fill="#f0f0f0" stroke="#999"/>
                                        <circle cx="290" cy="415" r="10" fill="#888"/>
                                    </g>
                                    
                                    <!-- Documenti che sporgono -->
                                    <path d="M85 95 L120 95 L120 85 L85 85 Z" fill="#FFD700"/>
                                    <path d="M140 95 L175 95 L175 85 L140 85 Z" fill="#87CEEB"/>
                                    <path d="M85 295 L120 295 L120 285 L85 285 Z" fill="#98FB98"/>
                                </svg>
                            </div>
                        </div>
                        <div class="login-form p-4">
                            <div class="text-center mb-4">
                                <h4>Sistema Gestione Educatori</h4>
                                <p class="text-muted">Accedi per gestire rapporti e relazioni</p>
                            </div>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Nome utente</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control with-icon" id="username" name="username" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control with-icon" id="password" name="password" required>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="login" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt me-2"></i>Accedi
                                    </button>
                                </div>
                            </form>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger mt-3">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>