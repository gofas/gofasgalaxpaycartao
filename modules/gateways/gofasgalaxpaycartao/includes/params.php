<?php
/**
 * Módulo Galax Pay Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14641
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		0.1.0
 */
if (!defined('WHMCS')){die();}
//use WHMCS\Database\Capsule;
if ($params['sandbox']){
    $api_mode = 'sandbox';
    $galax_id = $params['sandbox_galax_id'];
    $galax_hash = $params['sandbox_galax_hash'];
    $public_token = $params['sandbox_public_token'];
    $charge_url = 'https://api.sandbox.cloud.galaxpay.com.br/v2';
   //$referralToken = '34c8f0bb';
}
if (!$params['sandbox']){
    $api_mode = 'live';
    $galax_id = $params['galax_id'];
    $galax_hash = $params['galax_hash'];
    $public_token = $params['public_token'];
    $charge_url = 'https://api.galaxpay.com.br/v2';
    //$referralToken = '34c8f0bb';
}