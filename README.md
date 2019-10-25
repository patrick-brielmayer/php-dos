# PHP-DoS

Script to perform a randomized DoS UDP flood attack

## Usage
`http://127.0.0.1/dos.php?host=TARGET&port=PORT&time=SECONDS&random=true`

## Parameter
<pre>
host	REQUIRED  STRING  target IP
port	OPTIONAL  INT     target port. Default port is 80
time	OPTIONAL  INT     seconds to keep the DoS alive. Default time is 60 seconds
random	OPTIONAL  BOOLEAN randomize all packets to avoid packet drops. Default is false
</pre>

## Requirements
`PHP >= 5.3`
