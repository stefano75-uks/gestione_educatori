import pandas as pd
import mysql.connector
from datetime import datetime
import random
import string

def generate_unique_matricola(existing_matricole):
    """Genera una matricola casuale univoca"""
    while True:
        # Genera un numero casuale di 6 cifre
        matricola = str(random.randint(100000, 999999))
        if matricola not in existing_matricole:
            return matricola

def normalize_date(date_str):
    """Converte una data dal formato gg/mm/aaaa al formato yyyy-mm-dd per MySQL"""
    if pd.isna(date_str):
        return None
    try:
        # Converti la stringa in oggetto datetime
        date_obj = datetime.strptime(str(date_str), '%d/%m/%Y')
        # Formatta per MySQL
        return date_obj.strftime('%Y-%m-%d')
    except:
        print(f"Errore nella conversione della data: {date_str}")
        return None

def sync_detenuti(csv_path):
    # Connessione al database
    db = mysql.connector.connect(
        host="localhost",
        user="root",
        password="MERLIN",
        database="login_system"
    )
    cursor = db.cursor(dictionary=True)

    # Prova diverse codifiche comuni per l'italiano
    encodings = ['cp1252', 'latin1', 'iso-8859-1', 'utf-8']
    df = None
    
    for encoding in encodings:
        try:
            df = pd.read_csv(csv_path, sep=';', encoding=encoding)
            print(f"File letto correttamente con encoding: {encoding}")
            print("Colonne presenti nel CSV:", df.columns.tolist())
            break
        except UnicodeDecodeError:
            continue
    
    if df is None:
        raise Exception("Non è stato possibile leggere il file con nessuna delle codifiche provate")
    
    # Ottieni detenuti esistenti
    cursor.execute("SELECT matricola FROM detenuti")
    existing_matricole = {row['matricola'] for row in cursor.fetchall()}
    
    # Prepara query di inserimento
    insert_query = """
    INSERT INTO detenuti (
        matricola_int, cognome, nome, data_ingresso_istituto,
        data_uscita, data_nascita, dove, matricola, reparto
    ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE
        matricola_int = VALUES(matricola_int),
        cognome = VALUES(cognome),
        nome = VALUES(nome),
        data_ingresso_istituto = VALUES(data_ingresso_istituto),
        data_uscita = VALUES(data_uscita),
        data_nascita = VALUES(data_nascita),
        dove = VALUES(dove),
        reparto = VALUES(reparto)
    """
    
    # Processa ogni riga del CSV
    for idx, row in df.iterrows():
        try:
            # Determina la matricola
            if 'matricola' in row and pd.notna(row['matricola']):
                matricola = str(row['matricola']).strip()
            elif 'matricola_int' in row and pd.notna(row['matricola_int']):
                matricola = str(row['matricola_int']).strip()
            else:
                # Genera una nuova matricola univoca
                matricola = generate_unique_matricola(existing_matricole)
                existing_matricole.add(matricola)  # Aggiungi alla lista delle matricole esistenti
                print(f"Generata nuova matricola: {matricola}")
            
            # Normalizza le date dal formato italiano a MySQL
            data_ingresso = normalize_date(row['data_ingresso_istituto']) if 'data_ingresso_istituto' in row else None
            data_uscita = normalize_date(row['data_uscita']) if 'data_uscita' in row else None
            data_nascita = normalize_date(row['data_nascita']) if 'data_nascita' in row else None
            
            # Prepara valori per inserimento
            values = (
                matricola,  # Usa la stessa matricola per matricola_int
                row['cognome'].strip() if 'cognome' in row else None,
                row['nome'].strip() if 'nome' in row else None,
                data_ingresso,
                data_uscita,
                data_nascita,
                row['dove'].strip() if ('dove' in row and pd.notna(row['dove'])) else None,
                matricola,
                'Sconosciuto'  # Default reparto
            )
            
            # Esegui inserimento
            cursor.execute(insert_query, values)
            print(f"Processata riga {idx + 1} con matricola: {matricola}")
            
        except Exception as err:
            print(f"Errore nel processare la riga {idx + 1}: {err}")
            continue
    
    # Commit delle modifiche e chiusura connessione
    db.commit()
    cursor.close()
    db.close()

if __name__ == "__main__":
    csv_path = "cleaned_file.csv"  # Sostituisci con il percorso del tuo CSV
    sync_detenuti(csv_path)