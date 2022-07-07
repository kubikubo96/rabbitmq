<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest'); //Connection: là kết nối TCP giữa ứng dụng/ chương trình và message broker.
$channel = $connection->channel(); //Channel: là kết nối ảo trong một Connection là môi trường để thực hiện các hoạt động như publishing, consuming message từ queue.

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
  echo ' [x] Received ', $msg->body, "\n";
  sleep(substr_count($msg->body, '.'));
  echo " [x] Done\n";
};

$channel->basic_consume('hello', '', false, true, false, false, $callback); //nhận tin nhắn từ queue

while ($channel->is_open()) {
  $channel->wait();
}

$channel->close();
$connection->close();
?>
