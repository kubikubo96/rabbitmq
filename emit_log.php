<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest'); //Connection: là kết nối TCP giữa ứng dụng/ chương trình và message broker.
$channel = $connection->channel(); //Channel: là kết nối ảo trong một Connection là môi trường để thực hiện các hoạt động như publishing, consuming message từ queue.

$channel->exchange_declare('logs', 'fanout', false, false, false); // tạo exchange logs, type = fanout
                                                                  /*Fanout exchange: sẽ đẩy message đến toàn bộ hàng đợi được gắng với exchange đó.
                                                                    Hiểu một cách đơn giản, bản copy của message sẽ được gửi tới tất cả các hàng đợi và bỏ qua routing key.*/

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "info: Hello World!";
}
$msg = new AMQPMessage($data); //AMQP là một giao thức (protocol) truyền message được sử dụng trong RabbitMQ.

$channel->basic_publish($msg, 'logs'); //publish a message vào queue

echo ' [x] Sent ', $data, "\n";

$channel->close();
$connection->close();