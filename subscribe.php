<?php

// Starting from Redis 2.0 clients can subscribe and listen for events published
// on certain channels using a Publish/Subscribe (PUB/SUB) approach.

// Create a client and disable r/w timeout on the socket

require "./predis/src/Autoloader.php";
define('ROOT', __DIR__);
define('IS_AJAX',isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
Predis\Autoloader::register();
$server = [
    'host' => '127.0.0.1',
    'port' => '6379',
    'database' => 1

];
$client = new Predis\Client($server + array('read_write_timeout' => 0));

// Initialize a new pubsub consumer.
$pubsub = $client->pubSubLoop();
// Subscribe to your channels
$pubsub->subscribe('news', 'notifications');

// Start processing the pubsup messages. Open a terminal and use redis-cli
// to push messages to the channels. Examples:
//   ./redis-cli PUBLISH notifications "this is a test"
//   ./redis-cli PUBLISH control_channel quit_loop
foreach ($pubsub as $message) {
    switch ($message->kind) {
        case 'subscribe':
            echo "Subscribed to {$message->channel}", PHP_EOL;
            break;

        case 'message':
            // 取消订阅
            if ($message->channel == 'control_channel') {
                if ($message->payload == 'quit_loop') {
                    echo 'Aborting pubsub loop...', PHP_EOL;
                    $pubsub->unsubscribe();
                } else {
                    echo "Received an unrecognized command: {$message->payload}.", PHP_EOL;
                }
            } else {
                if ($message !== false && $message->kind == 'message') {
                    $msgKey = 'user:channel:'. $message->channel;
                    echo "set message key: ". $msgKey, PHP_EOL;
                } else {
                    echo "undefined error";
                }
            }
            break;
    }
}

// Always unset the pubsub consumer instance when you are done! The
// class destructor will take care of cleanups and prevent protocol
// desynchronizations between the client and the server.
unset($pubsub);



































//error_reporting(E_ALL);
//require "./predis/src/Autoloader.php";
//define('ROOT', __DIR__);
//define('IS_AJAX',isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
//Predis\Autoloader::register();
//
//function p ($data) {print_r($data); die;}
//$server = [
//    'host' => '127.0.0.1',
//    'port' => '6379',
//    'database' => 1
//
//];
//$client = new Predis\Client($server + ['read_write_timeout' => 0]);
//$channels = 'news';
//$callback = 'callback';
//$method = 'subscribe';
//
//$loop = $client->pubSubLoop();
//$rs = call_user_func_array([$loop, $method], [$channels]);
//foreach ($loop as $message) {
//    if ($message->kind === 'message' || $message->kind == 'pmessage') {
//        $callback($message);
//    }
//}
//
//unset($loop);
//
//function callback($obj) {
//    global $client;
//    $client->set('asdfas', 'asdfas');
//}


