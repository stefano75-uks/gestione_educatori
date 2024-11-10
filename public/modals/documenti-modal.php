<div class="modal fade modal-lg" id="documentiModal<?php echo $row['id']; ?>"
    data-detenuto-id="<?php echo $row['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Documenti - <?php echo htmlspecialchars($row['cognome'] . ' ' . $row['nome']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Filtri -->
                <div class="mb-3">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Data da:</label>
                            <input type="date" class="form-control form-control-sm filter-date"
                                data-type="da">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data a:</label>
                            <input type="date" class="form-control form-control-sm filter-date"
                                data-type="a">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo:</label>
                            <select class="form-select form-select-sm filter-tipo">
                                <option value="">Tutti</option>
                                <?php
                                $tipi_query = "SELECT DISTINCT tipo_documento FROM documenti WHERE user_id = ?";
                                $stmt_tipi = $conn->prepare($tipi_query);
                                $stmt_tipi->bind_param("i", $row['id']);
                                $stmt_tipi->execute();
                                $result_tipi = $stmt_tipi->get_result();
                                while ($tipo = $result_tipi->fetch_assoc()) {
                                    if ($tipo['tipo_documento']) {
                                        echo '<option value="' . htmlspecialchars($tipo['tipo_documento']) . '">' .
                                            htmlspecialchars($tipo['tipo_documento']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tabella documenti -->
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="documentiTable<?php echo $row['id']; ?>">
                        <thead>
                            <tr>
                                <th>Data Caricamento</th>
                                <th>Data Evento</th>
                                <th>Tipo</th>
                                <th>Operatore</th>
                                <th width="150">Azioni</th>  <!-- Aggiunto width per dare più spazio ai bottoni -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Popolato via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>