<?php
$config['page_title'] = '6paster';

$config['mysql_host'] = 'localhost';
$config['mysql_user'] = 'paster';
$config['mysql_pass'] = 'samplepass';
$config['mysql_db']   = 'paste';

$config['limit_hour']   = 60;           // throttle pastes per hour
$config['limit_day']    = 100;          // throttle pastes per day

$config['ttl_min']  = 3600;         // minimum lifetime is 1 hour
$config['ttl_max']  = 3600*24*30;       // maximum ttl is 30 days

$config['paste_max_chars'] = 1024*300;        // size limit in characters

$config['server_name'] = 'https://p.6core.net/'; // server url

