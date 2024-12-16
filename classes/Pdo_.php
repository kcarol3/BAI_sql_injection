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

    public function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
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

            if (!$user_data) {
                $this->register_user_login(-1, $this->getUserIP(), 0, 'PC-Unknown');
            }

            $password = hash('sha512', $password . $user_data['salt']);

            if ($password == $this->aes->decrypt($user_data['enc_password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user'] = $login;
                $_SESSION['expiry_time'] = time() + 30000;

                $this->register_user_login($user_data['id'], $this->getUserIP(), 1, "PC-$login");

                $this->get_privileges($login);
                echo 'Successful login<BR/>';

                header('Location: index.php');
                return true;
            } else {
                $this->register_user_login($user_data['id'], $this->getUserIP(), 0, 'PC-Unknown');
                echo 'login FAILED<BR/>';

                return false;
            }
        } catch (Exception $e) {
            print 'Exception' . $e->getMessage();
        }

        return false;
    }

    public function register_user_login($id_user, $ip_address, $correct, $computer)
    {
        $id_user = $this->purifier->purify($id_user);
        $ip_address = $this->purifier->purify($ip_address);
        $correct = $this->purifier->purify($correct);
        $computer = $this->purifier->purify($computer);

        try {
            $sql = "SELECT id, ok_login_num, bad_login_num, last_bad_login_num, permanent_lock, temp_lock 
                FROM ip_address WHERE address_IP = :address_IP";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['address_IP' => $ip_address]);
            $ip_data = $stmt->fetch();

            if (empty($ip_data['id'])) {
                $sql = "INSERT INTO ip_address (ok_login_num, bad_login_num, last_bad_login_num, permanent_lock, temp_lock, address_IP)
                    VALUES (0, 0, 0, 0, NULL, :address_IP)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['address_IP' => $ip_address]);

                $sql = "SELECT id, ok_login_num, bad_login_num, last_bad_login_num, permanent_lock, temp_lock 
                    FROM ip_address WHERE address_IP = :address_IP";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['address_IP' => $ip_address]);
                $ip_data = $stmt->fetch();
            }

            if ($ip_data['permanent_lock'] == 1) {
                print "IP address permanently blocked.";
                return;
            }

            if ($ip_data['temp_lock'] && strtotime($ip_data['temp_lock']) > time()) {
                print "IP address temporarily blocked until " . $ip_data['temp_lock'];
                return;
            }

            $id_address = $ip_data['id'];

            if ($id_user == -1) {
                $sql = "INSERT INTO incorrect_logins (id_user, time, session_id, id_address, computer) 
                    VALUES (:id_user, :time, :session_id, :id_address, :computer)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'id_user' => null,
                    'time' => date('Y-m-d H:i:s'),
                    'session_id' => session_id() ?: 'unknown',
                    'id_address' => $id_address,
                    'computer' => $computer ?: 'undetected'
                ]);

                $bad_login_num = $ip_data['bad_login_num'] + 1;
                $last_bad_login_num = $ip_data['last_bad_login_num'] + 1;

                $temp_lock = null;
                if ($last_bad_login_num >= 5) {
                    $temp_lock = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                }

                $sql = "UPDATE ip_address SET bad_login_num = :bad_login_num, last_bad_login_num = :last_bad_login_num, temp_lock = :temp_lock 
                    WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'bad_login_num' => $bad_login_num,
                    'last_bad_login_num' => $last_bad_login_num,
                    'temp_lock' => $temp_lock,
                    'id' => $id_address
                ]);

            } else {
                $sql = "INSERT INTO user_login (time, correct, id_user, computer, id_address) 
                    VALUES (:time, :correct, :id_user, :computer, :id_address)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'time' => date('Y-m-d H:i:s'),
                    'correct' => $correct,
                    'id_user' => $id_user,
                    'computer' => $computer ?: 'undetected',
                    'id_address' => $id_address
                ]);

                if ($correct == 1) {
                    $ok_login_num = $ip_data['ok_login_num'] + 1;

                    $sql = "UPDATE ip_address SET ok_login_num = :ok_login_num, last_bad_login_num = 0 
                        WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        'ok_login_num' => $ok_login_num,
                        'id' => $id_address
                    ]);
                } else {
                    $bad_login_num = $ip_data['bad_login_num'] + 1;
                    $last_bad_login_num = $ip_data['last_bad_login_num'] + 1;

                    $temp_lock = null;
                    if ($last_bad_login_num >= 5) {
                        $temp_lock = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    }

                    $sql = "UPDATE ip_address SET bad_login_num = :bad_login_num, last_bad_login_num = :last_bad_login_num, temp_lock = :temp_lock 
                        WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        'bad_login_num' => $bad_login_num,
                        'last_bad_login_num' => $last_bad_login_num,
                        'temp_lock' => $temp_lock,
                        'id' => $id_address
                    ]);
                }
            }

        } catch (Exception $e) {
            print 'Exception: ' . $e->getMessage();
        }
    }


    public
    function changePassword(array $request): string
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

    public
    function get_privileges($login)
    {
        try {
            $sql = "SELECT p.id,p.name FROM privilege p"
                . " INNER JOIN user_privilege up ON p.id=up.id_privilege" . " INNER JOIN user u ON u.id=up.id_user"
                . " WHERE u.login=:login";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['login' => $login]);
            $data = $stmt->fetchAll();
            foreach ($data as $row) {
                $privilege = $row['name'];
                $_SESSION[$privilege] = 'YES';
            }
            $data['status'] = 'success';
            return $data;
        } catch (Exception $e) {
            print 'Exception' . $e->getMessage();
        }
        return [
            'status' => 'failed'
        ];
    }
}