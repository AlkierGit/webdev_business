<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";


// Add a new developer
if (isset($_POST["add"])) {
    $name = trim($_POST["developer_name"]);
    $email = trim($_POST["email"]);
    $user_id = $_SESSION["user_id"];

    if ($name !== "" && $email !== "") {
        $stmt = $conn->prepare(
            "INSERT INTO developers
            (developer_name, email, added_by, updated_by)
            VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $email, $user_id, $user_id]);

        $record_id = $conn->lastInsertId();

        $log = $conn->prepare(
            "INSERT INTO activity_logs
            (user_id, action, entity_type, record_id, details)
            VALUES (?, 'INSERT', 'developer', ?, ?)"
        );
        $log->execute([
            $user_id,
            $record_id,
            "Added developer: " . $name
        ]);
    }

    header("Location: developers.php");
    exit;
}



// Update a developer
if (isset($_POST["update"])) {
    $id = (int) $_POST["developer_id"];
    $name = trim($_POST["developer_name"]);
    $email = trim($_POST["email"]);
    $user_id = $_SESSION["user_id"];

    if ($id > 0 && $name !== "" && $email !== "") {
        $stmt = $conn->prepare(
            "UPDATE developers
             SET developer_name = ?, email = ?, updated_by = ?
             WHERE developer_id = ?"
        );
        $stmt->execute([$name, $email, $user_id, $id]);

        $log = $conn->prepare(
            "INSERT INTO activity_logs
            (user_id, action, entity_type, record_id, details)
            VALUES (?, 'UPDATE', 'developer', ?, ?)"
        );
        $log->execute([
            $user_id,
            $id,
            "Updated developer: " . $name
        ]);
    }

    header("Location: developers.php");
    exit;
}


// Delete a developer
if (isset($_POST["delete"])) {
    $id = (int) $_POST["developer_id"];
    $user_id = $_SESSION["user_id"];

    if ($id > 0) {
        // Get developer details before deleting
        $stmt = $conn->prepare(
            "SELECT developer_name FROM developers WHERE developer_id = ?"
        );
        $stmt->execute([$id]);
        $developer = $stmt->fetch();

        if ($developer) {
            // Record the deletion
            $log = $conn->prepare(
                "INSERT INTO activity_logs
                (user_id, action, entity_type, record_id, details)
                VALUES (?, 'DELETE', 'developer', ?, ?)"
            );
            $log->execute([
                $user_id,
                $id,
                "Deleted developer: " . $developer["developer_name"]
            ]);

            // Delete the developer
            $stmt = $conn->prepare(
                "DELETE FROM developers WHERE developer_id = ?"
            );
            $stmt->execute([$id]);
        }
    }

    header("Location: developers.php");
    exit;
}


// Get developer information for editing
$editDeveloper = null;

if (isset($_GET["edit"])) {
    $id = (int) $_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM developers WHERE developer_id = ?"
    );
    $stmt->execute([$id]);
    $editDeveloper = $stmt->fetch();
}

// Get all developers
$stmt = $conn->query(
    "SELECT * FROM developers ORDER BY developer_id DESC"
);
$developers = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Developers</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>WebDev Business</h1>
    <p>Developer Management</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="developers.php">Developers</a>
    <a href="projects.php">Projects</a>
</nav>

<main>
    <h2>
        <?= $editDeveloper ? "Update Developer" : "Register Developer" ?>
    </h2>

    <form method="POST" action="developers.php" class="card">

        <?php if ($editDeveloper): ?>
            <input type="hidden" name="developer_id"
                   value="<?= e($editDeveloper["developer_id"]) ?>">
        <?php endif; ?>

        <label>Developer Name</label>
        <input type="text" name="developer_name"
               placeholder="Enter developer name"
               value="<?= e($editDeveloper["developer_name"] ?? "") ?>"
               required>

        <label>Email Address</label>
        <input type="email" name="email"
               placeholder="Enter email address"
               value="<?= e($editDeveloper["email"] ?? "") ?>"
               required>

        <?php if ($editDeveloper): ?>
            <button type="submit" name="update">Update Developer</button>
            <a href="developers.php">Cancel</a>
        <?php else: ?>
            <button type="submit" name="add">Add Developer</button>
        <?php endif; ?>

    </form>

    <h2>Registered Developers</h2>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($developers as $row): ?>
                    <tr>
                        <td><?= e($row["developer_id"]) ?></td>
                        <td><?= e($row["developer_name"]) ?></td>
                        <td><?= e($row["email"]) ?></td>
                        <td>
                            <a href="developers.php?edit=<?= e($row["developer_id"]) ?>">
                                Edit
                            </a>

                            <form method="POST" action="developers.php"
                                  onsubmit="return confirm('Delete this developer and all their projects?');">

                                <input type="hidden" name="developer_id"
                                       value="<?= e($row["developer_id"]) ?>">

                                <button type="submit" name="delete">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
