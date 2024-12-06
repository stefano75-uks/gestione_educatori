<?php
// Configurazione database corretta
$db_config = [
    'host' => 'localhost',
    'dbname' => 'login_system',
    'user' => 'root',
    'password' => 'MERLIN'
];

try {
    // Connessione al database
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8",
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Percorso del file CSV
   // $csv_file = 'ARCHIVIO.csv';
    $csv_file = 'cleaned_file.csv';

    if (!file_exists($csv_file)) {
        throw new Exception("File CSV non trovato: $csv_file");
    }

    $file = fopen($csv_file, 'r');
    if (!$file) {
        throw new Exception("Impossibile aprire il file CSV");
    }

    // Salta l'intestazione
    fgetcsv($file, 0, ';');

    // Query preparate
    $check_matricola = "SELECT matricola FROM detenuti WHERE matricola = ?";
    $insert_query = "INSERT INTO detenuti 
                    (matricola, cognome, nome, data_ingresso_istituto, 
                     data_uscita, data_nascita, reparto) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $check_stmt = $pdo->prepare($check_matricola);
    $insert_stmt = $pdo->prepare($insert_query);

    // Contatori
    $processed = 0;
    $inserted = 0;
    $skipped = 0;

    // Elabora ogni riga del CSV
    while (($row = fgetcsv($file, 0, ';')) !== FALSE) {
        $processed++;

        // Mappatura campi corretta
        $matricola = $row[0];      // MATRICOLA
        $afis = $row[10];          // Campo AFIS/SIAP
        $cognome = $row[1];        // COGNOME
        $nome = $row[2];           // NOME
        $data_ingresso = !empty($row[4]) ? date('Y-m-d', strtotime(str_replace('/', '-', $row[4]))) : null;
        $data_uscita = !empty($row[6]) ? date('Y-m-d', strtotime(str_replace('/', '-', $row[6]))) : null;
        $nato_il = !empty($row[9]) ? date('Y-m-d', strtotime(str_replace('/', '-', $row[9]))) : null;
        $ubicazione = $row[5];     // UBICAZIONE - corretto per il campo reparto

        // Verifica se l'AFIS esiste già come matricola
        $check_stmt->execute([$afis]);
        if ($check_stmt->rowCount() > 0) {
            $skipped++;
            continue;
        }

        // Se l'AFIS non esiste come matricola, inserisci il record
        try {
            $insert_stmt->execute([
                $afis,              // Usa AFIS come matricola
                $cognome,
                $nome,
                $data_ingresso,
                $data_uscita,
                $nato_il,
                $ubicazione         // Usa ubicazione invece di SIAP per il campo reparto
            ]);
            $inserted++;
            error_log("Inserito nuovo record - AFIS: $afis, Nome: $nome $cognome, Reparto: $ubicazione");
        } catch (PDOException $e) {
            error_log("Errore nell'inserimento del record con AFIS $afis: " . $e->getMessage());
        }
    }

    fclose($file);

    // Report finale
    echo "Elaborazione completata:\n";
    echo "- Record elaborati: $processed\n";
    echo "- Nuovi record inseriti: $inserted\n";
    echo "- Record saltati (AFIS già presente): $skipped\n";

} catch (Exception $e) {
    die("Errore: " . $e->getMessage());
}