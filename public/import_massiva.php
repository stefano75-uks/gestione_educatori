<?php
session_start();
// Aumenta il limite di tempo di esecuzione (0 = illimitato)
set_time_limit(0);
// Aumenta il limite di memoria se necessario
ini_set('memory_limit', '512M');
// Mostra tutti gli errori
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$base_path = 'D:/rapporti disciplinari';
$upload_dir = 'D:/upload/documenti';

$stats = [
    'processati' => 0,
    'associati' => 0,
    'non_trovati' => 0,
    'errori' => 0,
    'dettagli' => []
];

function cercaDetenuto($conn, $cognome, $nome, &$stats) {
    $sql = "SELECT id, cognome, nome FROM detenuti WHERE LOWER(cognome) = LOWER(?) AND LOWER(nome) = LOWER(?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $cognome, $nome);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    return false;
}

function salvaDocumento($conn, $detenuto, $data_evento, $progressivo, $file_path, $upload_dir, &$stats) {
    // Prima controlla se esiste già un documento per questa persona in questa data
    $check_sql = "SELECT COUNT(*) as esistenti, MAX(progressivo) as ultimo_progressivo 
                  FROM documenti 
                  WHERE user_id = ? AND data_evento = ? AND tipo_documento = 'rapporto'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $detenuto['id'], $data_evento);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();

    // Se non è specificato un progressivo ma esistono già documenti per quella data
    if ($progressivo === null && $check_result['esistenti'] > 0) {
        // Assegna automaticamente il prossimo progressivo
        $progressivo = $check_result['ultimo_progressivo'] + 1;
        $stats['dettagli'][] = "Assegnato progressivo automatico {$progressivo} per {$detenuto['cognome']} {$detenuto['nome']} - {$data_evento}";
    }
    // Se è specificato un progressivo, verifica che non esista già
    elseif ($progressivo !== null) {
        $check_specific_sql = "SELECT COUNT(*) as esiste 
                             FROM documenti 
                             WHERE user_id = ? AND data_evento = ? AND progressivo = ? AND tipo_documento = 'rapporto'";
        $check_specific_stmt = $conn->prepare($check_specific_sql);
        $check_specific_stmt->bind_param("isi", $detenuto['id'], $data_evento, $progressivo);
        $check_specific_stmt->execute();
        $exists = $check_specific_stmt->get_result()->fetch_assoc()['esiste'];
        
        if ($exists > 0) {
            $stats['errori']++;
            $stats['dettagli'][] = "ERRORE: Documento già esistente per {$detenuto['cognome']} {$detenuto['nome']} - {$data_evento} progressivo {$progressivo}";
            return false;
        }
    }

    $year = date('Y', strtotime($data_evento));
    $month = date('m', strtotime($data_evento));
    $dest_dir = "{$upload_dir}/{$year}/{$month}/{$detenuto['id']}";
    
    if (!file_exists($dest_dir)) {
        mkdir($dest_dir, 0777, true);
    }
    
    // Include il progressivo nel nome del file
    $new_file_name = uniqid() . '_' . date('Ymd', strtotime($data_evento));
    if ($progressivo !== null) {
        $new_file_name .= "_P{$progressivo}";
    }
    $new_file_name .= '.pdf';
    
    $dest_path = "{$dest_dir}/{$new_file_name}";
    
    if (copy($file_path, $dest_path)) {
        $sql = "INSERT INTO documenti (user_id, tipo_documento, data_evento, operatore, file_path, progressivo) 
                VALUES (?, 'rapporto', ?, 'administrator', ?, ?)";
        $stmt = $conn->prepare($sql);
        $db_file_path = "documenti/{$year}/{$month}/{$detenuto['id']}/{$new_file_name}";
        $stmt->bind_param("issi", $detenuto['id'], $data_evento, $db_file_path, $progressivo);
        
        if ($stmt->execute()) {
            $stats['associati']++;
            $detail_msg = "Associato: {$detenuto['cognome']} {$detenuto['nome']} - {$data_evento}";
            if ($progressivo !== null) {
                $detail_msg .= " (Progressivo: {$progressivo})";
            }
            $stats['dettagli'][] = $detail_msg;
            return true;
        } else {
            $stats['errori']++;
            $stats['dettagli'][] = "Errore DB per {$detenuto['cognome']} {$detenuto['nome']}: " . $conn->error;
        }
    } else {
        $stats['errori']++;
        $stats['dettagli'][] = "Errore copia file: {$file_path}";
    }
    return false;
}

function processFile($file_path, $conn, &$stats, $upload_dir) {
    $file_info = pathinfo($file_path);
    $file_name = $file_info['filename'];
    
    $pattern = '/^([A-Za-z\s]+)\s([A-Za-z\s]+)\s(\d{2})\.(\d{2})\.(\d{4})(?:-(\d+))?$/';
    
    if (preg_match($pattern, $file_name, $matches)) {
        $first = trim($matches[1]);
        $second = trim($matches[2]);
        $data_evento = "{$matches[5]}-{$matches[4]}-{$matches[3]}";
        $progressivo = isset($matches[6]) ? $matches[6] : null;

        // Prova prima come COGNOME NOME
        $detenuto = cercaDetenuto($conn, $first, $second, $stats);
        $inversione = false;
        
        // Se non trovato, prova come NOME COGNOME
        if (!$detenuto) {
            $detenuto = cercaDetenuto($conn, $second, $first, $stats);
            $inversione = true;
        }

        if ($detenuto) {
            if ($inversione) {
                $stats['dettagli'][] = "Trovato con nome/cognome invertito: {$detenuto['cognome']} {$detenuto['nome']}";
            }
            salvaDocumento($conn, $detenuto, $data_evento, $progressivo, $file_path, $upload_dir, $stats);
        } else {
            $stats['non_trovati']++;
            $stats['dettagli'][] = "Non trovato: {$first} {$second}";
        }
    } else {
        $stats['errori']++;
        $stats['dettagli'][] = "Formato file non valido: {$file_name}";
    }
    
    $stats['processati']++;
}

function scanDirectory($dir, $conn, &$stats, $upload_dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            scanDirectory($path, $conn, $stats, $upload_dir);
        } elseif (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
            processFile($path, $conn, $stats, $upload_dir);
        }
    }
}

try {
    scanDirectory($base_path, $conn, $stats, $upload_dir);
} catch (Exception $e) {
    $stats['errori']++;
    $stats['dettagli'][] = "Errore generale: " . $e->getMessage();
}

// Salva il report nel log
$log_content = date('Y-m-d H:i:s') . " - Importazione disciplinari completata\n";
$log_content .= "File processati: {$stats['processati']}\n";
$log_content .= "File associati: {$stats['associati']}\n";
$log_content .= "Detenuti non trovati: {$stats['non_trovati']}\n";
$log_content .= "Errori: {$stats['errori']}\n";
$log_content .= "Dettagli:\n" . implode("\n", $stats['dettagli']) . "\n\n";

file_put_contents('import_disciplinari_log.txt', $log_content, FILE_APPEND);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importazione Disciplinari</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Report Importazione Disciplinari</h5>
                        <a href="start.php" class="btn btn-primary btn-sm">Torna all'elenco</a>
                    </div>
<!-- Sostituisci la sezione della tabella nel file con questo codice -->
<div class="card-body">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">File Processati</h6>
                    <h2 class="mb-0"><?php echo $stats['processati']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">File Associati</h6>
                    <h2 class="mb-0"><?php echo $stats['associati']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Non Trovati</h6>
                    <h2 class="mb-0"><?php echo $stats['non_trovati']; ?></h2>
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

    <ul class="nav nav-tabs" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="success-tab" data-bs-toggle="tab" data-bs-target="#success" type="button" role="tab">
                Operazioni Riuscite
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="notfound-tab" data-bs-toggle="tab" data-bs-target="#notfound" type="button" role="tab">
                Non Trovati
                <?php if($stats['non_trovati'] > 0): ?>
                    <span class="badge bg-warning"><?php echo $stats['non_trovati']; ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="errors-tab" data-bs-toggle="tab" data-bs-target="#errors" type="button" role="tab">
                Errori
                <?php if($stats['errori'] > 0): ?>
                    <span class="badge bg-danger"><?php echo $stats['errori']; ?></span>
                <?php endif; ?>
            </button>
        </li>
    </ul>

    <div class="tab-content pt-3" id="reportTabsContent">
        <!-- Tab Operazioni Riuscite -->
        <div class="tab-pane fade show active" id="success" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Operazioni Completate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['dettagli'] as $dettaglio): ?>
                            <?php if (strpos($dettaglio, 'Associato:') === 0): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dettaglio); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Non Trovati -->
        <div class="tab-pane fade" id="notfound" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Detenuti Non Trovati</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['dettagli'] as $dettaglio): ?>
                            <?php if (strpos($dettaglio, 'Non trovato:') === 0): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dettaglio); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Errori -->
        <div class="tab-pane fade" id="errors" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Errori Riscontrati</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['dettagli'] as $dettaglio): ?>
                            <?php if (strpos($dettaglio, 'Errore') === 0 || strpos($dettaglio, 'ERRORE') === 0): ?>
                                <tr>
                                    <td class="text-danger"><?php echo htmlspecialchars($dettaglio); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>