<?php
namespace BrainSocket;

use Illuminate\Support\Facades\App;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class BrainSocketEventListener implements MessageComponentInterface {
    protected $clients;
    protected $response;

    public function __construct(BrainSocketResponseInterface $response) {
        $this->clients = new \SplObjectStorage;
        $this->response = $response;
    }

    public function onOpen(ConnectionInterface $conn) {
        echo "Connection Established! \n";
        $this->clients->attach($conn);
        $_msg = [
            'client' => [
                'event' => 'clients.newConnection',
                'data' => [
                    'resourceId' => $conn->resourceId
                    ]
                    ]
                ];
                $msg = json_encode($_msg);
                foreach ($this->clients as $client) {
                    if($client->resourceId === $conn->resourceId) {
                        $_msg['client']['data']['self'] = true;
                        $client->send($this->response->make(json_encode($_msg)));
                    } else {
                        $client->send($this->response->make($msg));
                    }
                }
            }

            public function onMessage(ConnectionInterface $from, $msg) {
                $numRecv = count($this->clients) - 1;
                echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
                , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

                $message = $this->response->make($msg);
                foreach ($this->clients as $client) {
                    $client->send($message);
                }
            }

            public function onClose(ConnectionInterface $conn) {
                $this->clients->detach($conn);
                $msg = [
                    'client' => [
                        'event' => 'clients.connectionClosed',
                        'data' => [
                            'resourceId' => $conn->resourceId
                            ]
                            ]
                        ];
                        $msg = json_encode($msg);
                        foreach ($this->clients as $client) {
                            $client->send($this->response->make($msg));
                        }
                        echo "Connection {$conn->resourceId} has disconnected\n";
                    }

                    public function onError(ConnectionInterface $conn, \Exception $e) {
                        echo "An error has occurred: {$e->getMessage()}\n";
                        $conn->close();
                    }
                }
