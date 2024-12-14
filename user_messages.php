<?php
include_once "classes/Page.php";
include_once "classes/Db.php";
include_once "checkSession.php";

$db = new Db("127.0.0.1", "root", "", "news");
// adding new message
if (isset($_REQUEST['add_message'])) {
    $name = $_REQUEST['name'];
    $type = $_REQUEST['type'];
    $content = $_REQUEST['content'];
    if (!$db->addMessage($name, $type, $content))
        echo "Adding new message failed";
}

if (isset($_REQUEST['update'])) {
    $id = $_REQUEST['id'];
    $name = $_REQUEST['name'];
    $type = $_REQUEST['type'];
    $content = $_REQUEST['content'];
    if (!$db->updateMessage($id, $name, $type, $content))
        echo "Updating new message failed";
}


?>
<hr>
<P>My Messages</P>
<ol>
    <?php
    $where_clause = "";

    if (isset($_REQUEST['filter_messages'])) {
        $string = $_REQUEST['string'];

        $messages = $db->getFilteredMessages($string);
    } else {
        // If no filter is applied, get all messages
        $sql = "SELECT * FROM message WHERE created_by = '" . $_SESSION['user'] . "'";
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
        if (hasPermission('edit_message') || $msg->created_by === $_SESSION['user']) {
            echo "<a href='message_update.php?id=$id' style='margin: 0px 20px 0px 20px'>Update</a>";
        }
        if (hasPermission('delete_message') || $msg->created_by === $_SESSION['user']) {
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
