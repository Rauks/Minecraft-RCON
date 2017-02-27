<?php
require 'rcon.php';
require '../config.php';

$host = $rconHost;
$port = $rconPort;
$password = $rconPassword;
$timeout = 3;
 
$rcon = new Rcon($host, $port, $password, $timeout);

if(!isset($_POST['cmd'])){
  return;
}

if ($rcon->connect()){
  $rcon->send_command($_POST['cmd']);
  echo htmlspecialchars($rcon->get_response()); 
}
?>
