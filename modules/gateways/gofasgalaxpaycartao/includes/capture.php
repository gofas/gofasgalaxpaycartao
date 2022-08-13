<?php
/**
 * Módulo Galax Pay Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14641
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		0.1.0
 */
use WHMCS\Database\Capsule;
function gofasgalaxpaycartao_capture($params){
	//require __DIR__.'/includes/params.php';
	require __DIR__.'/functions.php';
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggpcwhmcsurl') -> get( array( 'value','created_at') ) as $ggpcwhmcsurl_ ){
		$ggpcwhmcsurl					= $ggpcwhmcsurl_->value;
	}
	$Params = json_decode( json_encode($params), true);

	$GetInvoiceResults			= localAPI('getinvoice',array('invoiceid'=>$params['invoiceid'] ), (int)$params['admin'] );
	$line_items = array();
	foreach( $GetInvoiceResults['items']['item'] as $Value){
		$line_items[]	= substr( $Value['description'],  0, 80).' | R$ '.number_format( $Value['amount'],  2, ',', '.');	
	}
	
	if($address_complement){
		$address_complement = $address_complement;
	}
	else{
		$address_complement = false;
	}
	$postfields = array(
		'token'=> $galax_id,
		'description'=> substr( implode("\n",$line_items),  0, 400),
		'referralToken'=>$referralToken,
		'reference'=> $params['invoiceid'],
		'amount' => $params['amount'],
		'payerName' => urldecode($customer['name']),
		'payerEmail'=>urldecode($params['clientdetails']['email']),
		'payerCpfCnpj' => urldecode($customer['document']),
		'billingAddressStreet' => urldecode(preg_replace('/[0-9]+/i', '', $params['clientdetails']['address1'])),
		'billingAddressNumber'=>urldecode($address_number),
		'billingAddressComplement'=>urldecode($address_complement),
		'billingAddressNeighborhood'=>urldecode($params['clientdetails']['address2']),
		'billingAddressCity'=>urldecode($params['clientdetails']['city']),
		'billingAddressState'=>urldecode($params['clientdetails']['state']),
		'billingAddressPostcode'=>urldecode($params['clientdetails']['postcode']),
		'notificationUrl' => $ggpcwhmcsurl.'/modules/gateways/gofasgalaxpaycartao/includes/callback.php', //$_POST['returnurl'],
		'responseType' => 'json',
		'paymentTypes' => 'credit_card',
		'notifyPayer' => false,
		//'creditCardId'=> $credit_card_id,
		//'paymentAdvance'=>$paymentadvance,
	);
	$charge_ = ggpc_charge($charge_url,$postfields);
	$charge = json_decode( json_encode($charge_), true);
	if( $charge['result']['errorMessage']){
		$error .= $charge['result']['errorMessage'];
	}
	if( (string)$charge['result']['data']['charges']['0']['payments']['0']['status'] !== (string)'CONFIRMED'){
		$error .= 'Pagamento não confirmado. '.$charge['result']['data']['charges']['0']['payments']['0']['status'];
	}
	if( (string)$charge['result']['data']['charges']['0']['payments']['0']['status'] === (string)'DECLINED'){
		$declined = true;
	}

	if($params['log']){
		logModuleCall('gofasgalaxpaycartao', 'capture_payment', array('module_version'=>'1.4.0', 'params'=> $Params), 'post',  array('postfields'=>$postfields,'charge'=>$charge), 'replaceVars');
	}
	if(!$error and (string)$charge['result']['data']['charges']['0']['payments']['0']['status'] === (string)'CONFIRMED'){
		return array(
                    'status' => 'success',
                    'transid' => 'ggpcc-'.$charge['result']['data']['charges']['0']['code'].'-'.$api_mode.'-'.$charge['result']['data']['charges']['0']['payments']['0']['id'].'.',
					'fee' => $charge['result']['data']['charges']['0']['payments']['0']['fee'],
					'gatewayid' => NULL,
					'rawdata' => $charge
                );
	}
	if($error){
		return array(
                'status' => 'error',
                'rawdata' => $charge,
         );
	}
	if($declined){
		return array(
                'status' => 'declined',
				'declinereason' => 'Pagamento não foi autorizado.',
                'rawdata' => $charge,
         );
	}
}
function gofasgalaxpaycartao_refund($params){
    require_once __DIR__.'/includes/params.php';
	require_once __DIR__.'/includes/functions.php';
	if($params['sandbox']){
		$refund_url='https://api.sandbox.cloud.galaxpay.com.br/v2/charges/';
		$api_mode = 'sandbox';
	}
	elseif(!$params['sandbox']){
		$refund_url='https://api.galaxpay.com.br/v2/charges/';
		$api_mode = 'live';
	}
	if( !function_exists('ggpc_get_string_between') ){
	function ggpc_get_string_between($string, $start, $end){
		$string = " ".$string;
		$ini = strpos($string,$start);
		if($ini == 0) return "";
		$ini += strlen($start);   
		$len = strpos($string,$end,$ini) - $ini;
		return substr($string,$ini,$len);
	}}
	$trans_id = ggpc_get_string_between($params['transid'], $api_mode.'-', '.');
	$trans_code = ggpc_get_string_between($params['transid'], 'ggpcc-', '-'.$api_mode);
	$refund_= ggpc_charge($refund_url,array('token'=> $galax_id,'id'=>  $trans_id,));	
	$refund = json_decode( json_encode($refund_), true);
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
		logModuleCall('gofasgalaxpaycartao', 'refund_payment', array('module_version'=>'1.4.0','GetTransactions'=>$GetTransactions), 'post',  array('postfields'=>array('token'=> $galax_id,'id'=>  $trans_id,),'refund'=>$refund), 'replaceVars');
	}
	if($refund['result']['errorMessage']){
		return array(
    	    'status' => 'error',
	        'rawdata' => $refund,
	    );
	}
	elseif($refund['result']['success']){
	    return array(
        	'status' => 'success',
        	'rawdata' => $refund,
        	'transid' => 'ggpcc-'.$trans_code.'-'.$api_mode.'-refund-'.$trans_id,
        	'fee' => $fee,
    	);
	}
}