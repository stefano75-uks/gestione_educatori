// Funzione per mostrare gli alert
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.modal.show .modal-body') ||
        document.querySelector('.container-fluid');
    if (container) {
        container.insertAdjacentElement('afterbegin', alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }
}

// Funzione per formattare le date
function formatDate(dateString, includeTime = false) {
    if (!dateString) return '-';
    const options = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        ...(includeTime && { hour: '2-digit', minute: '2-digit' })
    };
    return new Date(dateString).toLocaleDateString('it-IT', options);
}

// Funzione per caricare i dettagli del reparto
function loadDettaglioReparto(reparto) {
    console.log('Caricamento dettagli per reparto:', reparto);
    const tableBody = document.querySelector('#dettaglioRepartoTable tbody');

    if (!tableBody) {
        console.error('Table body non trovato!');
        return;
    }

    fetch(`get_dettaglio_reparto.php?reparto=${encodeURIComponent(reparto)}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Errore nel caricamento dati');
            }

            tableBody.innerHTML = data.detenuti.map(detenuto =>
                `<tr>
                    <td>${detenuto.matricola || ''}</td>
                    <td>${detenuto.cognome || ''}</td>
                    <td>${detenuto.nome || ''}</td>
                    <td>
                        <span class="ubicazione-text">${detenuto.reparto || ''}</span>
                        <input type="text" class="form-control form-control-sm ubicazione-input" 
                               style="display:none" value="${detenuto.reparto || ''}">
                    </td>
                    <td>
                        ${userPermissions.delete ?
                    `<div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-edit" 
                                        onclick="toggleEditUbicazione(this, ${detenuto.id})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-outline-success btn-save" 
                                        style="display:none" onclick="saveUbicazione(this, ${detenuto.id})">
                                    <i class="bi bi-check"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-cancel" 
                                        style="display:none" onclick="cancelEditUbicazione(this)">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>`
                    : ''}
                    </td>
                </tr>`
            ).join('');
        })
        .catch(error => {
            console.error('Error:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">${error.message}</td>
                </tr>`;
        });
}

// Funzione per caricare i documenti
function loadDocumenti(detenutoId) {
    const tableBody = document.querySelector(`#documentiTable${detenutoId} tbody`);
    if (!tableBody) {
        console.error(`Table body non trovato per ID: ${detenutoId}`);
        return;
    }

    fetch(`get_documenti.php?user_id=${detenutoId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Errore nel caricamento dei documenti');
            }

            const documenti = data.data || [];
            if (documenti.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center">Nessun documento trovato</td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = documenti.map(doc => `
                <tr>
                    <td>${formatDate(doc.data_caricamento, true)}</td>
                    <td>${doc.data_evento ? formatDate(doc.data_evento) : '-'}</td>
                    <td>${doc.tipo_documento || '-'}</td>
                    <td>${doc.operatore}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-primary" onclick="previewDocument(${detenutoId}, ${doc.id})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="download_documento.php?id=${doc.id}" 
                               class="btn btn-success">
                                <i class="bi bi-download"></i>
                            </a>
                            ${userPermissions.delete ? `
                                <button class="btn btn-danger" onclick="deleteDocumento(${doc.id}, ${detenutoId})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>`).join('');
        })
        .catch(error => {
            console.error('Error:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">${error.message}</td>
                </tr>`;
        });
}

// Funzione per l'anteprima del documento
function previewDocument(detenutoId, docId) {
    const previewContainer = document.querySelector(`#pdfPreview${detenutoId}`);
    if (!previewContainer) return;

    previewContainer.style.display = 'block';
    const timestamp = new Date().getTime();
    previewContainer.innerHTML = `
        <iframe src="view_file.php?id=${docId}&t=${timestamp}" 
                style="width:100%;height:500px;border:none;"></iframe>`;
}

// Funzioni per gestire l'editing dell'ubicazione
function toggleEditUbicazione(btn, id) {
    const row = btn.closest('tr');
    const textSpan = row.querySelector('.ubicazione-text');
    const input = row.querySelector('.ubicazione-input');
    const editBtn = row.querySelector('.btn-edit');
    const saveBtn = row.querySelector('.btn-save');
    const cancelBtn = row.querySelector('.btn-cancel');

    textSpan.style.display = 'none';
    input.style.display = 'block';
    editBtn.style.display = 'none';
    saveBtn.style.display = 'inline-block';
    cancelBtn.style.display = 'inline-block';
    input.focus();
}

function saveUbicazione(btn, id) {
    const row = btn.closest('tr');
    const input = row.querySelector('.ubicazione-input');
    const newReparto = input.value;

    fetch('update_ubicazione.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            reparto: newReparto
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'Ubicazione aggiornata con successo', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                throw new Error(data.error || 'Errore durante l\'aggiornamento');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(error.message, 'danger');
        });
}

function cancelEditUbicazione(btn) {
    const row = btn.closest('tr');
    const textSpan = row.querySelector('.ubicazione-text');
    const input = row.querySelector('.ubicazione-input');
    const editBtn = row.querySelector('.btn-edit');
    const saveBtn = row.querySelector('.btn-save');
    const cancelBtn = row.querySelector('.btn-cancel');

    textSpan.style.display = 'block';
    input.style.display = 'none';
    editBtn.style.display = 'inline-block';
    saveBtn.style.display = 'none';
    cancelBtn.style.display = 'none';
}

// Funzione per eliminare un documento
function deleteDocumento(docId, detenutoId) {
    if (!docId) {
        showAlert('Parametro non valido per l\'eliminazione', 'danger');
        return;
    }

    if (!confirm('Sei sicuro di voler eliminare questo documento?')) return;

    const deleteButton = document.querySelector(`button[onclick*="deleteDocumento(${docId}"]`);
    if (deleteButton) {
        deleteButton.disabled = true;
        deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }

    fetch('delete_documento.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: docId })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Errore nella risposta del server');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'Documento eliminato con successo', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                throw new Error(data.error || 'Errore durante l\'eliminazione');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(error.message || 'Errore durante l\'eliminazione del documento', 'danger');
            if (deleteButton) {
                deleteButton.disabled = false;
                deleteButton.innerHTML = '<i class="bi bi-trash"></i>';
            }
        });
}

// Event Listeners quando il DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    console.log('Document ready');

    // Gestione form di upload
    document.querySelectorAll('.upload-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Form sottomesso');

            const detenutoId = this.getAttribute('data-detenuto-id');
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Caricamento...';

            fetch('upload-documenti.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Documento caricato con successo', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        throw new Error(data.error || 'Errore durante il caricamento');
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    showAlert(error.message || 'danger');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });
    });

    // Gestione pulsanti documenti
    document.querySelectorAll('.documenti-btn').forEach(button => {
        button.addEventListener('click', event => {
            const detenutoId = button.getAttribute('data-detenuto-id');
            console.log(`Caricamento documenti per ID: ${detenutoId}`);
            loadDocumenti(detenutoId);
        });
    });

    // Gestione del modal dettaglio reparto
    const dettaglioModal = document.getElementById('dettaglioRepartoModal');
    if (dettaglioModal) {
        dettaglioModal.addEventListener('show.bs.modal', function (event) {
            console.log('Modal reparto in apertura');
            const button = event.relatedTarget;
            const reparto = button.getAttribute('data-reparto');
            console.log('Reparto selezionato:', reparto);

            const modalTitle = dettaglioModal.querySelector('#repartoName');
            modalTitle.textContent = reparto;

            loadDettaglioReparto(reparto);
        });
    }

    // Gestione cambio password
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                old_password: formData.get('old_password'),
                new_password: formData.get('new_password'),
                confirm_password: formData.get('confirm_password')
            };

            // Verifica che le password coincidano
            if (data.new_password !== data.confirm_password) {
                showAlert('Le nuove password non coincidono', 'danger');
                return;
            }

            // Verifica lunghezza minima
            if (data.new_password.length < 8) {
                showAlert('La password deve essere di almeno 8 caratteri', 'danger');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Aggiornamento...';

            console.log('Dati che verranno inviati:', data);  // Debug

            // Cambio la chiamata fetch a
            fetch('./change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
                .then(response => {
                    console.log('Status della risposta:', response.status);  // Debug
                    return response.text();  // Usiamo text() invece di json() per vedere la risposta grezza
                })
                .then(text => {
                    console.log('Risposta dal server:', text);  // Debug
                    return JSON.parse(text);  // Poi proviamo a parsare il JSON
                })
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        this.reset();
                        const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
                        modal.hide();
                    } else {
                        throw new Error(data.error || 'Errore durante il cambio password');
                    }
                })
                .catch(error => {
                    showAlert(error.message, 'danger');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });
    }
});