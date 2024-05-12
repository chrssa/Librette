<?php
include 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Dbh();
    $conn = $db->connect();

    $username = $_POST['Username'];
    $password = $_POST['password'];

    // Check if admin credentials
    if ($username === 'libretteAdmin' && $password === 'dbAdmin1234') {
        $_SESSION['loggedIn'] = true;
        $_SESSION['username'] = $username;
        header("Location: main-admin.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM Users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        if ($result['password'] === $password) {
            $_SESSION['loggedIn'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['userFirstName'] = $result['first_name'];
            $_SESSION['userSurname'] = $result['surname'];
            $_SESSION['userID'] = $result['userID'];;

            // Fetch listID
            $stmtListID = $conn->prepare("SELECT listID FROM Lists WHERE userID = :userID");
            $stmtListID->bindParam(':userID', $_SESSION['userID']);
            $stmtListID->execute();
            $listID = $stmtListID->fetchColumn();

            $_SESSION['listID'] = $listID;
            
            header("Location: main.php");
            exit();
        } else {
            echo "<script>alert('Invalid username or password. Try again.'); </script>";
        }
    } else {
        echo "<script>alert('Invalid username or password. Try again.'); </script>";
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

    <!-- Login Form -->
    <section class="position-relative py-4 py-xl-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-md-8 col-xl-6 text-center mx-auto">
                    <h2>Welcome back!</h2>
                </div>
            </div>
            <div class="row d-flex justify-content-center">
                <div class="col-md-6 col-xl-4">
                    <div class="card mb-5">
                        <div class="card-body d-flex flex-column align-items-center">
                            <div class="bs-icon-xl bs-icon-rounded bs-icon-primary bs-icon my-4" style="background: var(--bs-primary-text-emphasis);"><img src="assets/img/logo2-removebg-invert.png" width="59" height="59"></div>
                            <h1 style="margin-bottom: 26px;margin-top: -15px;">Login</h1>
                            <form class="text-center" method="post">
                                <div class="mb-3"><input class="form-control" type="text" name="Username" placeholder="Username" style="border-color: var(--bs-primary-text-emphasis);" required=""></div>
                                <div class="mb-3"><input class="form-control" type="password" name="password" placeholder="Password" style="border-color: var(--bs-primary-text-emphasis);" required=""></div>
                                <div class="mb-3"><button class="btn btn-primary d-block w-100" type="submit" style="background: var(--bs-info);">Login</button></div>
                            </form>
                            <a href="signup.php" style="display: inline;transform: perspective(0px);font-size: 14px;border-radius: 1px;">New to site?</a>
                            <p></p> <p style="display: inline;transform: perspective(0px);font-size: 14px;border-radius: 1px;">Forgot password? Contact admin.<p>
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