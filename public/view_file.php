```php
<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin'])) {
    die('Non autorizzato');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Parametro non valido');
}

$doc_id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT d.*, det.cognome, det.nome 
    FROM documenti d 
    JOIN detenuti det ON d.user_id = det.id 
    WHERE d.id = ?
");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$result = $stmt->get_result();
$documento = $result->fetch_assoc();

if (!$documento) {
    die('Documento non trovato');
}

$file_path = 'D:/upload/' . $documento['file_path'];

if (!file_exists($file_path)) {
    die('File non trovato');
}

// Invia headers per visualizzazione inline
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' .
    $documento['cognome'] . '_' .
    $documento['nome'] . '_' .
    date('Y-m-d', strtotime($documento['data_evento'])) . '.pdf"');
header('Content-Length: ' . filesize($file_path));

// Invia il file
readfile($file_path);
exit;
?>