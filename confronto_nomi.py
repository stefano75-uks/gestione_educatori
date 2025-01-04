import os
from pathlib import Path
import mysql.connector
from thefuzz import fuzz
import re
from itertools import combinations

def converti_formato_data(data):
    """
    Converte la data dal formato GG.MM.AAAA a YYYY-MM-DD
    """
    try:
        if data == "99.99.9999":
            return "9999-99-99"
        giorno, mese, anno = data.split('.')
        giorno = giorno.zfill(2)
        mese = mese.zfill(2)
        return f"{anno}-{mese}-{giorno}"
    except:
        return "9999-99-99"

def normalizza_nome_file(nome_file):
    """
    Prima fase: normalizza il formato del file
    - Sostituisce spazi con underscore
    - Converte data in formato YYYY-MM-DD
    """
    # Rimuove .pdf e pulisce il nome
    nome = nome_file.replace('.pdf', '').strip()
    
    # Rimuove caratteri speciali iniziali
    nome = re.sub(r'^[^A-Za-z]+', '', nome)
    
    # Sostituisce caratteri di ritorno a capo con spazio
    nome = nome.replace('\r', ' ').replace('\n', ' ')
    
    # Trova la data nel vecchio formato
    match_data = re.search(r'(\d{1,2}\.\d{1,2}\.\d{4})(.*)', nome)
    if match_data:
        nome_base = nome[:match_data.start()].strip()
        data_vecchia = match_data.group(1)
        suffisso = match_data.group(2).strip()
        
        # Converte la data
        data_nuova = converti_formato_data(data_vecchia)
        
        # Sostituisce spazi con underscore e rimuove underscore multipli
        nome_base = re.sub(r'\s+', '_', nome_base)
        nome_base = re.sub(r'_+', '_', nome_base)
        nome_base = nome_base.strip('_')
        
        # Gestisce il suffisso numerico se presente
        if suffisso:
            if re.search(r'[-(]\d+[)]?', suffisso):
                suffisso = "-1"
            elif re.search(r'\b(bis|ter)\b', suffisso, re.I):
                suffisso = "-1"
            else:
                suffisso = ""
        
        return f"{nome_base}_{data_nuova}{suffisso}.pdf"
    else:
        # Se non c'è data, normalizza solo il nome e aggiungi data default
        nome = re.sub(r'\s+', '_', nome)
        return f"{nome}_9999-99-99.pdf"

def genera_combinazioni_nome(nome):
    """
    Genera tutte le possibili combinazioni cognome-nome
    Per esempio: "AVAM_VALICAAVRAM_VALRICA" genererà:
    [("AVAM", "VALICAAVRAM_VALRICA"),
     ("AVAM_VALICAAVRAM", "VALRICA"),
     ...]
    """
    parole = nome.split('_')
    combinazioni = []
    
    # Prova tutte le possibili divisioni
    for i in range(1, len(parole)):
        cognome = '_'.join(parole[:i])
        nome = '_'.join(parole[i:])
        combinazioni.append((cognome, nome))
    
    return combinazioni

def analizza_files(cartella_base="D:/relazioni"):
    try:
        # Connessione al database
        db_config = {
            'host': 'localhost',
            'user': 'root',
            'password': 'MERLIN',
            'database': 'login_system'
        }
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        cartella = Path(cartella_base)
        
        # Lista per il report
        files_processati = []
        possibili_corrispondenze = []
        errori = []
        
        # FASE 1: Normalizzazione
        print("=== FASE 1: Normalizzazione files ===")
        for file_path in cartella.glob("*.pdf"):
            try:
                vecchio_nome = file_path.name
                nuovo_nome = normalizza_nome_file(vecchio_nome)
                
                if vecchio_nome != nuovo_nome:
                    nuovo_path = file_path.parent / nuovo_nome
                    file_path.rename(nuovo_path)
                    files_processati.append((vecchio_nome, nuovo_nome))
                    print(f"Normalizzato: {vecchio_nome} -> {nuovo_nome}")
            except Exception as e:
                errori.append((vecchio_nome, f"Errore normalizzazione: {str(e)}"))
                print(f"Errore con {vecchio_nome}: {str(e)}")
        
        # FASE 2: Analisi e confronto con DB
        print("\n=== FASE 2: Analisi corrispondenze ===")
        for file_path in cartella.glob("*.pdf"):
            try:
                nome_file = file_path.name
                # Estrae nome senza data
                nome_base = nome_file.split('_20')[0]  # Divide sul primo _20 (anno)
                if '19' in nome_file:  # Gestisce anche date 19xx
                    nome_base = nome_file.split('_19')[0]
                
                # Genera tutte le possibili combinazioni
                combinazioni = genera_combinazioni_nome(nome_base)
                
                # Cerca nel database
                for cognome, nome in combinazioni:
                    cursor.execute("SELECT cognome, nome FROM detenuti WHERE cognome = %s AND nome = %s", 
                                 (cognome.replace('_', ' '), nome.replace('_', ' ')))
                    result = cursor.fetchone()
                    if result:
                        possibili_corrispondenze.append({
                            'file': nome_file,
                            'cognome_trovato': cognome,
                            'nome_trovato': nome,
                            'match': 'esatto'
                        })
                        break
                    else:
                        # Cerca corrispondenze fuzzy
                        cursor.execute("SELECT cognome, nome FROM detenuti")
                        for db_cognome, db_nome in cursor.fetchall():
                            ratio_cognome = fuzz.ratio(cognome.replace('_', ' '), db_cognome)
                            ratio_nome = fuzz.ratio(nome.replace('_', ' '), db_nome)
                            if ratio_cognome > 80 and ratio_nome > 80:
                                possibili_corrispondenze.append({
                                    'file': nome_file,
                                    'cognome_trovato': cognome,
                                    'nome_trovato': nome,
                                    'cognome_db': db_cognome,
                                    'nome_db': db_nome,
                                    'match': 'simile',
                                    'ratio_cognome': ratio_cognome,
                                    'ratio_nome': ratio_nome
                                })
                
            except Exception as e:
                errori.append((nome_file, f"Errore analisi: {str(e)}"))
                print(f"Errore analizzando {nome_file}: {str(e)}")
        
        # Genera il report
        report_path = cartella / "report_analisi.txt"
        with open(report_path, 'w', encoding='utf-8') as f:
            f.write("=== REPORT ANALISI FILES ===\n\n")
            
            f.write("1. FILES NORMALIZZATI:\n")
            for vecchio, nuovo in sorted(files_processati):
                f.write(f"Da:  {vecchio}\n")
                f.write(f"A:   {nuovo}\n\n")
            f.write(f"Totale files normalizzati: {len(files_processati)}\n\n")
            
            f.write("2. CORRISPONDENZE TROVATE:\n")
            for corr in possibili_corrispondenze:
                f.write(f"File: {corr['file']}\n")
                f.write(f"Suddivisione trovata: Cognome='{corr['cognome_trovato']}' Nome='{corr['nome_trovato']}'\n")
                if corr['match'] == 'esatto':
                    f.write("Match esatto nel database\n")
                else:
                    f.write(f"Possibile match nel DB: {corr['cognome_db']} {corr['nome_db']}\n")
                    f.write(f"Somiglianza: Cognome={corr['ratio_cognome']}% Nome={corr['ratio_nome']}%\n")
                f.write("\n")
            f.write(f"Totale corrispondenze: {len(possibili_corrispondenze)}\n\n")
            
            if errori:
                f.write("3. ERRORI:\n")
                for file, errore in errori:
                    f.write(f"File: {file}\n")
                    f.write(f"Errore: {errore}\n\n")
                f.write(f"Totale errori: {len(errori)}\n")
        
        print(f"\nReport generato in: {report_path}")
        
    except mysql.connector.Error as err:
        print(f"Errore database: {err}")
    except Exception as e:
        print(f"Errore generico: {e}")
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

def main():
    print("=== INIZIO ELABORAZIONE ===")
    try:
        analizza_files()
        print("\nOperazione completata!")
    except Exception as e:
        print(f"\nErrore: {str(e)}")
    
    input("\nPremi INVIO per chiudere...")

if __name__ == "__main__":
    main()