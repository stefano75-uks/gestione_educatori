<!-- Main Content -->
<div class="content">
    <div class="container-fluid">
        <h2>Tabella Scorrevole</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Colonna 1</th>
                        <th>Colonna 2</th>
                        <th>Colonna 3</th>
                        <th>Colonna 4</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Connessione al database
                    $conn = openDbConnection();
                    $result = mysqli_query($conn, "SELECT * FROM movimento");
                    while ($row = mysqli_fetch_assoc($result)):
                    ?>
                        <tr>
                            <td><?php echo $row['colonna1']; ?></td>
                            <td><?php echo $row['colonna2']; ?></td>
                            <td><?php echo $row['colonna3']; ?></td>
                            <td><?php echo $row['colonna4']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php mysqli_close($conn); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
