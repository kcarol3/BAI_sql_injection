<?php
include_once "classes/Page.php";
include_once "classes/Db.php";
Page::display_header("Add message");
$db = new Db("127.0.0.1", "root", "", "news");

$message = \get_object_vars($db->getMessage($_GET['id'])[0]);
?>
<hr>
<P> Update message</P>
<form method="POST" action="messages.php">
    <table>
        <tr>
            <td>Name</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="name" id="name" size="56" value="<?php echo $message['name']?>"/>
            </td>
        </tr>
        <tr>
            <td>Type</td>
            <td>
                <label for="type"></label>
                <select name="type" id="type">
                    <option value="public">Public</option>
                    <option value="private">Private</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>Message content</td>
            <td>
                <label for="content"></label>
                <textarea required type="text" name="content" id="content" rows="10" cols="40" >
<?php echo $message['message']?></textarea>
            </td>
        </tr>
    </table>
    <input type="hidden" name="id" value="<?php echo $message['id']?>"/>
    <input type="submit" id= "submit" value="Update message" name="update">
</form>
<hr>
<P>Navigation</P>
<?php
Page::display_navigation();
?>
</body>
</html>
