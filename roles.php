<?php
include 'classes/Db.php';
include_once "classes/Pdo_.php";

$pdo = new Pdo_();
$db = new Db("127.0.0.1", "root", "", "news");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_role'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $db->addRole($name, $description);
    } elseif (isset($_POST['delete_role'])) {
        $id = $_POST['id'];
        $db->deleteRole($id);
    }
}

$roles = $db->getAllRoles();
?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Manage Roles</title>
    </head>
    <body>
    <h1>Manage Roles</h1>
    <form method="post">
        <input type="text" name="name" placeholder="Role Name" required>
        <input type="text" name="description" placeholder="Description">
        <button type="submit" name="add_role">Add Role</button>
    </form>
    <h2>All Roles</h2>
    <ul>
        <?php foreach ($roles as $role): ?>
            <li>
                <?php echo htmlspecialchars($role['role_name']); ?> - <?php echo htmlspecialchars($role['description'] ?? ''); ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $role['id']; ?>">
                    <button type="submit" name="delete_role">Delete</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="index.php">Back to Home</a>
    </body>
    </html>
<?php
