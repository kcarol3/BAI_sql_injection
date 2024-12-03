<?php

include 'classes/Db.php';
include_once "classes/Pdo_.php";

$pdo = new Pdo_();
$db = new Db("127.0.0.1", "root", "", "news");


try {

    $users = $db->getPdo()->query("SELECT id, login FROM user ORDER BY login")->fetchAll(2);

    // Pobierz role przypisane do użytkownika
    function getUserRoles($pdo, $userId) {
        $sql = "
            SELECT 
                r.id AS role_id, 
                r.role_name, 
                r.description
            FROM role r
            INNER JOIN user_role ur ON r.id = ur.id_role
            WHERE ur.id_user = :user_id
            ORDER BY r.role_name;
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(2);
    }

    // Jeśli wybrano użytkownika, pobierz jego role
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $userRoles = $userId ? getUserRoles($db->getPdo(), $userId) : [];

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Roles</title>
</head>
<body>
<h1>User Roles Management</h1>

<!-- Formularz wyboru użytkownika -->
<form method="GET" action="user_roles.php">
    <label for="user_id">Select User:</label>
    <select name="user_id" id="user_id" required>
        <option value="">-- Select User --</option>
        <?php foreach ($users as $user): ?>
            <option value="<?= $user['id'] ?>" <?= ($userId === $user['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($user['login']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">View Roles</button>
</form>

<?php if ($userId): ?>
    <h2>Roles for User: <?= htmlspecialchars($users[array_search($userId, array_column($users, 'id'))]['login']) ?></h2>

    <h3>Assigned Roles:</h3>
    <ul>
        <?php foreach ($userRoles as $role): ?>
            <li>
                <?= htmlspecialchars($role['role_name']) ?> (<?= htmlspecialchars($role['description']) ?>)
                <form action="user_roles.php" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="user_id" value="<?= $userId ?>">
                    <input type="hidden" name="role_id" value="<?= $role['role_id'] ?>">
                    <button type="submit">Remove</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>

    <h3>Add Role:</h3>
    <form action="user_roles.php" method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="user_id" value="<?= $userId ?>">
        <label for="role_id">Role:</label>
        <select name="role_id" id="role_id" required>
            <?php
            $sql = "
                    SELECT id, role_name 
                    FROM role 
                    WHERE id NOT IN (SELECT id_role FROM user_role WHERE id_user = :user_id)
                    ORDER BY role_name;
                ";
            $stmt = $db->getPdo()->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $availableRoles = $stmt->fetchAll(2);

            foreach ($availableRoles as $role):
                ?>
                <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Add</button>
    </form>
<?php endif; ?>
</body>
</html>

<?php
// Połączenie z bazą danych

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'];
        $userId = $_POST['user_id'];
        $roleId = $_POST['role_id'];

        if ($action === 'add') {
            // Dodaj rolę do użytkownika
            $sql = "INSERT INTO user_role (id_user, id_role, issue_time) VALUES (:user_id, :role_id, NOW())";
            $stmt = $db->getPdo()->prepare($sql);
            $stmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
        } elseif ($action === 'remove') {
            // Usuń rolę od użytkownika
            $sql = "DELETE FROM user_role WHERE id_user = :user_id AND id_role = :role_id";
            $stmt = $db->getPdo()->prepare($sql);
            $stmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
        }
        header('Location: user_roles.php');
        exit;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
