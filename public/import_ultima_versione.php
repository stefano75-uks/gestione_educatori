<?php

$dir = 'D:/upload'; // Percorso della cartella contenente i PDF
$root_dir = 'D:/upload/root_copy'; // Cartella dove copiare i file normalizzati
$non_normalizzati_dir = 'D:/upload/non_normalizzati'; // Cartella per i file non normalizzabili

// Controlla se le cartelle esistono, altrimenti creale
if (!is_dir($root_dir)) {
    mkdir($root_dir, 0777, true);
}
if (!is_dir($non_normalizzati_dir)) {
    mkdir($non_normalizzati_dir, 0777, true);
}

echo "<pre>";
echo "Inizio copia e normalizzazione avanzata dei file...
";

// Scansiona tutte le cartelle strutturate in "ANNO xxxx   DATA DECISIONE/A-Z"
$years = range(2002, 2024);
foreach ($years as $year) {
    $decision_dirs = glob("$dir/ANNO $year   DATA DECISIONE", GLOB_ONLYDIR);
    foreach ($decision_dirs as $decision_dir) {
        $letter_dirs = glob($decision_dir . '/*', GLOB_ONLYDIR);
        foreach ($letter_dirs as $letter_dir) {
            $pdf_files = glob($letter_dir . '/*.pdf');
            foreach ($pdf_files as $pdf_file) {
                $filename = basename($pdf_file);
                
                // Rimuove caratteri speciali indesiderati
                $normalized_filename = preg_replace('/[^A-Za-z0-9\s\.]/', '', $filename);
                $normalized_filename = preg_replace('/\s+/', ' ', $normalized_filename);
                $normalized_filename = trim($normalized_filename);
                
                // Gestione dei cognomi composti e variazioni della data
                if (preg_match('/^([A-Z]+(?:\s[A-Z]+)*)\s([A-Z]+(?:\s[A-Z]+)*)\s(\d{2})\.(\d{2})\.(\d{4})(?:-\d+|\sBIS|\(\d+\))?\.pdf$/i', $normalized_filename, $matches)) {
                    $cognome = strtoupper($matches[1]);
                    $nome = strtoupper($matches[2]);
                    $giorno = $matches[3];
                    $mese = $matches[4];
                    $anno = $matches[5];
                    
                    // Crea il nome file normalizzato
                    $final_filename = "$cognome $nome $giorno.$mese.$anno.pdf";
                    $destination = "$root_dir/$final_filename";

                    // Copia il file normalizzato nella cartella root_copy
                    if (!file_exists($destination)) {
                        copy($pdf_file, $destination);
                        echo "Copiato e normalizzato: $filename -> $final_filename\n";
                    } else {
                        echo "File già presente: $final_filename, non copiato nuovamente.\n";
                    }
                } else {
                    // Se il file non è normalizzabile, lo sposta nella cartella "non_normalizzati"
                    $destination = "$non_normalizzati_dir/$filename";
                    copy($pdf_file, $destination);
                    echo "File non normalizzabile: $filename spostato in $non_normalizzati_dir\n";
                }
            }
        }
    }
}

echo "\nCopia e normalizzazione avanzata completate!\n";
echo "I file normalizzati si trovano in: $root_dir\n";
echo "I file ancora non normalizzabili restano in: $non_normalizzati_dir\n";
echo "</pre>";

?>
