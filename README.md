# swoole_server 中使用MySQL 消息队列
## 安装
```
composer require cym000/swoole-process-mysql-queue
```

## 说明
主要用于小型项目，平常无聊写写而已，如果用于线上项目还请多加测试，里面借助了easyswoole/mysqli-pool, 如跟你项目有冲突的话，可以自行修改；使用swoole_process做消息队列，可以多个消费process，在本实例swoole_process中可替换成常见的mysql-orm，但在swoole_server 中有协程出现，需使用协程版的mysql；也可以使用在其他swoole衍生框架中；

## 示例代码
```
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
$mysqlQueue->setTable('queue_jobs'); // 可以修改 表名，不然默认就是 queue_jobs
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
```

