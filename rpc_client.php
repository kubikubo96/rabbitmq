<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


/*
RPC sẽ làm việc như sau:

- Khi một client khởi động, nó sẽ tạo ra một callback_queue ẩn danh và riêng biệt
- Trong mỗi RPC request, client sẽ gửi message với 2 thuộc tính: reply_to dùng để nhận biết là callback_queue và correlation_id để đặt giá trị duy nhất cho mỗi lần request.
- Request được gửi đến rpc_queue queue.
- RPC worker (hay Server) chờ các request vào queue. Khi một request xuất hiện, nó sẽ xử lý và gửi message kèm theo kết quả cho client dựa trên trường reply_to.
- Client chờ dữ liệu được gửi về từ callback_queue. Khi message xuất hiện, nó sẽ kiểm tra giá trị trong trường correlation_id, nếu trùng nó sẽ trả reponse về cho ứng dụng.
*/


class FibonacciRpcClient
{
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;

    public function __construct()
    {
        //Connection: là kết nối TCP giữa ứng dụng/ chương trình và message broker.
        $this->connection = new AMQPStreamConnection(
            'localhost',
            5672,
            'guest',
            'guest'
        );

        //Channel: là kết nối ảo trong một Connection là môi trường để thực hiện các hoạt động như publishing, consuming message từ queue.
        $this->channel = $this->connection->channel();

        //tạo 1 non-durable queue với tên tự động tạo bất kỳ.
        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "",
            false,
            false,
            true,
            false
        );


        /* 
        RPC làm việc khá dễ dàng bên với RabbitMQ.
        Client gửi các message yêu cầu và server sẽ phản hồi lại.
        Để nhận lại phản hồi, client cần gửi thông tin callback queue kèm với theo request.
        */
        //nhận tin nhắn từ queue
        $this->channel->basic_consume(
            $this->callback_queue,
            '',
            false,
            true,
            false,
            false,
            array(
                $this,
                'onResponse'
            )
        );
    }

    //Khi message xuất hiện, nó sẽ kiểm tra giá trị trong trường correlation_id, nếu trùng nó sẽ trả reponse về cho ứng dụng.
    public function onResponse($rep)
    {
      /*chúng ta sẽ thấy correlation_id khi nhận các message từ 
        callback_queue và dựa vào nó ta có thể so sánh response với request. Nếu không thấy giá trị của correlation_id, message sẽ bị loại bỏ. */
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

  
    public function call($n)
    {
        $this->response = null;
        $this->corr_id = uniqid();

        //AMQP là một giao thức (protocol) truyền message được sử dụng trong RabbitMQ.

        /*Khi sử dụng thuộc tính correlation_id, nhận được response, nó sẽ không xóa thông tin của response này. 
        Chúng ta sẽ gán cho nó một giá trị duy nhất sau mỗi lần request.*/
        $msg = new AMQPMessage(
            (string) $n,
            array(
                'correlation_id' => $this->corr_id, // correlation_id để đặt giá trị duy nhất cho mỗi lần request.
                'reply_to' => $this->callback_queue //dùng để nhận biết là callback_queue
            )
        );

        //publish a message vào queue
        $this->channel->basic_publish($msg, '', 'rpc_queue');

        while (!$this->response) {
            $this->channel->wait();
        }
        return intval($this->response);
    }
}

//sử dụng một phương thức call để gửi các yêu cầu RPC cho đến khi đầu bên kia nhận được.
$fibonacci_rpc = new FibonacciRpcClient();
$response = $fibonacci_rpc->call(30);
echo ' [.] Got ', $response, "\n";