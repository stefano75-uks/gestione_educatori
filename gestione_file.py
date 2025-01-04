import os
import shutil
from pathlib import Path

def sposta_file_disciplinari():
    # Percorso sorgente e destinazione
    sorgente = Path("D:/rapporti disciplinari")
    destinazione = Path("D:/relazioni")
    cartella_duplicati = destinazione / "duplicati"
    
    print(f"Cerco file in: {sorgente}")
    
    # Crea le cartelle necessarie
    destinazione.mkdir(exist_ok=True)
    cartella_duplicati.mkdir(exist_ok=True)
    
    files_trovati = 0
    files_spostati = 0
    
    # Cerca tutte le cartelle che iniziano con "ANNO"
    for cartella_anno in sorgente.glob("ANNO *"):
        if not cartella_anno.is_dir():
            continue
            
        print(f"\nProcesso cartella: {cartella_anno}")
        
        # Itera attraverso le cartelle alfabetiche
        for lettera in "ABCDEFGHIJKLMNOPQRSTUVWXYZ":
            cartella_lettera = cartella_anno / lettera
            if not cartella_lettera.exists():
                continue
                
            print(f"\nElaboro cartella {lettera}:")
            # Processa tutti i file PDF nella cartella della lettera
            for file_pdf in cartella_lettera.glob("*.pdf"):
                files_trovati += 1
                print(f"Trovato: {file_pdf.name}")
                
                percorso_destinazione = destinazione / file_pdf.name
                
                # Se il file esiste già, spostalo nella cartella duplicati
                if percorso_destinazione.exists():
                    percorso_destinazione = cartella_duplicati / file_pdf.name
                    print(f"Duplicato trovato, sposto in cartella duplicati: {file_pdf.name}")
                
                # Sposta il file nella nuova destinazione
                try:
                    shutil.move(str(file_pdf), str(percorso_destinazione))
                    print(f"Spostato: {file_pdf.name}")
                    files_spostati += 1
                except Exception as e:
                    print(f"Errore durante lo spostamento di {file_pdf.name}: {str(e)}")
    
    return files_trovati, files_spostati

def main():
    print("=== INIZIO SPOSTAMENTO FILE DISCIPLINARI ===")
    try:
        files_trovati, files_spostati = sposta_file_disciplinari()
        print("\n=== RIEPILOGO ===")
        print(f"File trovati: {files_trovati}")
        print(f"File spostati con successo: {files_spostati}")
        print("\nOperazione completata!")
    except Exception as e:
        print(f"\nSi è verificato un errore generale: {str(e)}")
    
    input("\nPremi INVIO per chiudere...")

if __name__ == "__main__":
    main()