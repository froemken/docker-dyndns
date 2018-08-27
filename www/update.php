<?php
$user = 'username';
$ip = '123.123.123.123';

$output = [];

exec(
	sprintf(
		'%s %s %s %s',
		'/bin/sh',
		'/var/bind/nsclient_update.sh',
		escapeshellarg($user),
		escapeshellarg($ip)
	),
	$output
);

var_dump($output);
