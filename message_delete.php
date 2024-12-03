<?php
include_once "classes/Page.php";
include_once "classes/Db.php";
Page::display_header("Add message");
$db = new Db("127.0.0.1", "root", "", "news");

$db->deleteMessage($_GET['id']);

header('Location: messages.php');

?>