<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

//Tạo kết nối đến server
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest'); //Connection: là kết nối TCP giữa ứng dụng/ chương trình và message broker.
$channel = $connection->channel(); //Channel: là kết nối ảo trong một Connection là môi trường để thực hiện các hoạt động như publishing, consuming message từ queue.

//Khai báo queue để gửi đến
$channel->queue_declare('hello', false, false, false, false); 

$msg = new AMQPMessage('Hello World!'); //AMQP là một giao thức (protocol) truyền message được sử dụng trong RabbitMQ.
$channel->basic_publish($msg, '', 'hello'); //publish a message vào queue

echo " [x] Sent 'Hello World!'\n";

$channel->close();
$connection->close();
?>