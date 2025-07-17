<?php
// websocket-server.php – Tiszta PHP WebSocket szerver Composer nélkül

// A script ne legyen időkorlátos
set_time_limit(0);

// IP-cím és port amin figyel a szerver
$host = '0.0.0.0';
$port = 8080;

// Kapcsolódott kliensek listája
$clients = [];

// Létrehozunk egy TCP socket szervert
$server = stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);
if (!$server) {
    die("Hiba: $errstr ($errno)\n");
}
echo "WebSocket szerver elindult: {$host}:{$port}\n";

// WebSocket handshake – kapcsolat felépítése a klienssel
function handshake($client, $headers) {
    if (preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $headers, $matches)) {
        $key = trim($matches[1]);
        $acceptKey = base64_encode(pack('H*',
            sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
        ));
        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";
        fwrite($client, $upgrade);
        return true;
    }
    return false;
}

// WebSocket adat kódolása (visszaküldéshez)
function encode($payload, $type = 'text', $masked = false) {
    $frameHead = [];
    $payloadLength = strlen($payload);

    $frameHead[0] = 129; // FIN + text frame

    if ($payloadLength <= 125) {
        $frameHead[1] = $payloadLength;
    } elseif ($payloadLength >= 126 && $payloadLength <= 65535) {
        $frameHead[1] = 126;
        $frameHead[2] = ($payloadLength >> 8) & 255;
        $frameHead[3] = $payloadLength & 255;
    } else {
        $frameHead[1] = 127;
        for ($i = 2; $i < 10; $i++) {
            $frameHead[$i] = ($payloadLength >> (8 * (9 - $i))) & 255;
        }
    }

    foreach ($frameHead as &$byte) {
        $byte = chr($byte);
    }

    return implode('', $frameHead) . $payload;
}

// WebSocket adat dekódolása (bejövő üzenet)
function decode($data) {
    $length = ord($data[1]) & 127;
    if ($length == 126) {
        $masks = substr($data, 4, 4);
        $payload = substr($data, 8);
    } elseif ($length == 127) {
        $masks = substr($data, 10, 4);
        $payload = substr($data, 14);
    } else {
        $masks = substr($data, 2, 4);
        $payload = substr($data, 6);
    }
    $text = '';
    for ($i = 0; $i < strlen($payload); ++$i) {
        $text .= $payload[$i] ^ $masks[$i % 4];
    }
    return $text;
}

// Végtelen ciklus – folyamatos figyelés
while (true) {
    $read = $clients;
    $read[] = $server;
    $write = $except = null;

    // Várjuk az eseményeket (stream_select blokkol)
    if (stream_select($read, $write, $except, null) > 0) {

        // Új kliens csatlakozott
        if (in_array($server, $read)) {
            $client = stream_socket_accept($server);
            $clients[] = $client;

            // Handshake lebonyolítása
            $headers = fread($client, 1024);
            handshake($client, $headers);

            // Üzenet az összes kliensnek
            $msg = encode(json_encode([
                "type" => "log",
                "message" => "Új kapcsolat: " . count($clients)
            ]));

            foreach ($clients as $c) {
                @fwrite($c, $msg);
            }

            unset($read[array_search($server, $read)]);
        }

        // Kliens üzenetet küldött
        foreach ($read as $client) {
            $data = @fread($client, 2048);
            if (!$data) {
                // Kapcsolat megszakadt
                $clients = array_filter($clients, fn($c) => $c !== $client);
                $msg = encode(json_encode([
                    "type" => "log",
                    "message" => "Kapcsolat bontva. Aktív kliensek: " . count($clients)
                ]));
                foreach ($clients as $c) {
                    @fwrite($c, $msg);
                }
                fclose($client);
                continue;
            }

            // Üzenet dekódolása
            $msg = decode($data);

            // Ha jött egy "shutdown" parancs, zárjunk le mindent
            if ($msg === '{"type":"shutdown"}') {
                file_put_contents(__DIR__ . "/ws_output.log", "[".date('H:i:s')."] 🛑 Shutdown kérés fogadva\n", FILE_APPEND);
                fclose($client);
                foreach ($clients as $c) {
                    fclose($c);
                }
                fclose($server);
                exit;
            }

            // Üzenet továbbítása minden kliensnek
            $encoded = encode($msg);
            foreach ($clients as $c) {
                @fwrite($c, $encoded);
            }
        }
    }
}
?>
