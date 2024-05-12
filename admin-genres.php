<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true || $_SESSION['username'] !== 'libretteAdmin') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$db = new Dbh();
$conn = $db->connect();
$genres = [];

if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT genreID, genre FROM Genres");
        $stmt->execute();
        $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Librette</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/bs-theme-overrides.css">
    <link rel="stylesheet" href="assets/css/Features-Cards-icons.css">
    <link rel="icon" type="image/x-icon" href="assets/img/logo2-removebg-preview.png">
</head>

<body>
    <?php include 'navbar-admin.html'; ?>

    <section style="padding: 9px;margin: 0px;margin-top: 18px;margin-right: 60px;margin-left: 59px;">
        <div class="container">
            <div class="row">
                <div class="col-xl-12 text-center mb-3">
                    <h1 style="font-size: 29px;">Manage Genres</h1>
                    <div class="d-flex justify-content-center mt-3">
                        <a href="main-admin.php" class="btn btn-primary btn-sm me-2" role="button">Manage Users</a>
                        <a href="admin-books.php" class="btn btn-primary btn-sm me-2" role="button">Manage Books</a>
                        <a href="admin-authors.php" class="btn btn-primary btn-sm me-2" role="button">Manage Authors</a>
                        <a href="admin-publishers.php" class="btn btn-primary btn-sm" role="button">Manage Publishers</a>
                    </div>
                    <a href="#" class="btn btn-info btn-sm float-end mb-3" role="button" data-bs-toggle="modal" data-bs-target="#addGenreModal">
                        <i class="fas fa-plus"></i><strong>&nbsp;Add Genre</strong>
                    </a>
                </div>

                <!-- Add Genre Modal -->
                <div class="modal fade" id="addGenreModal" tabindex="-1" aria-labelledby="addGenreModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addGenreModalLabel">Add Genre</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="addGenreForm" method="POST" action="admin-bookControls.php">
                                    <input type="hidden" name="action" value="addGenre">
                                    <div class="mb-3">
                                        <label for="genreName" class="form-label">Genre Name</label>
                                        <input type="text" class="form-control border border-primary" id="genreName" name="genreName" required>
                                    </div>

                                    <?php
                                        if (isset($_SESSION['error']) && $_SESSION['error'] === 'genreExists') {
                                            echo '<div class="text-danger">Genre already exists.</div>';
                                            unset($_SESSION['error']);
                                        }
                                    ?>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary" name="addGenreBtn">Add Genre</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Display table -->
                <div class="col">
                    <div class="table-responsive" id="myTable">
                        <table class="table table-striped table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th >Genre</th>
                                    <th class="text-center" style="width: 50px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($genres as $genre): ?>
                                    <tr>
                                        <td><?php echo $genre['genreID']; ?></td>
                                        <td><?php echo $genre['genre']; ?></td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group" aria-label="Action Buttons">
                                                <a href="admin-bookControls.php?action=deleteGenre&genreID=<?php echo $genre['genreID']; ?>" class="del-row">
                                                    <i class="fas fa-trash" style="font-size: 20px;"></i>
                                                </a>
                                                <a href="#" class="del-row" data-bs-toggle="modal" data-bs-target="#editGenreModal<?php echo $genre['genreID']; ?>">
                                                    <i class="fas fa-edit" style="font-size: 20px;margin-left: 9px;"></i>
                                                </a>                          
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Edit Genre Modal -->
                                    <div class="modal fade" id="editGenreModal<?php echo $genre['genreID']; ?>" tabindex="-1" aria-labelledby="editGenreModalLabel<?php echo $genre['genreID']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editGenreModalLabel<?php echo $genre['genreID']; ?>">Edit Genre</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form id="editGenreForm<?php echo $genre['genreID']; ?>" method="POST" action="admin-bookControls.php">
                                                        <input type="hidden" name="action" value="editGenre">
                                                        <input type="hidden" name="genreID" value="<?php echo $genre['genreID']; ?>">
                                                        <div class="mb-3">
                                                            <label for="editedGenreName<?php echo $genre['genreID']; ?>" class="form-label">Genre</label>
                                                            <input type="text" class="form-control border border-primary" id="editedGenreName<?php echo $genre['genreID']; ?>" name="editedGenreName" value="<?php echo htmlspecialchars($genre['genre']); ?>" required>
                                                        </div>

                                                        <?php
                                                            if (isset($_GET['error']) && $_GET['error'] === 'genreExists') {
                                                                echo '<div class="text-danger">Genre already exists.</div>';
                                                                unset($_SESSION['error']);
                                                            }
                                                        ?>

                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary" name="editGenreBtn">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                <?php endforeach; ?>
                            </tbody>                        
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <?php include 'footer.html'; ?>

    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/bs-init.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>

</html>
