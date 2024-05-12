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

function updateBookTitle($isbn, $newTitle)
{
    $db = new Dbh();
    $conn = $db->connect();

    if ($conn) {
        try {
            $conn->beginTransaction();

            // Update the main Books table
            $stmt = $conn->prepare("UPDATE Books SET book_title = :newTitle WHERE ISBN = :isbn");
            $stmt->bindParam(':newTitle', $newTitle);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();

            $conn->commit();

            // Redirect back to the edit book page after updating the title
            header("Location: admin-editBook.php?ISBN=$isbn");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            echo "Error: " . $e->getMessage();
        }
    }
}

// Function to update ISBN
function updateISBN($oldISBN, $newISBN)
{
    $db = new Dbh();
    $conn = $db->connect();

    if ($conn) {
        try {
            $error = '';

            // Check if the new ISBN already exists in the Books table
            if ($newISBN !== '' && strlen($newISBN) == 13) {
                $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM Books WHERE ISBN = :newISBN AND ISBN <> :oldISBN");
                $stmt->bindParam(':newISBN', $newISBN);
                $stmt->bindParam(':oldISBN', $oldISBN);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result['count'] > 0) {
                    $error = "ISBN already exists.";
                }
            } else {
                $error = "Input valid ISBN value.";
            } 

            if (!empty($error)) {
                header("Location: admin-editBook.php?ISBN=$oldISBN&error=$error");
                exit();
            }

            // Update Books table
            $stmt = $conn->prepare("UPDATE Books SET ISBN = :newISBN WHERE ISBN = :oldISBN");
            $stmt->bindParam(':newISBN', $newISBN);
            $stmt->bindParam(':oldISBN', $oldISBN);
            $stmt->execute();

            header("Location: admin-editBook.php?ISBN=$newISBN");
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}



// Handle adding authors to the book
function addAuthorToBook($isbn, $authorFirstName, $authorSurname){
    $db = new Dbh();
    $conn = $db->connect();

    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT authorID FROM Authors WHERE first_name = :authorFirstName AND surname = :authorSurname");
            $stmt->bindParam(':authorFirstName', $authorFirstName);
            $stmt->bindParam(':authorSurname', $authorSurname);
            $stmt->execute();
            $author = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$author) {
                // if author doesn't exist, insert into Authors table
                $stmt = $conn->prepare("INSERT INTO Authors (first_name, surname) VALUES (:authorFirstName, :authorSurname)");
                $stmt->bindParam(':authorFirstName', $authorFirstName);
                $stmt->bindParam(':authorSurname', $authorSurname);
                $stmt->execute();

                $authorID = $conn->lastInsertId();
            } else {
                $authorID = $author['authorID'];
            }

            // Check if the author is already associated with the book
            $stmt = $conn->prepare("SELECT * FROM books_authors WHERE ISBN = :isbn AND authorID = :authorID");
            $stmt->bindParam(':isbn', $isbn);
            $stmt->bindParam(':authorID', $authorID);
            $stmt->execute();
            $existingAssociation = $stmt->fetch();

            if ($existingAssociation) {
                $_SESSION['authorErr'] = "Author is already associated with the book.";
            } else {
                // Insert into books_authors table
                $stmt = $conn->prepare("INSERT INTO books_authors (ISBN, authorID) VALUES (:isbn, :authorID)");
                $stmt->bindParam(':isbn', $isbn);
                $stmt->bindParam(':authorID', $authorID);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

// Handle adding genres to the book
function addGenreToBook($isbn, $genre){
    $db = new Dbh();
    $conn = $db->connect();

    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT genreID FROM Genres WHERE genre = :genre");
            $stmt->bindParam(':genre', $genre);
            $stmt->execute();
            $existingGenre = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingGenre) {
                // If genre doesn't exist, insert into Genres table
                $stmt = $conn->prepare("INSERT INTO Genres (genre) VALUES (:genre)");
                $stmt->bindParam(':genre', $genre);
                $stmt->execute();

                $genreID = $conn->lastInsertId();
            } else {
                $genreID = $existingGenre['genreID'];
            }

            // Check if the genre is already associated with the book
            $stmt = $conn->prepare("SELECT * FROM books_genres WHERE ISBN = :isbn AND genreID = :genreID");
            $stmt->bindParam(':isbn', $isbn);
            $stmt->bindParam(':genreID', $genreID);
            $stmt->execute();
            $existingAssociation = $stmt->fetch();

            if ($existingAssociation) {
                $_SESSION['genreErr'] = "Genre is already associated with the book.";
            } else {
                $stmt = $conn->prepare("INSERT INTO books_genres (ISBN, genreID) VALUES (:isbn, :genreID)");
                $stmt->bindParam(':isbn', $isbn);
                $stmt->bindParam(':genreID', $genreID);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    header("Location: admin-editBook.php?ISBN=$isbn");
    exit();
}

// Handle adding publishers to the book
function addPublisherToBook($isbn, $publisherName){
    $db = new Dbh();
    $conn = $db->connect();

    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT publisherID FROM Publishers WHERE publisher_name = :publisherName");
            $stmt->bindParam(':publisherName', $publisherName);
            $stmt->execute();
            $existingPublisher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingPublisher) {
                // If publisher doesn't exist, insert into Publishers table
                $stmt = $conn->prepare("INSERT INTO Publishers (publisher_name) VALUES (:publisherName)");
                $stmt->bindParam(':publisherName', $publisherName);
                $stmt->execute();

                $publisherID = $conn->lastInsertId();
            } else {
                $publisherID = $existingPublisher['publisherID'];
            }

            // Check if the publisher is already associated with the book
            $stmt = $conn->prepare("SELECT * FROM books_publishers WHERE ISBN = :isbn AND publisherID = :publisherID");
            $stmt->bindParam(':isbn', $isbn);
            $stmt->bindParam(':publisherID', $publisherID);
            $stmt->execute();
            $existingAssociation = $stmt->fetch();

            if ($existingAssociation) {
                $_SESSION['publisherErr'] = "Publisher is already associated with the book.";
            } else {
                $stmt = $conn->prepare("INSERT INTO books_publishers (ISBN, publisherID) VALUES (:isbn, :publisherID)");
                $stmt->bindParam(':isbn', $isbn);
                $stmt->bindParam(':publisherID', $publisherID);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    header("Location: admin-editBook.php?ISBN=$isbn");
    exit();
}

// delete an author from the book
function deleteAuthor($authorID, $isbn){
    $db = new Dbh();
    $conn = $db->connect();

    if ($conn) {
        try {
            // Delete author from books_authors table
            $stmt = $conn->prepare("DELETE FROM books_authors WHERE authorID = :authorID AND ISBN = :isbn");
            $stmt->bindParam(':authorID', $authorID);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();

            header("Location: admin-editBook.php?ISBN=" . $isbn);
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

function deleteGenre($genreID){
    $db = new Dbh();
    $conn = $db->connect();

    if ($conn) {
        try {
            // Delete genre from books_genres table
            $stmt = $conn->prepare("DELETE FROM books_genres WHERE genreID = :genreID");
            $stmt->bindParam(':genreID', $genreID);
            $stmt->execute();

            header("Location: admin-editBook.php?ISBN=" . $_GET['isbn']);
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

// delete a publisher from the book
function deletePublisher($publisherID, $isbn){
    $db = new Dbh();
    $conn = $db->connect();

    if ($conn) {
        try {
            // Delete publisher from books_publishers table
            $stmt = $conn->prepare("DELETE FROM books_publishers WHERE publisherID = :publisherID AND ISBN = :isbn");
            $stmt->bindParam(':publisherID', $publisherID);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();

            header("Location: admin-editBook.php?ISBN=" . $isbn);
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

// --------- Main ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'editBookTitle' && isset($_POST['isbn'], $_POST['newTitle'])) {
        $isbn = $_POST['isbn'];
        $newTitle = $_POST['newTitle'];
        updateBookTitle($isbn, $newTitle);
    }elseif ($_POST['action'] === 'editISBN' && isset($_POST['isbn'], $_POST['newISBN'])) {
        $isbn = $_POST['isbn'];
        $newISBN = $_POST['newISBN'];
        updateISBN($isbn, $newISBN);
    }
}


if (isset($_POST['action'])) {
    $isbn = $_POST['isbn'];

    if ($_POST['action'] === 'addAuthor') {
        addAuthorToBook($isbn, $_POST['authorFirstName'], $_POST['authorSurname']);
    } elseif ($_POST['action'] === 'addGenre') {
        addGenreToBook($isbn, $_POST['genre']);
    } elseif ($_POST['action'] === 'addPublisher') {
        addPublisherToBook($isbn, $_POST['publisherName']);
    }

    // Redirect back to the edit book page
    header("Location: admin-editBook.php?ISBN=$isbn");
    exit();

} 

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'deleteAuthor' && isset($_GET['authorID'], $_GET['isbn'])) {
        deleteAuthor($_GET['authorID'], $_GET['isbn']);
    }elseif ($_GET['action'] === 'deleteGenre' && isset($_GET['genreID'])) {
        deleteGenre($_GET['genreID']);
    }elseif ($_GET['action'] === 'deletePublisher' && isset($_GET['publisherID'], $_GET['isbn'])) {
        deletePublisher($_GET['publisherID'], $_GET['isbn']);
    }
}

?>