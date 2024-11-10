<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$base_path = 'D:/disciplinari';
$upload_dir = 'D:/upload/documenti';

$stats = [
    'processati' => 0,
    'associati' => 0,
    'non_trovati' => 0,
    'errori' => 0,
    'dettagli' => []
];

function processFile($file_path, $conn, &$stats, $upload_dir) {
    $file_info = pathinfo($file_path);
    $file_name = $file_info['filename'];
    
    if (preg_match('/^([A-Za-z\s]+)\s([A-Za-z\s]+)\s(\d{2})\.(\d{2})\.(\d{4})$/', $file_name, $matches)) {
        $cognome = trim($matches[1]);
        $nome = trim($matches[2]);
        $data_evento = "{$matches[5]}-{$matches[4]}-{$matches[3]}";
        
        $sql = "SELECT id FROM detenuti WHERE LOWER(cognome) = LOWER(?) AND LOWER(nome) = LOWER(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $cognome, $nome);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $detenuto_id = $row['id'];
            
            $year = date('Y', strtotime($data_evento));
            $month = date('m', strtotime($data_evento));
            $dest_dir = "{$upload_dir}/{$year}/{$month}/{$detenuto_id}";
            if (!file_exists($dest_dir)) {
                mkdir($dest_dir, 0777, true);
            }
            
            $new_file_name = uniqid() . '_' . date('Ymd_His') . '.pdf';
            $dest_path = "{$dest_dir}/{$new_file_name}";
            
            if (copy($file_path, $dest_path)) {
                $sql = "INSERT INTO documenti (user_id, tipo_documento, data_evento, operatore, file_path) 
                        VALUES (?, 'rapporto', ?, 'administrator', ?)";
                $stmt = $conn->prepare($sql);
                $db_file_path = "documenti/{$year}/{$month}/{$detenuto_id}/{$new_file_name}";
                $stmt->bind_param("iss", $detenuto_id, $data_evento, $db_file_path);
                
                if ($stmt->execute()) {
                    $stats['associati']++;
                    $stats['dettagli'][] = "Associato: {$cognome} {$nome} - {$data_evento}";
                } else {
                    $stats['errori']++;
                    $stats['dettagli'][] = "Errore DB: {$cognome} {$nome} - {$data_evento}";
                }
            } else {
                $stats['errori']++;
                $stats['dettagli'][] = "Errore copia file: {$file_path}";
            }
        } else {
            $stats['non_trovati']++;
            $stats['dettagli'][] = "Non trovato: {$cognome} {$nome}";
        }
        $stmt->close();
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

// Resto del codice HTML...

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
