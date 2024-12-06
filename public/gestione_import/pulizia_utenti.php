<?php

class DBSynchronizer {
    private $db;
    private $filename;
    private $headers;
    
    public function __construct($filename) {
        $this->filename = $filename;
        
        // Connessione al database
        $this->db = new mysqli('localhost', 'root', 'MERLIN', 'login_system');
        if ($this->db->connect_error) {
            throw new Exception("Connessione al database fallita: " . $this->db->connect_error);
        }
        $this->db->set_charset("utf8mb4");
    }

    private function normalizeDate($date) {
        if (empty($date) || $date === '9999-99-99') {
            return null;
        }
        return $date;
    }

    private function getColumnValue($record, $columnNames) {
        foreach ($columnNames as $name) {
            if (isset($record[$name])) {
                return $record[$name];
            }
        }
        return null;
    }
    
    public function synchronize() {
        if (!file_exists($this->filename)) {
            throw new Exception("File CSV non trovato: " . $this->filename);
        }

        $handle = fopen($this->filename, "r");
        if ($handle === false) {
            throw new Exception("Impossibile aprire il file CSV");
        }

        // Leggi l'intestazione
        $this->headers = fgetcsv($handle, 0, ";");
        echo "Colonne trovate nel CSV: " . implode(", ", $this->headers) . "\n\n";
        
        $stats = [
            'processed' => 0,
            'inserted' => 0,
            'errors' => 0
        ];

        // Query allineata con la struttura esatta della tabella
        $insertQuery = "INSERT INTO detenuti (
                         cognome,
                         data_ingresso_istituto,
                         data_nascita,
                         data_uscita,
                         dove,
                         matricola,
                         matricola_int,
                         nome,
                         reparto
                       ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Sconosciuto')";
                       
        $stmt = $this->db->prepare($insertQuery);

        while (($row = fgetcsv($handle, 0, ";")) !== false) {
            $stats['processed']++;
            
            $record = array_combine($this->headers, $row);
            
            // Controlla se il record esiste già usando la matricola (chiave unique)
            $checkQuery = "SELECT COUNT(*) as count FROM detenuti WHERE 
                         matricola = ? COLLATE utf8mb4_general_ci";
            
            $check = $this->db->prepare($checkQuery);
            $check->bind_param("s", $record['matricola']);
            $check->execute();
            $result = $check->get_result();
            $exists = $result->fetch_assoc()['count'] > 0;
            
            if (!$exists) {
                // Ottieni e normalizza i dati
                $dataNascita = $this->getColumnValue($record, ['nato_il', 'data_nascita']);
                $dataIngresso = $this->getColumnValue($record, ['data_ingresso_istituto']);
                $dataUscita = $this->getColumnValue($record, ['data_uscita']);
                $dove = $this->getColumnValue($record, ['dove']);
                
                // Normalizza le date
                $data_ingresso = $this->normalizeDate($dataIngresso);
                $data_uscita = $this->normalizeDate($dataUscita);
                $data_nascita = $this->normalizeDate($dataNascita);
                
                // Bind dei parametri nell'ordine corretto della query
                $stmt->bind_param("ssssssss",
                    $record['cognome'],
                    $data_ingresso,
                    $data_nascita,
                    $data_uscita,
                    $dove,
                    $record['matricola'],
                    $record['matricola_int'],
                    $record['nome']
                );

                try {
                    if ($stmt->execute()) {
                        $stats['inserted']++;
                        echo "Inserito: Matricola: {$record['matricola']}, " .
                             "Nome: {$record['nome']}, Cognome: {$record['cognome']}\n";
                    } else {
                        $stats['errors']++;
                        echo "Errore - Matricola: {$record['matricola']}, " .
                             "Errore: " . $stmt->error . "\n";
                    }
                } catch (Exception $e) {
                    $stats['errors']++;
                    echo "Errore - Matricola: {$record['matricola']}, " .
                         "Errore: " . $e->getMessage() . "\n";
                }
            }
        }

        $stmt->close();
        fclose($handle);
        $this->db->close();

        echo "\nRiepilogo:\n";
        echo "Processati: {$stats['processed']}\n";
        echo "Inseriti: {$stats['inserted']}\n";
        echo "Errori: {$stats['errors']}\n";
        
        return $stats;
    }
}

try {
    $syncer = new DBSynchronizer("output_final.csv");
    $syncer->synchronize();
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}

?>