# DoS-PHP-Script

Script to perform a randomized DoS UDP flood attack

## Usage
`http://127.0.0.1/dos.php?host=TARGET&port=PORT&time=SECONDS`

## Parameter
<pre>
host	REQUIRED target IP
port	OPTIONAL target port. Default port is 80
time	OPTIONAL seconds to keep the DoS alive. Default time is 60 seconds
</pre>

## Requirements
`PHP >= 5.2`