<?php
session_start();
require_once '../config.php';

// Verifica accesso admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Connessione al database sorgente (detenuti_da_afis_2)
$conn_source = new mysqli("localhost", "root", "MERLIN", "detenuti_presenti");
if ($conn_source->connect_error) {
    die("Connessione al database sorgente fallita: " . $conn_source->connect_error);
}
$conn_source->set_charset("utf8mb4");

// Dopo la connessione al database sorgente
echo "Struttura tabella sorgente (detenuti_presenti):<br>";
$debug_source = $conn_source->query("DESCRIBE detenuti_da_afis_2");
while($row = $debug_source->fetch_assoc()) {
    echo "Colonna: " . $row['Field'] . "<br>";
}

echo "<br>Struttura tabella destinazione (login_system):<br>";
$debug_dest = $conn->query("DESCRIBE detenuti");
while($row = $debug_dest->fetch_assoc()) {
    echo "Colonna: " . $row['Field'] . "<br>";
}

// Statistiche sincronizzazione
$stats = [
    'nuovi' => 0,
    'aggiornati' => 0,
    'errori' => 0,
    'totali' => 0,
    'dettagli' => []
];

try {
    $query_source = "SELECT 
                    matricola,
                    cognome,
                    nome,
                    data_ingresso_istituto,
                    data_uscita,
                    data_nascita,
                    reparto
                FROM detenuti_da_afis_2
                ORDER BY matricola";

    $result_source = $conn_source->query($query_source);

    if (!$result_source) {
        throw new Exception("Errore nella query sorgente: " . $conn_source->error);
    }

    $check_query = "SELECT id, 
                           data_uscita,
                           reparto 
                    FROM detenuti 
                    WHERE matricola = ?";

    $insert_query = "INSERT INTO detenuti 
                    (matricola, cognome, nome, data_ingresso_istituto, data_uscita, data_nascita, 
                    reparto, data_creazione) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

    $update_query = "UPDATE detenuti 
            SET cognome = ?, 
    nome = ?, 
    data_ingresso_istituto = ?,
    data_uscita = ?,
    data_nascita = ?,
    reparto = ?
WHERE matricola = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_insert = $conn->prepare($insert_query);
    $stmt_update = $conn->prepare($update_query);

    while ($row = $result_source->fetch_assoc()) {
        $stats['totali']++;

        try {
            $stmt_check->bind_param("s", $row['matricola']);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $existing = $result_check->fetch_assoc();

            if (!$existing) {
                $stmt_insert->bind_param(
                    "sssssss",
                    $row['matricola'],
                    $row['cognome'],
                    $row['nome'],
                    $row['data_ingresso_istituto'],
                    $row['data_uscita'],
                    $row['data_nascita'],
                    $row['reparto']
                );

                if ($stmt_insert->execute()) {
                    $stats['nuovi']++;
                    $stats['dettagli'][] = "Aggiunto: {$row['matricola']} - {$row['cognome']} {$row['nome']}";
                } else {
                    throw new Exception("Errore inserimento matricola {$row['matricola']}");
                }
            } else {
                if (
                    $existing['data_uscita'] != $row['data_uscita'] ||
                    $existing['reparto'] != $row['reparto']
                ) {

                    $stmt_update->bind_param(
                        "sssssss",
                        $row['cognome'],
                        $row['nome'],
                        $row['data_ingresso_istituto'],
                        $row['data_uscita'],
                        $row['data_nascita'],
                        $row['reparto'],
                        $row['matricola']
                    );

                    if ($stmt_update->execute()) {
                        $stats['aggiornati']++;
                        $stats['dettagli'][] = "Aggiornato: {$row['matricola']} - {$row['cognome']} {$row['nome']}";
                    } else {
                        throw new Exception("Errore aggiornamento matricola {$row['matricola']}");
                    }
                }
            }
        } catch (Exception $e) {
            $stats['errori']++;
            $stats['dettagli'][] = "Errore per matricola {$row['matricola']}: {$e->getMessage()}";
        }
    }
} catch (Exception $e) {
    $stats['errori']++;
    $stats['dettagli'][] = "Errore generale: {$e->getMessage()}";
} finally {
    // Chiudi le connessioni
    if (isset($stmt_check)) $stmt_check->close();
    if (isset($stmt_insert)) $stmt_insert->close();
    if (isset($stmt_update)) $stmt_update->close();
    $conn_source->close();
}

// Salva il report nel log
$log_content = date('Y-m-d H:i:s') . " - Sincronizzazione completata\n";
$log_content .= "Totali processati: {$stats['totali']}\n";
$log_content .= "Nuovi inserimenti: {$stats['nuovi']}\n";
$log_content .= "Aggiornamenti: {$stats['aggiornati']}\n";
$log_content .= "Errori: {$stats['errori']}\n";
$log_content .= "Dettagli:\n" . implode("\n", $stats['dettagli']) . "\n\n";

file_put_contents('sync_log.txt', $log_content, FILE_APPEND);
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincronizzazione Detenuti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Report Sincronizzazione</h5>
                        <a href="start.php" class="btn btn-primary btn-sm">Torna all'elenco</a>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Totali Processati</h6>
                                        <h2 class="mb-0"><?php echo $stats['totali']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Nuovi Inserimenti</h6>
                                        <h2 class="mb-0"><?php echo $stats['nuovi']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Aggiornamenti</h6>
                                        <h2 class="mb-0"><?php echo $stats['aggiornati']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Errori</h6>
                                        <h2 class="mb-0"><?php echo $stats['errori']; ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6>Dettagli Operazioni:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Operazione</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['dettagli'] as $dettaglio): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dettaglio); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>