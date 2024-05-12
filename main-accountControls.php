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

// Delete user account
function deleteAccount($userId) {
    $conn = getDatabaseConnection();

    if ($conn) {
        try {
            // Delete userID in Lists table
            $stmtLists = $conn->prepare("DELETE FROM Lists WHERE userID = :id");
            $stmtLists->bindParam(':id', $userId);
            $stmtLists->execute();

            // Delete userID in Users table
            $stmtUser = $conn->prepare("DELETE FROM Users WHERE userID = :id");
            $stmtUser->bindParam(':id', $userId);
            $stmtUser->execute();

            session_destroy();
            redirectTo("index.php");
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

// Update user account
function editAccount($userData) {
    $errors = validateUserInput($userData);

    if (empty($errors)) {
        $conn = getDatabaseConnection();

        if ($conn) {
            try {
                // Update user information
                $stmt = $conn->prepare("UPDATE Users SET first_name = :firstName, surname = :surname, email = :email, password = :password WHERE userID = :id");
                
                $stmt->bindParam(':firstName', $userData['editFirstName']);
                $stmt->bindParam(':surname', $userData['editSurname']);
                $stmt->bindParam(':email', $userData['editEmail']);
                $stmt->bindParam(':password', $userData['editPassword']);
                $stmt->bindParam(':id', $userData['userID']);
                
                $stmt->execute();
                
                redirectTo("main.php");
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['editErrors'] = $errors;
        $userId = $userData['userID'];
        redirectTo("main.php#editAccountModal$userId");
    }
}

// Input Validation
function validateUserInput($userData) {
    $errors = [];

    $firstName = $userData['editFirstName'] ?? '';
    $surname = $userData['editSurname'] ?? '';
    $username = $userData['editUsername'] ?? '';
    $email = $userData['editEmail'] ?? '';
    $userId = $userData['userID'] ?? null;
    $password = $userData['editPassword'] ?? '';
    $confirmPassword = $userData['editConfirmPassword'] ?? '';

    // Validate first name
    if (empty($firstName)) {
        $errors['firstNameErr'] = 'First name is required';
    }

    // Validate surname
    if (empty($surname)) {
        $errors['surnameErr'] = 'Surname is required';
    }

    // Validate username
    if (empty($username)) {
        $errors['usernameErr'] = 'Username is required';
    } else {
        // Check if username already exists
        $conn = getDatabaseConnection();
        
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT userID FROM Users WHERE username = :username AND userID != :id");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':id', $userId);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $errors['usernameErr'] = 'Username already exists. Please choose a different username.';
                }

                if ($username === 'libretteAdmin') {
                    $errors['usernameErr'] = 'Username is used by Admin. Please choose a different username';
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }

    // Validate email
    if (empty($email)) {
        $errors['emailErr'] = 'Email is required';
    } else {
        // Check if email already exists
        $conn = getDatabaseConnection();
        
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT userID FROM Users WHERE email = :email AND userID != :id");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':id', $userId);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $errors['emailErr'] = 'Email already exists. Please use a different email address.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['emailErr'] = 'Invalid email format';
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }
    
    // Validate password and confirm password
    if ($password !== $confirmPassword) {
        $errors['confirmPasswordErr'] = 'Passwords do not match';
    }

    return $errors;
}

// -------------- Main -----------------

if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    redirectTo("login.php");
}

// Delete account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'deleteAccount') {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            deleteAccount($userId);
        }
    } elseif ($_POST['action'] === 'editAccount') {
        editAccount($_POST);
    }
}

?>
