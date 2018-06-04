<?php

return array(
	'collectionDir' => __DIR__ . '/../collections/',
	'libraryDir' => __DIR__ . '/../library/',
	'publicControllerDir' => __DIR__ . '/../controllers/',
	'publicViewDir' => __DIR__ . '/../views/',
	'publicModelsDir' => __DIR__ . '/../models/',
	'baseUri' => '/',
	'cookie' => array(
		'domain' => $_SERVER['HTTP_HOST'],
	),

	'database' => array(//mysql连接
		'adapter' => 'Mysql',
		'host' => '127.0.0.1',
		'port' => '3306',
		'username' => 'root',
		'password' => '123456',
        'charset'=>'utf8',
		'dbname' => 'jiaoben',

	),

    'style' => array(
        'error' => 'alert bg-danger',
        'success' => 'alert bg-success',
        'notice' => 'alert bg-warning',
    ),
    'ad_key'				=> 'miduo',//管理员后台密码加密串
    'auth_key'=>array(
        'key'=>'9e13yK8RN2M0lKP8CLRLhgadfdsf1WMaSlbDeddre_ddsdd@miduo@yousdk@2015'//平台账户加密串
    ),
);