<?php
/**
 * Módulo Galax Pay Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14641
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		1.0.0
 */
use WHMCS\Database\Capsule;
function gofasgalaxpaycartao_capture($params){
	require __DIR__.'/functions.php';
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggpcwhmcsurl') -> get( array( 'value','created_at') ) as $ggpcwhmcsurl_ ){
		$ggpcwhmcsurl					= $ggpcwhmcsurl_->value;
	}
	$Params = json_decode( json_encode($params), true);
	$pay_method_id = $Params['payMethod']['payment']['pay_method_id'];
	$params_api = ggpc_api_connect();
	$access_token_ = ggpc_get_token();
	$access_token = $access_token_['result']['access_token'];
	$customer = ggpc_customer($params['clientdetails']['userid']);
	$GetInvoiceResults			= localAPI('getinvoice',array('invoiceid'=>$params['invoiceid'] ), (int)$params['admin'] );
	$line_items = array();
	foreach( $GetInvoiceResults['items']['item'] as $Value){
		$line_items[]	= substr( $Value['description'],  0, 80).' | R$ '.number_format( $Value['amount'],  2, ',', '.');	
	}
	$amount = ((int)$params['amount'])*100;
	$postfields = array(
		'access_token'=> $access_token,
		'charge'=> [
			'additionalInfo'=> substr( implode("\n",$line_items),  0, 400),
			'myId'=> $params['invoiceid'].time(),
			'value' => $amount,
			'payday'=>date("Y-m-d"),
			'payedOutsideGalaxPay' => false,
			'mainPaymentMethodId' => "creditcard",
			'Customer' => [
				'myId'=> $params['clientdetails']['userid'],
				'name'=> $customer['name'],
        		'document'=> $customer['document'],
        		'emails'=> [
        	    	$customer['email'],
        		],
        		'phones'=> [
        	    	$customer['phone'],
        		],
			],
    		'PaymentMethodCreditCard'=> [
    		    'Card'=>[
					'myId'=> $pay_method_id,
				],
			],
    		'preAuthorize'=> false,
    		'qtdInstallments'=> 1
    	],
	);
	$charge = ggpc_charge($postfields);
	if( $charge['result']['error']){
		$error .= $charge['result']['error']['message'];
		$error .= implode(', ',$charge['result']['error']['details']);
	}
	if((string)$charge['result']['Charge']['Transactions']['0']['status'] !== (string)'captured'){
		$error .= $charge['result']['Charge']['Transactions']['0']['statusDescription'] ;
	}
	if( (string)$charge['result']['Charge']['Transactions']['0']['status'] === (string)'denied'){
		$declined = true;
	}
	if(!$error and (string)$charge['result']['Charge']['Transactions']['0']['status'] === (string)'captured'){
		$fee = (($params['amount'] * $params['fee']) / 100);
		return array(
            'status' => 'success',
            'transid' => 'ggpc-'.$charge['result']['Charge']['galaxPayId'].'-'.$params_api['api_mode'].'-'.$charge['result']['Charge']['Transactions']['0']['galaxPayId'].'.',
			'fee' => $fee,
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
				'declinereason' => $charge['result']['Charge']['Transactions']['0']['statusDescription'],
                'rawdata' => $charge,
         );
	}
}