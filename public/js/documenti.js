document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM Loaded');

    // Gestione modale tramite evento bootstrap
    document.querySelectorAll('.modal').forEach(function (modalElement) {
        modalElement.addEventListener('show.bs.modal', function (event) {
            console.log('Modal show event triggered');
            const detenutoId = this.getAttribute('data-detenuto-id');
            console.log('Detenuto ID from modal:', detenutoId);
            if (detenutoId) {
                loadDocumenti(detenutoId);
            }
        });
    });

    // Gestione pulsanti documenti
    document.querySelectorAll('.documenti-btn').forEach(function (button) {
        button.addEventListener('click', function (event) {
            console.log('Button clicked');
            const detenutoId = this.getAttribute('data-detenuto-id');
            console.log('Detenuto ID from button:', detenutoId);
            // Il caricamento verrà gestito dall'evento show.bs.modal
        });
    });
});

function loadDocumenti(detenutoId) {
    console.log('Loading documenti for:', detenutoId);
    const tableBody = document.querySelector(`#documentiTable${detenutoId} tbody`);

    if (!tableBody) {
        console.error(`Table body not found for detenuto ${detenutoId}`);
        return;
    }

    // Mostra loading state
    tableBody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center">
                <div class="d-flex justify-content-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                    </div>
                </div>
            </td>
        </tr>
    `;

    console.log('Fetching data from:', `get_documenti.php?user_id=${detenutoId}`);

    fetch(`get_documenti.php?user_id=${detenutoId}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response');
            }
        })
        .then(data => {
            console.log('Parsed data:', data);

            if (!data.success) {
                throw new Error(data.error || 'Errore nel caricamento dei documenti');
            }

            const documenti = data.data || [];
            if (documenti.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center">
                            <i class="bi bi-info-circle me-2"></i>
                            Nessun documento trovato
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = documenti.map(doc => `
                <tr>
                    <td>${formatDate(doc.data_caricamento, true)}</td>
                    <td>${doc.data_evento ? formatDate(doc.data_evento) : '-'}</td>
                    <td>${doc.tipo_documento || '-'}</td>
                    <td>${doc.operatore}</td>
                    <td>
                        <div class="btn-group">
                            <a href="view_file.php?id=${doc.id}" 
                            class="btn btn-info btn-sm" 
                    target="_blank" 
                    title="Visualizza">
                        <i class="bi bi-eye"></i>
                    </a>
                            <a href="download_documento.php?id=${doc.id}" 
                            class="btn btn-primary btn-sm">
                                <i class="bi bi-download"></i>
                            </a>
                            ${userPermissions.delete ? `
                                <button type="button" 
                                        class="btn btn-danger btn-sm"
                                        onclick="deleteDocumento(${doc.id}, ${detenutoId})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');

            // Aggiorna il conteggio
            updateDocumentCount(detenutoId, documenti.length);
        })
        .catch(error => {
            console.error('Error:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        ${error.message}
                    </td>
                </tr>
            `;
        });
}

// ... resto del codice per formatDate, updateDocumentCount, etc. ...

function updateDocumentCount(detenutoId, count) {
    console.log('Updating count for detenuto:', detenutoId, 'Count:', count);
    const badge = document.querySelector(`[data-bs-target="#documentiModal${detenutoId}"] .badge`);
    if (badge) {
        badge.textContent = count;
    }
}

function formatDate(dateString, includeTime = false) {
    if (!dateString) {
        return '-';
    }
    const date = new Date(dateString);
    const options = {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        ...(includeTime && {
            hour: '2-digit',
            minute: '2-digit'
        })
    };
    return date.toLocaleDateString('it-IT', options);
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertAdjacentElement('afterbegin', alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }
}
document.addEventListener('DOMContentLoaded', function () {
    // Gestione filtri
    document.querySelectorAll('.filter-date, .filter-tipo').forEach((filter) => {
        filter.addEventListener('change', function () {
            const modal = this.closest('.modal');
            const detenutoId = modal.getAttribute('data-detenuto-id');

            // Raccogli tutti i valori dei filtri
            const filters = {
                data_da: modal.querySelector('.filter-date[data-type="da"]').value,
                data_a: modal.querySelector('.filter-date[data-type="a"]').value,
                tipo: modal.querySelector('.filter-tipo').value
            };

            loadDocumenti(detenutoId, filters);
        });
    });
});

//load document 1
function loadDocumenti(detenutoId, filters = {}) {
    console.log('Loading documenti for:', detenutoId, 'with filters:', filters);
    const tableBody = document.querySelector(`#documentiTable${detenutoId} tbody`);

    // Costruisci i parametri della query
    const params = new URLSearchParams({ user_id: detenutoId });

    // Aggiungi i filtri se presenti
    if (filters.data_da) params.append('data_da', filters.data_da);
    if (filters.data_a) params.append('data_a', filters.data_a);
    if (filters.tipo) params.append('tipo', filters.tipo);

    fetch('get_documenti.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Errore nel caricamento dei documenti');
            }

            const documenti = data.data;
            if (documenti.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Nessun documento trovato</td></tr>';
                return;
            }

            tableBody.innerHTML = documenti.map(doc => `
                <tr>
                    <td>${formatDate(doc.data_caricamento, true)}</td>
                    <td>${doc.data_evento ? formatDate(doc.data_evento) : '-'}</td>
                    <td>${doc.tipo_documento || '-'}</td>
                    <td>${doc.operatore}</td>
                    <td>
                        <div class="btn-group">
                            <a href="download_documento.php?id=${doc.id}" 
                               class="btn btn-primary btn-sm">
                                <i class="bi bi-download"></i>
                            </a>
                            ${userPermissions.delete ? `
                                <button type="button" class="btn btn-danger btn-sm"
                                        onclick="deleteDocumento(${doc.id}, ${detenutoId})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');

            updateDocumentCount(detenutoId, documenti.length);
        })
        .catch(error => {
            console.error('Error:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        ${error.message}
                    </td>
                </tr>
            `;
        });
}