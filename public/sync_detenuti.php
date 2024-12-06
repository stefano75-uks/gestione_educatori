<?php
session_start();
require_once '../config.php';

// Verifica accesso admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Connessione al database sorgente
$conn_source = new mysqli("localhost", "root", "MERLIN", "detenuti_presenti");
if ($conn_source->connect_error) {
    die("Connessione al database sorgente fallita: " . $conn_source->connect_error);
}
$conn_source->set_charset("utf8mb4");

// Statistiche sincronizzazione
$stats = [
    'nuovi' => 0,
    'aggiornati' => 0,
    'usciti' => 0,
    'errori' => 0,
    'totali' => 0,
    'dettagli' => []
];

try {
    // 1. Raccolta matricole presenti
    $matricole_presenti = [];
    $query_presenti = "SELECT matricola FROM detenuti_da_afis_2";
    $result_presenti = $conn_source->query($query_presenti);
    while ($row = $result_presenti->fetch_assoc()) {
        $matricole_presenti[] = $row['matricola'];
    }

    // 2. Aggiorna non presenti
    $update_non_presenti = "UPDATE detenuti 
                           SET data_uscita = '9999-09-09'
                           WHERE matricola NOT IN ('" . implode("','", $matricole_presenti) . "')
                           AND (data_uscita IS NULL OR data_uscita = '0000-00-00')";
    
    if ($conn->query($update_non_presenti)) {
        $stats['usciti'] = $conn->affected_rows;
        $stats['dettagli'][] = "Aggiornati {$stats['usciti']} detenuti non più presenti con data uscita 9999-09-09";
    }

    // 3. Query sorgente modificata per includere piano e cella
    $query_source = "SELECT 
                    matricola,
                    cognome,
                    nome,
                    data_ingresso_istituto,
                    data_uscita,
                    data_nascita,
                    reparto,
                    CASE WHEN piano = '0' THEN 'T' ELSE piano END as piano,
                    cella
                FROM detenuti_da_afis_2
                ORDER BY matricola";

    $result_source = $conn_source->query($query_source);

    $check_query = "SELECT id, data_uscita, reparto FROM detenuti WHERE matricola = ?";
    $insert_query = "INSERT INTO detenuti 
                    (matricola, cognome, nome, data_ingresso_istituto, data_uscita, 
                     data_nascita, reparto, data_creazione) 
                    VALUES (?, ?, ?, ?, NULL, ?, ?, NOW())";
    $update_query = "UPDATE detenuti 
                    SET cognome = ?, 
                        nome = ?, 
                        data_ingresso_istituto = ?,
                        data_uscita = NULL,
                        data_nascita = ?,
                        reparto = ?
                    WHERE matricola = ?";

    $stmt_check = $conn->prepare($check_query);
    $stmt_insert = $conn->prepare($insert_query);
    $stmt_update = $conn->prepare($update_query);

    while ($row = $result_source->fetch_assoc()) {
        $stats['totali']++;

        // Composizione del campo reparto
        $reparto_completo = $row['reparto'] . " " . $row['piano'] . "-" . $row['cella'];

        try {
            $stmt_check->bind_param("s", $row['matricola']);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $existing = $result_check->fetch_assoc();

            if (!$existing) {
                // Nuovo record
                $stmt_insert->bind_param(
                    "ssssss",
                    $row['matricola'],
                    $row['cognome'],
                    $row['nome'],
                    $row['data_ingresso_istituto'],
                    $row['data_nascita'],
                    $reparto_completo  // Usa il reparto composito
                );

                if ($stmt_insert->execute()) {
                    $stats['nuovi']++;
                    $stats['dettagli'][] = "Aggiunto: {$row['matricola']} - {$row['cognome']} {$row['nome']} - $reparto_completo";
                } else {
                    throw new Exception("Errore inserimento matricola {$row['matricola']}");
                }
            } else {
                // Aggiorna record esistente
                $stmt_update->bind_param(
                    "ssssss",
                    $row['cognome'],
                    $row['nome'],
                    $row['data_ingresso_istituto'],
                    $row['data_nascita'],
                    $reparto_completo,  // Usa il reparto composito
                    $row['matricola']
                );

                if ($stmt_update->execute()) {
                    $stats['aggiornati']++;
                    $stats['dettagli'][] = "Aggiornato: {$row['matricola']} - {$row['cognome']} {$row['nome']} - $reparto_completo";
                } else {
                    throw new Exception("Errore aggiornamento matricola {$row['matricola']}");
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
    if (isset($stmt_check)) $stmt_check->close();
    if (isset($stmt_insert)) $stmt_insert->close();
    if (isset($stmt_update)) $stmt_update->close();
    $conn_source->close();
}
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