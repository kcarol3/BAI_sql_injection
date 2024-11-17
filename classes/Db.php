<?php

include_once "Filter.php";
class Db
{
    private $pdo;
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

    public function getFilteredMessages($searchTerm)
    {
        $searchTerm = '%' . trim($searchTerm) . '%';

        $sql = "SELECT * FROM message WHERE name LIKE :searchTerm";
        $stmt = $this->pdo->prepare($sql);

        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
