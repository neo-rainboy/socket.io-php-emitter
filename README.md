shakahl/socket.io-php-emitter
=====================

A PHP implementation of node.js socket.io-emitter (0.1.0).

## Installation

composer require shakahl/socket.io-php-emitter

## Usage

### Emit payload message
```php
use Predis;
use Shakahl\SocketIO;
...

$client = new Predis\Client();

(new Emitter($client))
    ->of('namespace')->emit('event', 'payload message');
```

### Flags
Possible flags
* json
* volatile
* broadcast

#### To use flags, just call it like in example bellow
```php
use Predis;
use Shakahl\SocketIO;
...

$client = new Predis\Client();

(new Emitter($client))
    ->broadcast->emit('broadcast-event', 'payload message');
```

### Emit an object
```php
use Predis;
use Shakahl\SocketIO;
...

$client = new Predis\Client();

(new Emitter($client))
    ->emit('broadcast-event', ['param1' => 'value1', 'param2' => 'value2', ]);
```

## Credits

This library is forked from [exls/socket.io-emitter](https://github.com/exls/socket.io-emitter) created by Anton Pavlov.

