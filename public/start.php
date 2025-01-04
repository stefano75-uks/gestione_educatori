<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config.php';

// Verifica accesso
if (!isset($_SESSION['loggedin'])) {
    header("Location: ../login.php");
    exit();
}

// Definizione permessi per ruolo
$permissions = [
    'admin' => ['view' => true, 'upload' => true, 'delete' => true],
    'operator' => ['view' => true, 'upload' => true, 'delete' => false],
    'guest' => ['view' => true, 'upload' => false, 'delete' => false]
];

$userPermissions = $permissions[$_SESSION['role']];

// Impostazioni paginazione
$perPageOptions = [10, 25, 50, 100];
$perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Gestione ordinamento
$orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'cognome';
$orderDir = isset($_GET['orderDir']) ? $_GET['orderDir'] : 'ASC';
$validColumns = ['matricola', 'nome', 'cognome', 'reparto', 'data_ingresso_istituto', 'data_uscita'];
if (!in_array($orderBy, $validColumns)) {
    $orderBy = 'cognome';
}
$orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

// Gestione ricerca
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];
$types = '';

if ($search) {
    $whereClause = "WHERE cognome LIKE ? OR nome LIKE ? OR matricola LIKE ? OR reparto LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    $types = "ssss";
}

// Query principale con conteggio documenti
$sql = "SELECT d.*, 
            (SELECT COUNT(*) FROM documenti WHERE user_id = d.id) as doc_count,
            (SELECT data_evento FROM documenti WHERE user_id = d.id ORDER BY data_evento DESC LIMIT 1) as ultimo_evento
        FROM detenuti d 
        $whereClause 
        ORDER BY 
            CASE 
                WHEN data_uscita IS NOT NULL AND data_uscita != '0000-00-00' THEN 1 
                ELSE 0 
            END,
            cognome ASC,
            nome ASC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($search) {
    $params[] = $perPage;
    $params[] = $offset;
    $types .= "ii";
} else {
    $params = [$perPage, $offset];
    $types = "ii";
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Statistiche
$countSql = "SELECT COUNT(*) as total FROM detenuti $whereClause";
$countStmt = $conn->prepare($countSql);
if ($search) {
    $countStmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

$presentiSql = "SELECT COUNT(*) as presenti FROM detenuti WHERE data_uscita IS NULL OR data_uscita = '0000-00-00'";
$presentiResult = $conn->query($presentiSql);
$presenti = $presentiResult->fetch_assoc()['presenti'];


$totaleDocumentiSql = "SELECT COUNT(*) as totale_documenti FROM documenti";
$totaleDocumentiResult = $conn->query($totaleDocumentiSql);
$totaleDocumenti = $totaleDocumentiResult->fetch_assoc()['totale_documenti'];

// Modifica la query per prendere solo i primi caratteri del reparto
$repartiSql = "SELECT 
                  CASE 
                    WHEN LENGTH(reparto) <= 3 THEN UPPER(reparto)
                    ELSE UPPER(LEFT(reparto, 3))
                  END as reparto_formattato,
                  COUNT(*) as conteggio 
               FROM detenuti 
               WHERE (data_uscita IS NULL OR data_uscita = '0000-00-00')
                 AND reparto IS NOT NULL 
                 AND reparto != ''
               GROUP BY reparto_formattato 
               ORDER BY reparto_formattato ASC";

$repartiResult = $conn->query($repartiSql);
$reparti = [];
while ($row = $repartiResult->fetch_assoc()) {
    $reparti[] = $row;
}

$repartiResult = $conn->query($repartiSql);
$reparti = [];
while ($row = $repartiResult->fetch_assoc()) {
    $reparti[] = $row;
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Detenuti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bs-primary-rgb: 13, 110, 253;
            --bs-secondary-rgb: 108, 117, 125;
            --bs-info-rgb: 13, 202, 240;
        }

        body {
            background-color: #f8f9fa;
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-3px);
        }

        .table th {
            white-space: nowrap;
            background-color: #f8f9fa;
        }

        .table td {
            vertical-align: middle;
        }

        .btn-group-sm>.btn {
            padding: .25rem .5rem;
            font-size: .875rem;
        }

        .badge {
            padding: 0.5em 0.8em;
        }

        .pagination {
            margin-bottom: 0;
        }

        .search-form {
            position: relative;
        }

        .search-form .btn {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            border-radius: 0 0.375rem 0.375rem 0;
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(var(--bs-primary-rgb), 0.05);
        }

        .stats-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, .125);
            background-color: #f8f9fa;
        }

        .table-sm td,
        .table-sm th {
            padding: 0.5rem;
        }

        .badge {
            font-weight: 500;
        }

        .text-white-50 {
            font-size: 0.875rem;
        }

        .card-body .row {
            margin-right: -0.5rem;
            margin-left: -0.5rem;
        }

        .card-body .col-auto {
            padding-right: 0.5rem;
            padding-left: 0.5rem;
        }

        .reparto-item {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .reparto-item:hover {
            background-color: #e9ecef !important;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-buildings me-2"></i>
                Sistema Gestione
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="start.php">
                            <i class="bi bi-people me-1"></i>
                            Detenuti
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin.php">
                                <i class="bi bi-person-gear me-1"></i>
                                Gestione Utenti
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sync_detenuti.php">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                Aggiorna detenuti
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        <li class="nav-item">
                            <a href="cambia_password_utenti.php" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i>Cambio Password
                            </a>
                        </li>
                        <span class="badge bg-light text-primary ms-2">
                            <?php echo htmlspecialchars($_SESSION['role']); ?>
                        </span>
                    </span>
                    <a href="../logout.php" class="btn btn-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <!-- Statistiche -->
        <div class="row g-4 mb-4">
            <!-- Detenuti Presenti -->
            <div class="col-md-4">
                <div class="card stats-card bg-primary bg-gradient text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Totale Presenti</h6>
                                <h2 class="my-2"><?php echo number_format($presenti); ?></h2>
                            </div>
                            <div class="fs-1">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reparti con popup per dettagli -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title d-flex align-items-center mb-3">
                            <i class="bi bi-building me-2"></i>
                            Presenze per Reparto
                        </h6>
                        <div class="row g-2">
                            <?php foreach ($reparti as $reparto): ?>
                                <div class="col-4">
                                    <div class="d-flex align-items-center bg-light rounded px-2 py-1 reparto-item"
                                        role="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#dettaglioRepartoModal"
                                        data-reparto="<?php echo htmlspecialchars($reparto['reparto_formattato']); ?>">
                                        <small class="text-secondary me-2"><?php echo htmlspecialchars($reparto['reparto_formattato']); ?></small>
                                        <span class="badge bg-secondary ms-auto"><?php echo $reparto['conteggio']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal per i dettagli del reparto -->
            <div class="modal fade" id="dettaglioRepartoModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Dettaglio Reparto <span id="repartoName" class="badge bg-secondary"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover" id="dettaglioRepartoTable">
                                    <thead>
                                        <tr>
                                            <th>Matricola</th>
                                            <th>Cognome</th>
                                            <th>Nome</th>
                                            <th>Ubicazione</th>
                                            <th>Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Popolato via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Documenti -->
            <div class="col-md-4">
                <div class="card stats-card bg-info bg-gradient text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Totale Documenti</h6>
                                <h2 class="my-2"><?php echo number_format($totaleDocumenti); ?></h2>
                            </div>
                            <div class="fs-1">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Controlli -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <form class="search-form">
                        <input type="search" name="search" class="form-control pe-5"
                            placeholder="Cerca per cognome, nome, matricola o reparto..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary px-3">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-6 text-md-end">
                    <select class="form-select d-inline-block w-auto"
                        onchange="window.location.href='?perPage='+this.value+'&search=<?php echo urlencode($search); ?>'">
                        <?php foreach ($perPageOptions as $option): ?>
                            <option value="<?php echo $option; ?>"
                                <?php echo $perPage == $option ? 'selected' : ''; ?>>
                                <?php echo $option; ?> righe
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabella -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Matricola</th>
                            <th>Cognome</th>
                            <th>Nome</th>
                            <th>Reparto</th>
                            <th>Entrato il</th>
                            <th>Uscito il</th>
                            <th>Ultimo Evento</th>
                            <th>Documenti</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['matricola'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['cognome'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['nome'] ?? ''); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($row['reparto'] ?? 'Non Assegnato'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    echo (!empty($row['data_ingresso_istituto']) && $row['data_ingresso_istituto'] !== '0000-00-00')
                                        ? date('d/m/Y', strtotime($row['data_ingresso_istituto']))
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo (!empty($row['data_uscita']) && $row['data_uscita'] !== '0000-00-00')
                                        ? date('d/m/Y', strtotime($row['data_uscita']))
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo (!empty($row['ultimo_evento']))
                                        ? date('d/m/Y', strtotime($row['ultimo_evento']))
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo $row['doc_count']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button"
                                            class="btn btn-outline-primary documenti-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#documentiModal<?php echo $row['id']; ?>"
                                            data-detenuto-id="<?php echo $row['id']; ?>">
                                            <i class="bi bi-file-earmark-text"></i>
                                            Visualizza
                                        </button>

                                        <?php if ($userPermissions['upload']): ?>
                                            <button type="button"
                                                class="btn btn-outline-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#uploadModal<?php echo $row['id']; ?>"
                                                data-detenuto-id="<?php echo $row['id']; ?>">
                                                <i class="bi bi-upload"></i>
                                                Carica
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Include i modali -->
                                    <?php include 'modals/documenti-modal.php'; ?>
                                    <?php if ($userPermissions['upload']): ?>
                                        <?php include 'modals/upload-modal.php'; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginazione -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Navigazione pagine" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&perPage=<?php echo $perPage; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link"
                                        href="?page=<?php echo $i; ?>&perPage=<?php echo $perPage; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php elseif (abs($i - $page) == 3): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link"
                                href="?page=<?php echo $page + 1; ?>&perPage=<?php echo $perPage; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    </div>
    <!-- Modal Cambio Password -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambio Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label for="oldPassword" class="form-label">Password Attuale</label>
                            <input type="password" class="form-control" id="oldPassword" name="old_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Nuova Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password"
                                required minlength="8">
                            <div class="form-text">La password deve essere di almeno 8 caratteri</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Conferma Nuova Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                            <button type="submit" class="btn btn-primary">Cambia Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const userPermissions = {
            upload: <?php echo json_encode($userPermissions['upload']); ?>,
            delete: <?php echo json_encode($userPermissions['delete']); ?>
        };
    </script>
    <script src="js/documenti.js"></script>
</body>

</html>