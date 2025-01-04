import tabula
import pandas as pd
import mysql.connector
from datetime import datetime

def leggi_pdf(file_path):
    print("1. Inizio lettura PDF...")
    print("ATTENZIONE: L'elaborazione di 2550 pagine richiederà diversi minuti...")
    try:
        print("\nLettura del PDF in corso (potrebbe volerci molto tempo)...")
        tables = tabula.read_pdf(
            file_path,
            pages='all',
            lattice=True,
            encoding='latin1',
            guess=False,
            multiple_tables=True,
            java_options=['-Xmx4096m']  # Aumentiamo la memoria per Java
        )
        
        print("\nPDF letto con successo!")
        print(f"Trovate {len(tables)} tabelle")
        
        print("\nCombinazione delle tabelle...")
        df_completo = pd.concat(tables, ignore_index=True)
        print(f"Tabelle combinate: {len(df_completo)} righe totali")
        
        print("\nPulizia e filtro dei dati...")
        df_completo['MOTIVO_PULITO'] = df_completo['MOTIVO'].str.replace('\n', ' ').str.replace('\r', ' ')
        
        motivi_da_escludere = [
            'RICOVERO IN OSPEDALE',
            'USCITA PERMESSO PREMIO',
            'USCITA PER LICENZA PREMIO',
            'USCITA PER PERMESSO',
            'VISITA AMBULATORIALE'
        ]
        
        # Filtriamo e mostriamo statistiche
        df_filtrato = df_completo[~df_completo['MOTIVO_PULITO'].isin(motivi_da_escludere)]
        print(f"Record dopo filtro motivi: {len(df_filtrato)}")
        
        df_filtrato = df_filtrato.sort_values('DATA\rMOVIMENTO', ascending=False)
        df_filtrato = df_filtrato.drop_duplicates(subset=['MATRICOLA'], keep='first')
        print(f"Record finali dopo rimozione duplicati: {len(df_filtrato)}")
        
        df_filtrato = df_filtrato.drop('MOTIVO_PULITO', axis=1)
        
        return df_filtrato
        
    except Exception as e:
        print(f"Errore lettura PDF: {e}")
        print(f"Dettaglio errore: {str(e)}")
        return None

class DatabaseManager:
    def __init__(self):
        print("\n2. Connessione al database...")
        self.conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="MERLIN",
            database="detenuti_presenti"
        )
        self.cursor = self.conn.cursor()
        self.create_table()

    def create_table(self):
        print("3. Creazione/verifica tabella...")
        create_table_query = """
        CREATE TABLE IF NOT EXISTS detenuti_assenti (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prog INT,
            cognome VARCHAR(255),
            nome VARCHAR(255),
            matricola VARCHAR(255) UNIQUE,
            nato_a VARCHAR(255),
            data_nascita DATE,
            stato_giuridico VARCHAR(255),
            motivo VARCHAR(255),
            data_movimento DATE,
            data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        """
        self.cursor.execute(create_table_query)
        self.conn.commit()

    def insert_data(self, df):
        print("\n4. Inizio inserimento dati nel database...")
        insert_query = """
        INSERT INTO detenuti_assenti 
        (prog, cognome, nome, matricola, nato_a, data_nascita, 
         stato_giuridico, motivo, data_movimento)
        VALUES (%s, %s, %s, %s, %s, STR_TO_DATE(%s, '%Y-%m-%d'), 
                %s, %s, STR_TO_DATE(%s, '%Y-%m-%d'))
        ON DUPLICATE KEY UPDATE
        cognome = VALUES(cognome),
        nome = VALUES(nome),
        nato_a = VALUES(nato_a),
        data_nascita = VALUES(data_nascita),
        stato_giuridico = VALUES(stato_giuridico),
        motivo = VALUES(motivo),
        data_movimento = VALUES(data_movimento)
        """
        
        total = len(df)
        processed = 0
        errors = 0
        
        for _, row in df.iterrows():
            try:
                # Conversione date
                data_nascita = datetime.strptime(str(row['IL']), '%d/%m/%Y').strftime('%Y-%m-%d')
                data_movimento = datetime.strptime(str(row['DATA\rMOVIMENTO']), '%d/%m/%Y').strftime('%Y-%m-%d')

                values = (
                    row['PROG'], row['COGNOME'], row['NOME'],
                    row['MATRICOLA'], row['NATO A'], data_nascita,
                    row['STATO GIURIDICO'], row['MOTIVO'], data_movimento
                )

                self.cursor.execute(insert_query, values)
                processed += 1
                
                if processed % 100 == 0:
                    self.conn.commit()
                    print(f"Progresso: {processed}/{total} record ({(processed/total)*100:.1f}%)")

            except Exception as e:
                errors += 1
                print(f"Errore riga {processed+errors}: {str(e)}")

        self.conn.commit()
        print(f"\nInserimento completato:")
        print(f"Record processati con successo: {processed}")
        print(f"Errori riscontrati: {errors}")

    def close(self):
        self.cursor.close()
        self.conn.close()
        print("\nConnessione al database chiusa")

def main():
    print("INIZIO ELABORAZIONE")
    pdf_path = 'usciti_2005.pdf'
    
    df = leggi_pdf(pdf_path)
    if df is not None:
        db = DatabaseManager()
        try:
            db.insert_data(df)
        finally:
            db.close()
    
    print("\nFINE ELABORAZIONE")

if __name__ == "__main__":
    main()