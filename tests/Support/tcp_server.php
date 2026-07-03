<?php

declare(strict_types=1);

/*
 * Standalone stub TCP server for SocketClient tests. NOT a class (excluded from
 * PSR-4). Binds an ephemeral port, prints "PORT=<n>\n" to stdout (the test blocks
 * reading that line, so there is no bind race), accepts ONE connection, captures
 * the exact frame bytes to --capture, then replies per --ack. It closes stdout on
 * exit, which the test uses as a sleep-free "done" signal (stream_get_contents).
 *
 *   --ack=2:OK   read frame, capture, reply "2:OK"   (happy path)
 *   --ack=bad    read frame, capture, reply "NOPE"   (wrong ack)
 *   --ack=none   read frame, capture, close silently (no ack)
 *   --ack=close  accept then close immediately       (socket dies mid-exchange)
 *
 * --reply=<bytes> writes extra bytes immediately after the ack — used to script
 * the daemon's `{length}:{json}` STATS counters reply (or garbage instead of it).
 */

$opts = getopt('', ['ack:', 'capture:', 'reply:']);
$ack = $opts['ack'] ?? '2:OK';
$capture = $opts['capture'] ?? '';
$reply = $opts['reply'] ?? '';

$server = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);

if ($server === false) {
    fwrite(STDERR, "bind failed: {$errstr}\n");
    exit(1);
}

$name = stream_socket_get_name($server, false);
$port = substr($name, strrpos($name, ':') + 1);

fwrite(STDOUT, 'PORT='.$port."\n");
fflush(STDOUT);

$conn = @stream_socket_accept($server, 5);

if ($conn === false) {
    exit(2);
}

if ($ack === 'close') {
    fclose($conn);
    exit(0);
}

stream_set_timeout($conn, 2);

// Read the length prefix, then exactly that many body bytes.
$data = '';
while (strpos($data, ':') === false) {
    $chunk = @fread($conn, 8192);
    if ($chunk === '' || $chunk === false) {
        break;
    }
    $data .= $chunk;
}

$colon = strpos($data, ':');
if ($colon !== false) {
    $need = $colon + 1 + (int) substr($data, 0, $colon);
    while (strlen($data) < $need) {
        $chunk = @fread($conn, 8192);
        if ($chunk === '' || $chunk === false) {
            break;
        }
        $data .= $chunk;
    }
}

if ($capture !== '') {
    file_put_contents($capture, $data);
}

if ($ack === 'bad') {
    fwrite($conn, 'NOPE');
} elseif ($ack !== 'none') {
    fwrite($conn, '2:OK');
}

if ($reply !== '') {
    fwrite($conn, $reply);
}

fclose($conn);
exit(0);
