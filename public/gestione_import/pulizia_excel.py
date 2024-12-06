import pandas as pd

# Funzione per normalizzare il campo SIAP
def normalize_siap(siap):
    return siap.replace("-", "").replace(" ", "") if isinstance(siap, str) else siap

# Percorso del file CSV
file_path = "Utenti.csv"  # Sostituisci con il percorso del file

# Prova a caricare il file con diverse codifiche
encodings_to_try = ['utf-8', 'latin1', 'ISO-8859-1', 'cp1252']
for encoding in encodings_to_try:
    try:
        df = pd.read_csv(file_path, sep=';', encoding=encoding)
        print(f"File caricato con codifica: {encoding}")
        break
    except UnicodeDecodeError:
        print(f"Errore con codifica: {encoding}")
else:
    raise ValueError("Impossibile leggere il file con le codifiche disponibili.")

# Verifica che le colonne necessarie esistano
required_columns = ['MATRICOLA', 'COGNOME', 'NOME', 'DATA_INGRESSO', 'DATA_USCITA', 'NATO_IL', 'NATO_A', 'SIAP']
missing_columns = [col for col in required_columns if col not in df.columns]
if missing_columns:
    raise ValueError(f"Il file non contiene le colonne richieste: {', '.join(missing_columns)}")

# Rimuovi i duplicati basandoti su COGNOME, NOME e NATO_IL
df = df.drop_duplicates(subset=['COGNOME', 'NOME', 'NATO_IL'])

# Normalizza il campo SIAP
df['SIAP'] = df['SIAP'].apply(normalize_siap)

# Salva il risultato in un nuovo file CSV
output_path = "cleaned_file.csv"  # Sostituisci con il percorso di output desiderato
df.to_csv(output_path, sep=';', index=False, encoding=encoding)  # Usa la stessa codifica per salvare

print(f"File pulito e salvato in: {output_path}")
