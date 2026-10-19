<?php
require_once "db.php";
function e($value) {
   return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
// Insert project
if (isset($_POST["add"])) {
   $name = trim($_POST["project_name"]);
   $description = trim($_POST["description"]);
   $developer_id = (int)$_POST["developer_id"];
   if ($name !== "" && $developer_id > 0) {
       $stmt = $conn->prepare(
           "INSERT INTO projects (project_name, description, developer_id)
            VALUES (?, ?, ?)"
       );
       $stmt->bind_param("ssi", $name, $description, $developer_id);
       $stmt->execute();
       $stmt->close();
   }
   header("Location: projects.php");
   exit;
}
// Update project
if (isset($_POST["update"])) {
   $id = (int)$_POST["project_id"];
   $name = trim($_POST["project_name"]);
   $description = trim($_POST["description"]);
   $developer_id = (int)$_POST["developer_id"];
   if ($id > 0 && $name !== "" && $developer_id > 0) {
       $stmt = $conn->prepare(
           "UPDATE projects
            SET project_name = ?, description = ?, developer_id = ?
            WHERE project_id = ?"
       );
       $stmt->bind_param(
           "ssii", $name, $description, $developer_id, $id
       );
       $stmt->execute();
       $stmt->close();
   }
   header("Location: projects.php");
   exit;
}
// Delete project
if (isset($_POST["delete"])) {
   $id = (int)$_POST["project_id"];
   if ($id > 0) {
       $stmt = $conn->prepare(
           "DELETE FROM projects WHERE project_id = ?"
       );
       $stmt->bind_param("i", $id);
       $stmt->execute();
       $stmt->close();
   }
   header("Location: projects.php");
   exit;
}
// Find project to edit
$editProject = null;
if (isset($_GET["edit"])) {
   $id = (int)$_GET["edit"];
   $stmt = $conn->prepare(
       "SELECT * FROM projects WHERE project_id = ?"
   );
   $stmt->bind_param("i", $id);
   $stmt->execute();
   $editProject = $stmt->get_result()->fetch_assoc();
   $stmt->close();
}
// Get developers for dropdown
$developers = $conn->query(
   "SELECT developer_id, developer_name FROM developers
    ORDER BY developer_name"
);
// Get projects with developer names
$projects = $conn->query(
   "SELECT p.*, d.developer_name
    FROM projects p
    JOIN developers d ON p.developer_id = d.developer_id
    ORDER BY p.project_id DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Manage Projects</title>
   <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
   <h1>WebDev Business</h1>
   <p>Project Management</p>
</header>
<nav>
   <a href="index.php">Home</a>
   <a href="developers.php">Developers</a>
   <a href="projects.php">Projects</a>
</nav>
<main>
   <h2><?= $editProject ? "Update Project" : "Add Project" ?></h2>
   <form method="POST" action="projects.php" class="card">
       <?php if ($editProject): ?>
           <input type="hidden" name="project_id"
                  value="<?= e($editProject["project_id"]) ?>">
       <?php endif; ?>
       <label>Project Name</label>
       <input type="text" name="project_name"
              placeholder="Enter project name"
              value="<?= e($editProject["project_name"] ?? "") ?>"
              required>
       <label>Description</label>
       <input type="text" name="description"
              placeholder="Enter project description"
              value="<?= e($editProject["description"] ?? "") ?>">
       <label>Assigned Developer</label>
       <select name="developer_id" required>
           <option value="">Choose a developer</option>
           <?php while ($dev = $developers->fetch_assoc()): ?>
               <option value="<?= e($dev["developer_id"]) ?>"
                   <?= isset($editProject["developer_id"]) &&
                       $editProject["developer_id"] == $dev["developer_id"]
                       ? "selected" : "" ?>>
                   <?= e($dev["developer_name"]) ?>
               </option>
           <?php endwhile; ?>
       </select>
       <?php if ($editProject): ?>
           <button type="submit" name="update">Update Project</button>
           <a href="projects.php">Cancel</a>
       <?php else: ?>
           <button type="submit" name="add">Add Project</button>
       <?php endif; ?>
   </form>
   <h2>Registered Projects</h2>
   <div class="card">
       <table>
           <thead>
               <tr>
                   <th>ID</th>
                   <th>Project</th>
                   <th>Description</th>
                   <th>Developer</th>
                   <th>Actions</th>
               </tr>
           </thead>
           <tbody>
               <?php while ($row = $projects->fetch_assoc()): ?>
                   <tr>
                       <td><?= e($row["project_id"]) ?></td>
                       <td><?= e($row["project_name"]) ?></td>
                       <td><?= e($row["description"]) ?></td>
                       <td><?= e($row["developer_name"]) ?></td>
                       <td>
                           <a href="projects.php?edit=<?= e($row["project_id"]) ?>">
                               Edit
                           </a>
                           <form method="POST" action="projects.php"
                                 onsubmit="return confirm('Delete this project?');">
                               <input type="hidden" name="project_id"
                                      value="<?= e($row["project_id"]) ?>">
                               <button type="submit" name="delete">
                                   Delete
                               </button>
                           </form>
                       </td>
                   </tr>
               <?php endwhile; ?>
           </tbody>
       </table>
   </div>
</main>
</body>
</html>