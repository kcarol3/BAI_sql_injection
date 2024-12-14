<?php
//require '.. /HTMLPurifier/library/HTMLPurifier.auto.php';

use PHPMailer\src\Exception\M;

require __DIR__ . '/../htmlpurifier/library/HTMLPurifier.auto.php';
include_once "Aes.php";
include_once "M.php";

class Pdo_
{
    private $db;
    private $purifier;
    private $aes;
    private $mailer;
    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $this->purifier = new HTMLPurifier($config);
        try {
            $this->db = new PDO('mysql:host=127.0.0.1:3306;dbname=news', 'root', '');
        } catch (PDOException $e) {
// add relevant code
            echo $e->getMessage();
        }

        $this->aes = new Aes();
        $this->mailer = new M();
    }

    public function add_user($login, $email, $password)
    {
        $login = $this->purifier->purify($login);
        $email = $this->purifier->purify($email);

        try {
            $sql = "INSERT INTO `user`( `login`, `email`, `hash`, `salt`, `id_status`, `password_form`, `enc_password`)
VALUES (:login,:email,:hash,:salt,:id_status,:password_form, :enc_password)";
            //generate salt
            $salt = random_bytes(16);
            //hash password with salt
            $password = hash('sha512', $password . $salt);
            $encPasswd = $this->aes->encrypt($password);
            $data = [
                'login' => $login,
                'email' => $email,
                'hash' => $password,
                'salt' => $salt,
                'id_status' => '1',
                'password_form' => '1',
                'enc_password' => $encPasswd
            ];
            $this->db->prepare($sql)->execute($data);
        } catch (Exception $e) {
//modify the code here
            print 'Exception' . $e->getMessage();
        }
    }

    public function log_user_in($login, $password): bool
    {
        $login = $this->purifier->purify($login);

        try {
            $sql = "SELECT id,enc_password,login,salt FROM user
          WHERE login=:login";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['login' => $login]);
            $user_data = $stmt->fetch();

            $password = hash('sha512', $password . $user_data['salt']);

            if ($password == $this->aes->decrypt($user_data['enc_password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user'] = $login;
                $_SESSION['expiry_time'] = time() + 30000;

                $this->get_privileges($login);
                echo 'Successful login<BR/>';

                header( 'Location: index.php');
                return true;
            } else {
                echo 'login FAILED<BR/>';

                return false;
            }
        } catch (Exception $e) {
//modify the code here
            print 'Exception' . $e->getMessage();
        }

        return false;
    }

    public function changePassword(array $request): string
    {
        $loginStatus = $this->log_user_in($request['login'], $request['currentPassword']);

        try {
            if ($loginStatus) {
                if ($request['newPassword1'] == $request['newPassword2']) {
                    $salt = random_bytes(16);
                    $password = hash('sha512', $request['newPassword1'] . $salt);
                    $sql = "update user set hash= :hash where login = :login";

                    $data = [
                        'hash' => $password,
                        'login' => $request['login']
                    ];
                    $this->db->prepare($sql)->execute($data);

                    return 'Password was change successfully';
                } else {
                    return 'Passwords doesn\'t match';
                }

            } else {
                return "Invalid data";
            }
        } catch (Exception $e) {
            return 'Exception' . $e->getMessage();
        }
    }

    public function get_privileges($login)
    {
        try {
            $sql = "SELECT p.id,p.name FROM privilege p"
                ." INNER JOIN user_privilege up ON p.id=up.id_privilege" ." INNER JOIN user u ON u.id=up.id_user"
                ." WHERE u.login=:login";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['login' => $login]);
            $data = $stmt->fetchAll();
            foreach ($data as $row) {
                $privilege=$row['name'];
                $_SESSION[$privilege]='YES'; }
            $data['status']='success';
            return $data;
        } catch (Exception $e) {
            print 'Exception' . $e->getMessage(); }
        return [
            'status' => 'failed'
        ]; }
}