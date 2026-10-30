<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebDev Business Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>WebDev Business</h1>
    <p>Developer and Project Management System</p>
    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]) ?>!
    </p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="developers.php">Developers</a>
    <a href="projects.php">Projects</a>
    <a href="logout.php">Logout</a>
</nav>

<main>
    <h2>Welcome to WebDev Business</h2>
    <p>Manage your web developers and their projects in one place.</p>

    <div class="cards">
        <div class="card">
            <h3>Developers</h3>
            <p>Register and manage web developers.</p>
            <a href="developers.php">Manage Developers</a>
        </div>

        <div class="card">
            <h3>Projects</h3>
            <p>Organize projects assigned to developers.</p>
            <a href="projects.php">Manage Projects</a>
        </div>
    </div>

    <p class="status">Database connection successful!</p>
</main>

</body>
</html>
