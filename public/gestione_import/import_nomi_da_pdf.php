<?php
class MatricolaGenerator {
    private $used_matricole = [];
    private $used_matricole_int = [];
    
    public function generateMatricola() {
        do {
            $matricola = 'MAT' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
        } while (isset($this->used_matricole[$matricola]));
        
        $this->used_matricole[$matricola] = true;
        return $matricola;
    }
    
    public function generateMatricolaInt() {
        do {
            $matricola = 'INT' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        } while (isset($this->used_matricole_int[$matricola]));
        
        $this->used_matricole_int[$matricola] = true;
        return $matricola;
    }
}

function scanDirectory($dir) {
    $detenuti = [];
    $totale_files = 0;
    
    $years = glob($dir . '/ANNO *');
    echo "Trovate " . count($years) . " directory degli anni\n";
    
    foreach ($years as $yearDir) {
        $year = basename($yearDir);
        echo "\nProcessando $year\n";
        
        $pdfFiles = glob($yearDir . '/*/*.pdf');
        $totale_files += count($pdfFiles);
        
        foreach ($pdfFiles as $pdf) {
            $filename = basename($pdf);
            echo "Processando file: $filename\n";
            
            if (preg_match('/^([^0-9]+) ([^0-9]+) \d{2}\.\d{2}\.\d{4}\.pdf$/i', $filename, $matches)) {
                $cognome = trim($matches[1]);
                $nome = trim($matches[2]);
                
                $key = strtolower($cognome . '_' . $nome);
                if (!isset($detenuti[$key])) {
                    $detenuti[$key] = [
                        'cognome' => $cognome,
                        'nome' => $nome
                    ];
                    echo "Aggiunto: $cognome $nome\n";
                } else {
                    echo "Duplicato ignorato: $cognome $nome\n";
                }
            } else {
                echo "ATTENZIONE: Il file $filename non corrisponde al pattern atteso\n";
            }
        }
    }
    
    $count = count($detenuti);
    echo "\nTotale file PDF trovati: $totale_files\n";
    echo "Totale detenuti unici trovati: $count\n";
    return array_values($detenuti);
}

function insertIntoDatabase($detenuti) {
    $host = 'localhost';
    $dbname = 'login_system';
    $username = 'root';
    $password = 'MERLIN';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verifica matricole esistenti
        $existing_matricole = [];
        $stmt = $pdo->query("SELECT matricola FROM detenuti");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_matricole[$row['matricola']] = true;
        }

        // Verifica detenuti esistenti per nome e cognome
        $existing_detenuti = [];
        $stmt = $pdo->query("SELECT LOWER(CONCAT(cognome, '_', nome)) as full_name FROM detenuti");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_detenuti[$row['full_name']] = true;
        }

        $matricolaGen = new MatricolaGenerator();
        
        $stmt = $pdo->prepare("INSERT INTO detenuti (cognome, nome, matricola, matricola_int, reparto, 
                              data_ingresso_istituto, data_uscita, data_nascita, dove, foto) 
                              VALUES (:cognome, :nome, :matricola, :matricola_int, :reparto,
                              :data_ingresso, :data_uscita, :data_nascita, :dove, :foto)");

        $inserted = 0;
        $skipped = 0;
        foreach ($detenuti as $detenuto) {
            $key = strtolower($detenuto['cognome'] . '_' . $detenuto['nome']);
            
            // Verifica se il detenuto esiste già
            if (isset($existing_detenuti[$key])) {
                echo "Detenuto già presente nel database: {$detenuto['cognome']} {$detenuto['nome']}\n";
                $skipped++;
                continue;
            }

            $data_nascita = date('Y-m-d', strtotime('-' . rand(20, 70) . ' years'));
            $data_ingresso = date('Y-m-d', strtotime('-' . rand(1, 5) . ' years'));
            $data_uscita = rand(0, 1) ? date('Y-m-d', strtotime('+' . rand(1, 10) . ' years')) : null;
            
            try {
                // Genera matricola unica
                do {
                    $matricola = $matricolaGen->generateMatricola();
                } while (isset($existing_matricole[$matricola]));
                
                $matricola_int = $matricolaGen->generateMatricolaInt();
                
                $stmt->execute([
                    ':cognome' => $detenuto['cognome'],
                    ':nome' => $detenuto['nome'],
                    ':matricola' => $matricola,
                    ':matricola_int' => $matricola_int,
                    ':reparto' => 'Reparto ' . rand(1, 5),
                    ':data_ingresso' => $data_ingresso,
                    ':data_uscita' => $data_uscita,
                    ':data_nascita' => $data_nascita,
                    ':dove' => 'Sezione ' . rand(1, 10),
                    ':foto' => 'foto_' . strtolower($detenuto['cognome']) . '.jpg'
                ]);
                $inserted++;
                echo "Inserito nel database: {$detenuto['cognome']} {$detenuto['nome']} (Matricola: $matricola)\n";
                
                // Aggiorna le liste di controllo
                $existing_matricole[$matricola] = true;
                $existing_detenuti[$key] = true;
                
            } catch (PDOException $e) {
                echo "Errore inserimento {$detenuto['cognome']} {$detenuto['nome']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\nRiepilogo:\n";
        echo "- Detenuti già presenti (saltati): $skipped\n";
        echo "- Nuovi detenuti inseriti: $inserted\n";
        echo "- Totale processati: " . count($detenuti) . "\n";
        return true;
    } catch (PDOException $e) {
        echo "Errore connessione database: " . $e->getMessage() . "\n";
        return false;
    }
}

// Esecuzione dello script
$directoryPath = 'D:/rapporti disciplinari';

if (!is_dir($directoryPath)) {
    die("La directory $directoryPath non esiste o non è accessibile\n");
}

$detenuti = scanDirectory($directoryPath);

if (!empty($detenuti)) {
    if (insertIntoDatabase($detenuti)) {
        echo "\nProcesso completato con successo.";
    } else {
        echo "\nSi è verificato un errore durante l'inserimento nel database.";
    }
} else {
    echo "\nNessun file trovato o errore nella lettura dei file.";
}
?>