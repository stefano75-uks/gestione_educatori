document.addEventListener('DOMContentLoaded', () => {
    console.log('Document ready');

    // Gestione form di upload
    document.querySelectorAll('.upload-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form sottomesso');

            const detenutoId = this.getAttribute('data-detenuto-id');
            const formData = new FormData(this);

            // Disabilita il pulsante di submit durante l'upload
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
                    // Mostra messaggio di successo
                    showAlert('Documento caricato con successo', 'success');
                    
                    // Chiudi il modal
                    const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                    modal.hide();
                    
                    // Ricarica i documenti
                    loadDocumenti(detenutoId);
                    
                    // Reset form
                    this.reset();
                } else {
                    throw new Error(data.error || 'Errore durante il caricamento');
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                showAlert(error.message, 'danger');
            })
            .finally(() => {
                // Riabilita il pulsante
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    });

    // Caricamento documenti (il tuo codice esistente)
    document.querySelectorAll('.documenti-btn').forEach(button => {
        button.addEventListener('click', event => {
            const detenutoId = button.getAttribute('data-detenuto-id');
            console.log(`Caricamento documenti per ID: ${detenutoId}`);
            loadDocumenti(detenutoId);
        });
    });

    // Funzione per mostrare alert
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

                // Popola la tabella con i documenti
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
                console.error(error);
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-danger">${error.message}</td>
                    </tr>`;
            });
    }

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
});

// Funzione per l'anteprima del documento
function previewDocument(detenutoId, docId) {
    const previewContainer = document.querySelector(`#pdfPreview${detenutoId}`);
    if (!previewContainer) return;

    previewContainer.style.display = 'block';
    const timestamp = new Date().getTime();
    previewContainer.innerHTML = `<iframe src="view_file.php?id=${docId}&t=${timestamp}" style="width:100%;height:500px;border:none;"></iframe>`;
}

// Funzione per eliminare un documento
function deleteDocumento(docId, detenutoId) {
    if (!confirm('Sei sicuro di voler eliminare questo documento?')) return;

    fetch('delete_documento.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${docId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Documento eliminato con successo', 'success');
            loadDocumenti(detenutoId);
        } else {
            throw new Error(data.error || 'Errore durante l\'eliminazione');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message, 'danger');
    });
}