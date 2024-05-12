<?php
session_start();

if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php'; 

$username = $_SESSION['username'] ?? '';

$db = new Dbh();
$conn = $db->connect();

// Fetch user details
$stmtUser = $conn->prepare("SELECT userID, first_name, surname, username, email, password FROM Users WHERE username = :username");
$stmtUser->bindParam(':username', $username);
$stmtUser->execute();
$userDetails = $stmtUser->fetch(PDO::FETCH_ASSOC);

$userFirstName = $userDetails['first_name'];

// Fetch the collection of books associated with the user
$stmt = $conn->prepare("SELECT lb.ISBN, b.book_title, 
    GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', a.surname) SEPARATOR ', ') AS authors,
    GROUP_CONCAT(DISTINCT g.genre SEPARATOR ', ') AS genres, 
    GROUP_CONCAT(DISTINCT p.publisher_name SEPARATOR ', ') AS publishers,
    lb.book_price, lb.purchase_date, lb.status
    FROM Lists_Books lb
    INNER JOIN Books b ON lb.ISBN = b.ISBN
    LEFT JOIN books_authors ba ON lb.ISBN = ba.ISBN
    LEFT JOIN Authors a ON ba.authorID = a.authorID
    LEFT JOIN books_genres bg ON lb.ISBN = bg.ISBN
    LEFT JOIN Genres g ON bg.genreID = g.genreID
    LEFT JOIN books_publishers bp ON lb.ISBN = bp.ISBN
    LEFT JOIN Publishers p ON bp.publisherID = p.publisherID
    INNER JOIN Lists l ON lb.listID = l.listID
    INNER JOIN Users u ON l.userID = u.userID
    WHERE u.username = :username
    GROUP BY lb.ISBN, b.book_title, lb.book_price, lb.purchase_date, lb.status");


$stmt->bindParam(':username', $username);
$stmt->execute();

function getStatusColor($status) {
    switch ($status) {
        case 'COMPLETED':
            return 'btn-success'; 
        case 'ONGOING':
            return 'btn-primary'; 
        case 'PAUSED':
            return 'btn-secondary'; 
        case 'DROPPED':
            return 'btn-danger'; 
        default:
            return 'btn-dark'; 
    }
}

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Librette.</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/bs-theme-overrides.css">
    <link rel="stylesheet" href="assets/css/Features-Cards-icons.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="assets/img/logo2-removebg-preview.png">
</head>

<body>
    <section>
        <div></div>
        <nav class="navbar navbar-expand-md sticky-top bg-primary-subtle py-3" style="position: sticky;border-width: 3px;">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="main.php">
                    <span class="bs-icon-sm bs-icon-rounded bs-icon-primary d-flex justify-content-center align-items-center me-2 bs-icon">
                    <img width="24" height="24" src="assets/img/logo2-removebg-invert.png">
                    <path fill-rule="evenodd" d="M0 10.5A1.5 1.5 0 0 1 1.5 9h1A1.5 1.5 0 0 1 4 10.5v1A1.5 1.5 0 0 1 2.5 13h-1A1.5 1.5 0 0 1 0 11.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm10.5.5A1.5 1.5 0 0 1 13.5 9h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zM6 4.5A1.5 1.5 0 0 1 7.5 3h1A1.5 1.5 0 0 1 10 4.5v1A1.5 1.5 0 0 1 8.5 7h-1A1.5 1.5 0 0 1 6 5.5v-1zM7.5 4a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"></path>
                    <path d="M6 4.5H1.866a1 1 0 1 0 0 1h2.668A6.517 6.517 0 0 0 1.814 9H2.5c.123 0 .244.015.358.043a5.517 5.517 0 0 1 3.185-3.185A1.503 1.503 0 0 1 6 5.5v-1zm3.957 1.358A1.5 1.5 0 0 0 10 5.5v-1h4.134a1 1 0 1 1 0 1h-2.668a6.517 6.517 0 0 1 2.72 3.5H13.5c-.123 0-.243.015-.358.043a5.517 5.517 0 0 0-3.185-3.185z"></path>
                    </svg></span><span><strong>Librette.</strong></span>
                </a>

                <h1 style="font-size: 29px; flex-grow: 1; text-align: center;"><?php echo htmlspecialchars($userFirstName) ?>'s Collection</h1>
                
                <a href="#" class="del-row" data-bs-toggle="modal" data-bs-target="#editAccountModal">
                    <i class="material-icons" style="font-size: 26px; color: var(--bs-primary-text-emphasis);">settings</i>
                </a>

                <!-- Edit Account Modal -->
                <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" data-bs-backdrop="false" aria-hidden="true">
                    <div class="modal-dialog"> 
                        <div class="modal-content"> 
                            <div class="modal-header">
                                <h5 class="modal-title" id="editAccountModalLabel">Edit Account</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="main-accountControls.php" method="POST">
                                <input type="hidden" name="userID" value="<?php echo htmlspecialchars($userDetails['userID']); ?>">
                                <input type="hidden" name="action" value="editAccount">                                

                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="editFirstName" class="form-label">First Name</label>
                                        <input type="text" class="form-control border border-primary" id="editFirstName" name="editFirstName" value="<?php echo htmlspecialchars($userFirstName); ?>" required>

                                        <?php if(isset($_SESSION['editErrors']['firstNameErr'])): ?>
                                            <div class="text-danger"><?php echo $_SESSION['editErrors']['firstNameErr']; ?></div>
                                        <?php endif; ?>

                                    </div>

                                    <div class="mb-3">
                                    <label for="editSurname" class="form-label">Surname</label>
                                    <input type="text" class="form-control border border-primary" id="editSurname" name="editSurname" value="<?php echo htmlspecialchars($userDetails['surname']); ?>" required>
                                        
                                    <?php if(isset($_SESSION['editErrors']['surnameErr'])): ?>
                                        <div class="text-danger"><?php echo $_SESSION['editErrors']['surnameErr']; ?></div>
                                    <?php endif; ?>

                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="editUsername" class="form-label">Username</label>
                                        <input type="text" class="form-control border border-primary" id="editUsername" name="editUsername" value="<?php echo htmlspecialchars($userDetails['username']); ?>" required readonly>
                                        <p class="text-info">Contact admin to change username.</p>
                                        <?php if(isset($_SESSION['editErrors']['usernameErr'])): ?>
                                            <div class="text-danger"><?php echo $_SESSION['editErrors']['usernameErr']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label for="editEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control border border-primary" id="editEmail" name="editEmail" value="<?php echo htmlspecialchars($userDetails['email']); ?>" required>
                                        <?php if(isset($_SESSION['editErrors']['emailErr'])): ?>
                                            <div class="text-danger"><?php echo $_SESSION['editErrors']['emailErr']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label for="editPassword" class="form-label">Password</label>
                                        <input type="password" class="form-control border border-primary" id="editPassword" name="editPassword" value="<?php echo htmlspecialchars($userDetails['password']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="editConfirmPassword" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control border border-primary" id="editConfirmPassword" name="editConfirmPassword" value="<?php echo htmlspecialchars($userDetails['password']); ?>" required>
                                        <?php if(isset($_SESSION['editErrors']['confirmPasswordErr'])): ?>
                                            <div class="text-danger"><?php echo $_SESSION['editErrors']['confirmPasswordErr']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary" name="editAccount">Save changes</button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
                
                <form action="logout.php" method="post">
                    <button class="btn btn-outline-info me-2" type="submit" style="font-size: 13px; margin-left: 10px;">Logout</button>
                </form>

            </div>
        </nav>
    </section>

    <!-- Display list -->
    <section style="padding: 9px;margin: 0px;margin-top: 18px;margin-right: 60px;margin-left: 59px;">
        <div class="container">
            <div class="row">
                <div class="col-xl-12 text-center mb-3">

                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="fas fa-trash"></i> Delete Account
                    </button>
                    
                    <!-- Delete Account -->
                    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteAccountModalLabel">Confirm Account Deletion</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <p>Are you sure you want to delete your account? </p> <p>This action cannot be undone.</p>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <form id="deleteAccountForm" action="main-accountControls.php" method="POST">
                                        <input type="hidden" name="action" value="deleteAccount">
                                        <input type="hidden" name="userId" value="<?php echo htmlspecialchars($userDetails['userID']); ?>">
                                        <button type="submit" class="btn btn-danger" id="confirmDeleteBtn" name="deleteAccountBtn">Delete Account</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button class="btn btn-info btn-sm float-end mb-3 add-row" type="button" data-bs-toggle="modal" data-bs-target="#addBookModal">
                        <i class="fas fa-plus"></i><strong>&nbsp;Add Book</strong>
                    </button>
                </div>

                <!-- Add Book Modal -->
                <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addBookModalLabel">Search and Add Book</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form id="addBookForm" action="main-bookControls.php" method="POST">
                                <input type="hidden" name="action" value="addBook">

                                <div class="modal-body">
                                    <div class="mb-3">
                                         <label for="isbn" class="form-label">ISBN-13</label>
                                        <input type="text" class="form-control border border-primary" id="isbn" name="isbn" required placeholder="xxx-x-xxx-xxxxx-x">
                                    </div>
                                        <?php
                                            if (isset($_SESSION['addError'])) {
                                                echo '<div class="text-danger" role="alert">' . htmlspecialchars($_SESSION['addError']) . '</div>';
                                                unset($_SESSION['addError']);
                                            }
                                        ?>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary" name="addBook">Confirm</button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>

                <!-- Display table-->
                <div class="col">
                    <div class="table-responsive" id="myTable">
                        <table class="table table-striped table-sm table-bordered">
                            <thead>
                                <tr class="table-header">
                                    <th class="wider-cell">ISBN-13</th>
                                    <th class="wider-cell">Book Title</th>
                                    <th>Author/s</th>
                                    <th>Genre/s</th>
                                    <th>Publisher/s</th>
                                    <th>Transaction</th>
                                    <th>Status</th>
                                    <th class="action-cell">Action</th>
                                </tr>
                            </thead> 
                            <tbody> 
                                <?php foreach ($books as $book) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book['ISBN']); ?></td>
                                        <td><?php echo htmlspecialchars($book['book_title']); ?></td>
                                        <td><?php echo htmlspecialchars($book['authors']); ?></td>
                                        <td><?php echo htmlspecialchars($book['genres']); ?></td>
                                        <td><?php echo htmlspecialchars($book['publishers']); ?></td>
                                        <td>
                                            <?php
                                            $transactionInfo = '';
                                            if (!empty($book['book_price']) && !empty($book['purchase_date'])) {
                                                $transactionInfo = '₱' . htmlspecialchars($book['book_price']) . ' on ' . htmlspecialchars($book['purchase_date']);
                                            } elseif (!empty($book['book_price']) && empty($book['purchase_date'])){
                                                $transactionInfo = '₱' . htmlspecialchars($book['book_price']);
                                            } elseif (empty($book['book_price']) && !empty($book['purchase_date'])){
                                                $transactionInfo = htmlspecialchars($book['purchase_date']);
                                            }else {
                                                $transactionInfo = '-none-';
                                            }
                                            echo $transactionInfo;
                                            ?>
                                        </td>

                                        <td>
                                            <form action="main-bookControls.php" method="POST">
                                                <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($book['ISBN']); ?>">
                                                <select name="status" onchange="this.form.submit()" class="btn <?php echo getStatusColor($book['status']); ?>">
                                                    <option value="COMPLETED" <?php echo ($book['status'] === 'COMPLETED') ? 'selected' : ''; ?> class="btn-success">COMPLETED</option>
                                                    <option value="ONGOING" <?php echo ($book['status'] === 'ONGOING') ? 'selected' : ''; ?> class="btn-primary">ONGOING</option>
                                                    <option value="PAUSED" <?php echo ($book['status'] === 'PAUSED') ? 'selected' : ''; ?> class="btn-secondary">PAUSED</option>
                                                    <option value="DROPPED" <?php echo ($book['status'] === 'DROPPED') ? 'selected' : ''; ?> class="btn-danger">DROPPED</option>
                                                </select>
                                                <input type="hidden" name="action" value="updateStatus">
                                            </form>
                                        </td>
                                        <td class="text-center">
                                            <a class="del-row" href="main-bookControls.php?action=deleteBook&isbn=<?php echo htmlspecialchars($book['ISBN']); ?>">
                                                <i class="fas fa-trash" style="font-size: 20px;"></i>
                                            </a>

                                            <a class="edit-row" href="#editBookModal<?php echo htmlspecialchars($book['ISBN']); ?>" data-bs-toggle="modal">
                                                <i class="fas fa-edit" style="font-size: 20px; margin-left: 9px;"></i>
                                            </a>

                                            <!-- Edit Book Details Modal -->
                                            <div class="modal fade" id="editBookModal<?php echo htmlspecialchars($book['ISBN']); ?>" 
                                                tabindex="-1" aria-labelledby="editBookModalLabel<?php echo htmlspecialchars($book['ISBN']); ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editBookModalLabel<?php echo htmlspecialchars($book['ISBN']); ?>">Edit Book Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form action="main-bookControls.php" method="POST">
                                                                <input type="hidden" name="action" value="editBook">
                                                                <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($book['ISBN']); ?>">

                                                                <div class="mb-3">
                                                                    <label for="editBookPrice" class="form-label">Book Price</label>
                                                                    <input type="text" class="form-control border border-primary" id="editBookPrice" name="editBookPrice" value="<?php echo isset($book['book_price']) ? htmlspecialchars($book['book_price']) : ''; ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="editPurchaseDate" class="form-label">Purchase Date</label>
                                                                    <input type="date" class="form-control border border-primary" id="editPurchaseDate" name="editPurchaseDate" value="<?php echo isset($book['purchase_date']) ? htmlspecialchars($book['purchase_date']) : ''; ?>">
                                                                    
                                                                    <?php
                                                                        if (isset($_SESSION['priceError'])) {
                                                                            echo '<div class="alert text-danger">' . htmlspecialchars($_SESSION['priceError']) . '</div>';
                                                                            unset($_SESSION['priceError']);
                                                                        }
                                                                    ?>

                                                                </div>

                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary" name="editBook">Save Changes</button>
                                                                </div>

                                                            </form>
                                                            <p></p> <p style="display: inline;transform: perspective(0px);font-size: 14px;border-radius: 1px;">Wrong book details? Contact admin.</p>
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
                </div>
            </div> 
        </div>
    </section>

    <?php include 'footer.html'; ?>

    <script src="assets/js/bs-init.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    
    <script>
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            document.getElementById('deleteAccountForm').submit();
        });
    </script>

</body>

</html>