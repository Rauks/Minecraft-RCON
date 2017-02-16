<?php
/**
 * Connects to a minecraft server using the [Gamespy?] Query protocol.
 * more info on the protocol can be found at
 * http://wiki.vg/Query
 *
 * @copyright 2013 Chris Churchwell
 *
 */
class Query {

  private $host;
  private $port;
  private $timeout;

  private $token = null;

  private $socket;

  private $errstr = "";

  const SESSION_ID = 2;

  const TYPE_HANDSHAKE = 0x09;
  const TYPE_STAT = 0x00;

  public function __construct($host, $port=25565, $timeout=3, $auto_connect = false) {

    $this->host = $host;
    $this->port = $port;
    $this->timeout = $timeout;

    if (is_array($host))
    {
      $this->host = $host['host'];
      $this->port = empty($host['port'])?$port:$host['port'];
      $this->timeout = empty($host['timeout'])?$timeout:$host['timeout'];
      $auto_connect = empty($host['auto_connect'])?$auto_connect:$host['auto_connect'];
    }

    if ($auto_connect === true) {
      $this->connect();
    }

  }

  /**
   * Returns the description of the last error produced.
   *
   * @return String - Last error string.
   */
  public function get_error() {
    return $this->errstr;
  }

  /**
   * Checks whether or not the current connection is established.
   *
   * @return boolean - True if connected; false otherwise.
   */
  public function is_connected() {
    if (empty($this->token)) return false;
    return true;
  }

  /**
   * Disconnects!
   * duh
   */
  public function disconnect() {
    if ($this->socket) {
      fclose($this->socket);
    }
  }

  /**
   * Connects to the host via UDP with the provided credentials.
   * @return boolean - true if successful, false otherwise.
   */
  public function connect()
  {
    $this->socket = fsockopen( 'udp://' . $this->host, $this->port, $errno, $errstr, $this->timeout );

    if (!$this->socket)
    {
      $this->errstr = $errstr;
      return false;
    }

    stream_set_timeout( $this->socket, $this->timeout );
    stream_set_blocking( $this->socket, true );

    return $this->get_challenge();

  }

  /**
   * Authenticates with the host server and saves the authentication token to a class var.
   *
   * @return boolean - True if succesfull; false otherwise.
   */
  private function get_challenge()
  {
    if (!$this->socket)
    {
      return false;
    }

    //build packet to get challenge.
    $packet = pack("c3N", 0xFE, 0xFD, Query::TYPE_HANDSHAKE, Query::SESSION_ID);

    //write packet
    if ( fwrite($this->socket, $packet, strlen($packet)) === FALSE) {
      $this->errstr = "Unable to write to socket";
      return false;
    }

    //read packet.
    $response = fread($this->socket, 2056);

    if (empty($response)) {
      $this->errstr = "Unable to authenticate connection";
      return false;
    }

    $response_data = unpack("c1type/N1id/a*token", $response);

    if (!isset($response_data['token']) || empty($response_data['token'])) {
      $this->errstr = "Unable to authenticate connection.";
      return false;
    }

    $this->token = $response_data['token'];

    return true;

  }

  /**
   * Gets all the info from the server.
   *
   * @return boolean|array - Returns the data in an array, or false if there was an error.
   */
  public function get_info()
  {
    if (!$this->is_connected()) {
      $this->errstr = "Not connected to host";
      return false;
    }
    //build packet to get info
    $packet = pack("c3N2", 0xFE, 0xFD, Query::TYPE_STAT, Query::SESSION_ID, $this->token);

    //add the full stat thingy.
    $packet = $packet . pack("c4", 0x00, 0x00, 0x00, 0x00);

    //write packet
    if (!fwrite($this->socket, $packet, strlen($packet))) {
      $this->errstr = "Unable to write to socket.";
      return false;
    }

    //read packet header
    $response = fread($this->socket, 16);
    //$response = stream_get_contents($this->socket);

    // first byte is type. next 4 are id. dont know what the last 11 are for.
    $response_data = unpack("c1type/N1id", $response);

    //read the rest of the stream.
    $response = fread($this->socket, 2056);

    //split the response into 2 parts.
    $payload = explode ( "\x00\x01player_\x00\x00" , $response);

    $info_raw = explode("\x00",  rtrim($payload[0], "\x00"));

    //extract key->value chunks from info
    $info = array();
    foreach (array_chunk($info_raw, 2) as $pair) {
      list($key, $value) = $pair;
      //strip possible color format codes from hostname
      if ($key == "hostname") {
        $key = 'description';
        $value = $this->strip_color_codes($value);
      }
      $info[$key] = $value;
    }

    //get player data.
    $players_raw = rtrim($payload[1], "\x00");
    $players = array();
    if (!empty($players_raw)) {
      $players = explode("\x00", $players_raw);
    }

    //attach player data to info for simplicity
    $info['players'] = $players;
    $info['hostip'] = gethostbyname($this->host);
    return $info;
  }

  /**
   * Clears Minecraft color codes from a string.
   *
   * @param String $string - the string to remove the codes from
   * @return String - a clean string.
   */
  public function strip_color_codes($string) {
    return preg_replace('/[\x00-\x1F\x80-\xFF]./', '', $string);
  }

}

?>
