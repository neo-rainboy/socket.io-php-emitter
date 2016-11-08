<?php

/**
 * @author Soma Szélpál <szelpalsoma@gmail.com>
 * @author Anton Pavlov <anton.pavlov.it@gmail.com>
 * @license MIT
 */
namespace Shakahl\SocketIO;

use MessagePack\Packer;
use Predis;
use Shakahl\SocketIO\Constants\Emitter\Type;
use Shakahl\SocketIO\Constants\Emitter\Flag;

/**
 * Class Emitter
 * @package Shakahl\SocketIO
 */
class Emitter
{
    /**
     * Default namespace
     *
     * @var string
     */
    const DEFAULT_NAMESPACE = '/';

    /**
     * @var string
     */
    protected $uid = 'emitter';

    /**
     * @var int
     */
    protected $type;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * Rooms
     * @var array
     */
    protected $rooms;

    /**
     * @var array
     */
    protected $validFlags = [];

    /**
     * @var array
     */
    protected $flags;

    /**
     * @var Packer
     */
    protected $packer;

    /**
     * @var Predis\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * Emitter constructor.
     * 
     * @param Predis\Client $client
     * @param string $prefix
     */
    public function __construct(Predis\Client $client, $prefix = 'socket.io')
    {
        $this->client = $client;
        $this->prefix = $prefix;
        $this->packer = new Packer();
        $this->reset();

        $this->validFlags = [
            Flag::JSON,
            Flag::VOLATILE,
            Flag::BROADCAST,
        ];
    }

    /**
     * Set room
     *
     * @param  string $room
     * @return $this
     */
    public function in($room)
    {
        //multiple
        if (is_array($room)) {
            foreach ($room as $r) {
                $this->in($r);
            }
            return $this;
        }
        //single
        if (!in_array($room, $this->rooms)) {
            array_push($this->rooms, $room);
        }
        return $this;
    }

    /**
     * Alias for in
     * 
     * @param  string $room
     * @return $this
     */
    public function to($room)
    {
        return $this->in($room);
    }

    /**
     * Set a namespace
     *
     * @param  string $namespace
     * @return $this
     */
    public function of($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Set flags with magic method
     *
     * @param  int $flag
     * @return $this
     */
    public function __get($flag)
    {
        return $this->flag();
    }

    /**
     * Set flags
     *
     * @param  int $flag
     * @return $this
     */
    public function flag($flag) {
        
        if (!array_key_exists($flag, $this->validFlags)) {
            throw new \InvalidArgumentException('Invalid socket.io flag used: ' . $flag);
        }

        $this->flags[$flag] = true;

        return $this;
    }

    /**
     * Set type
     * 
     * @param  int $type
     * @return $this
     */
    public function type($type = Type::REGULAR_EVENT)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Emitting
     *
     * @return $this
     */
    public function emit()
    {
        $packet = [
            'type' => $this->type,
            'data' => func_get_args(),
            'nsp'  => $this->namespace,
        ];

        $options = [
            'rooms' => $this->rooms,
            'flags' => $this->flags,
        ];
        $channelName = sprintf('%s#%s#', $this->prefix, $packet['nsp']);

        $message = $this->packer->pack([$this->uid, $packet, $options]);

        // hack buffer extensions for msgpack with binary
        if ($this->type === Type::BINARY_EVENT) {
            $message = str_replace(pack('c', 0xda), pack('c', 0xd8), $message);
            $message = str_replace(pack('c', 0xdb), pack('c', 0xd9), $message);
        }

        // publish
        if (is_array($this->rooms) && count($this->rooms) > 0) {
            foreach ($this->rooms as $room) {
                $chnRoom = $channelName . $room . '#';
                $this->client->publish($chnRoom, $message);
            }
        } else {
            $this->client->publish($channelName, $message);
        }

        // reset state
        return $this->reset();
    }

    /**
     * Reset all values
     * @return $this
     */
    protected function reset()
    {
        $this->rooms     = [];
        $this->flags     = [];
        $this->namespace = self::DEFAULT_NAMESPACE;
        $this->type      = Type::REGULAR_EVENT;
        return $this;
    }
}
