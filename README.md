# Adding.sqli 

 - Automatic addition and deletion of databases in the cPanel host to automate installation and database operations

## Allegations
```php
include "MYSQL.php";
$database = get_current_user() . "_ayhan"; 
$api = new cpanelAPI("user_host", "passhost", "domain_host");
```

## Create a database
```php
$api->api->Mysql->create_database(array('name' => $database));
$api->api->Mysql->create_user(array('name' => $database,'password' => $database_pass));
$api->api->Mysql->set_privileges_on_database(array('user' => $database,'database' => $database,'privileges' => 'ALL'));
```
