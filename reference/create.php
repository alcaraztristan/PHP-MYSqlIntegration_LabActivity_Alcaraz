<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!empty($name) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            // Prepared statement using "?" as a structural placeholder
            $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            
            // Bind variables: "ss" signifies both parameters are string types
            $stmt->bind_param("ss", $name, $email);
            $stmt->execute();
            
            echo "User registered successfully! ID: " . $conn->insert_id;
            $stmt->close();
            
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // MySQL code for Duplicate entry error
                echo "Error: That email address is already registered.";
            } else {
                echo "An error occurred while saving the profile.";
            }
        }
    } else {
        echo "Please provide a valid name and email address.";
    }
}
?>

<form method="POST">
    <input type="text" name="name" placeholder="Full Name" required><br>
    <input type="email" name="email" placeholder="Email Address" required><br>
    <button type="submit">Create User</button>
</form>