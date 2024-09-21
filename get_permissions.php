<?php
include 'config.php';

if (isset($_GET['user_id']) && isset($_GET['table'])) {
    $user_id = $_GET['user_id'];
    $table_name = $_GET['table'];

    // Funzione per ottenere i permessi dell'utente per una specifica tabella
    function getTablePermissions($conn, $user_id, $table_name) {
        $sql = "SELECT can_read, can_write, can_delete FROM permissions WHERE user_id = ? AND table_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $table_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $permissions = $result->fetch_assoc();
        $stmt->close();
        return $permissions;
    }

    $permissions = getTablePermissions($conn, $user_id, $table_name);

    echo json_encode($permissions);
}

