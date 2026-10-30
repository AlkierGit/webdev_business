<?php
require_once "db.php";

$message = "";

if (isset($_POST["register"])) {
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $address = trim($_POST["address"]);
    $age = (int) $_POST["age"];
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (
        $first_name === "" || $last_name === "" ||
        $address === "" || $age < 1 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {
        $message = "Please enter valid information. Password must be at least 8 characters.";
    } else {
        $check = $conn->prepare(
            "SELECT user_id FROM users WHERE email = ?"
        );
        $check->execute([$email]);

        if ($check->fetch()) {
            $message = "Email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users
                (first_name, last_name, address, age, email, password)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $first_name,
                $last_name,
                $address,
                $age,
                $email,
                $hashed_password
            ]);

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>WebDev Business</h1>
    <p>Create Your Account</p>
</header>

<main>
    <h2>Register</h2>

    <div class="card">
        <?php if ($message !== ""): ?>
            <p><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" action="register.php">

            <label>First Name</label>
            <input type="text" name="first_name" required>

            <label>Last Name</label>
            <input type="text" name="last_name" required>

            <label>Address</label>
            <input type="text" name="address" required>

            <label>Age</label>
            <input type="number" name="age" min="1" required>

            <label>Email Address</label>
            <input type="email" name="email" required>

            <label>Password (at least 8 characters)</label>
            <input type="password" name="password" minlength="8" required>

            <button type="submit" name="register">
                Create Account
            </button>

        </form>

        <p>Already have an account?
            <a href="login.php">Login here</a>
        </p>
    </div>
</main>

</body>
</html>
