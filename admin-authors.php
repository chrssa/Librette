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
$authors = [];

if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT authorID, first_name, surname FROM Authors");
        $stmt->execute();
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1 style="font-size: 29px;">Manage Authors</h1>
                    <div class="d-flex justify-content-center mt-3">
                        <a href="main-admin.php" class="btn btn-primary btn-sm me-2" role="button">Manage Users</a>
                        <a href="admin-books.php" class="btn btn-primary btn-sm me-2" role="button">Manage Books</a>
                        <a href="admin-genres.php" class="btn btn-primary btn-sm me-2" role="button">Manage Genres</a>
                        <a href="admin-publishers.php" class="btn btn-primary btn-sm" role="button">Manage Publishers</a>
                    </div>
                    <button type="button" class="btn btn-info btn-sm float-end mb-3" data-bs-toggle="modal" data-bs-target="#addAuthorModal">
                        <i class="fas fa-plus"></i><strong>&nbsp;Add Author</strong>
                    </button>
                </div>

                <!-- Add Author Modal -->
                <div class="modal fade" id="addAuthorModal" tabindex="-1" aria-labelledby="addAuthorModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addAuthorModalLabel">Add Author</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="addAuthorForm" method="POST" action="admin-bookControls.php">
                                    <input type="hidden" name="action" value="addAuthor">
                                    <div class="mb-3">
                                        <label for="authorFirstName" class="form-label">First Name</label>
                                        <input type="text" class="form-control border border-primary" id="authorFirstName" name="authorFirstName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="authorSurname" class="form-label">Surname</label>
                                        <input type="text" class="form-control border border-primary" id="authorSurname" name="authorSurname" required>
                                    </div>
                                    <?php
                                        if (isset($_GET['error']) && $_GET['error'] === 'authorExists') {
                                            echo '<div class="text-danger">Author is already in the list.</div>';
                                            unset($_SESSION['error']);
                                        }
                                    ?>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary" name="addAuthorBtn">Add Author</button>
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
                                    <th>Author</th>
                                    <th class="text-center" style="width: 50px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($authors as $author): ?>
                                    <tr>
                                        <td><?php echo $author['authorID']; ?></td>
                                        <td><?php echo $author['first_name'] . ' ' . $author['surname']; ?></td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group" aria-label="Action Buttons">
                                            <a href="admin-bookControls.php?action=deleteAuthor&authorID=<?php echo $author['authorID']; ?>" class="del-row">
                                                <i class="fas fa-trash" style="font-size: 20px;"></i>
                                            </a>
                                            <a href="#" class="del-row" data-bs-toggle="modal" data-bs-target="#editAuthorModal<?php echo $author['authorID']; ?>">
                                                <i class="fas fa-edit" style="font-size: 20px;margin-left: 9px;"></i>
                                            </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Edit Author Modal -->
                                    <div class="modal fade" id="editAuthorModal<?php echo $author['authorID']; ?>" tabindex="-1" aria-labelledby="editAuthorModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editAuthorModalLabel">Edit Author</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form id="editAuthorForm<?php echo $author['authorID']; ?>" method="POST" action="admin-bookControls.php">
                                                        <input type="hidden" name="action" value="editAuthor">
                                                        <input type="hidden" name="authorID" value="<?php echo $author['authorID']; ?>">
                                                        <div class="mb-3">
                                                            <label for="editedAuthorFirstName<?php echo $author['authorID']; ?>" class="form-label">First Name</label>
                                                            <input type="text" class="form-control border border-primary" id="editedAuthorFirstName<?php echo $author['authorID']; ?>" name="editedAuthorFirstName" value="<?php echo htmlspecialchars($author['first_name']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="editedAuthorSurname<?php echo $author['authorID']; ?>" class="form-label">Surname</label>
                                                            <input type="text" class="form-control border border-primary" id="editedAuthorSurname<?php echo $author['authorID']; ?>" name="editedAuthorSurname" value="<?php echo htmlspecialchars($author['surname']); ?>" required>
                                                        </div>

                                                        <?php
                                                            if (isset($_GET['error']) && $_GET['error'] === 'authorExists') {
                                                                echo '<div class="text-danger">Author is already in the list.</div>';
                                                                unset($_SESSION['error']);
                                                            }
                                                        ?>
                                                        
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary" name="editAuthorBtn">Save Changes</button>
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
