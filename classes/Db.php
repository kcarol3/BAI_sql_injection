<?php

include_once "Filter.php";

class Db
{
    private $pdo;

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function setPdo(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private $select_result;
    private $allowed_types = ['public', 'private'];

    public function __construct($serwer, $user, $pass, $baza)
    {
        $dsn = "mysql:host=$serwer;dbname=$baza;charset=utf8";
        try {
            $this->pdo = new PDO($dsn, $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            printf("Connection to server failed: %s \n", $e->getMessage());
            exit();
        }
    }

    function __destruct()
    {
        $this->pdo = null;
    }

    public function select($sql, $params = [])
    {
        $results = [];
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            printf("Select query failed: %s \n", $e->getMessage());
        }
        $this->select_result = $results;
        return $results;
    }

    public function addMessage($name, $type, $content)
    {
        if (!in_array($type, $this->allowed_types)) {
            echo "Invalid message type provided.<br>";
            return false;
        }

        $name = Filter::filter($name, 'name');
        $content = Filter::filter($content, 'content');

        // SQL query to insert the new message
        $sql = "INSERT INTO message (`name`, `type`, `message`, `deleted`)
                VALUES (:name, :type, :content, 0)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':content', $content);
            $stmt->execute();
            echo "Message added successfully.<br>";
            return true;
        } catch (PDOException $e) {
            printf("Add message failed: %s \n", $e->getMessage());
            return false;
        }
    }

    public function updateMessage($id, $name, $type, $content)
    {
        if (!in_array($type, $this->allowed_types)) {
            echo "Invalid message type provided.<br>";
            return false;
        }

        try {
            $sql = "SELECT * FROM message WHERE id=:id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            $msg_data = $stmt->fetch();
            $prev_data = $id . "|" . $msg_data['name'] . "|"
                . $msg_data['type'] . "|" . $msg_data['message'];
        } catch (Exception $e) {
            print 'Exception' . $e->getMessage();
        }

        $name = Filter::filter($name, 'name');
        $content = Filter::filter($content, 'content');

        $sql = "UPDATE message SET `name` = :name, `type` = :type, `message` = :content WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':content', $content);
            $stmt->execute();

            $data = [
                'name' => $name, 'type' => $type, 'content' => $content, 'id' => $id
            ];

            $new_data = $id . "|" . $data['name'] . "|" . $data['type'] . "|" . $content;

            $this->register_user_activity('edit', 1, $prev_data, $new_data, 'mesage');
            echo "Message updated successfully.<br>";
            return true;
        } catch (PDOException $e) {
            printf("Update message failed: %s \n", $e->getMessage());
            return false;
        }
    }

    public function getMessage($message_id)
    {
        $sql = "SELECT * FROM message WHERE id = :id";
        return $this->select($sql, [':id' => $message_id]);
    }

    public function deleteMessage($message_id)
    {
        if (isset($_SESSION['delete message'])) {
            if (filter_var($message_id, FILTER_VALIDATE_INT)) {
                try {
                    $sql = "UPDATE `message` SET `deleted`=1 WHERE `id`=:id";
                    $data = [
                        'id' => $message_id];
                    $this->pdo->prepare($sql)->execute($data);

                    $this->register_user_activity('delete',1, "message id = $message_id", null, 'mesage');

                    return true;
                } catch (Exception $e) {
                    print 'Exception' . $e->getMessage();
                }
            }
        } else
            echo 'YOU HAVE NO PRIVILEGE TO DELETE MESSAGE <BR/>';
        return false;
    }


    public function getFilteredMessages($searchTerm)
    {
        $searchTerm = '%' . trim($searchTerm) . '%';

        $sql = "SELECT * FROM message WHERE name LIKE :searchTerm";
        $stmt = $this->pdo->prepare($sql);

        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function get_privileges($login)
    {
        try {
            $sql = "SELECT p.id,p.name FROM privilege p"
                . " INNER JOIN user_privilege up ON p.id=up.id_privilege" . " INNER JOIN user u ON u.id=up.id_user"
                . " WHERE u.login=:login";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['login' => $login]);
            $data = $stmt->fetchAll();
            foreach ($data as $row) {
                $privilege = $row['name'];
                $_SESSION[$privilege] = 'YES';
            }
            $data['status'] = 'success';
            return $data;
        } catch (Exception $e) {
            print 'Exception' . $e->getMessage();
        }
        return [
            'status' => 'failed'
        ];
    }

    function getAllPermissions()
    {
        $stmt = $this->pdo->query("SELECT * FROM privilege");
        return $stmt->fetchAll();
    }

    function addPermission($name)
    {
        $stmt = $this->pdo->prepare("INSERT INTO privilege (name, active) VALUES (:name, 1)");
        return $stmt->execute([':name' => $name]);
    }

    function deletePermission($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM privilege WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    function getAllRoles()
    {
        $stmt = $this->pdo->query("SELECT * FROM role");
        return $stmt->fetchAll();
    }

    function addRole($roleName, $description)
    {
        $stmt = $this->pdo->prepare("INSERT INTO role (role_name, description) VALUES (:role_name, :description)");
        return $stmt->execute([':role_name' => $roleName, ':description' => $description]);
    }

    function deleteRole($roleId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM role WHERE id = :id");
        return $stmt->execute([':id' => $roleId]);
    }

    function addUserRole($userId, $roleId, $issueTime, $expireTime = null)
    {
        $stmt = $this->pdo->prepare("
        INSERT INTO user_role (id_user, id_role, issue_time, expire_time)
        VALUES (:id_user, :id_role, :issue_time, :expire_time)
    ");
        return $stmt->execute([
            ':id_user' => $userId,
            ':id_role' => $roleId,
            ':issue_time' => $issueTime,
            ':expire_time' => $expireTime,
        ]);
    }

    function removeUserRole($userRoleId)
    {
        $stmt = $this->db->prepare("DELETE FROM user_role WHERE id = :id");
        return $stmt->execute([':id' => $userRoleId]);
    }

    function getRolesWithPermissions()
    {
        try {
            $sql = "
            SELECT 
                r.id AS role_id, 
                r.role_name, 
                r.description, 
                p.id AS permission_id, 
                p.name AS permission_name, 
                p.asset_url
            FROM role r
            LEFT JOIN role_privilege rp ON r.id = rp.id_role
            LEFT JOIN privilege p ON rp.id_privilege = p.id
            ORDER BY r.role_name, p.name;
        ";

            // Wykonaj zapytanie
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            // Grupowanie wyników: role z przypisanymi permissions
            $roles = [];
            while ($row = $stmt->fetch(2)) {
                $roleId = $row['role_id'];

                // Jeśli rola nie została jeszcze dodana, dodaj ją
                if (!isset($roles[$roleId])) {
                    $roles[$roleId] = [
                        'id' => $row['role_id'],
                        'name' => $row['role_name'],
                        'description' => $row['description'],
                        'permissions' => [],
                    ];
                }

                // Jeśli permission istnieje, dodaj go do danej roli
                if ($row['permission_id']) {
                    $roles[$roleId]['permissions'][] = [
                        'id' => $row['permission_id'],
                        'name' => $row['permission_name'],
                        'asset_url' => $row['asset_url']
                    ];
                }
            }

            return $roles;
        } catch (Exception $e) {
            // Obsłuż błąd
            throw new Exception("Error fetching roles with permissions: " . $e->getMessage());
        }
    }

    public function register_user_activity($action_taken, $row_number, $previous_data, $present_data, $table_affected)
    {
        $action_taken = $this->purifier->purify($action_taken);
        $row_number = $this->purifier->purify($row_number);
        $previous_data = $this->purifier->purify($previous_data);
        $present_data = $this->purifier->purify($present_data);
        $table_affected = $this->purifier->purify($table_affected);
        try {
            $sql = "INSERT INTO `user_activity`( `id_user`, `action_taken`, `table_affected`,
`row_number`, `previous_data`, `new_data`) VALUES (:user_id,:action_taken,:table_affected,:row_number,:previous_data,:new_data)";
            $data = [
                'user_id' => 8, 'action_taken' => $action_taken, 'table_affected' => $table_affected, 'row_number' => $row_number, 'previous_data' => $previous_data, 'new_data' => $present_data
            ];
            $this->pdo->prepare($sql)->execute($data);
        } catch (Exception $e) {
            print ' Exception' . $e->getMessage();
        }
    }
}
