<div class="modal fade" id="uploadModal<?php echo $row['id']; ?>" 
     data-detenuto-id="<?php echo $row['id']; ?>"
     tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Carica Documento - <?php echo htmlspecialchars($row['cognome'] . ' ' . $row['nome']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" 
                  enctype="multipart/form-data"
                  class="upload-form"
                  data-detenuto-id="<?php echo $row['id']; ?>">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Documento (PDF)</label>
                        <div class="drop-zone">
                            <div class="drop-zone__prompt">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                <p>Trascina qui il file PDF o clicca per selezionarlo</p>
                            </div>
                            <input type="file" name="documento" class="drop-zone__input" accept=".pdf" required>
                        </div>
                        <div id="fileInfo" class="mt-2 small text-muted"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo Documento</label>
                        <select class="form-select" name="tipo_documento" required>
                            <option value="">Seleziona tipo documento...</option>
                            <option value="varie">Varie</option>
                            <option value="rapporti">Rapporti</option>
                            <option value="disciplinari">Disciplinari</option>
                            <option value="art_21">Art. 21</option>
                            <option value="art_20">Art. 20</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Data Evento</label>
                        <input type="date" class="form-control" name="data_evento" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Carica</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.drop-zone {
    max-width: 100%;
    height: 200px;
    padding: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-weight: 500;
    font-size: 1.2rem;
    cursor: pointer;
    color: #cccccc;
    border: 4px dashed #009578;
    border-radius: 10px;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.drop-zone--over {
    border-style: solid;
    background-color: rgba(0, 149, 120, 0.1);
}

.drop-zone__input {
    display: none;
}

.drop-zone__prompt {
    text-align: center;
}

.drop-zone__prompt i {
    color: #009578;
}

.drop-zone__prompt p {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

.drop-zone__thumb {
    width: 100%;
    height: 100%;
    border-radius: 10px;
    overflow: hidden;
    background-color: #cccccc;
    background-size: cover;
    position: relative;
}

.drop-zone__thumb::after {
    content: attr(data-label);
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 5px 0;
    color: #ffffff;
    background: rgba(0, 0, 0, 0.75);
    font-size: 14px;
    text-align: center;
}
</style>

<script>
document.querySelectorAll(".drop-zone__input").forEach(inputElement => {
    const dropZoneElement = inputElement.closest(".drop-zone");

    dropZoneElement.addEventListener("click", e => {
        inputElement.click();
    });

    inputElement.addEventListener("change", e => {
        if (inputElement.files.length) {
            updateThumbnail(dropZoneElement, inputElement.files[0]);
        }
    });

    dropZoneElement.addEventListener("dragover", e => {
        e.preventDefault();
        dropZoneElement.classList.add("drop-zone--over");
    });

    ["dragleave", "dragend"].forEach(type => {
        dropZoneElement.addEventListener(type, e => {
            dropZoneElement.classList.remove("drop-zone--over");
        });
    });

    dropZoneElement.addEventListener("drop", e => {
        e.preventDefault();

        if (e.dataTransfer.files.length) {
            const file = e.dataTransfer.files[0];
            if (file.type === "application/pdf") {
                inputElement.files = e.dataTransfer.files;
                updateThumbnail(dropZoneElement, file);
            } else {
                alert("Per favore, carica solo file PDF");
            }
        }

        dropZoneElement.classList.remove("drop-zone--over");
    });
});

function updateThumbnail(dropZoneElement, file) {
    let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");

    // Rimuovi il thumbnail se esisteva già
    if (dropZoneElement.querySelector(".drop-zone__prompt")) {
        dropZoneElement.querySelector(".drop-zone__prompt").remove();
    }

    // Prima volta - crea il thumbnail
    if (!thumbnailElement) {
        thumbnailElement = document.createElement("div");
        thumbnailElement.classList.add("drop-zone__thumb");
        dropZoneElement.appendChild(thumbnailElement);
    }

    // Mostra il nome del file
    thumbnailElement.dataset.label = file.name;

    // Aggiorna info file
    const fileInfo = dropZoneElement.closest('.modal-body').querySelector('#fileInfo');
    if (fileInfo) {
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        fileInfo.textContent = `Nome file: ${file.name} | Dimensione: ${fileSize} MB`;
    }

    // Mostra icona PDF
    thumbnailElement.style.backgroundImage = 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 384 512\'%3E%3Cpath fill=\'%23e2e5e7\' d=\'M369.9 97.9L286 14C277 5 264.8-.1 252.1-.1H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V131.9c0-12.7-5.1-25-14.1-34zM332.1 128H256V51.9l76.1 76.1zM48 464V48h160v104c0 13.3 10.7 24 24 24h104v288H48z\'/%3E%3C/svg%3E")';
    thumbnailElement.style.backgroundSize = "64px 64px";
    thumbnailElement.style.backgroundPosition = "center";
    thumbnailElement.style.backgroundRepeat = "no-repeat";
    thumbnailElement.style.backgroundColor = "#f8f9fa";
}

// Imposta la data di oggi come valore predefinito
document.querySelectorAll('input[name="data_evento"]').forEach(input => {
    const today = new Date().toISOString().split('T')[0];
    input.value = today;
});
</script>