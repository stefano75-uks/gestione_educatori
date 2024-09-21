<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include_once 'db_connection.php';
include 'recupero_permessi.php';

// Definisci le opzioni per il numero di risultati per pagina
$perPageOptions = [10, 20, 50];

// Ottieni il numero di risultati per pagina selezionato dalla query string, predefinito a 10
$perPage = isset($_GET['perPage']) ? intval($_GET['perPage']) : 10;
if (!in_array($perPage, $perPageOptions)) {
    $perPage = 10;
}

// Ottieni la pagina corrente dalla query string, predefinito a 1
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) {
    $page = 1;
}

// Calcola l'offset per la query SQL
$offset = ($page - 1) * $perPage;

// Ottieni il criterio di ordinamento dalla query string, predefinito a 'matricola'
$orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'matricola';
$validColumns = ['matricola', 'cognome', 'reparto'];
if (!in_array($orderBy, $validColumns)) {
    $orderBy = 'matricola';
}

// Ottieni la direzione di ordinamento dalla query string, predefinito a 'asc'
$orderDir = isset($_GET['orderDir']) && $_GET['orderDir'] === 'desc' ? 'desc' : 'asc';

// Esegui la query per recuperare tutti gli utenti con limitazione, offset e ordinamento
$sql = "SELECT id, nome, cognome, telefono, reparto, matricola, foto FROM users ORDER BY $orderBy $orderDir LIMIT $perPage OFFSET $offset";
$result = $conn->query($sql);

// Fetch totale utenti
$totalSql = "SELECT COUNT(*) AS total FROM users";
$totalResult = $conn->query($totalSql);
$totalUsers = $totalResult->fetch_assoc()['total'];

// Calcola il numero totale di pagine
$totalPages = ceil($totalUsers / $perPage);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rubrica</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <h1 class="my-4">Rubrica</h1>

        <div class="d-flex justify-content-between mb-3">
            <div>
               <a href="add_user.php" class="btn btn-primary<?php if (!$permissions['can_write']) echo ' disabled'; ?>">Aggiungi Utente</a>
            </div>
            <div>
                <span>Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <form action="search.php" method="GET">
            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="Cerca per Cognome" name="searchCognome">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Cerca</button>
                </div>
            </div>
        </form>

        <table class="table table-bordered border-primary table-sm custom-table">
            <thead>
                <tr>
                    <th>
                        <a href="?page=<?php echo $page; ?>&perPage=<?php echo $perPage; ?>&orderBy=matricola&orderDir=<?php echo $orderDir === 'asc' ? 'desc' : 'asc'; ?>">
                            Matricola
                        </a>
                    </th>
                    <th>Nome</th>
                    <th>
                        <a href="?page=<?php echo $page; ?>&perPage=<?php echo $perPage; ?>&orderBy=cognome&orderDir=<?php echo $orderDir === 'asc' ? 'desc' : 'asc'; ?>">
                            Cognome
                        </a>
                    </th>
                    <th>
                <a href="?page=<?php echo $page; ?>&perPage=<?php echo $perPage; ?>&orderBy=reparto&orderDir=<?php echo $orderDir === 'asc' ? 'desc' : 'asc'; ?>">
                    Reparto
                </a>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $user_id = $row['id'];
                        $count_attachments_sql = "SELECT COUNT(*) AS count FROM documenti WHERE user_id = $user_id";
                        $count_result = $conn->query($count_attachments_sql);
                        $attachments_count = $count_result->fetch_assoc()['count'];

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['matricola']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['cognome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['reparto']) . "</td>";
                        echo "<td>";
                        echo "<a href='view_user.php?id=" . $row['id'] . "' class='btn btn-primary btn-sm'>Allegati <span class='badge badge-light'>$attachments_count</span></a>";
                        echo "<a href='upload_document.php?user_id=" . $row['id'] . "' class='btn btn-secondary btn-sm'";
                        if (!$permissions['can_write']) {
                            echo " disabled";
                        }
                        echo ">Carica Documento</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Nessun utente trovato</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Paginazione -->
        <nav aria-label="Page navigation">  
            <ul class="pagination">
                <?php if ($page > 1) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&perPage=<?php echo $perPage; ?>&orderBy=<?php echo $orderBy; ?>&orderDir=<?php echo $orderDir; ?>">Precedente</a>
                    </li>
                <?php endif; ?>
                <li class="page-item <?php if ($page == 1) echo 'active'; ?>">
                    <a class="page-link" href="?page=1&perPage=<?php echo $perPage; ?>&orderBy=<?php echo $orderBy; ?>&orderDir=<?php echo $orderDir; ?>">1</a>
                </li>
                <?php if ($totalPages > 1) : ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <li class="page-item <?php if ($page == $totalPages) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $totalPages; ?>&perPage=<?php echo $perPage; ?>&orderBy=<?php echo $orderBy; ?>&orderDir=<?php echo $orderDir; ?>"><?php echo $totalPages; ?></a>
                    </li>
                <?php endif; ?>
                <?php if ($page < $totalPages) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&perPage=<?php echo $perPage; ?>&orderBy=<?php echo $orderBy; ?>&orderDir=<?php echo $orderDir; ?>">Successivo</a>
                    </li>
                <?php endif; ?>
                <!-- Controlli di Paginazione -->
                <form method="GET" action="" class="form-inline mb-3">
                    <div class="form-group">
                        <label for="perPage" class="mr-2"></label>
                        <select id="perPage" name="perPage" class="form-control mr-2" onchange="this.form.submit()">
                            <?php foreach ($perPageOptions as $option) : ?>
                                <option value="<?php echo $option; ?>" <?php if ($perPage == $option) echo 'selected'; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </ul>
        </nav>
    </div>
</body>
</html>
