<?php

//composer require workerman/workerman:^4.0
//composer require workerman/mqtt

use Dotenv\Dotenv;
use Workerman\Mqtt\Client;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    Dotenv::create(__DIR__)->load();
}

$runtime = __DIR__ . '/runtime';

if (!file_exists($runtime)) {
    mkdir($runtime, 0777, true);
}

Worker::$pidFile = $runtime . '/workerman.pid';
Worker::$logFile = $runtime . '/workerman.log';

$data = [
    // '$SYS/broker/bytes/received' => 0,
];

$dataCounter = [
    '$SYS/broker/bytes/received',     //自启动以来收到的所有字节数
    '$SYS/broker/bytes/sent',         //自启动以来发送的所有字节数

    '$SYS/broker/messages/received',  //自启动以来收到的任何类型的消息总数
    '$SYS/broker/messages/sent',      //自启动以来发送的任何类型的消息总数
];

$dataGauge = [
    '$SYS/broker/clients/connected',   // 当前在线连接数
    '$SYS/broker/clients/disconnected',// 断开的连接数

    '$SYS/broker/clients/total',       // 所有连接数（活动的和非活动的）

    '$SYS/broker/heap/current',        // 当前用到的内存

    '$SYS/broker/messages/stored',     // 服务器存储的消息的总数，包括保留消息和持久连接客户端的消息队列中的消息数
];


$worker = new Worker('http://0.0.0.0:9100');


function make_key($key)
{
    // $SYS/broker/bytes/received  => broker/bytes/received
    if (substr($key, 0, 5) === '$SYS/') {
        $key = substr($key, 5);
    }

    // broker/bytes/received  => broker_bytes_received
    $key = str_replace('/', '_', $key);
    return 'mosquitto_' . $key;
}

$worker->onMessage = function ($connection, $request) {
    global $dataCounter;
    global $dataGauge;
    global $data;

    $res = '';
    foreach ($dataCounter as $item) {
        $key = make_key($item);

        $res .= "# HELP $key\n";
        $res .= "# TYPE $key counter\n";

        $val = array_key_exists($item, $data) ? $data[$item] : 0;
        $res .= "$key " . $val . "\n";
    }

    foreach ($dataGauge as $item) {
        $key = make_key($item);

        $res .= "# HELP $key\n";
        $res .= "# TYPE $key gauge\n";

        $val = array_key_exists($item, $data) ? $data[$item] : 0;
        $res .= "$key " . $val . "\n";
    }

    $response = new Response(200, [
        'Content-Type' => 'text/plain; version=0.0.4',
    ], $res);

    $connection->send($response);
};


$worker->onWorkerStart = function () {

    $address = getenv('MQTT_ADDRESS');
    $username = getenv('MQTT_USERNAME');
    $password = getenv('MQTT_PASSWORD');

    $config = [
        'username' => $username,
        'password' => $password,
    ];

    $mqtt = new Client($address, $config);
    $mqtt->onConnect = function ($mqtt) {

        global $dataCounter;
        global $dataGauge;

        foreach ($dataCounter as $k) {
            $mqtt->subscribe($k);
        }
        foreach ($dataGauge as $k) {
            $mqtt->subscribe($k);
        }
    };

    $mqtt->onMessage = function ($topic, $content) {
        global $data;
        $data[$topic] = $content;
    };
    $mqtt->connect();
};

Worker::runAll();
