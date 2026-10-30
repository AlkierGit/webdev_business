<?php
session_start();
require_once "db.php";

$message = "";

if (isset($_POST["login"])) {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email = ?"
    );
    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["first_name"] = $user["first_name"];
        $_SESSION["last_name"] = $user["last_name"];

        header("Location: index.php");
        exit;
    } else {
        $message = "Incorrect email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>WebDev Business</h1>
    <p>Login to Your Account</p>
</header>

<main>
    <h2>Login</h2>

    <div class="card">

        <?php if (isset($_GET["registered"])): ?>
            <p>Registration successful! You can now log in.</p>
        <?php endif; ?>

        <?php if ($message !== ""): ?>
            <p><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <label>Email Address</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" name="login">Login</button>

        </form>

        <p>Don't have an account?
            <a href="register.php">Register here</a>
        </p>

    </div>
</main>

</body>
</html>
