<?php

require "classes/Db.php";

$db = new Db("127.0.0.1", "root", "", "news");

    $sql = "SELECT id_user, time, action_taken, table_affected, previous_data, new_data 
            FROM user_activity ORDER BY time DESC";
    $stmt = $db->getPdo()->query($sql);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Users activity";
    echo "<br>";

    foreach ($activities as $activity) {
        echo "User ID: {$activity['id_user']} | ";
        echo "Time: {$activity['time']} | ";
        echo "Action: {$activity['action_taken']} | ";
        echo "Table: {$activity['table_affected']}<br>";

        if (!empty($activity['previous_data'])) {
            echo "Previous Data: {$activity['previous_data']}<br>";
        }
        if (!empty($activity['new_data'])) {
            echo "New Data: {$activity['new_data']}<br>";
        }
        echo "<hr>";
    }

