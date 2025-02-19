<?php

use Bluerhinos\phpMQTT;

include_once "classes/Page.php";
include_once "classes/Db.php";
Page::display_header("Messages");


$db = new Db("127.0.0.1", "root", "", "news");

$host = 'broker.emqx.io';
$port = 1883;
$username = '';
$password = '';
$clientID = 'php-mqtt-sender';

$mqtt = new phpMQTT($host, $port, $clientID);

if ($mqtt->connect(true, NULL, $username, $password)) {
    if (isset($_POST['send_message'])) {
        $message = [
            'name' => $_POST['name'],
            'type' => $_POST['type'],
            'content' => $_POST['content']
        ];
        $topic = 'messages/topic';
        $mqtt->publish($topic, json_encode($message), 0);
        echo "Message sent to MQTT broker!";
    }
    $mqtt->close();
} else {
    echo "Failed to connect to MQTT broker!";
}

//if (isset($_REQUEST['update'])) {
//    $id = $_REQUEST['id'];
//    $name = $_REQUEST['name'];
//    $type = $_REQUEST['type'];
//    $content = $_REQUEST['content'];
//    if (!$db->updateMessage($id, $name, $type, $content))
//        echo "Updating new message failed";
//}


?>
<hr>
<P> Messages</P>
<ol>
 <?php
 $where_clause = "";

 if (isset($_REQUEST['filter_messages'])) {
     $string = $_REQUEST['string'];

     $messages = $db->getFilteredMessages($string);
 } else {
     // If no filter is applied, get all messages
     $sql = "SELECT * FROM message";
     $messages = $db->select($sql);
 }
 function hasPermission($permissionName) {
     if (isset($_SESSION['permissions'])) {
         return in_array($permissionName, $_SESSION['permissions']);
     }
     return false;
 }
foreach ($messages as $msg) {
    $id = \get_object_vars($msg)['id'];
    echo "<li>";
    echo $msg->message . " ";
    if (hasPermission('edit_message') || true) {
        echo "<a href='message_update.php?id=$id' style='margin: 0px 20px 0px 20px'>Update</a>";
    }
    if (hasPermission('delete_message') || true) {
        echo "<a href='message_delete.php?id=$id'>Delete</a>";
    }
    echo "</li>";
}


?>
</ol>
<hr>
<P>Messages filtering</P>
<form method="post" action="messages.php">
    <table>
        <tr>
            <td>Title contains: </td>
            <td>
                <label for="name"></label>
                <input required type="text" name="string" id="string" size="80"/>
            </td>
        </tr>
    </table>
    <input type="submit" id= "submit"
           value="Find messages" name="filter_messages">
</form>

<hr>
<P>Navigation</P>
<?php
Page::display_navigation();
?>
 </body>
</html>
