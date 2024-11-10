<?php
$servername = "localhost";
$username = "root";
$password = "MERLIN";
$dbname = "login_system";

// Crea connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla connessione
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

