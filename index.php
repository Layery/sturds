<?php 
include "D:/www/func.php";
require "./predis/src/Autoloader.php";

define('ROOT', __DIR__);
define('IS_AJAX',isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
Predis\Autoloader::register();

$server = [
	'host' => '127.0.0.1',
	'port' => '6379',
	'db' => 0

];
$client = new Predis\Client($server);

if (IS_AJAX && $_REQUEST['cmd'] == 'create') {
    
}
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
            <input type="hidden" name="cmd" value="create">
            <button type="button" class="btn btn-success">Submit</button>
        </form>
    </div>
</div>
</body>
</html>
<script type="text/javascript">
    $("button").on('click', function() {
        var data = {
            name: $("#username").val(),
            password: $("#password").val(),
            cmd: $("input[name='cmd']").val()
        };
        $.post('index.php?', data, function(e) {
            alert(e);
        }, 'json');
    });
</script>