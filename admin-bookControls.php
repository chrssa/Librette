<?php
session_start();
include 'db_connect.php';

// -------------- Functions -----------------
function getDatabaseConnection() {
    $db = new Dbh();
    return $db->connect();
}

function redirectTo($location) {
    header("Location: $location");
    exit();
}

function deletePublisher($publisherID) {
    $conn = getDatabaseConnection();
    
    if ($conn) {
        try {
            // Delete publisher in the publishers table
            $stmt = $conn->prepare("DELETE FROM Publishers WHERE publisherID = :publisherID");
            $stmt->bindParam(':publisherID', $publisherID);
            $stmt->execute();

            redirectTo("admin-publishers.php");
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

function deleteGenre($genreID) {
    $conn = getDatabaseConnection();

    if ($conn) {
        try {
            $conn->beginTransaction();

            // Delete genre in the genres table
            $stmt = $conn->prepare("DELETE FROM Genres WHERE genreID = :genreID");
            $stmt->bindParam(':genreID', $genreID);
            $stmt->execute();

            $conn->commit();

            redirectTo("admin-genres.php");
        } catch (PDOException $e) {
            $conn->rollBack();
            echo "Error: " . $e->getMessage();
        }
    }
}

function deleteAuthor($authorID) {
    $conn = getDatabaseConnection();

    if ($conn) {
        try {
            // Delete the author in the authors table
            $stmt = $conn->prepare("DELETE FROM Authors WHERE authorID = :authorID");
            $stmt->bindParam(':authorID', $authorID);
            $stmt->execute();

            redirectTo("admin-authors.php");
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

function deleteBook($ISBN) {
    $conn = getDatabaseConnection();
    
    if ($conn) {
        try {
            $conn->beginTransaction();

            // Delete book
            $stmt = $conn->prepare("DELETE FROM Books WHERE ISBN = :isbn");
            $stmt->bindParam(':isbn', $ISBN);
            $stmt->execute();

            $conn->commit();
            redirectTo("admin-books.php");
        } catch (PDOException $e) {
            $conn->rollBack();
            echo "Error: " . $e->getMessage();
        }
    }
}

function addPublisher($data) {
    $conn = getDatabaseConnection();

    if ($conn) {
        try {
            $publisherName = strtolower(trim($data['publisherName']));

            // Check if the publisher already exists
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Publishers WHERE LOWER(publisher_name) = :publisherName");
            $stmt->bindParam(':publisherName', $publisherName);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                header("Location: admin-publishers.php?error=publisherExists");
                exit();
            } else {
                // Insert the new publisher into the table
                $stmt = $conn->prepare("INSERT INTO Publishers (publisher_name) VALUES (:publisherName)");
                $stmt->bindParam(':publisherName', $data['publisherName']);
                $stmt->execute();
                redirectTo("admin-publishers.php");
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

function addGenre($data) {
    $conn = getDatabaseConnection();

    if ($conn && isset($data['genreName'])) {
        try {
            $genreName = $data['genreName'];

            // Check if the genre already exists
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Genres WHERE LOWER(genre) = LOWER(:genreName)");
            $stmt->bindParam(':genreName', $genreName);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                // Genre already exists, set error message in session variable
                $_SESSION['error'] = 'genreExists';
                redirectTo("admin-genres.php");
            } else {
                // Insert the new genre
                $stmt = $conn->prepare("INSERT INTO Genres (genre) VALUES (:genreName)");
                $stmt->bindParam(':genreName', $genreName);
                $stmt->execute();

                redirectTo("admin-genres.php");
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

function addAuthor($data) {
    $conn = getDatabaseConnection();

    if ($conn) {
        try {
            $authorFirstName = $data['authorFirstName'];
            $authorSurname = $data['authorSurname'];

            // Check if the author already exists
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Authors WHERE first_name = :authorFirstName AND surname = :authorSurname");
            $stmt->bindParam(':authorFirstName', $authorFirstName);
            $stmt->bindParam(':authorSurname', $authorSurname);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                header("Location: admin-authors.php?error=authorExists");
                exit();
            } else {
                // Insert the new author into the table
                $stmt = $conn->prepare("INSERT INTO Authors (first_name, surname) VALUES (:authorFirstName, :authorSurname)");
                $stmt->bindParam(':authorFirstName', $authorFirstName);
                $stmt->bindParam(':authorSurname', $authorSurname);
                $stmt->execute();
                redirectTo("admin-authors.php");
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

function addBook($data) {
    $conn = getDatabaseConnection();

    if ($conn) {
        try {
            $isbn = preg_replace('/[\s-]+/', '', htmlspecialchars($data['isbn']));
            $title = htmlspecialchars($data['title']);
            $authorFirstNames = isset($data['authorFirstName']) ? $data['authorFirstName'] : [];
            $authorSurnames = isset($data['authorSurname']) ? $data['authorSurname'] : [];
            $genres = isset($data['genres']) ? $data['genres'] : [];
            $publishers = isset($data['publishers']) ? $data['publishers'] : [];

            // Validation and sanitization of input data
            if (
                !preg_match('/^(978|979)\d{10}$/', $isbn) ||
                strlen($isbn) !== 13 ||
                empty($title) ||
                empty($authorFirstNames) ||
                empty($genres) ||
                empty($publishers)
            ) {
                $_SESSION['error_message'] = "Invalid input data.";
                redirectTo("admin-books.php");
                return;
            }

            $conn->beginTransaction();

            // Check if the ISBN already exists in db
            $stmt = $conn->prepare("SELECT * FROM books WHERE ISBN = :isbn");
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();
            $existingBook = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingBook) {
                $_SESSION['error_message'] = "Book of the inputted ISBN already exists.";
                redirectTo("admin-books.php");
                return;
            }

            // Insert book into books table
            $isbn = handleBook($conn, $isbn, $title);

            // Insert authors
            handleAuthors($conn, $isbn, $authorFirstNames, $authorSurnames);

            // Insert genres
            handleGenres($conn, $isbn, $genres);

            // Insert publishers
            handlePublishers($conn, $isbn, $publishers);

            $conn->commit();

            unset($_SESSION['error_message']);
            redirectTo("admin-books.php");
        } catch (PDOException $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
            redirectTo("admin-books.php");
        }
    }
}

// Check if publisher already exists
function handlePublishers($conn, $isbn, $publishers) {
    foreach ($publishers as $publisher) {
        $stmt = $conn->prepare("SELECT publisherID FROM publishers WHERE publisher_name = :publisherName");
        $stmt->bindParam(':publisherName', $publisher);
        $stmt->execute();
        $existingPublisher = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingPublisher) {
            $stmt = $conn->prepare("INSERT INTO publishers (publisher_name) VALUES (:publisherName)");
            $stmt->bindParam(':publisherName', $publisher);
            $stmt->execute();

            $publisherID = $conn->lastInsertId();
        } else {
            $publisherID = $existingPublisher['publisherID'];
        }

        $stmt = $conn->prepare("INSERT IGNORE INTO books_publishers (ISBN, publisherID) VALUES (:isbn, :publisherID)");
        $stmt->bindParam(':isbn', $isbn);
        $stmt->bindParam(':publisherID', $publisherID);
        $stmt->execute();
    }
}

// Check if book already exists
function handleBook($conn, $isbn, $title) {
    $stmt = $conn->prepare("SELECT ISBN FROM books WHERE ISBN = :isbn");
    $stmt->bindParam(':isbn', $isbn);
    $stmt->execute();
    $existingBook = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingBook) {
        $stmt = $conn->prepare("INSERT INTO books (ISBN, book_title) VALUES (:isbn, :title)");
        $stmt->bindParam(':isbn', $isbn);
        $stmt->bindParam(':title', $title);
        $stmt->execute();
    }

    return $isbn;
}

// Check if author already exists
function handleAuthors($conn, $isbn, $authorFirstNames, $authorSurnames) {
    foreach ($authorFirstNames as $key => $authorFirstName) {
        $stmt = $conn->prepare("SELECT authorID  FROM authors WHERE first_name = :firstName AND surname = :surname");
        $stmt->bindParam(':firstName', $authorFirstName);
        $stmt->bindParam(':surname', $authorSurnames[$key]);
        $stmt->execute();
        $existingAuthor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingAuthor) {
            $stmt = $conn->prepare("INSERT INTO authors (first_name, surname) VALUES (:firstName, :surname)");
            $stmt->bindParam(':firstName', $authorFirstName);
            $stmt->bindParam(':surname', $authorSurnames[$key]);
            $stmt->execute();

            $authorID = $conn->lastInsertId();
        } else {
            $authorID = $existingAuthor['authorID'];
        }

        $stmt = $conn->prepare("INSERT IGNORE INTO books_authors (ISBN, authorID) VALUES (:isbn, :authorID)");
        $stmt->bindParam(':isbn', $isbn);
        $stmt->bindParam(':authorID', $authorID);
        $stmt->execute();
    }
}

// Check if genre already exists
function handleGenres($conn, $isbn, $genres) {
    foreach ($genres as $genre) {
        $stmt = $conn->prepare("SELECT genreID FROM genres WHERE genre = :genreName");
        $stmt->bindParam(':genreName', $genre);
        $stmt->execute();
        $existingGenre = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingGenre) {
            $stmt = $conn->prepare("INSERT INTO genres (genre) VALUES (:genreName)");
            $stmt->bindParam(':genreName', $genre);
            $stmt->execute();

            $genreID = $conn->lastInsertId();
        } else {
            $genreID = $existingGenre['genreID'];
        }

        $stmt = $conn->prepare("INSERT IGNORE INTO books_genres (ISBN, genreID) VALUES (:isbn, :genreID)");
        $stmt->bindParam(':isbn', $isbn);
        $stmt->bindParam(':genreID', $genreID);
        $stmt->execute();
    }
}

function editPublisher($data) {
    $conn = getDatabaseConnection();

    if ($conn) {
        try {
            $publisherID = $data['publisherID'];
            $editedPublisherName = strtolower(trim($data['editedPublisherName']));

            // Check if the edited publisher already exists excluding the current
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Publishers WHERE LOWER(publisher_name) = :editedPublisherName AND publisherID != :publisherID");
            $stmt->bindParam(':editedPublisherName', $editedPublisherName);
            $stmt->bindParam(':publisherID', $publisherID);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                header("Location: admin-publishers.php?error=publisherExists");
                exit();
            } else {
                $conn->beginTransaction();

                // Update the publisher's name in the table
                $stmt = $conn->prepare("UPDATE Publishers SET publisher_name = :editedPublisherName WHERE publisherID = :publisherID");
                $stmt->bindParam(':editedPublisherName', $data['editedPublisherName']);
                $stmt->bindParam(':publisherID', $publisherID);
                $stmt->execute();

                $conn->commit();
                redirectTo("admin-publishers.php");
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            echo "Error: " . $e->getMessage();
        }
    }
}

function editGenre($data) {
    $conn = getDatabaseConnection();

    if ($conn && isset($data['genreID'], $data['editedGenreName'])) {
        try {
            $genreID = $data['genreID'];
            $editedGenreName = $data['editedGenreName'];

            // Check if the edited genre already exists, excluding the current
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Genres WHERE LOWER(genre) = LOWER(:editedGenreName) AND genreID != :genreID");
            $stmt->bindParam(':editedGenreName', $editedGenreName);
            $stmt->bindParam(':genreID', $genreID);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                header("Location: admin-genres.php?error=genreExists&genreID=$genreID");
                exit();
            } else {
                $conn->beginTransaction();

                // Update the genre in table
                $stmt = $conn->prepare("UPDATE Genres SET genre = :editedGenreName WHERE genreID = :genreID");
                $stmt->bindParam(':editedGenreName', $editedGenreName);
                $stmt->bindParam(':genreID', $genreID);
                $stmt->execute();

                $conn->commit();
                redirectTo("admin-genres.php");
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            echo "Error: " . $e->getMessage();
        }
    }
}


function editAuthor($data) {
    $conn = getDatabaseConnection();

    if ($conn) {
        try {
            $authorID = $data['authorID'];
            $editedFirstName = $data['editedAuthorFirstName'];
            $editedSurname = $data['editedAuthorSurname'];

            // Check if the edited author already exists excluding the current
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Authors WHERE (first_name = :editedFirstName AND surname = :editedSurname) AND authorID != :authorID");
            $stmt->bindParam(':editedFirstName', $editedFirstName);
            $stmt->bindParam(':editedSurname', $editedSurname);
            $stmt->bindParam(':authorID', $authorID);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                header("Location: admin-authors.php?error=authorExists");
                exit();
            } else {
                // Update author's name in Authors table
                $stmt = $conn->prepare("UPDATE Authors SET first_name = :editedFirstName, surname = :editedSurname WHERE authorID = :authorID");
                $stmt->bindParam(':editedFirstName', $editedFirstName);
                $stmt->bindParam(':editedSurname', $editedSurname);
                $stmt->bindParam(':authorID', $authorID);
                $stmt->execute();

                redirectTo("admin-authors.php");
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

// -------------- Main -----------------

if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true || $_SESSION['username'] !== 'libretteAdmin') {
    redirectTo("login.php");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'deleteBook' && isset($_GET['ISBN'])){
        deleteBook($_GET['ISBN']);
    } elseif($_GET['action'] === 'deletePublisher' && isset($_GET['publisherID'])){
        deletePublisher($_GET['publisherID']);
    } elseif($_GET['action'] === 'deleteAuthor' && isset($_GET['authorID'])){
        deleteAuthor($_GET['authorID']);
    } elseif($_GET['action'] === 'deleteGenre' && isset($_GET['genreID'])){
        deleteGenre($_GET['genreID']);
    } 
}


// CRUD functionalities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'addPublisher') {
        addPublisher($_POST);
    } elseif ($_POST['action'] === 'editPublisher') {
        editPublisher($_POST);
    } elseif ($_POST['action'] === 'addGenre') {
        addGenre($_POST);
    } elseif ($_POST['action'] === 'editGenre') {
        editGenre($_POST);
    } elseif ($_POST['action'] === 'addAuthor') {
        addAuthor($_POST);
    } elseif ($_POST['action'] === 'editAuthor') {
        editAuthor($_POST);
    } elseif ($_POST['action'] === 'addBook') {
        addBook($_POST);
    }
}
?>
