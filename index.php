<?php
error_reporting(E_ALL & ~E_NOTICE);
function p($data)
{
    print_r($data);
    die;
}

require "./predis/src/Autoloader.php";

define('ROOT', __DIR__);
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
Predis\Autoloader::register();
$server = [
    'host' => '127.0.0.1',
    'port' => '6379',
    'database' => 1

];
$client = new Predis\Client($server);

if (IS_AJAX && $_POST['cmd'] == 'register') {
    $params = [
        'name' => $_POST['name'],
        'password' => md5($_POST['password']) . '_' . 'salt',
        'age' => rand(20, 50)
    ];
    if (empty($params['name'])) ajaxReturn('用户名不允许为空');
    if (empty($params['password'])) ajaxReturn('密码不允许为空');
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
    $password = $_POST['password'];
    if (empty($name)) ajaxReturn('用户名不允许为空');
    if (empty($password)) ajaxReturn('密码不允许为空');
    $result = checkLogin($name, $password);
    if ($result['status'] != 1) {
        ajaxReturn($result['desc']);
    }
    $result = $result['data'];
    $_COOKIE['userToken'] = base64_encode(json_encode([
        'name' => $result['name'],
        'age' => $result['age']
    ]));
    setcookie('userToken', base64_encode(json_encode([
        'name' => $result['name'],
        'age' => $result['age']
    ])), 0);
    ajaxReturn('ok');
}

//loginOut
if (IS_AJAX && $_POST['cmd'] == 'loginOut') {
    if ($_COOKIE['userToken'] == '') {
        ajaxReturn('您尚未登录');
    }
    setcookie('userToken', '');
    ajaxReturn('ok');
}

// pub & sub
if (IS_AJAX && $_POST['cmd'] == 'sendMsg') {
    $message = json_encode($_POST['message']);
    $client->publish('news', $message);
}

// display message
if (IS_AJAX && $_POST['cmd'] == 'getmsg') {
    $channel = 'news';
    $msg = $client->get('user:channel:' . $channel);
    ajaxReturn($msg, 'text');
}

if (IS_AJAX && $_POST['cmd'] == 'unsub') {
    $client->publish('control_channel', 'quit_loop');
    ajaxReturn('ok');
}

function ajaxReturn($msg, $type = 'json')
{
    switch ($type) {
        case 'text':
            $result = $msg;
            break;
        default :
            $result = json_encode($msg);
            break;
    }
    return exit($result);
}

function checkLogin($name, $password)
{
    global $client;
    $return = [];
    $key = 'user:' . $name;
    $id = $client->get($key); // 根据name获取id
    if (!$id) {
        $return = ['status' => 0, 'desc' => '未注册', 'data' => []];
        return $return;
    }
    $tmp = $client->get('user:uid:' . $id . ':password');
    if (md5($password) != substr($tmp, 0, -5)) {
        $return = ['status' => 0, 'desc' => '账号或密码错误', 'data' => []];
        return $return;
    }
    $key = 'user:uid:' . $id . '*';
    $list = $client->keys($key);
    foreach ($list as $val) {
        $fields = explode(':', $val);
        $fields = end($fields);
        $return[$fields] = $client->get($val);
    }
    unset($return['password']);
    return ['status' => 1, 'desc' => 'true', 'data' => $return];
}

function register($userInfo)
{
    // key = 表名:主键名:主键值:列名
    global $client;
    // 检查是否注册过
    $exitsKey = 'user:' . $userInfo['name'];
    if (!empty($client->get($exitsKey))) {
        return false;
    }
    $uid = logPrimaryKey('user');
    foreach ($userInfo as $key => $val) {
        $userInfoKey = 'user:uid:' . $uid . ':' . $key;
        if ($key == 'name') {
            $client->set('user:' . $val, $uid);
        }
        $client->set($userInfoKey, $val);
    }
    return true;
}

function logPrimaryKey($tabel)
{
    global $client;
    $key = $tabel . ":id";
    return $client->incr($key);
}

// http://www.cnblogs.com/nixi8/p/6708252.html

// http://blog.csdn.net/lijingshan34/article/details/51991595

// https://www.bilibili.com/video/av15978264/
?>


<!DOCTYPE>
<html>
<head>
    <title>sturds</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <link rel="stylesheet" href="./static/bootstrap.min.css">
    <script src="./static/jquery.min.js"></script>
    <script type="text/javascript" src="./static/bootstrap.min.js"></script>
</head>
<body>
<div class="container" style="margin-left: 35px;">
    <div class="row" style="margin-top: 5px;">
        <div class=".col-xs-12 .col-md-8 div-lead"
             style="display: <?php echo $_COOKIE['userToken'] ? 'block' : 'none'; ?>">
            ^_^ 登录成功~
            <p class="lead">
                <?php echo json_decode(base64_decode($_COOKIE['userToken']))->name; ?>
            </p>
        </div>
        <div class=".col-xs-6 .col-md-4"></div>
    </div>
    <div class="row">
        <form class="form" style="width: 60%; margin-top: 1%">
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

            <button type="button" class="btn btn-danger" name="loginOut">login out</button>
        </form>
    </div>
    <hr>
    <label style="">消息接收区</label> :<br>
    <div class="row" style="display: block; height: 80px; width: 60%;">
        <div class="col-md-12" id="display-msg" style="display: block;">
        </div>
    </div>
    <br>

    <div class="row">
        <div class="form-group">
            <input type="text" style="width: 300px;" class="form-control" name="message" value="" placeholder="测试发送消息">
        </div>
        <button type="button" class="btn btn-success" name="sendMsg">send msg</button>
        <button type="button" class="btn btn-danger" name="unsub">取消订阅</button>
    </div>
</div>
</body>
</html>
<script type="text/javascript">
    $("button").on('click', function () {
        var cmd = $(this).attr('name');
        var data = {
            name: $("#username").val(),
            password: $("#password").val(),
            message: $("input[name='message']").val(),
            cmd: cmd
        };
        $.post('index.php?', data, function (e) {
            switch (cmd) {
                case 'login':
                    if (e == 'ok') {
                        $(".lead").html('<?php echo json_decode(base64_decode($_COOKIE['userToken']))->name; ?>');
                        $(".div-lead").show();
                        window.location.reload();
                    } else {
                        alert(e);
                        return false;
                    }
                    break;
                case 'loginOut':
                    if (e == 'ok') {
                        alert('退出成功');
                        window.location.reload();
                    } else {
                        alert(e);
                    }
                    break;
                case 'register':
                    alert(e);
                    break;

                case 'sendMsg':
                    alert(e);
                    break;
                case 'unsub':
                    alert('取消订阅成功');
                    $("#display-msg").hide();

                    break;
                default:
                    break;
            }
        }, 'json');
    });


    setInterval(function () {
        $.post('index.php', {cmd: 'getmsg'}, function (e) {
            if (e) {
                console.log(e);
                $("#display-msg").html(e);
            }
        }, 'json');
    }, 3000);
</script>