import os
from pathlib import Path
import mysql.connector
from thefuzz import fuzz
import re

def pulisci_stringa(s):
    """Pulisce la stringa da caratteri speciali e normalizza spazi"""
    return ' '.join(s.replace('_', ' ').split())

def trova_detenuto(nome_completo, cursor):
    """
    Cerca nel database provando diverse combinazioni di parole come cognome
    """
    print(f"\nProcesso: {nome_completo}")
    parti = nome_completo.split('_')
    migliore_match = None
    max_ratio = 0

    # Prova diverse combinazioni di parole come cognome
    for i in range(1, min(4, len(parti))):
        possibile_cognome = ' '.join(parti[:i])
        print(f"Provo cognome: {possibile_cognome}")
        
        # Prima cerca match esatto
        query = """
        SELECT id, cognome, nome 
        FROM detenuti 
        WHERE UPPER(TRIM(cognome)) = %s
        """
        cursor.execute(query, (possibile_cognome.upper(),))
        results = cursor.fetchall()
        
        if results:
            print(f"Match esatto trovato per {possibile_cognome}")
            return results[0][0], i, 100, results[0][1], results[0][2]
        
        # Se non trova match esatto, cerca con LIKE
        query = """
        SELECT id, cognome, nome 
        FROM detenuti 
        WHERE UPPER(TRIM(cognome)) LIKE %s
        """
        cursor.execute(query, (f"%{possibile_cognome.upper()}%",))
        results = cursor.fetchall()
        
        for id_detenuto, db_cognome, db_nome in results:
            ratio = fuzz.ratio(possibile_cognome.upper(), db_cognome.upper())
            print(f"Confronto: '{possibile_cognome}' con '{db_cognome}' - Ratio: {ratio}")
            if ratio > max_ratio and ratio > 85:
                max_ratio = ratio
                migliore_match = (id_detenuto, i, ratio, db_cognome, db_nome)
                print(f"Nuovo miglior match: {db_cognome} {db_nome}")

    return migliore_match if max_ratio > 85 else None

def main():
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'MERLIN',
        'database': 'login_system',
        'charset': 'utf8mb4',
        'collation': 'utf8mb4_unicode_ci'
    }

    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(buffered=True)

        # Percorso cartella
        cartella = Path(r"D:\relazioni")  # usa percorso raw string
        importati = []
        non_trovati = []
        errori = []
        
        print("=== INIZIO IMPORTAZIONE DOCUMENTI ===")
        
        # Conta i file totali
        pdf_files = list(cartella.glob("*.pdf"))
        totale_files = len(pdf_files)
        print(f"Trovati {totale_files} file da processare")

        for count, file_path in enumerate(pdf_files, 1):
            try:
                nome_file = file_path.name
                print(f"\nProcessando file {count}/{totale_files}: {nome_file}")
                
                # Estrae la data dal nome file
                match_data = re.search(r'(\d{4}-\d{2}-\d{2})', nome_file)
                if match_data:
                    data_evento = match_data.group(1)
                    nome_base = nome_file.split(data_evento)[0].strip('_')
                    
                    # Cerca il detenuto nel database
                    risultato = trova_detenuto(nome_base, cursor)
                    
                    if risultato:
                        user_id, num_parole_cognome, ratio, db_cognome, db_nome = risultato
                        
                        # Inserisce il documento
                        sql = """
                        INSERT INTO documenti 
                        (data_caricamento, data_evento, file_path, operatore, 
                         tipo_documento, user_id) 
                        VALUES 
                        (NOW(), %s, %s, %s, %s, %s)
                        """
                        values = (
                            data_evento,
                            f"D:/upload/{nome_file}",  # Percorso corretto per il database
                            'admin',
                            'rapporto',
                            user_id
                        )
                        
                        cursor.execute(sql, values)
                        conn.commit()
                        
                        importati.append((nome_file, user_id, num_parole_cognome, ratio, db_cognome, db_nome))
                        print(f"Importato: {nome_file} -> {db_cognome} {db_nome}")
                    else:
                        non_trovati.append(nome_file)
                        print(f"Nessuna corrispondenza trovata per: {nome_file}")
                
            except Exception as e:
                errori.append((nome_file, str(e)))
                print(f"Errore processando {nome_file}: {e}")

        # Genera report
        report_path = cartella / "report_importazione.txt"
        with open(report_path, 'w', encoding='utf-8') as f:
            f.write("=== REPORT IMPORTAZIONE DOCUMENTI ===\n\n")
            
            f.write(f"File totali trovati: {totale_files}\n\n")
            
            f.write("DOCUMENTI IMPORTATI:\n")
            for nome, user_id, num_parole, ratio, db_cognome, db_nome in importati:
                f.write(f"File: {nome}\n")
                f.write(f"Trovato: {db_cognome} {db_nome} (ID: {user_id})\n")
                f.write(f"Parole usate come cognome: {num_parole}\n")
                f.write(f"Percentuale somiglianza: {ratio}%\n\n")
            f.write(f"Totale documenti importati: {len(importati)}\n\n")
            
            f.write("DOCUMENTI NON TROVATI:\n")
            for nome in non_trovati:
                f.write(f"File: {nome}\n")
            f.write(f"\nTotale non trovati: {len(non_trovati)}\n\n")
            
            if errori:
                f.write("ERRORI:\n")
                for nome, errore in errori:
                    f.write(f"File: {nome}\n")
                    f.write(f"Errore: {errore}\n\n")
                f.write(f"Totale errori: {len(errori)}")
        
        print(f"\nReport generato in: {report_path}")
        
        # Riepilogo
        print("\nRIEPILOGO:")
        print(f"- Documenti importati: {len(importati)}")
        print(f"- Non trovati: {len(non_trovati)}")
        print(f"- Errori: {len(errori)}")

    except mysql.connector.Error as err:
        print(f"Errore database: {err}")
    except Exception as e:
        print(f"Errore generico: {e}")
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    try:
        main()
        print("\nOperazione completata!")
    except Exception as e:
        print(f"\nErrore: {str(e)}")
    
    input("\nPremi INVIO per chiudere...")