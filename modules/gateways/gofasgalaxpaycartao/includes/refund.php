<?php
/**
 * Módulo GalaxPay Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14641
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		1.0.0
 */
use WHMCS\Database\Capsule;
if(!function_exists('gofasgalaxpaycartao_refund')){
function gofasgalaxpaycartao_refund($params){
	require_once __DIR__.'/functions.php';
	$params_api = ggpc_api_connect();
	$access_token_ = ggpc_get_token();
	$access_token = $access_token_['result']['access_token'];
	$charge_id = ggpc_get_string_between($params['transid'], 'ggpc-', '-'.$params_api['api_mode']);
	$refund = ggpc_refund($charge_id,$access_token);

	$GetTransactions = localAPI('GetTransactions',array('transid' => $params['transid']), (int)$params['admin']);
	$dt = new DateTime($GetTransactions['transactions']['transaction']['0']['date']);
	$payment_date = $dt->format('Ymd');
	$today = date('Ymd');
	if((int)$today > (int)$payment_date){
		$fee = $GetTransactions['transactions']['transaction']['0']['fees'];
	}
	elseif((int)$today === (int)$payment_date){
		$fee = NULL;
	}
	if($params['log']){
		logModuleCall('gofasgalaxpaycartao', 'refund_payment', array('module_version'=>ggpc_version(),'params'=>$params,'GetTransactions'=>$GetTransactions), 'post',  array('access_token'=> $access_token,'charge_id'=> $charge_id,'refund'=>$refund), 'replaceVars');
	}
	if( $refund['result']['error'] || (int)$refund['result_code'] !== 200){
		return array(
    	    'status' => 'error',
	        'rawdata' => $refund,
	    );
	}
	if((int)$refund['result_code'] === 200){
	    return array(
        	'status' => 'success',
        	'rawdata' => $refund,
        	'ggpc-'.$charge['result']['Charge']['galaxPayId'].'-'.$params_api['api_mode'].'-'.$charge_id.'.',
			'fee' => $fee,
    	);
	}
}}