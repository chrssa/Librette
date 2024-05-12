<?php
    if (!class_exists('Dbh')) {
        class Dbh {
            // Establish a database connection
            public function connect() {
                try {
                    // Database connection parameters
                    $username = "root";
                    $password = "qwerty";
                    $dbname = "Librette";

                    $dbh = new PDO('mysql:host=localhost;dbname=' . $dbname, $username, $password);

                    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    return $dbh;
                } catch (PDOException $e) {
                    echo "Connection failed: " . $e->getMessage();
                    return null;
                }
            }
        }
    }
?>
