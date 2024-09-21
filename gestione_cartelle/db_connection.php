<?php
// Connessione al database
$conn = new mysqli('localhost', 'root', '', 'login_system');
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Funzione per registrare le azioni nel log
if (!function_exists('logAction')) {
    function logAction($conn, $user_id, $username, $nome, $cognome, $action) {
        // Funzione per ottenere l'indirizzo IP del client
        function getClientIp() {
            $ipaddress = '';
            if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != 'unknown') {
                $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown') {
                $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED'] != 'unknown') {
                $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
            } elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] != 'unknown') {
                $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR'] != 'unknown') {
                $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED'] != 'unknown') {
                $ipaddress = $_SERVER['HTTP_FORWARDED'];
            } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != 'unknown') {
                $ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            return $ipaddress;
        }

        // Ottenere l'indirizzo IP del client
        $ip_address = getClientIp();

        // Debug: stampa l'indirizzo IP ottenuto
        echo "<pre>IP Address: $ip_address</pre>";

        $sql = "INSERT INTO log (user_id, username, nome, cognome, ip_address, action) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $user_id, $username, $nome, $cognome, $ip_address, $action);
        $stmt->execute();
        $stmt->close();
    }
}
