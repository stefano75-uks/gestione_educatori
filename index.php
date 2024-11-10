<?php
session_start();

// Se l'utente non è loggato, redirect al login
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Se l'utente è loggato, redirect alla pagina appropriata
if ($_SESSION['role'] === 'admin') {
    header("Location: admin.php");
} else {
    header("Location: public/start.php");
}
exit;
?>