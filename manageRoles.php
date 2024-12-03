<?php

include 'classes/Db.php';
include_once "classes/Pdo_.php";

$pdo = new Pdo_();
$db = new Db("127.0.0.1", "root", "", "news");


$roles =  $db->getRolesWithPermissions();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles and Permissions</title>
</head>
<body>
<h1>Roles and Permissions</h1>

<?php foreach ($roles as $role): ?>
    <h2>Role: <?= htmlspecialchars($role['name']) ?> (<?= htmlspecialchars($role['description']) ?>)</h2>
    <h3>Permissions:</h3>
    <ul>
        <?php foreach ($role['permissions'] as $permission): ?>
            <li>
                <?= htmlspecialchars($permission['name']) ?> (URL: <?= htmlspecialchars($permission['asset_url'] ?? '') ?>)
                <form action="manageRoles.php" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                    <input type="hidden" name="permission_id" value="<?= $permission['id'] ?>">
                    <button type="submit">Remove</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>

    <h3>Add Permission:</h3>
    <form action="manageRoles.php" method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
        <label for="permission_id">Permission:</label>
        <select name="permission_id" id="permission_id" required>
            <?php
            // Pobierz wszystkie dostępne uprawnienia
            $permissions = $db->getPdo()->query("SELECT id, name FROM privilege ORDER BY name")->fetchAll(2);
            foreach ($permissions as $permission):
                ?>
                <option value="<?= $permission['id'] ?>"><?= htmlspecialchars($permission['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Add</button>
    </form>
<div>
    ---------------------------------------------------------------------------|
</div>
<?php endforeach; ?>
</body>
</html>

<?php

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'];
        $roleId = $_POST['role_id'];
        $permissionId = $_POST['permission_id'];

        if ($action === 'add') {
            $sql = "INSERT INTO role_privilege (id_role, id_privilege, issue_time) VALUES (:role_id, :permission_id, NOW())";
            $stmt = $db->getPdo()->prepare($sql);
            $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
        } elseif ($action === 'remove') {
            $sql = "DELETE FROM role_privilege WHERE id_role = :role_id AND id_privilege = :permission_id";
            $stmt = $db->getPdo()->prepare($sql);
            $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
        header('Location: manageRoles.php');
        exit;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
