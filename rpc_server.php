<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest'); //Connection: là kết nối TCP giữa ứng dụng/ chương trình và message broker.
$channel = $connection->channel(); //Channel: là kết nối ảo trong một Connection là môi trường để thực hiện các hoạt động như publishing, consuming message từ queue.

//Khai báo queue để gửi đến
$channel->queue_declare('rpc_queue', false, false, false, false);

function fib($n)
{
    if ($n == 0) {
        return 0;
    }
    if ($n == 1) {
        return 1;
    }
    return fib($n-1) + fib($n-2);
}

echo " [x] Awaiting RPC requests\n";

$callback = function ($req) {
    $n = intval($req->body);
    echo ' [.] fib(', $n, ")\n";

    //AMQP là một giao thức (protocol) truyền message được sử dụng trong RabbitMQ.
    $msg = new AMQPMessage(
        (string) fib($n),
        array('correlation_id' => $req->get('correlation_id'))
    );

    //publish a message vào queue
    /*xử lý và gửi message kèm theo kết quả cho client dựa trên trường reply_to */
    $req->delivery_info['channel']->basic_publish( 
        $msg,
        '',
        $req->get('reply_to')
    );
    
    /* khi RabbitMQ cung cấp tin nhắn, library sẽ cung cấp delivery_info
     $delivery_info = array(
      "channel" => $this,
      "consumer_tag" => $consumer_tag,
      "delivery_tag" => $delivery_tag,
      "redelivered" => $redelivered,
      "exchange" => $exchange,
      "routing_key" => $routing_key
    );

     có thể truy cập sử dụng  AMQPMessage::get function:
     vd: $req->get('reply_to');
     */

    $req->ack(); //ack là dùng để báo nhận và xử lý thành công.
};

$channel->basic_qos(null, 1, null); //chỉ gửi 1 Message cho Consumer, khi nào xử lý xong hãy gửi Message kế tiếp
$channel->basic_consume('rpc_queue', '', false, false, false, false, $callback); //nhận tin nhắn từ queue

while ($channel->is_open()) {
    $channel->wait();
}

$channel->close();
$connection->close();