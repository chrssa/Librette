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
$books = [];

// Fetch books from database
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT ISBN, book_title FROM Books");
        $stmt->execute();

        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

                    <h1 style="font-size: 29px;">Manage Books</h1>

                    <div class="d-flex justify-content-center mt-3">
                        <a href="main-admin.php" class="btn btn-primary btn-sm me-2" role="button">Manage Users</a>
                        <a href="admin-authors.php" class="btn btn-primary btn-sm me-2" role="button">Manage Authors</a>
                        <a href="admin-genres.php" class="btn btn-primary btn-sm me-2" role="button">Manage Genres</a>
                        <a href="admin-publishers.php" class="btn btn-primary btn-sm" role="button">Manage Publishers</a>
                    </div>

                    <a href="#" class="btn btn-info btn-sm float-end mb-3" role="button" data-bs-toggle="modal" data-bs-target="#addBookModal">
                        <i class="fas fa-plus"></i><strong>&nbsp;Add Book</strong>
                    </a>

                </div>

                <!-- Add Book Modal -->
                <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addBookModalLabel">Add New Book</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form id="addBookForm" action="admin-bookControls.php" method="POST">
                                <input type="hidden" name="action" value="addBook">

                                <div class="modal-body">
                                    <div class="mb-3">
                                         <label for="isbn" class="form-label">ISBN-13</label>
                                        <input type="text" class="form-control border border-primary" id="isbn" name="isbn" required placeholder="xxx-x-xxx-xxxxx-x">
                                    </div>
                                    <div class="mb-3">
                                        <label for="bookTitle" class="form-label">Book Title</label>
                                        <input type="text" class="form-control border border-primary" id="bookTitle" name="title" required>
                                    </div>

                                    <div id="authorInputs">
                                        <div class="mb-3">
                                            <label for="authorFirstName" class="form-label">Author's First Name</label>
                                            <input type="text" class="form-control border border-primary authorFirstName" name="authorFirstName[]" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="authorSurname" class="form-label">Author's Surname</label>
                                            <input type="text" class="form-control border border-primary authorSurname" name="authorSurname[]">
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-secondary" onclick="addAuthorFields()">Add Author</button>

                                    <div class="form-group">
                                        <label for="genres">Genre/s:</label>
                                        <div id="genreInputs">
                                            <input type="text" name="genres[]" class="form-control border border-primary genre" required>
                                            <button type="button" onclick="addGenreInput()" class="btn btn-sm btn-secondary mt-2">Add Genre</button>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="publishers">Publisher/s:</label>
                                        <div id="publisherInputs">
                                            <input type="text" name="publishers[]" class="form-control border border-primary publisher" required>
                                            <button type="button" onclick="addPublisherInput()" class="btn btn-sm btn-secondary mt-2">Add Publisher</button>
                                        </div>
                                    </div>

                                    <?php if(isset($_SESSION['error_message'])): ?>
                                        <div class="text-danger">
                                            <?php echo $_SESSION['error_message']; ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary" name="addBook">Add Book</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Display table -->
                <div class="col">
                    <div class="table-responsive" id="myTable">
                        <table class="table table-striped table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>ISBN</th>
                                    <th>Book Title</th>
                                    <th>Author/s</th>
                                    <th>Genre/s</th>
                                    <th>Publisher/s</th>
                                    <th class="text-center" style="width: 50px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td><?php echo $book['ISBN']; ?></td>
                                        <td><?php echo $book['book_title'] ?></td>
                                        <td>
                                            <?php
                                            $stmtAuthors = $conn->prepare("SELECT CONCAT(a.first_name, ' ', a.surname) as author_name FROM Authors a INNER JOIN books_authors ba ON a.authorID = ba.authorID WHERE ba.ISBN = :isbn");
                                            $stmtAuthors->bindParam(':isbn', $book['ISBN']);
                                            $stmtAuthors->execute();
                                            $authors = $stmtAuthors->fetchAll(PDO::FETCH_ASSOC);
                                            $authorNames = array_column($authors, 'author_name');
                                            echo implode(', ', $authorNames);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $stmtGenres = $conn->prepare("SELECT g.genre FROM Genres g INNER JOIN books_genres bg ON g.genreID = bg.genreID WHERE bg.ISBN = :isbn");
                                            $stmtGenres->bindParam(':isbn', $book['ISBN']);
                                            $stmtGenres->execute();
                                            $genres = $stmtGenres->fetchAll(PDO::FETCH_ASSOC);
                                            $genreNames = array_column($genres, 'genre');
                                            echo implode(', ', $genreNames);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $stmtPublishers = $conn->prepare("SELECT p.publisher_name FROM Publishers p INNER JOIN books_publishers bp ON p.publisherID = bp.publisherID WHERE bp.ISBN = :isbn");
                                            $stmtPublishers->bindParam(':isbn', $book['ISBN']);
                                            $stmtPublishers->execute();
                                            $publishers = $stmtPublishers->fetchAll(PDO::FETCH_ASSOC);
                                            $publisherNames = array_column($publishers, 'publisher_name');
                                            echo implode(', ', $publisherNames);
                                            ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group" aria-label="Action Buttons">
                                            <a href="admin-bookControls.php?action=deleteBook&ISBN=<?php echo $book['ISBN']; ?>" class="del-row">
                                                <i class="fas fa-trash" style="font-size: 20px;"></i>
                                            </a>
                                            <a href="admin-editBook.php?ISBN=<?php echo $book['ISBN']; ?>" class="del-row">
                                                <i class="fas fa-edit" style="font-size: 20px;margin-left: 9px;"></i>
                                            </a>                  
                                            </div>
                                        </td>
                                    </tr>
                        
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

    <script>
        // For handling multiple author, genre, and publisher inputs
        function addAuthorFields() {
            const authorInputs = document.getElementById('authorInputs');
            
            // Input fields for first names and surnames
            const fnInput = document.createElement('input');
            fnInput.type = 'text';
            fnInput.name = 'authorFirstName[]';
            fnInput.className = 'form-control border border-primary authorFirstName mt-2';
            fnInput.required = true;

            const surnameInput = document.createElement('input');
            surnameInput.type = 'text';
            surnameInput.name = 'authorSurname[]';
            surnameInput.className = 'form-control border border-primary authorSurname mt-2';
            surnameInput.required = true;

            authorInputs.appendChild(fnInput);
            authorInputs.appendChild(surnameInput);
        }

        function addGenreInput() {
            const genreInputs = document.getElementById('genreInputs');

            // Input fields for genres
            const genreInput = document.createElement('input');
            genreInput.type = 'text';
            genreInput.name = 'genres[]';
            genreInput.className = 'form-control border border-primary genre mt-2';
            genreInput.required = true;
            genreInputs.appendChild(genreInput);
        }

        function addPublisherInput() {
            const publisherInputs = document.getElementById('publisherInputs');

            // Input fields for publishers
            const pubInput = document.createElement('input');
            pubInput.type = 'text';
            pubInput.name = 'publishers[]';
            pubInput.className = 'form-control border border-primary publisher mt-2';
            pubInput.required = true;
            publisherInputs.appendChild(pubInput);
        }
    </script>
</body>

</html>
