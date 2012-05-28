<?php

//Impostazioni database Postgresql
define('DB_NAME', 'geoweb_genova');
define('DB_SCHEMA', 'gisclient_31');
define('USER_SCHEMA', 'gisclient_31');
define('CHAR_SET', 'UTF-8');
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '5432');
define('DB_USER', 'postgres'); //Superutente
define('DB_PWD', 'postgres');

//Alias per postgis_transform_geom
define('POSTGIS_TRANSOFRM_GEOMETRY','ST_Transform_geometry');

//Utente scritto sul file .map
define('MAP_USER','postgres');
define('MAP_PWD','postgres');

//Superutente per l'accesso ad Author
define('SUPER_USER','admin');
define('SUPER_PWD','admin');

?>