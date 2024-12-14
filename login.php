<?php
include_once "classes/Page.php";
include_once "classes/Pdo_.php";
session_start();
$message = isset($_GET['message']) ? $_GET['message'] : '';

Page::display_header("Main page");
$Pdo = new Pdo_();
// adding new user
if (isset($_REQUEST['add_user'])) {
    $login = $_REQUEST['login'];
    $email = $_REQUEST['email'];
    $password = $_REQUEST['password'];
    $password2 = $_REQUEST['password2'];
    if ($password == $password2) {
        $Pdo->add_user($login, $email, $password);
    } else {
        echo 'Passwords doesn\'t match';
    }
}

if (isset($_REQUEST['log_user_in'])) {
    $password = $_REQUEST['password'];
    $login = $_REQUEST['login'];
    $Pdo->log_user_in($login, $password);

}

if (isset($_REQUEST['change_password'])) {
    echo $Pdo->changePassword($_REQUEST);
}

if (isset($_REQUEST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<H2> Main page</H2>
<hr>
<P> Register new user</P>
<form method="post" action="login.php">
    <table>
        <tr>
            <td>login</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="login" id="login" size="40"/></td>
        </tr>
        <tr>
            <td>email</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="email" id="email" size="40"/></td>
        </tr>
        <tr>
            <td>password</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="password" id="password" size="40"/></td>
        </tr>
        <tr>
            <td>repeat password</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="password2" id="password2" size="40"/></td>
        </tr>
    </table>
    <input type="submit" id="submit" value="Create account" name="add_user"></form>
<hr>
<P> Log in</P>
<form method="post" action="login.php">
    <table>
        <tr>
            <td>login</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="login" id="login" size="40" value="test123"/></td>
        </tr>
        <tr>
            <td>password</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="password"
                       id="password" size="40" value="student"/></td>
        </tr>
    </table>
    <input type="submit" id="submit" value="Log in" name="log_user_in">
</form>

<P>Change password</P>
<form method="post" action="login.php">
    <table>
        <tr>
            <td>login</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="login" id="login" size="40" value="test123"/></td>
        </tr>
        <tr>
            <td>current password</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="currentPassword"
                       id="currentPassword" size="40" value="student"/></td>
        </tr>
        <tr>
            <td>new password</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="newPassword1"
                       id="newPassword1" size="40" value="newstudent"/></td>
        </tr>
        <tr>
            <td>repeat new password</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="newPassword2"
                       id="newPassword12" size="40" value="newstudent"/></td>
        </tr>
    </table>
    <input type="submit" id="submit" value="Change password" name="change_password">
</form>
</body>
</html>