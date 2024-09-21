<?php
// Parametri di connessione al database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rubrica";

// Connessione al database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
   