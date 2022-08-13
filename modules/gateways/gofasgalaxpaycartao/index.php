<?php
/**
 * Módulo Galax Pay Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14641
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		0.1.0
 */
//use WHMCS\Database\Capsule;
require __DIR__.'/includes/hooks.php';
require_once __DIR__.'/includes/config.php';
require __DIR__.'/includes/3dsecure.php';
require __DIR__.'/includes/capture.php';