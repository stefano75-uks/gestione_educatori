<?php
// Includi l'autoload di Composer
require 'vendor/autoload.php';
require 'config2.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['upload'])) {
    // Fase 1: Caricamento del file e visualizzazione dei campi
    $file = $_FILES['file']['tmp_name'];
    $tempFile = 'uploads/' . basename($_FILES['file']['name']);
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }
    move_uploaded_file($file, $tempFile);
    
    $spreadsheet = IOFactory::load($tempFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $fields = $worksheet->toArray()[0]; // Ottieni i campi dalla prima riga

    // Connessione al database per ottenere i campi della tabella
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("DESCRIBE users");
        $tableFields = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    // Campi obbligatori
    $requiredFields = ['matricola', 'nome', 'cognome'];

    echo '<!DOCTYPE html>';
    echo '<html lang="it">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Seleziona i Campi</title>';
    echo '<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">';
    echo '<style>';
    echo '.required { color: red; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="container mt-5">';
    echo '<h2>Seleziona i campi da importare</h2>';
    echo '<form action="import.php" method="post">';
    echo '<input type="hidden" name="file" value="' . htmlentities($tempFile) . '">';
    echo '<div class="row">';
    
    foreach ($fields as $index => $field) {
        $isRequired = in_array($field, $requiredFields);
        echo '<div class="col-md-6">';
        echo '<div class="form-group">';
        echo '<label for="field' . $index . '"' . ($isRequired ? ' class="required"' : '') . '>' . htmlentities($field) . ':</label>';
        echo '<select name="fields[' . $field . ']" class="form-control">';
        echo '<option value="">--Seleziona--</option>';
        foreach ($tableFields as $tableField) {
            echo '<option value="' . $tableField . '">' . ucfirst($tableField) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';

    echo '<button type="submit" name="import" class="btn btn-primary">Importa Dati</button>';
    echo '</form>';
    echo '</div>';
    echo '<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>';
    echo '<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>';
    echo '<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>';
    echo '</body>';
    echo '</html>';
} elseif (isset($_POST['import'])) {
    // Fase 2: Importazione dei dati
    $file = $_POST['file'];
    $fields = $_POST['fields'];

    if (empty($file) || empty($fields)) {
        die("File o campi non selezionati");
    }

    // Connessione al database
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    $spreadsheet = IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    array_shift($rows); // Rimuovi la prima riga (intestazioni)

    $requiredFields = ['matricola', 'nome', 'cognome'];
    $inserted = 0;

    foreach ($rows as $row) {
        $data = [];
        foreach ($fields as $excelField => $dbField) {
            if (!empty($dbField)) {
                $data[$dbField] = $row[array_search($excelField, array_keys($fields))];
            }
        }

        // Controlla se i campi obbligatori sono vuoti
        $missingRequiredFields = array_filter($requiredFields, function ($field) use ($data) {
            return empty($data[$field]);
        });

        if (!empty($missingRequiredFields)) {
            echo "Errore: i campi obbligatori " . implode(", ", $missingRequiredFields) . " non possono essere vuoti.<br>";
            continue;
        }

        // Verifica se la matricola esiste già
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE matricola = :matricola");
        $stmt->execute(['matricola' => $data['matricola']]);
        if ($stmt->fetchColumn() == 0) {
            // Inserisci i dati
            try {
                $placeholders = array_fill(0, count($data), '?');
                $sql = "INSERT INTO users (" . implode(',', array_keys($data)) . ") VALUES (" . implode(',', $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($data));
                $inserted++;
            } catch (PDOException $e) {
                echo "Errore durante l'inserimento: " . $e->getMessage() . "<br>";
            }
        }
    }

    echo "$inserted record(s) importati con successo!";
} else {
    // Fase 0: Mostra il form per caricare il file
    echo '<!DOCTYPE html>';
    echo '<html lang="it">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Importa Dati</title>';
    echo '<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">';
    echo '</head>';
    echo '<body>';
    echo '<div class="container mt-5">';
    echo '<h2>Importa Dati dalla Rubrica</h2>';
    echo '<form action="import.php" method="post" enctype="multipart/form-data">';
    echo '<div class="form-group">';
    echo '<label for="file">Seleziona File Excel:</label>';
    echo '<input type="file" name="file" class="form-control" id="file" required>';
    echo '</div>';
    echo '<button type="submit" name="upload" class="btn btn-primary">Carica File</button>';
    echo '</form>';
    echo '</div>';
    echo '<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>';
    echo '<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>';
    echo '<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>';
    echo '</body>';
    echo '</html>';
}

