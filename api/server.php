<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . "config.php";

$http = new swoole_http_server("127.0.0.1", 9501);

$http->set(array(
    'daemonize' => 1,
    'worker_num' => 8,
    'max_request' => 1000,
    'dispatch_mode' => 1,
    'user' => 'www',
    'group' => 'www',
    'pid_file' => '/var/log/swoole.pid',
));
$http->on("start", function ($server) {
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

$http->on("request", function ($request, $response) {
    $response->header("Content-Type", "text/plain;charset=UTF-8");
    require_once __DIR__ . DIRECTORY_SEPARATOR . "controller.php";

    $action = 'action_'.str_replace('/api/', '', $request->server['request_uri']);
    $data = $action($request);
    $response->end($data);
});

$http->start();

