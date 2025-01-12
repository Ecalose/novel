<?php
declare (strict_types=1);

namespace app\install\controller;

use think\facade\Request;
use think\facade\View;
use think\facade\Db;

class Index extends Base
{
    /**
     * 首页
     */
    public function index()
    {
        if (Request::isAjax()) {
            $data = Request::post();
            $res = false;
            $dbhost = trim($data['host']);
            $dbuser = trim($data['user']);
            $dbpass = trim($data['pass']);
            $dbport = trim($data['port']);
            $dbname = trim($data['name']);

            switch ($data['step']) {
                case '1':
                    $res = $this->validate($data, [
                        'pk|数据库前缀' => 'alphaDash',
                    ]);
                    try {
                        $dsn = "mysql:host=$dbhost:$dbport";
                        $db = new \PDO($dsn, $dbuser, $dbpass);
                        $sql = 'CREATE DATABASE IF NOT EXISTS ' . $dbname . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
                        $db->exec($sql);
                    } catch (\PDOException $e) {
                        $res = "数据库信息错误";
                    }
                    break;
                case '2':
                    $res = $this->validate($data, [
                        'username|管理用账号' => 'require|alphaDash|min:5',
                        'password|登录密码' => 'require|min:5',
                    ]);
                    break;
                case '3':
                    $dbpk = trim($data['pk']);
                    $dsn = "mysql:host=$dbhost:$dbport;dbname=$dbname";
                    $db = new \PDO($dsn, $dbuser, $dbpass);
                    $info = self::createTables($db, $dbpk);
                    if ($info) {
                        $res = "数据表创建失败" . $info;
                        break;
                    }
                    $username = trim($data['username']);
                    $password = trim($data['password']);
                    $key_str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
                    $salt = substr(str_shuffle($key_str), mt_rand(0, strlen($key_str) - 11), 5);
                    $pass = md5(md5(trim($password)) . $salt);
                    $sql = "INSERT INTO {$dbpk}system_groups (`name`,description,grouptype) VALUES('游客','游客',0);INSERT INTO {$dbpk}system_groups (`name`,description,grouptype) VALUES('管理员','系统管理员',0);INSERT INTO {$dbpk}system_users (uname,`name`,pass,salt,groupid) VALUES('$username','管理员','$pass','$salt',2);";
                    $db->query($sql);
                    // $sql = "";
                    // $db->query($sql);
                    $content = str_replace(['{{$dbhost}}', '{{$dbname}}', '{{$dbuser}}', '{{$dbpass}}', '{{$dbport}}', '{{$dbpk}}'],
                        [$dbhost, $dbname, $dbuser, $dbpass, $dbport, $dbpk],
                        file_get_contents(app_path() . 'data' . DS . 'database.tpl'));
                    @mkdir(root_path() . ".env", 0755, true);
                    @file_put_contents(root_path() . ".env", $content);
                    @touch(public_path() . 'install.lock');
                    $data = "安装成功";
                    break;
                default:
                    break;
            }
            if ($res) {
                $this->jsonApi($res, 201);
            }
            $this->jsonApi($data);
        }
        return $this->fetch();
    }

    private function createTables($db, $pk)
    {
        $sql = str_replace(['{{$pk}}'],
            [$pk],
            file_get_contents(app_path() . 'data' . DS . 'data.sql'));
        $sql_array = preg_split("/;[\r\n]+/", $sql);
        foreach ($sql_array as $k => $v) {
            if (!empty($v)) {
                if (substr($v, 0, 12) == 'CREATE TABLE') {
                    $name = preg_replace("/^CREATE TABLE `(\w+)` .*/s", "\\1", $v);
                    $msg = "创建数据表{$name}";
                    $res = $db->query($v);
                    if ($res == false) {
                        return $msg . '失败';
                    }
                } else {
                    $res = $db->query($v);
                    if ($res == false) {
                        return '数据插入失败';
                    }
                }
            }
        }
        return false;
    }

}
