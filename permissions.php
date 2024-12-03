<?php
include 'classes/Db.php';
include_once "classes/Pdo_.php";

$pdo = new Pdo_();
$db = new Db("127.0.0.1", "root", "", "news");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_permission'])) {
        $name = $_POST['name'];
        $db->addPermission($name);
    } elseif (isset($_POST['delete_permission'])) {
        $id = $_POST['id'];
        $db->deletePermission($id);
    }
}

$permissions = $db->getAllPermissions();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Permissions</title>
</head>
<body>
<h1>Manage Permissions</h1>
<form method="post">
    <input type="text" name="name" placeholder="Permission Name" required>
    <button type="submit" name="add_permission">Add Permission</button>
</form>
<h2>All Permissions</h2>
<ul>
    <?php foreach ($permissions as $permission): ?>
        <li>
            <?php echo htmlspecialchars($permission['name']); ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo $permission['id']; ?>">
                <button type="submit" name="delete_permission">Delete</button>
            </form>
        </li>
    <?php endforeach; ?>
</ul>
<a href="index.php">Back to Home</a>
</body>
</html>
