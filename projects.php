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
// Upload project image
function uploadProjectImage() {
   if (!isset($_FILES["image"]) || $_FILES["image"]["error"] === UPLOAD_ERR_NO_FILE) {
       return null;
   }
   if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
       throw new RuntimeException("Image upload failed.");
   }
   if ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
       throw new RuntimeException("Image must not exceed 5MB.");
   }
   $finfo = new finfo(FILEINFO_MIME_TYPE);
   $mime = $finfo->file($_FILES["image"]["tmp_name"]);
   $allowed = [
       "image/jpeg" => "jpg",
       "image/png" => "png",
       "image/gif" => "gif",
       "image/webp" => "webp"
   ];
   if (!isset($allowed[$mime])) {
       throw new RuntimeException("Only JPG, PNG, GIF, and WEBP images are allowed.");
   }
   $folder = __DIR__ . "/uploads/projects/";
   if (!is_dir($folder)) {
       mkdir($folder, 0755, true);
   }
   $filename = bin2hex(random_bytes(16)) . "." . $allowed[$mime];
   if (!move_uploaded_file(
       $_FILES["image"]["tmp_name"],
       $folder . $filename
   )) {
       throw new RuntimeException("Unable to save the image.");
   }
   return "uploads/projects/" . $filename;
}

// ADD PROJECT
if (isset($_POST["add"])) {
   try {
       $name = trim($_POST["project_name"]);
       $description = trim($_POST["description"]);
       $developer_id = (int)$_POST["developer_id"];
       $user_id = (int)$_SESSION["user_id"];
       if ($name !== "" && $developer_id > 0) {
           $image = uploadProjectImage();
           $stmt = $conn->prepare(
               "INSERT INTO projects
               (project_name, description, developer_id,
                added_by, last_updated_by, image)
               VALUES (?, ?, ?, ?, ?, ?)"
           );
           $stmt->execute([
               $name, $description, $developer_id,
               $user_id, $user_id, $image
           ]);
           $record_id = $conn->lastInsertId();
           $log = $conn->prepare(
               "INSERT INTO activity_logs
               (user_id, action, entity_type, record_id, details)
               VALUES (?, 'INSERT', 'project', ?, ?)"
           );
           $log->execute([
               $user_id, $record_id, "Added project: " . $name
           ]);
       }
   } catch (Throwable $ex) {
       die(e($ex->getMessage()));
   }
   header("Location: projects.php");
   exit;
}

// UPDATE PROJECT
if (isset($_POST["update"])) {
   try {
       $id = (int)$_POST["project_id"];
       $name = trim($_POST["project_name"]);
       $description = trim($_POST["description"]);
       $developer_id = (int)$_POST["developer_id"];
       $user_id = (int)$_SESSION["user_id"];
       if ($id > 0 && $name !== "" && $developer_id > 0) {
           $image = uploadProjectImage();
           if ($image !== null) {
               $stmt = $conn->prepare(
                   "UPDATE projects
                    SET project_name = ?,
                        description = ?,
                        developer_id = ?,
                        image = ?,
                        last_updated_by = ?,
                        last_updated = CURRENT_TIMESTAMP
                    WHERE project_id = ? AND deleted_at IS NULL"
               );
               $stmt->execute([
                   $name, $description, $developer_id,
                   $image, $user_id, $id
               ]);
           } else {
               $stmt = $conn->prepare(
                   "UPDATE projects
                    SET project_name = ?,
                        description = ?,
                        developer_id = ?,
                        last_updated_by = ?,
                        last_updated = CURRENT_TIMESTAMP
                    WHERE project_id = ? AND deleted_at IS NULL"
               );
               $stmt->execute([
                   $name, $description, $developer_id,
                   $user_id, $id
               ]);
           }
           $log = $conn->prepare(
               "INSERT INTO activity_logs
               (user_id, action, entity_type, record_id, details)
               VALUES (?, 'UPDATE', 'project', ?, ?)"
           );
           $log->execute([
               $user_id, $id, "Updated project: " . $name
           ]);
       }
   } catch (Throwable $ex) {
       die(e($ex->getMessage()));
   }
   header("Location: projects.php");
   exit;
}

// SOFT DELETE PROJECT
if (isset($_POST["delete"])) {
   $id = (int)$_POST["project_id"];
   $user_id = (int)$_SESSION["user_id"];
   if ($id > 0) {
       $stmt = $conn->prepare(
           "SELECT project_name FROM projects
            WHERE project_id = ? AND deleted_at IS NULL"
       );
       $stmt->execute([$id]);
       $project = $stmt->fetch();
       if ($project) {
           $stmt = $conn->prepare(
               "UPDATE projects
                SET deleted_at = CURRENT_TIMESTAMP,
                    deleted_by = ?
                WHERE project_id = ?"
           );
           $stmt->execute([$user_id, $id]);
           $log = $conn->prepare(
               "INSERT INTO activity_logs
               (user_id, action, entity_type, record_id, details)
               VALUES (?, 'DELETE', 'project', ?, ?)"
           );
           $log->execute([
               $user_id, $id,
               "Moved project to recycle bin: " . $project["project_name"]
           ]);
       }
   }
   header("Location: projects.php");
   exit;
}

// FIND PROJECT TO EDIT
$editProject = null;
if (isset($_GET["edit"])) {
   $id = (int)$_GET["edit"];
   $stmt = $conn->prepare(
       "SELECT * FROM projects
        WHERE project_id = ? AND deleted_at IS NULL"
   );
   $stmt->execute([$id]);
   $editProject = $stmt->fetch();
}

// GET DEVELOPERS
$stmt = $conn->query(
   "SELECT developer_id, developer_name
    FROM developers ORDER BY developer_name"
);
$developers = $stmt->fetchAll();

// SEARCH ACTIVE PROJECTS
$search = trim($_GET["search"] ?? "");
if ($search !== "") {
   $stmt = $conn->prepare(
       "SELECT p.*, d.developer_name
        FROM projects p
        JOIN developers d ON p.developer_id = d.developer_id
        WHERE p.deleted_at IS NULL
        AND (p.project_name LIKE ?
             OR p.description LIKE ?
             OR d.developer_name LIKE ?)
        ORDER BY p.project_id DESC"
   );
   $keyword = "%" . $search . "%";
   $stmt->execute([$keyword, $keyword, $keyword]);
} else {
   $stmt = $conn->query(
       "SELECT p.*, d.developer_name
        FROM projects p
        JOIN developers d ON p.developer_id = d.developer_id
        WHERE p.deleted_at IS NULL
        ORDER BY p.project_id DESC"
   );
}
$projects = $stmt->fetchAll();
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
   <a href="recycle_bin.php">Recycle Bin</a>
</nav>
<main>
   <h2><?= $editProject ? "Update Project" : "Add Project" ?></h2>
   <form method="POST" action="projects.php"
         class="card" enctype="multipart/form-data">
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
           <?php foreach ($developers as $dev): ?>
               <option value="<?= e($dev["developer_id"]) ?>"
                   <?= isset($editProject["developer_id"]) &&
                   $editProject["developer_id"] == $dev["developer_id"]
                   ? "selected" : "" ?>>
                   <?= e($dev["developer_name"]) ?>
               </option>
           <?php endforeach; ?>
       </select>
       <label>Project Image</label>
       <input type="file" name="image" accept="image/*">
       <?php if (!empty($editProject["image"])): ?>
           <p>Current Image:</p>
           <img src="<?= e($editProject["image"]) ?>"
                width="120" alt="Current project image">
       <?php endif; ?>
       <?php if ($editProject): ?>
           <button type="submit" name="update">Update Project</button>
           <a href="projects.php">Cancel</a>
       <?php else: ?>
           <button type="submit" name="add">Add Project</button>
       <?php endif; ?>
   </form>
   <h2>Registered Projects</h2>
   <form method="GET" action="projects.php" class="card">
       <label>Search Project</label>
       <input type="text" name="search"
              placeholder="Search project or developer..."
              value="<?= e($search) ?>">
       <button type="submit">Search</button>
       <a href="projects.php">Show All</a>
   </form>
   <div class="card">
       <table>
           <thead>
               <tr>
                   <th>ID</th>
                   <th>Image</th>
                   <th>Project</th>
                   <th>Description</th>
                   <th>Developer</th>
                   <th>Date Added</th>
                   <th>Added By</th>
                   <th>Last Updated By</th>
                   <th>Last Updated At</th>
                   <th>Actions</th>
               </tr>
           </thead>
           <tbody>
               <?php foreach ($projects as $row): ?>
                   <tr>
                       <td><?= e($row["project_id"]) ?></td>
                       <td>
                           <?php if (!empty($row["image"])): ?>
                               <img src="<?= e($row["image"]) ?>"
                                    width="80" alt="Project image">
                           <?php else: ?>
                               No image
                           <?php endif; ?>
                       </td>
                       <td><?= e($row["project_name"]) ?></td>
                       <td><?= e($row["description"]) ?></td>
                       <td><?= e($row["developer_name"]) ?></td>
                       <td><?= e($row["date_added"]) ?></td>
                       <td><?= e($row["added_by"]) ?></td>
                       <td><?= e($row["last_updated_by"]) ?></td>
                       <td><?= e($row["last_updated"]) ?></td>
                       <td>
                           <a href="projects.php?edit=<?= e($row["project_id"]) ?>">
                               Edit
                           </a>
                           <form method="POST" action="projects.php"
                                 onsubmit="return confirm('Move this project to the Recycle Bin?');">
                               <input type="hidden" name="project_id"
                                      value="<?= e($row["project_id"]) ?>">
                               <button type="submit" name="delete">
                                   Delete
                               </button>
                           </form>
                       </td>
                   </tr>
               <?php endforeach; ?>
               <?php if (empty($projects)): ?>
                   <tr>
                       <td colspan="10">No projects found.</td>
                   </tr>
               <?php endif; ?>
           </tbody>
       </table>
   </div>
</main>
</body>
</html>
