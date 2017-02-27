<?php
header('Content-type: application/json');

require 'rcon.php';
require '../config.php';

$host = $rconHost;
$port = $rconPort;
$password = $rconPassword;
$timeout = 3;

$response = array();
$rcon = new Rcon($host, $port, $password, $timeout);

if(!isset($_POST['cmd'])){
  $response['status'] = 'error';
  $response['error'] = 'Empty command';
}
else{
  if ($rcon->connect()){
    $rcon->send_command($_POST['cmd']);
    $response['status'] = 'success';
    $response['command'] = $_POST['cmd'];
    $response['response'] = $rcon->get_response();
  }
  else{
    $response['status'] = 'error';
    $response['error'] = 'RCON connection error';
  }
}

echo json_encode($response);
?>
