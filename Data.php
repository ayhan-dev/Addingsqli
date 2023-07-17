<?php 
include "MYSQL.php";

$user_host      = get_current_user();
$passhost       = "pass";  //pass host
$database       = get_current_user()."_ayhan"; //Name data + _uaee
$database_pass  = "ag[6-2fb-26]gfoa#js51";    //Pass data
$domain_host    = $_SERVER['SERVER_NAME'];

$api = new cpanelAPI($user_host,$passhost,$domain_host);
$api->uapi->Mysql->create_database(
    array(
    'name' => $database
    )); 
    
$api->uapi->Mysql->create_user(
   array(
   'name'     => $database, 
   'password' => $database_pass
   ));
   
$api->uapi->Mysql->set_privileges_on_database(
    array(
    'user'       => $database, 
    'database'   => $database, 
    'privileges' => 'ALL'));


/* ayhan G.Y - dev (@HT_ayhan)