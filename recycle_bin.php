<?php
session_start();
if (!isset($_SESSION["user_id"])) {
   header("Location: login.php");
   exit;
}
require_once "db.php";
function e($value) {
   return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
// RESTORE PROJECT
if (isset($_POST["restore"])) {
   $id = (int)$_POST["project_id"];
   $user_id = (int)$_SESSION["user_id"];
   if ($id > 0) {
       $stmt = $conn->prepare(
           "SELECT project_name FROM projects
            WHERE project_id = ? AND deleted_at IS NOT NULL"
       );
       $stmt->execute([$id]);
       $project = $stmt->fetch();
       if ($project) {
           $stmt = $conn->prepare(
               "UPDATE projects
                SET deleted_at = NULL,
                    deleted_by = NULL
                WHERE project_id = ?"
           );
           $stmt->execute([$id]);
           $log = $conn->prepare(
               "INSERT INTO activity_logs
               (user_id, action, entity_type, record_id, details)
               VALUES (?, 'RESTORE', 'project', ?, ?)"
           );
           $log->execute([
               $user_id,
               $id,
               "Restored project: " . $project["project_name"]
           ]);
       }
   }
   header("Location: recycle_bin.php");
   exit;
}

// GET DELETED PROJECTS
$stmt = $conn->query(
   "SELECT p.*, d.developer_name,
           u.first_name, u.last_name
    FROM projects p
    LEFT JOIN developers d
       ON p.developer_id = d.developer_id
    LEFT JOIN users u
       ON p.deleted_by = u.user_id
    WHERE p.deleted_at IS NOT NULL
    ORDER BY p.deleted_at DESC"
);
$deletedProjects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Recycle Bin</title>
   <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
   <h1>WebDev Business</h1>
   <p>Project Recycle Bin</p>
</header>
<nav>
   <a href="index.php">Home</a>
   <a href="developers.php">Developers</a>
   <a href="projects.php">Projects</a>
   <a href="recycle_bin.php">Recycle Bin</a>
</nav>
<main>
   <h2>Deleted Projects</h2>
   <p>
       Here you can see deleted projects, who deleted them,
       and when they were deleted.
   </p>
   <div class="card">
       <table>
           <thead>
              <tr>
                   <th>ID</th>
                   <th>Project Name</th>
                   <th>Developer</th>
                   <th>Deleted By</th>
                   <th>Deleted At</th>
                   <th>Action</th>
               </tr>
           </thead>
           <tbody>
               <?php foreach ($deletedProjects as $row): ?>
                   <tr>
                       <td><?= e($row["project_id"]) ?></td>
                       <td><?= e($row["project_name"]) ?></td>
                       <td><?= e($row["developer_name"] ?? "Unknown") ?></td>
                       <td>
                           <?= e(trim(
                               ($row["first_name"] ?? "") . " " .
                               ($row["last_name"] ?? "")
                           ) ?: "Unknown") ?>
                       </td>
                       <td><?= e($row["deleted_at"]) ?></td>
                       <td>
                           <form method="POST"
                                 action="recycle_bin.php"
                                 onsubmit="return confirm('Restore this project?');">
                               <input type="hidden"
                                      name="project_id"
                                      value="<?= e($row["project_id"]) ?>">
                               <button type="submit" name="restore">
                                   Restore
                               </button>
                           </form>
                       </td>
                   </tr>
               <?php endforeach; ?>
               <?php if (empty($deletedProjects)): ?>
                   <tr>
                       <td colspan="6">Recycle Bin is empty.</td>
                   </tr>
               <?php endif; ?>
           </tbody>
       </table>
   </div>
   <p>
       <a href="projects.php">Back to Projects</a>
   </p>
</main>
</body>
</html>
