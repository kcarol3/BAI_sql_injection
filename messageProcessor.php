<?php

include_once "classes/Db.php";

$db = new Db("127.0.0.1", "root", "", "news");

$db->processMessage();
