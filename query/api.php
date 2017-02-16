<?php
require 'query.php';
require '../config.php';

if(!isset($_POST["case"])){
  return;
}

try{
  switch($_POST["case"])  {
    case "getinfo":{
      $Query = new Query($queryHost, $queryPort);
      if ($Query->connect())
      {
        header("Content-type: application/json");
        echo json_encode($Query->get_info());
      }
      break;
    }
    default: break;
  }
}
catch(Exception $e){
  echo $e->getMessage( );
}
?>
