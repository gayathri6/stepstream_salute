<?php
if (!defined('STATUSNET') && !defined('LACONICA')) { exit(1); }

$config['site']['name'] = 'stepstream';

$config['site']['server'] = 'salute.cc.gt.atl.ga.us';
$config['site']['path'] = 'statusnet'; 

$config['db']['database'] = 'mysqli://root:stepstream@8903@localhost/stepstream';

$config['db']['type'] = 'mysql';

$config['site']['profile'] = 'private';

$config['site']['theme'] = 'stepstream';

addPlugin('Tips');

