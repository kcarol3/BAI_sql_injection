<?php
include_once "classes/Page.php";
include_once "classes/Db.php";
Page::display_header("Add message");
$db = new Db("127.0.0.1", "root", "", "news");

$result = $db->deleteMessage($_GET['id']);

if ($result) {
    header('Location: messages.php');
}

?>