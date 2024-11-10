<div class="modal fade" id="uploadModal<?php echo $row['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Carica Documento - <?php echo htmlspecialchars($row['cognome'] . ' ' . $row['nome']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="upload-form" 
                  data-detenuto-id="<?php echo $row['id']; ?>">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Documento (PDF)</label>
                        <input type="file" class="form-control" name="documento" 
                               accept=".pdf" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo Documento</label>
                        <input type="text" class="form-control" name="tipo_documento" required>
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