<?php
/**
 * Módulo iugu Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14946
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		1.0.0
 */
//use WHMCS\Database\Capsule;
require __DIR__.'/includes/hooks.php';
require_once __DIR__.'/includes/config.php';
require __DIR__.'/includes/3dsecure.php';
require __DIR__.'/includes/capture.php';
require __DIR__.'/includes/refund.php';