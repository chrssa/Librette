<?php
session_start();

include 'db_connect.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true || $_SESSION['username'] !== 'libretteAdmin') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';

$db = new Dbh();
$conn = $db->connect();

$users = [];

if ($conn) {
    try {
        // Fetch users' information
        $stmt = $conn->prepare("SELECT userID, first_name, surname, username, email, password FROM Users");
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1 style="font-size: 29px;">Manage Users</h1>

                    <div class="d-flex justify-content-center mt-3">
                        <a href="admin-books.php" class="btn btn-primary btn-sm me-2" role="button">Manage Books</a>
                        <a href="admin-authors.php" class="btn btn-primary btn-sm me-2" role="button">Manage Authors</a>
                        <a href="admin-genres.php" class="btn btn-primary btn-sm me-2" role="button">Manage Genres</a>
                        <a href="admin-publishers.php" class="btn btn-primary btn-sm" role="button">Manage Publishers</a>
                    </div>

                    <button class="btn btn-info btn-sm float-end mb-3 add-row" type="button" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-user-circle"></i><strong>&nbsp;Add User</strong></button>
                </div>

                <!-- Add User Modal -->
                <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">

                                <form action="admin-controls.php" method="POST">
                                    <input type="hidden" name="action" value="addUser">
                                    
                                    <!-- Add First Name -->
                                    <div class="mb-3">
                                        <label for="addFirstName" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="addFirstName" name="addFirstName" required>
                                        <?php
                                            if (isset($_SESSION['addErrors']['firstNameErr'])) {
                                                echo "<span class='text-danger'>{$_SESSION['addErrors']['firstNameErr']}</span>";
                                            }
                                        ?>
                                    </div>

                                    <!-- Add Surname -->
                                    <div class="mb-3">
                                        <label for="addSurname" class="form-label">Surname</label>
                                        <input type="text" class="form-control" id="addSurname" name="addSurname" required>
                                        <?php
                                            if (isset($_SESSION['addErrors']['surnameErr'])) {
                                                echo "<span class='text-danger'>{$_SESSION['addErrors']['surnameErr']}</span>";
                                            }
                                        ?>                                    
                                    </div>

                                    <!-- Add Username -->
                                    <div class="mb-3">
                                        <label for="addUsername" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="addUsername" name="addUsername" required>
                                        <?php
                                            if (isset($_SESSION['addErrors']['usernameErr']) && !empty($_SESSION['addErrors']['usernameErr'])) {
                                                echo "<span class='text-danger'>{$_SESSION['addErrors']['usernameErr']}</span>";
                                                unset($_SESSION['addErrors']['usernameErr']);
                                            }
                                        ?>
                                    </div>

                                    <!-- Add Email -->
                                    <div class="mb-3">
                                        <label for="addEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="addEmail" name="addEmail" required>
                                        <?php
                                            if (isset($_SESSION['addErrors']['emailErr']) && !empty($_SESSION['addErrors']['emailErr'])) {
                                                echo "<span class='text-danger'>{$_SESSION['addErrors']['emailErr']}</span>";
                                                unset($_SESSION['addErrors']['emailErr']);
                                            }
                                        ?>
                                    </div>

                                    <!-- Add Password -->
                                    <div class="mb-3">
                                        <label for="addPassword" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="addPassword" name="addPassword" required>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div class="mb-3">
                                        <label for="addConfirmPassword" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" id="addConfirmPassword" name="addConfirmPassword" required>
                                        <?php
                                            if (isset($_SESSION['editErrors']['confirmPasswordErr'])) {
                                                echo "<span class='text-danger'>{$_SESSION['editErrors']['confirmPasswordErr']}</span>";
                                            }
                                        ?>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Add User</button>
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
                                    <th>#</th>
                                    <th>NAME</th>
                                    <th>USERNAME</th>
                                    <th>EMAIL</th>
                                    <th>PASSWORD</th>
                                    <th class="text-center" style="width: 50px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['userID']; ?></td>
                                        <td><?php echo $user['first_name'] . ' ' . $user['surname']; ?></td>
                                        <td><?php echo $user['username']; ?></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td><?php echo $user['password']; ?></td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group" aria-label="Action Buttons">
                                                <a href="admin-controls.php?id=<?php echo $user['userID']; ?>" class="del-row"><i class="fas fa-trash" style="font-size: 20px;"></i></a>
                                                <a href="#" class="del-row" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['userID']; ?>">
                                                    <i class="fas fa-edit" style="font-size: 20px;margin-left: 9px;"></i></a>                          
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Edit User Modal -->
                                    <div class="modal fade" id="editUserModal<?php echo $user['userID']; ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?php echo $user['userID']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editUserModalLabel<?php echo $user['userID']; ?>">Edit User Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="admin-controls.php" method="POST">
                                                        <input type="hidden" name="userID" value="<?php echo $user['userID']; ?>">
                                                        <input type="hidden" name="action" value="editUser">
                                                        
                                                        <!-- Edit First Name -->
                                                        <div class="mb-3">
                                                            <label for="editFirstName_<?php echo $user['userID']; ?>" class="form-label">First Name</label>
                                                            <input type="text" class="form-control" id="editFirstName_<?php echo $user['userID']; ?>" name="editFirstName" value="<?php echo $user['first_name']; ?>">
                                                            <?php
                                                                if (isset($_SESSION['editErrors']['firstNameErr'])) {
                                                                    echo "<span class='text-danger'>{$_SESSION['editErrors']['firstNameErr']}</span>";
                                                                }
                                                            ?>
                                                        </div>

                                                        <!-- Edit Surname -->
                                                        <div class="mb-3">
                                                            <label for="editSurname_<?php echo $user['userID']; ?>" class="form-label">Surname</label>
                                                            <input type="text" class="form-control" id="editSurname_<?php echo $user['userID']; ?>" name="editSurname" value="<?php echo $user['surname']; ?>">
                                                            <?php
                                                                if (isset($_SESSION['editErrors']['surnameErr'])) {
                                                                    echo "<span class='text-danger'>{$_SESSION['editErrors']['surnameErr']}</span>";
                                                                }
                                                            ?>
                                                        </div>

                                                        <!-- Edit Username -->
                                                        <div class="mb-3">
                                                            <label for="editUsername_<?php echo $user['userID']; ?>" class="form-label">Username</label>
                                                            <input type="text" class="form-control" id="editUsername_<?php echo $user['userID']; ?>" name="editUsername" value="<?php echo $user['username']; ?>">
                                                            <?php
                                                                if (isset($_SESSION['editErrors']['usernameErr'])) {
                                                                    echo "<span class='text-danger'>{$_SESSION['editErrors']['usernameErr']}</span>";
                                                                }
                                                            ?>
                                                        </div>

                                                        <!-- Edit Email -->
                                                        <div class="mb-3">
                                                            <label for="editEmail_<?php echo $user['userID']; ?>" class="form-label">Email</label>
                                                            <input type="email" class="form-control" id="editEmail_<?php echo $user['userID']; ?>" name="editEmail" value="<?php echo $user['email']; ?>">
                                                            <?php
                                                            if (isset($_SESSION['editErrors']['emailErr'])) {
                                                                echo "<span class='text-danger'>{$_SESSION['editErrors']['emailErr']}</span>";
                                                            }
                                                            ?>
                                                        </div>

                                                        <!-- Edit Password -->
                                                        <div class="mb-3">
                                                            <label for="editPassword_<?php echo $user['userID']; ?>" class="form-label">Password</label>
                                                            <input type="password" class="form-control" id="editPassword_<?php echo $user['userID']; ?>" name="editPassword" value="<?php echo $user['password']; ?>">
                                                        </div>

                                                        <!-- Confirm Password -->
                                                        <div class="mb-3">
                                                            <label for="editConfirmPassword_<?php echo $user['userID']; ?>" class="form-label">Confirm Password</label>
                                                            <input type="password" class="form-control" id="editConfirmPassword_<?php echo $user['userID']; ?>" name="editConfirmPassword" value="<?php echo $user['password']; ?>">
                                                            <?php
                                                                if (isset($_SESSION['editErrors']['confirmPasswordErr'])) {
                                                                    echo "<span class='text-danger'>{$_SESSION['editErrors']['confirmPasswordErr']}</span>";
                                                                }
                                                            ?>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Save changes</button>
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