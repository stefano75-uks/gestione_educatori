<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importa Dati</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Importa Dati dalla Rubrica</h2>
    <form action="import.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="file">Seleziona File Excel:</label>
            <input type="file" name="file" class="form-control" id="file" required>
        </div>
        <button type="submit" name="upload" class="btn btn-primary">Carica File</button>
    </form>
</div>
</body>
</html>
