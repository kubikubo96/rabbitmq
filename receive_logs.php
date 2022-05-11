<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest'); //Connection: là kết nối TCP giữa ứng dụng/ chương trình và message broker.
$channel = $connection->channel(); //Channel: là kết nối ảo trong một Connection là môi trường để thực hiện các hoạt động như publishing, consuming message từ queue.

$channel->exchange_declare('logs', 'fanout', false, false, false);  // tạo exchange logs, type = fanout
                                                                     /*Fanout exchange: sẽ đẩy message đến toàn bộ hàng đợi được gắn với exchange đó.
                                                                    Hiểu một cách đơn giản, bản copy của message sẽ được gửi tới tất cả các hàng đợi và bỏ qua routing key.*/

list($queue_name, ,) = $channel->queue_declare("", false, false, true, false); //tạo 1 non-durable queue với tên tự động tạo bất kỳ.
$channel->queue_bind($queue_name, 'logs'); // exchange gửi message lên queue, quan hệ giữa exchange và queue gọi là binding

echo " [*] Waiting for logs. To exit press CTRL+C\n";

$callback = function ($msg) {
    echo ' [x] ', $msg->body, "\n";
};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback); //nhận tin nhắn từ queue

while ($channel->is_open()) {
    $channel->wait();
}

$channel->close();
$connection->close();