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
$orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'matricola';
$orderDir = isset($_GET['orderDir']) ? $_GET['orderDir'] : 'ASC';
$validColumns = ['matricola', 'nome', 'cognome', 'reparto', 'data_ingresso_istituto', 'data_uscita'];
if (!in_array($orderBy, $validColumns)) {
    $orderBy = 'matricola';
}
$orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

// Gestione ricerca
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];
$types = '';

if ($search) {
    $whereClause = "WHERE cognome LIKE ? OR matricola LIKE ? OR reparto LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types = "sss";
}

// Query principale con conteggio documenti
$sql = "SELECT d.*, 
            (SELECT COUNT(*) FROM documenti WHERE user_id = d.id) as doc_count 
        FROM detenuti d 
        $whereClause 
        ORDER BY $orderBy $orderDir 
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

// Conteggio totale per paginazione
$countSql = "SELECT COUNT(*) as total FROM detenuti $whereClause";
$countStmt = $conn->prepare($countSql);
if ($search) {
    $countStmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);
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
        .table th {
            white-space: nowrap;
        }

        .btn-group-sm>.btn {
            padding: .25rem .5rem;
            font-size: .875rem;
            line-height: 1.5;
            border-radius: .2rem;
        }

        .modal-lg {
            max-width: 900px;
        }

        .sort-icon::after {
            content: "\F282";
            font-family: "bootstrap-icons";
            font-size: 0.8em;
            margin-left: 0.5em;
        }

        .sort-icon.desc::after {
            content: "\F286";
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Sistema Gestione</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="start.php">Detenuti</a>
                    </li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin.php">Gestione Utenti</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="sync_detenuti.php">Aggiorna detenuti</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        <span class="badge bg-light text-primary ms-2">
                            <?php echo htmlspecialchars($_SESSION['role']); ?>
                        </span>
                    </span>
                    <a href="../logout.php" class="btn btn-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenuto principale -->
    <div class="container-fluid my-4">
        <!-- Barra di ricerca e controlli -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <form class="d-flex">
                            <input type="search" name="search" class="form-control me-2"
                                placeholder="Cerca per cognome, matricola o reparto..."
                                value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <select class="form-select d-inline-block w-auto"
                            onchange="window.location.href='?perPage='+this.value">
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

        <!-- Tabella detenuti -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <a href="?orderBy=matricola&orderDir=<?php echo $orderDir === 'ASC' ? 'DESC' : 'ASC'; ?>"
                                        class="text-decoration-none text-dark">
                                        Matricola
                                        <i class="bi bi-arrow-<?php echo $orderDir === 'ASC' ? 'down' : 'up'; ?>"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="?orderBy=nome&orderDir=<?php echo $orderDir === 'ASC' ? 'DESC' : 'ASC'; ?>"
                                        class="text-decoration-none text-dark">
                                        Nome
                                        <i class="bi bi-arrow-<?php echo $orderDir === 'ASC' ? 'down' : 'up'; ?>"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="?orderBy=cognome&orderDir=<?php echo $orderDir === 'ASC' ? 'DESC' : 'ASC'; ?>"
                                        class="text-decoration-none text-dark">
                                        Cognome
                                        <i class="bi bi-arrow-<?php echo $orderDir === 'ASC' ? 'down' : 'up'; ?>"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="?orderBy=reparto&orderDir=<?php echo $orderDir === 'ASC' ? 'DESC' : 'ASC'; ?>"
                                        class="text-decoration-none text-dark">
                                        Reparto
                                        <i class="bi bi-arrow-<?php echo $orderDir === 'ASC' ? 'down' : 'up'; ?>"></i>
                                    </a>
                                </th>
                                <th>Entrato il</th>
                                <th>Uscito il</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['matricola'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['nome'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['cognome'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['reparto'] ?? ''); ?></td>
                                    <td> <?php
                                            echo (!empty($row['data_ingresso_istituto']) && $row['data_ingresso_istituto'] !== '0000-00-00')
                                                ? date('d/m/Y', strtotime($row['data_ingresso_istituto']))
                                                : '-';
                                            ?></td>
                                    <td><?php
                                        echo (!empty($row['data_uscita']) && $row['data_uscita'] !== '0000-00-00')
                                            ? date('d/m/Y', strtotime($row['data_uscita']))
                                            : '-';
                                        ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <!-- Pulsante Visualizza Documenti -->
                                            <button type="button"
                                                class="btn btn-primary btn-sm documenti-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#documentiModal<?php echo $row['id']; ?>"
                                                data-detenuto-id="<?php echo $row['id']; ?>">
                                                <i class="bi bi-file-earmark-text"></i>
                                                Documenti
                                                <span class="badge bg-light text-primary"><?php echo $row['doc_count']; ?></span>
                                            </button>

                                            <?php if ($userPermissions['upload']): ?>
                                                <!-- Pulsante Carica Documento -->
                                                <button type="button"
                                                    class="btn btn-success btn-sm"
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&perPage=<?php echo $perPage; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&perPage=<?php echo $perPage; ?>">
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
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&perPage=<?php echo $perPage; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Definisci i permessi utente per JavaScript
        const userPermissions = {
            upload: <?php echo json_encode($userPermissions['upload']); ?>,
            delete: <?php echo json_encode($userPermissions['delete']); ?>
        };
    </script>
    <script src="js/documenti.js"></script>
</body>

</html>