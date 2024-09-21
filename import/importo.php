<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Importa dati da Excel/PDF a MySQL</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Importa dati da Excel/PDF a MySQL</h3>
                    </div>
                    <div class="card-body">
                        <form action="import.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="file">Seleziona il file (Excel o PDF)</label>
                                <input type="file" name="file" id="file" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="fields">Seleziona i campi da importare</label>
                                <div class="form-check">
                                    <input type="checkbox" name="fields[]" value="nome" class="form-check-input" id="fieldNome">
                                    <label class="form-check-label" for="fieldNome">Nome</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="fields[]" value="cognome" class="form-check-input" id="fieldCognome">
                                    <label class="form-check-label" for="fieldCognome">Cognome</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="fields[]" value="matricola" class="form-check-input" id="fieldMatricola">
                                    <label class="form-check-label" for="fieldMatricola">Matricola</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="fields[]" value="reparto" class="form-check-input" id="fieldReparto">
                                    <label class="form-check-label" for="fieldReparto">Reparto</label>
                                </div>
                            </div>
                            <button type="submit" name="submit" class="btn btn-primary">Importa</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
