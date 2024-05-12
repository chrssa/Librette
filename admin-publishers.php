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
$publishers = [];

// Fetch publishers from db
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT publisherID, publisher_name FROM Publishers");
        $stmt->execute();
        $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1 style="font-size: 29px;">Manage Publishers</h1>
                    <div class="d-flex justify-content-center mt-3">
                        <a href="main-admin.php" class="btn btn-primary btn-sm me-2" role="button">Manage Users</a>
                        <a href="admin-books.php" class="btn btn-primary btn-sm me-2" role="button">Manage Books</a>
                        <a href="admin-authors.php" class="btn btn-primary btn-sm me-2" role="button">Manage Authors</a>
                        <a href="admin-genres.php" class="btn btn-primary btn-sm" role="button">Manage Genres</a>
                    </div>

                    <button type="button" class="btn btn-info btn-sm float-end mb-3" data-bs-toggle="modal" data-bs-target="#addPublisherModal">
                        <i class="fas fa-plus"></i><strong>&nbsp;Add Publisher</strong>
                    </button>

                </div>

                <!-- Add Publisher Modal -->
                <div class="modal fade" id="addPublisherModal" tabindex="-1" aria-labelledby="addPublisherModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addPublisherModalLabel">Add Publisher</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">

                                <form id="addPublisherForm" method="POST" action="admin-bookControls.php">
                                    <input type="hidden" name="action" value="addPublisher">
                                    <div class="mb-3">
                                        <label for="publisherName" class="form-label">Publisher Name</label>
                                        <input type="text" class="form-control border border-primary" id="publisherName" name="publisherName" required>
                                    </div>
                                    
                                    <div class="text-danger" id="publisherError" style="display: <?php echo isset($_GET['error']) ? 'block' : 'none'; ?>;">Publisher already exists!</div>
                                    
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary" name="addPublisherBtn">Add Publisher</button>
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
                                    <th>Publisher</th>
                                    <th class="text-center" style="width: 50px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($publishers as $publisher): ?>
                                    <tr>
                                        <td><?php echo $publisher['publisherID']; ?></td>
                                        <td><?php echo $publisher['publisher_name']; ?></td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group" aria-label="Action Buttons">
                                                <a href="admin-bookControls.php?action=deletePublisher&publisherID=<?php echo $publisher['publisherID']; ?>" class="del-row"><i class="fas fa-trash" style="font-size: 20px;"></i></a>
                                                <a href="#" class="del-row" data-bs-toggle="modal" data-bs-target="#editPublisherModal<?php echo $publisher['publisherID']; ?>">
                                                    <i class="fas fa-edit" style="font-size: 20px;margin-left: 9px;"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Edit Publisher Modal -->
                                    <div class="modal fade" id="editPublisherModal<?php echo $publisher['publisherID']; ?>" tabindex="-1" aria-labelledby="editPublisherModalLabel<?php echo $publisher['publisherID']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editPublisherModalLabel<?php echo $publisher['publisherID']; ?>">Edit Publisher</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">

                                                    <form id="editPublisherForm<?php echo $publisher['publisherID']; ?>" method="POST" action="admin-bookControls.php">
                                                        <input type="hidden" name="action" value="editPublisher">
                                                        <input type="hidden" name="publisherID" value="<?php echo $publisher['publisherID']; ?>">
                                                        <div class="mb-3">
                                                            <label for="editedPublisherName<?php echo $publisher['publisherID']; ?>" class="form-label">Edited Publisher Name</label>
                                                            <input type="text" class="form-control border border-primary" id="editedPublisherName<?php echo $publisher['publisherID']; ?>" name="editedPublisherName" value="<?php echo $publisher['publisher_name']; ?>" required>
                                                        </div>
                                                        
                                                        <div class="text-danger" id="editedPublisherError<?php echo $publisher['publisherID']; ?>" style="display: <?php echo isset($_GET['error']) ? 'block' : 'none'; ?>;">Publisher already exists.</div>
                                                        <div class="modal-footer">
                                                            
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary" name="editPublisherBtn">Save Changes</button>
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
