<?php
include '../config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: start.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = $_SESSION['username'];
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verifica vecchia password
        $sql = "SELECT password FROM utenti WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!password_verify($old_password, $row['password'])) {
            echo "<div class='alert alert-danger'>Password attuale errata</div>";
            exit;
        }

        if (strlen($new_password) < 8) {
            echo "<div class='alert alert-danger'>Minimo 8 caratteri</div>";
            exit;
        }

        if ($new_password !== $confirm_password) {
            echo "<div class='alert alert-danger'>Le password non coincidono</div>";
            exit;
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql_update = "UPDATE utenti SET password = ? WHERE username = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ss", $hashed_password, $username);

        if ($stmt_update->execute()) {
            $response = array(
                'status' => 'success',
                'message' => 'Password modificata con successo!'
            );
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } else {
            echo "<div class='alert alert-danger'>Errore aggiornamento</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Errore: " . $e->getMessage() . "</div>";
    } finally {
        if (isset($stmt)) $stmt->close();
        if (isset($stmt_update)) $stmt_update->close();
        $conn->close();
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="bootstrap-5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <title>Modifica Password</title>
    <style>
        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Modifica Password</h2>
                        <div id="message"></div>
                        <form id="passwordForm">
                            <div class="mb-3">
                                <label>Password Attuale:</label>
                                <div class="password-container">
                                    <input type="password" class="form-control" name="old_password" id="old_password" required>
                                    <i class="toggle-password bi bi-eye"></i>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label>Nuova Password:</label>
                                <div class="password-container">
                                    <input type="password" class="form-control" name="new_password" id="new_password" required>
                                    <i class="toggle-password bi bi-eye"></i>
                                </div>
                                <div id="passwordStrength" class="password-strength"></div>
                                <small class="text-muted">Minimo 8 caratteri</small>
                            </div>
                            <div class="mb-4">
                                <label>Conferma Password:</label>
                                <div class="password-container">
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                                    <i class="toggle-password bi bi-eye"></i>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Conferma</button>
                                <a href="start.php" class="btn btn-secondary">Annulla</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', () => {
                const input = icon.previousElementSibling;
                input.type = input.type === 'password' ? 'text' : 'password';
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            });
        });
        document.getElementById('passwordForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('cambia_password_utenti.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(response => {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            alert(data.message);
                            window.location.replace('start.php');
                        }
                    } catch (e) {
                        document.getElementById('message').innerHTML = response;
                    }
                });
        };

        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strength = document.getElementById('passwordStrength');
            const hasLetter = /[A-Za-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            const isLongEnough = password.length >= 8;

            let strengthText = '';
            let strengthClass = '';

            if (password.length === 0) {
                strengthText = '';
            } else if (!isLongEnough) {
                strengthText = 'Troppo corta';
                strengthClass = 'text-danger';
            } else if (hasLetter && hasNumber && hasSpecial) {
                strengthText = 'Forte';
                strengthClass = 'text-success';
            } else if (hasLetter && hasNumber) {
                strengthText = 'Media';
                strengthClass = 'text-warning';
            } else {
                strengthText = 'Debole';
                strengthClass = 'text-danger';
            }

            strength.textContent = strengthText;
            strength.className = 'password-strength ' + strengthClass;
        });
    </script>
</body>

</html>