<?php
function openDbConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "move";

    // Crea connessione
    $conn = mysqli_connect($servername, $username, $password, $dbname);

    // Controlla connessione
    if (!$conn) {
        die("Connessione fallita: " . mysqli_connect_error());
    }

    return $conn;
}
?>
