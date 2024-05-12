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

function deleteUser($userId) {
    $conn = getDatabaseConnection();
    
    if ($conn) {
        try {
            // Remove userID in Lists table
            $stmtLists = $conn->prepare("DELETE FROM Lists WHERE userID = :id");
            $stmtLists->bindParam(':id', $userId);
            $stmtLists->execute();

            // Delete userID in Users table
            $stmtUser = $conn->prepare("DELETE FROM Users WHERE userID = :id");
            $stmtUser->bindParam(':id', $userId);
            $stmtUser->execute();

            redirectTo("main-admin.php");
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

function addUser($userData) {
    $errors = validateInput($userData);

    if (empty($errors)) {
        $conn = getDatabaseConnection();

        if ($conn) {
            try {
                // note: check existing username here because validateInput does not work
                $stmtCheckUsername = $conn->prepare("SELECT COUNT(*) FROM Users WHERE username = :username");
                $stmtCheckUsername->bindParam(':username', $userData['addUsername']);
                $stmtCheckUsername->execute();

                if ($stmtCheckUsername->fetchColumn() > 0) {
                    $_SESSION['addErrors']['usernameErr'] = 'Username already exists. Please choose a different username.';
                    redirectTo("main-admin.php#addUserModal");
                }

                // note: check existing email here because validateInput does not work
                $stmtCheckEmail = $conn->prepare("SELECT COUNT(*) FROM Users WHERE email = :email");
                $stmtCheckEmail->bindParam(':email', $userData['addEmail']);
                $stmtCheckEmail->execute();

                if ($stmtCheckEmail->fetchColumn() > 0) {
                    $_SESSION['addErrors']['emailErr'] = 'Email already exists. Please use a different email address.';
                    redirectTo("main-admin.php#addUserModal");
                }

                $stmt = $conn->prepare("INSERT INTO Users (first_name, surname, username, email, password) VALUES (:firstName, :surname, :username, :email, :password)");
                
                $stmt->bindParam(':firstName', $userData['addFirstName']);
                $stmt->bindParam(':surname', $userData['addSurname']);
                $stmt->bindParam(':username', $userData['addUsername']);
                $stmt->bindParam(':email', $userData['addEmail']);
                $stmt->bindParam(':password', $userData['addPassword']);
                
                $stmt->execute();

                $userId = $conn->lastInsertId();

                // Create an empty list for the user in Lists table
                $listStmt = $conn->prepare("INSERT INTO Lists (userID) VALUES (:userId)");
                $listStmt->bindParam(':userId', $userId);
                $listStmt->execute();
                
                redirectTo("main-admin.php");
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['addErrors'] = $errors;
        redirectTo("main-admin.php#addUserModal");
    }
}

// Edit existing user account
function editUser($userData) {
    $errors = validateInput($userData);

    if (empty($errors)) {
        $conn = getDatabaseConnection();

        if ($conn) {
            try {
                $stmt = $conn->prepare("UPDATE Users SET first_name = :firstName, surname = :surname, 
                    username = :username, email = :email, password = :password WHERE userID = :id");
                
                $stmt->bindParam(':firstName', $userData['editFirstName']);
                $stmt->bindParam(':surname', $userData['editSurname']);
                $stmt->bindParam(':username', $userData['editUsername']);
                $stmt->bindParam(':email', $userData['editEmail']);
                $stmt->bindParam(':password', $userData['editPassword']);
                $stmt->bindParam(':id', $userData['userID']);
                
                $stmt->execute();
                
                redirectTo("main-admin.php");
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['editData'] = $userData;
        $_SESSION['editErrors'] = $errors;
        $userId = $userData['userID'];
        redirectTo("main-admin.php#editUserModal$userId");
    }
}

// Input validation
function validateInput($userData) {
    $errors = [];

    $firstName = $userData['addFirstName'] ?? $userData['editFirstName'] ?? '';
    $surname = $userData['addSurname'] ?? $userData['editSurname'] ?? '';
    $username = $userData['addUsername'] ?? $userData['editUsername'] ?? '';
    $email = $userData['addEmail'] ?? $userData['editEmail'] ?? '';
    $userId = $userData['userID'] ?? null;
    $password = $userData['addPassword'] ?? $userData['editPassword'] ?? '';
    $confirmPassword = $userData['addConfirmPassword'] ?? $userData['editConfirmPassword'] ?? '';

    if (empty($firstName)) {
        $errors['firstNameErr'] = 'First name is required';
    } elseif(empty($surname)) {
        $errors['surnameErr'] = 'Surname is required';
    } elseif (empty($username)) {
        $errors['usernameErr'] = 'Username is required';
    } else {
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

    if (empty($email)) {
        $errors['emailErr'] = 'Email is required';
    } else {
        $conn = getDatabaseConnection();
        
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT userID FROM Users WHERE email = :email AND userID != :id");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':id', $userId);
                $stmt->execute();

                                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['emailErr'] = 'Invalid email format';
                }

                if ($stmt->rowCount() > 0) {
                    $errors['emailErr'] = 'Email already exists. Please use a different email address.';
                } 

            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirmPasswordErr'] = 'Passwords do not match';
    }

    return $errors;
}

// -------------- Main -----------------

if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true || $_SESSION['username'] !== 'libretteAdmin') {
    redirectTo("login.php");
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    deleteUser($_GET['id']);
}

// Add or edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'addUser') {
        addUser($_POST);
    } elseif ($_POST['action'] === 'editUser') {
        editUser($_POST);
    }
}
?>
