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

// Fetch book details based on ISBN
if (isset($_GET['ISBN'])) {
    $isbn = $_GET['ISBN'];
    $book = [];

    $db = new Dbh();
    $conn = $db->connect();

    if ($conn) {
        try {
            // Fetch book
            $stmt = $conn->prepare("SELECT ISBN, book_title FROM Books WHERE ISBN = :isbn");
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();
            $book = $stmt->fetch(PDO::FETCH_ASSOC);

            // Fetch existing authors associated with the given ISBN
            $stmtAuthors = $conn->prepare("SELECT a.authorID, CONCAT(a.first_name, ' ', a.surname) AS author_name FROM Authors a INNER JOIN books_authors ba ON a.authorID = ba.authorID WHERE ba.ISBN = :isbn");
            $stmtAuthors->bindParam(':isbn', $isbn);
            $stmtAuthors->execute();
            $authors = $stmtAuthors->fetchAll(PDO::FETCH_ASSOC);

            // Fetch existing genres associated with the given ISBN
            $stmtGenres = $conn->prepare("SELECT g.genreID, g.genre FROM Genres g INNER JOIN books_genres bg ON g.genreID = bg.genreID WHERE bg.ISBN = :isbn");
            $stmtGenres->bindParam(':isbn', $isbn);
            $stmtGenres->execute();
            $genres = $stmtGenres->fetchAll(PDO::FETCH_ASSOC);

            // Fetch existing publishers associated with the given ISBN
            $stmtPublishers = $conn->prepare("SELECT p.publisherID, p.publisher_name FROM Publishers p INNER JOIN books_publishers bp ON p.publisherID = bp.publisherID WHERE bp.ISBN = :isbn");
            $stmtPublishers->bindParam(':isbn', $isbn);
            $stmtPublishers->execute();
            $publishers = $stmtPublishers->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
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
                    <h1 style="font-size: 29px;">Edit Book</h1>
                </div>

                <div class="col">
                    <form action="admin-editBookControls.php" method="POST">
                        <input type="hidden" name="isbn" value="<?php echo $book['ISBN']; ?>">

                        <!-- ISBN and Book Title -->
                        <div class="mb-3">
                            <label for="bookTitle" class="form-label"><strong>Book Title</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control border border-primary" id="bookTitle" name="title" required readonly value="<?php echo $book['book_title']; ?>">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editBookTitleModal">Edit Book Title</button>
                            </div>
                        </div>

                        <!-- Edit Book Title Modal -->
                        <div class="modal fade" id="editBookTitleModal" tabindex="-1" aria-labelledby="editBookTitleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editBookTitleModalLabel">Edit Book Title</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="admin-editBookControls.php" method="POST">
                                        <input type="hidden" name="action" value="editBookTitle">
                                        <input type="hidden" name="isbn" value="<?php echo $book['ISBN']; ?>">

                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="newBookTitle" class="form-label">New Book Title</label>
                                                <input type="text" class="form-control border border-primary" id="newBookTitle" name="newTitle" required value="<?php echo $book['book_title']; ?>">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary" name="saveNewTitle">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="isbn" class="form-label"><strong>ISBN-13</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control border border-primary" id="isbn" name="isbn" required readonly value="<?php echo $book['ISBN']; ?>">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editISBNModal">Edit ISBN</button>
                            </div>
                        </div>

                        <!-- Edit ISBN Modal -->
                        <div class="modal fade" id="editISBNModal" tabindex="-1" aria-labelledby="editISBNModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editISBNModalLabel">Edit ISBN</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="admin-editBookControls.php" method="POST">
                                        <input type="hidden" name="action" value="editISBN">
                                        <input type="hidden" name="isbn" value="<?php echo $book['ISBN']; ?>">
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="newISBN" class="form-label">New ISBN</label>
                                                <input type="text" class="form-control border border-primary" id="newISBN" name="newISBN" required value="<?php echo $book['ISBN']; ?>">
                                                
                                                <?php if (isset($_GET['ISBN']) && $_GET['ISBN'] == $book['ISBN']): ?>
                                                    <?php if (isset($_GET['error'])): ?>
                                                        <div class="text-danger"><?php echo $_GET['error']; ?></div>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary" name="saveNewISBN">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Authors Table -->
                        <div class="col">
                            <h5>Authors</h5>

                            <button type="button" class="btn btn-outline-dark float-end mb-3" data-bs-toggle="modal" data-bs-target="#addAuthorModal">
                                <i class="fas fa-plus"></i>&nbsp;Add Author
                            </button>

                            <!-- Add Author Modal -->
                            <div class="modal fade" id="addAuthorModal" tabindex="-1" aria-labelledby="addAuthorModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="addAuthorModalLabel">Add Author</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="admin-editBookControls.php" method="POST">
                                            <input type="hidden" name="action" value="addAuthor">
                                            <input type="hidden" name="isbn" value="<?php echo $isbn; ?>">
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="authorFirstName" class="form-label">First Name</label>
                                                    <input type="text" class="form-control border border-primary" id="authorFirstName" name="authorFirstName" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="authorSurname" class="form-label">Surname</label>
                                                    <input type="text" class="form-control border border-primary" id="authorSurname" name="authorSurname" required>
                                                </div>

                                                <?php if (isset($_SESSION['authorErr'])): ?>
                                                    <div class="text-danger" role="alert">
                                                        <?php echo $_SESSION['authorErr']; ?>
                                                    </div>
                                                    <?php unset($_SESSION['authorErr']); ?>
                                                <?php endif; ?>

                                            </div>
                                            <div class="modal-footer">
                                                <a href="admin-books.php" class="btn btn-outline-danger">Cancel</a>
                                                <button type="submit" class="btn btn-primary" name="addAuthor">Add Author</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Display Author table -->
                            <table class="table table-striped table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Author</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($authors as $author): ?>
                                        <tr>
                                            <td><?php echo $author['author_name']; ?></td>
                                            <td class="text-center" style="width: 50px;">
                                                <div class="btn-group" role="group" aria-label="Action Buttons">
                                                    <a href="admin-editBookControls.php?action=deleteAuthor&authorID=<?php echo $author['authorID']; ?>&isbn=<?php echo $isbn; ?>" class="del-row"><i class="fas fa-trash" style="font-size: 20px;"></i></a>

                                                    <!-- Edit Author Modal -->
                                                    <div class="modal fade" id="editAuthorModal<?php echo $author['authorID']; ?>" tabindex="-1" aria-labelledby="editAuthorModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="editAuthorModalLabel">Edit Author</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="admin-editBookControls.php" method="POST">
                                                                    <input type="hidden" name="action" value="editAuthor">
                                                                    <input type="hidden" name="isbn" value="<?php echo $isbn; ?>">
                                                                    <input type="hidden" name="authorID" value="<?php echo $author['authorID']; ?>">
                                                                    <div class="modal-body">
                                                                        <div class="mb-3">
                                                                            <label for="editAuthorFirstName" class="form-label">First Name</label>
                                                                            <input type="text" class="form-control border border-primary" id="editAuthorFirstName" name="editAuthorFirstName" required value="<?php echo $author['first_name']; ?>">
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="editAuthorSurname" class="form-label">Surname</label>
                                                                            <input type="text" class="form-control border border-primary" id="editAuthorSurname" name="editAuthorSurname" required value="<?php echo $author['surname']; ?>">
                                                                        </div>

                                                                        <?php if (isset($_SESSION['authorErr'])): ?>
                                                                            <div class="text-danger" role="alert">
                                                                                <?php echo $_SESSION['authorErr']; ?>
                                                                            </div>
                                                                            <?php unset($_SESSION['authorErr']); ?>
                                                                        <?php endif; ?>

                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-primary" name="saveAuthorChanges">Save Changes</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Genres Table -->
                        <h5>Genres</h5>
                        
                        <button type="button" class="btn btn-outline-dark float-end mb-3" data-bs-toggle="modal" data-bs-target="#addGenreModal">
                            <i class="fas fa-plus"></i>&nbsp;Add Genre
                        </button>
                        
                        <!-- Add Genre Modal -->
                        <div class="modal fade" id="addGenreModal" tabindex="-1" aria-labelledby="addGenreModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="addGenreModalLabel">Add Genre</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <form action="admin-editBookControls.php" method="POST">
                                        <input type="hidden" name="action" value="addGenre">
                                        <input type="hidden" name="isbn" value="<?php echo $book['ISBN']; ?>">

                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="genre" class="form-label">Genre</label>
                                                <input type="text" class="form-control border border-primary" id="genre" name="genre" required>
                                            </div>

                                            <?php if (isset($_SESSION['genreErr'])): ?>
                                                <div class="text-danger" role="alert">
                                                    <?php echo $_SESSION['genreErr']; ?>
                                                </div>
                                                <?php unset($_SESSION['genreErr']); ?>
                                            <?php endif; ?>
                             
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary" name="addGenre">Add Genre</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Display Genres Table -->
                        <table class="table table-striped table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Genre</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($genres as $genre): ?>
                                    <tr>
                                        <td><?php echo $genre['genre']; ?></td>
                                        <td class="text-center" style="width: 50px;">
                                            <div class="btn-group" role="group" aria-label="Action Buttons">
                                                <a href="admin-editBookControls.php?action=deleteGenre&genreID=<?php echo $genre['genreID']; ?>&isbn=<?php echo $isbn; ?>" class="del-row"><i class="fas fa-trash" style="font-size: 20px;"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>


                        <!-- Publishers Table -->
                        <h5>Publishers</h5>

                        <button type="button" class="btn btn-outline-dark float-end mb-3" data-bs-toggle="modal" data-bs-target="#addPublisherModal">
                            <i class="fas fa-plus"></i>&nbsp;Add Publisher
                        </button>

                        <!-- Add Publisher Modal -->
                        <div class="modal fade" id="addPublisherModal" tabindex="-1" aria-labelledby="addPublisherModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="addPublisherModalLabel">Add Publisher</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="admin-editBookControls.php" method="POST">
                                        <input type="hidden" name="action" value="addPublisher">
                                        <input type="hidden" name="isbn" value="<?php echo $book['ISBN']; ?>">
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="publisherName" class="form-label">Publisher Name</label>
                                                <input type="text" class="form-control border border-primary" id="publisherName" name="publisherName" required>
                                            </div>

                                            <?php if (isset($_SESSION['publisherErr'])): ?>
                                                <div class="text-danger" role="alert">
                                                    <?php echo $_SESSION['publisherErr']; ?>
                                                </div>
                                                <?php unset($_SESSION['publisherErr']); ?>
                                            <?php endif; ?>

                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary" name="addPublisher">Add Publisher</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <table class="table table-striped table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Publisher</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($publishers as $publisher): ?>
                                    <tr>
                                        <td><?php echo $publisher['publisher_name']; ?></td>
                                        <td class="text-center" style="width: 50px;">
                                            <div class="btn-group" role="group" aria-label="Action Buttons">
                                                <a href="admin-editBookControls.php?action=deletePublisher&publisherID=<?php echo $publisher['publisherID']; ?>&isbn=<?php echo $isbn; ?>" class="del-row"><i class="fas fa-trash" style="font-size: 20px;"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="col text-end">
                            <div class="mt-3">
                                <a href="admin-books.php" class="btn btn-info">Back to Book List</a>
                            </div>
                        </div>
                    </form>
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