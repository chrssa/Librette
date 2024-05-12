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

//Check if ISBN is in database.
function fetchBookDetails($isbn) {
    $cleanIsbn = str_replace(['-', ' '], '', $isbn);
    $conn = getDatabaseConnection();
    
    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT b.ISBN, b.book_title, 
                GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', a.surname) SEPARATOR ', ') AS authors, 
                GROUP_CONCAT(DISTINCT g.genre SEPARATOR ', ') AS genres, 
                GROUP_CONCAT(DISTINCT p.publisher_name SEPARATOR ', ') AS publishers
                FROM Books b
                LEFT JOIN books_authors ba ON b.ISBN = ba.ISBN
                LEFT JOIN Authors a ON ba.authorID = a.authorID
                LEFT JOIN books_genres bg ON b.ISBN = bg.ISBN
                LEFT JOIN Genres g ON bg.genreID = g.genreID
                LEFT JOIN books_publishers bp ON b.ISBN = bp.ISBN
                LEFT JOIN Publishers p ON bp.publisherID = p.publisherID
                WHERE b.ISBN = :isbn
                GROUP BY b.ISBN, b.book_title");
            $stmt->bindParam(':isbn', $cleanIsbn);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    return null;
}

function addBookToList($listID, $isbn) {
    $conn = getDatabaseConnection();

    if ($conn) {
        try {
            // Check if the ISBN already exists in the user's list
            $stmt = $conn->prepare("SELECT * FROM Lists_Books WHERE listID = :listID AND ISBN = :isbn");
            $stmt->bindParam(':listID', $listID);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();
            $existingEntry = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingEntry) {
                $_SESSION['addError'] = "The book with ISBN $isbn is already in your list.";
                redirectTo("main.php");
                return;
            } else {
                // Fetch book details based on ISBN
                $bookDetails = fetchBookDetails($isbn);

                if ($bookDetails) {
                    // Insert book details into Lists_Books table
                    $stmt = $conn->prepare("INSERT INTO Lists_Books (listID, ISBN, status) VALUES (:listID, :isbn, 'ONGOING')");
                    $stmt->bindParam(':listID', $listID);
                    $stmt->bindParam(':isbn', $bookDetails['ISBN']);
                    $stmt->execute();
                }else {
                    $_SESSION['addError'] = "Book with ISBN $isbn not found. Contact admin to add more books.";
                }
                redirectTo("main.php");
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

function getBookDetails($isbn) {
    $conn = getDatabaseConnection();
    
    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT b.ISBN, b.book_title, 
                GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', a.surname) SEPARATOR ', ') AS authors,
                GROUP_CONCAT(DISTINCT g.genre SEPARATOR ', ') AS genres, 
                GROUP_CONCAT(DISTINCT p.publisher_name SEPARATOR ', ') AS publishers
                FROM Books b
                LEFT JOIN books_authors ba ON b.ISBN = ba.ISBN
                LEFT JOIN Authors a ON ba.authorID = a.authorID
                LEFT JOIN books_genres bg ON b.ISBN = bg.ISBN
                LEFT JOIN Genres g ON bg.genreID = g.genreID
                LEFT JOIN books_publishers bp ON b.ISBN = bp.ISBN
                LEFT JOIN Publishers p ON bp.publisherID = p.publisherID
                WHERE b.ISBN = :isbn
                GROUP BY b.ISBN, b.book_title");

            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}


function deleteBook($isbn){
    $conn = getDatabaseConnection();
    
    if ($conn) {
        try {
            $listID = $_SESSION['listID'];

            // Delete isbn from Lists_Books table
            $stmt = $conn->prepare("DELETE FROM lists_books WHERE ISBN = :isbn AND listID = :listID");
            $stmt->bindParam(':isbn', $isbn);
            $stmt->bindParam(':listID', $listID);
            $stmt->execute();

            redirectTo("main.php");
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }   
}

function editBook($data) {
    $conn = getDatabaseConnection();

    if ($conn) {
        try {
            $isbn = $data['isbn'];
            $bookPrice = !empty($data['editBookPrice']) ? $data['editBookPrice'] : null;
            $purchaseDate = !empty($data['editPurchaseDate']) ? $data['editPurchaseDate'] : null;

            // Check if the entered book price is a positive numeric value if it is not null
            if ($bookPrice !== null && (!is_numeric($bookPrice) || $bookPrice < 0)) {
                $_SESSION['priceError'] = "Invalid value for book price.";
                redirectTo("main.php");
                return;
            }

            // Prepare and execute the SQL statement to update book details
            $stmt = $conn->prepare("UPDATE Lists_Books SET book_price = :bookPrice, purchase_date = :purchaseDate WHERE ISBN = :isbn");
            $stmt->bindParam(':bookPrice', $bookPrice, PDO::PARAM_STR);
            $stmt->bindParam(':purchaseDate', $purchaseDate, PDO::PARAM_STR);
            $stmt->bindParam(':isbn', $isbn, PDO::PARAM_STR);
            $stmt->execute();

            redirectTo("main.php"); // Redirect to main.php after successful update
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

// Update reading status
function updateStatus($data) {
    $conn = getDatabaseConnection();

    if ($conn) {
        try {
            $isbn = htmlspecialchars($data['isbn']);
            $status = htmlspecialchars($data['status']);

            $stmt = $conn->prepare("UPDATE lists_books SET status = :status WHERE ISBN = :isbn");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();

            redirectTo("main.php");
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}


// -------------- Main -----------------

if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    redirectTo("login.php");
}

// Delete book
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'deleteBook') {
    if (isset($_GET['isbn'])) {
        deleteBook($_GET['isbn']);
    }
}

// Add or edit book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'editBook') {
        editBook($_POST);
    } elseif ($_POST['action'] === 'updateStatus'){
        updateStatus($_POST);
    }elseif ($_POST['action'] === 'addBook') {
        $listID = $_SESSION['listID'];
        $isbn = $_POST['isbn'];
        addBookToList($listID, $isbn);
    }
}

?>