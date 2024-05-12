<?php
include 'db_connect.php';

// Define and initialize variables
$first_name = $surname = $username = $email = $password = $confirmPassword = '';
$firstNameErr = $surnameErr = $usernameErr = $emailErr = $passwordErr = $confirmPasswordErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate first name
    if (empty($_POST['first_name'])) {
        $firstNameErr = 'First name is required';
    } else {
        $first_name = $_POST['first_name'];
    }

    // Validate surname
    if (empty($_POST['surname'])) {
        $surnameErr = 'Surname is required';
    } else {
        $surname = $_POST['surname'];
    }

    // Validate username
    if (empty($_POST['username'])) {
        $usernameErr = 'Username is required';
    } else {
        $username = $_POST['username'];

        // Check if username already exists
        $db = new Dbh();
        $conn = $db->connect();
        $stmt = $conn->prepare("SELECT userID FROM Users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $usernameErr = 'Username already exists. Please choose a different username.';
        }

        if ($username === 'libretteAdmin') {
            $usernameErr = 'Username is used by Admin. Please choose a different username';
        }
    }

    // Validate email
    if (empty($_POST['email'])) {
        $emailErr = 'Email is required';
    } else {
        $email = $_POST['email'];

        // Check if email already exists
        $stmt = $conn->prepare("SELECT userID FROM Users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $emailErr = 'Email already exists. Please use a different email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = 'Invalid email format';
        }
    }

    // Validate password
    if (empty($_POST['password'])) {
        $passwordErr = 'Password is required';
    } else {
        $password = $_POST['password'];
    }

    // Validate confirm password
    if (empty($_POST['confirmPassword'])) {
        $confirmPasswordErr = 'Please confirm password';
    } else {
        $confirmPassword = $_POST['confirmPassword'];
        if ($confirmPassword !== $password) {
            $confirmPasswordErr = 'Passwords do not match';
        }
    }

    // If no errors, proceed to insert into database
    if (empty($firstNameErr) && empty($surnameErr) && empty($usernameErr) && 
        empty($emailErr) && empty($passwordErr) && empty($confirmPasswordErr)) {

        $stmt = $conn->prepare("INSERT INTO Users (first_name, surname, username, email, password) 
            VALUES (:first_name, :surname, :username, :email, :password)");
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':surname', $surname);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->execute();

        // Create an empty list for the user in Lists table
        $userId = $conn->lastInsertId();
        $stmt = $conn->prepare("INSERT INTO Lists (userID) VALUES (:userId)");
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Librette.</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bs-theme-overrides.css">
    <link rel="stylesheet" href="assets/css/Features-Cards-icons.css">
    <link rel="icon" type="image/x-icon" href="assets/img/logo2-removebg-preview.png">
</head>

<body>

    <div id="navbar">
        <?php include 'navbar.html'; ?>
    </div>

    <!-- Signup Form -->
    <section class="position-relative py-4 py-xl-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-md-8 col-xl-6 text-center mx-auto">
                    <h2></h2>
                </div>
            </div>
            <div class="row d-flex justify-content-center">
                <div class="col-md-6 col-xl-4">
                    <div class="card mb-5">
                        <div class="card-body d-flex flex-column align-items-center">
                            <h2 style="margin-bottom: 37px;">Sign Up</h2>
                            <form class="text-center" method="post">
                                <div class="mb-3">
                                    <input class="focus-ring form-control" type="text" name="first_name" placeholder="First Name" style="border-color: var(--bs-primary-text-emphasis);transform: perspective(0px);" autofocus="" required="" minlength="" maxlength="30">
                                    <span class="text-danger"><?php echo $firstNameErr; ?></span>
                                </div>
                                <div class="mb-3">
                                    <input class="focus-ring form-control" type="text" name="surname" placeholder="Surname" style="border-color: var(--bs-primary-text-emphasis);transform: perspective(0px);" autofocus="" required="" minlength="" maxlength="30">
                                    <span class="text-danger"><?php echo $surnameErr; ?></span>
                                </div>
                                <div class="mb-3">
                                    <input class="focus-ring form-control" type="text" name="username" placeholder="Username (unchangeable)" style="border-color: var(--bs-primary-text-emphasis);transform: perspective(0px);" autofocus="" required="" minlength="3" maxlength="25">
                                    <span class="text-danger"><?php echo $usernameErr; ?></span>
                                </div>
                                <div class="mb-3">
                                    <input class="focus-ring form-control" type="email" name="email" placeholder="Email" style="border-color: var(--bs-primary-text-emphasis);" autofocus="" required="">
                                    <span class="text-danger"><?php echo $emailErr; ?></span>
                                </div>
                                <div class="mb-3">
                                    <input class="focus-ring form-control" type="password" name="password" placeholder="Password" style="border-color: var(--bs-primary-text-emphasis);" autofocus="" required="" minlength="8" maxlength="24">
                                    <span class="text-danger"><?php echo $passwordErr; ?></span>
                                </div>
                                <div class="mb-3">
                                    <input class="focus-ring focus-ring-success form-control" type="password" name="confirmPassword" placeholder="Repeat Password" style="border-color: var(--bs-primary-text-emphasis);" autofocus="" required="">
                                    <span class="text-danger"><?php echo $confirmPasswordErr; ?></span>
                                </div>
                                <div class="mb-3">
                                    <button class="btn btn-primary d-block w-100" type="submit" style="background: var(--bs-info);">Sign Up</button>
                                </div>
                            </form>
                            <a href="login.php" style="display: inline;transform: perspective(0px);font-size: 14px;border-radius: 1px;">Have an account?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.html'; ?>

    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/bs-init.js"></script>
</body>

</html>
