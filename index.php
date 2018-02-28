<?php 
include "D:/www/func.php";
require "./predis/src/Autoloader.php";

define('ROOT', __DIR__);
define('IS_AJAX',isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
Predis\Autoloader::register();

$server = [
	'host' => '127.0.0.1',
	'port' => '6379',
	'database' => 0

];
$client = new Predis\Client($server);

if (IS_AJAX && $_POST['cmd'] == 'register') {
    $params = [
        'name' => $_POST['name'],
        'password' => md5($_POST['password']). '_'. 'salt',
        'age' => rand(20, 50)
    ];
    $rs = register($params);
    if ($rs) {
        ajaxReturn('register ok');
    } else {
        ajaxReturn('register false');
    }
}

if (IS_AJAX && $_POST['cmd'] == 'login') {
    if (!empty($_COOKIE['userToken'])) {
        $info = json_decode(base64_decode($_COOKIE['userToken']));
        if ($info->name == $_POST['name']) {
            ajaxReturn('您已经登录了');
        }
    }
    $name = $_POST['name'];
    $result = getUserInfo($name);
    if ($result['status'] != 1) {
        ajaxReturn('账户或密码错误');
    }
    $result = $result['data'];
    setcookie('userToken',base64_encode(json_encode([
        'name' => $result['name'],
        'age' => $result['age']
    ])), 0);
    ajaxReturn('ok');
}

function ajaxReturn($msg, $type = 'json') {
    switch ($type) {
        case 'text':
            $result = $msg;
            break;
        default :
            $result = json_encode($msg);
            break;
    }
    exit($result);
}


function getUserInfo($name)
{
    global $client;
    $return = [];
    $key = 'user:'. $name;
    $id = $client->get($key); // 根据name获取id
    if (!$id) {
        $return = ['status' => 0, 'desc' => '未注册', 'data' => []];
        return $return;
    }
    $key = 'user:uid:'. $id. '*';
    $list = $client->keys($key);
    foreach ($list as $val) {
        $fields = explode(':', $val);
        $fields = end($fields);
        $return[$fields] = $client->get($val);
    }
    return ['status' => 1, 'desc' => 'true', 'data' => $return];
}


function register($userInfo)
{
    // key = 表名:主键名:主键值:列名
    global $client;
    // 检查是否注册过
    $exitsKey = 'user:'. $userInfo['name'];
    if (!empty($client->get($exitsKey))) {
        return false;
    }
    $uid = logPrimaryKey('user');
    foreach ($userInfo as $key => $val) {
        $userInfoKey = 'user:uid:'. $uid. ':'. $key;
        if ($key == 'name') {
            $client->set('user:'. $val, $uid);
        }
        $client->set($userInfoKey, $val);
    }
    return true;
}

function logPrimaryKey($tabel)
{
    global $client;
    $key = $tabel. ":id";
    return $client->incr($key);
}
// http://www.cnblogs.com/nixi8/p/6708252.html
?>


<!DOCTYPE>
<html>
<head>
    <title>sturds</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script type="text/javascript" src="http://task.www.sogou.com/cips-sogou_qa/pc/js/jquery/jquery-2.1.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
    <div class="row" style="margin-top: 55px;">
        <div class=".col-xs-12 .col-md-8">
            <p class="lead" style="display: none">

            </p>
        </div>
        <div class=".col-xs-6 .col-md-4"></div>
    </div>
    <div class="row">
        <form class="form" style="width: 60%; margin-top: 10%">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" placeholder="Username">
            </div>
            <div class="form-group">
                <label for="exampleInputPassword1">Password</label>
                <input type="password" class="form-control" id="password" placeholder="Password">
            </div>
            <button type="button" class="btn btn-default" name="register">register</button>

            <button type="button" class="btn btn-default" name="login">login</button>
        </form>
    </div>
</div>
</body>
</html>
<script type="text/javascript">
    var loginStatus;
    $("button").on('click', function() {
        loginStatus = 0;
        var cmd = $(this).attr('name');
        var data = {
            name: $("#username").val(),
            password: $("#password").val(),
            cmd: cmd
        };
        $.post('index.php?', data, function(e) {
            if (e !='ok') {
                alert(e);
            }
            loginStatus = 1;
            console.log(loginStatus);
        }, 'json');
    });
    console.log(loginStatus + 'ppp');
    if (loginStatus) {
        $(".lead").text('asdfadfasdf').show();
    }
</script>