<?php
/**
 * Created by PhpStorm.
 * User: CYM
 * Date: 19-9-1
 * Time: 下午12:53
 */

require 'vendor/autoload.php';


$http = new swoole_http_server("127.0.0.1", 9501);

// 注册mysqli-pool
$mysqlConfigData = [
    'host'                 => '127.0.0.1',
    'port'                 => 3306,
    'user'                 => 'root',
    'password'             => '123456',
    'database'             => 'test',
    'timeout'              => 30,
    'charset'              => 'utf8mb4',
    'connect_timeout'      => '5',//连接超时时间
    'maxObjectNum'         => 20,
    'minObjectNum'         => 1,
];
$mysqlConfig = new \EasySwoole\Mysqli\Config($mysqlConfigData);
$poolConf = \EasySwoole\MysqliPool\Mysql::getInstance()->register('mysql',$mysqlConfig);

// 注册队列
$queueConfig = [
    'MysqlQueue\\queue\\test' => [  // 所执行的类
        'num'       => 10,               // 启动多少子进程消费， 默认0
        'timeout'   => 0.5              // 子进程间隔时钟定时器 float
    ]
];
$mysqlQueue = \MysqlQueue\MysqlQueue::getInstance($queueConfig);
$mysqlQueue->setSwooleProcessQueue($http);

$http->on("start", function ($server) {
    // echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

$http->on('WorkerStart', function ($serv, $worker_id){
    if($worker_id == 0){
        \Swoole\Timer::tick(40, function(){
            $jobHandlerClassName = 'MysqlQueue\\queue\\test';
            \MysqlQueue\MysqlQueue::getInstance()->push($jobHandlerClassName, ['3']);
            \MysqlQueue\MysqlQueue::getInstance()->push($jobHandlerClassName, ['2']);
            \MysqlQueue\MysqlQueue::getInstance()->push($jobHandlerClassName, ['1']);
        });
    }
});

$http->on("request", function ($request, $response) {

    $uri = $request->server['request_uri'];
    if ($uri == '/favicon.ico') {
        $response->status(404);
        $response->end();
        return;
    }

    // $jobHandlerClassName = 'MysqlQueue\\queue\\test';
    // \MysqlQueue\MysqlQueue::getInstance()->push($jobHandlerClassName, ['1']);
    // \MysqlQueue\MysqlQueue::getInstance()->push($jobHandlerClassName, 2);
    // \MysqlQueue\MysqlQueue::getInstance()->push($jobHandlerClassName, [2]);
    // \MysqlQueue\MysqlQueue::getInstance()->push($jobHandlerClassName, 2);

    $response->header("Content-Type", "text/plain");
    $response->end("Hello World\n");
});

$http->start();

