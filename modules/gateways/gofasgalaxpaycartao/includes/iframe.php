<?php
/**
 * Módulo Galax Pay Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14641
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		0.1.0
 */
// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../../includes/invoicefunctions.php';
use WHMCS\Database\Capsule;
if($_POST and !$_POST['error'] ){
	require __DIR__.'/functions.php';
	$params = getGatewayVariables('gofasgalaxpaycartao');
	$params_api = ggpc_api_connect();
	$customer = ggpc_customer($_POST['userid']);
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggpcwhmcsurl') -> get( array( 'value','created_at') ) as $ggpcwhmcsurl_ ){
		$ggpcwhmcsurl					= $ggpcwhmcsurl_->value;
	}
	$access_token_ = ggpc_get_token();
	$access_token = $access_token_['response']['access_token'];
	// Invoice Info
	$GetInvoiceResults			= localAPI('getinvoice',array('invoiceid'=>$_POST['invoiceid'] ),(int)$params['admin']);
	$line_items = array();
	foreach( $GetInvoiceResults['items']['item'] as $Value){
		$line_items[]	= substr( $Value['description'],  0, 80).' | R$ '.number_format( $Value['amount'],  2, ',', '.');	
	}
	if( (int)$_POST['installmentsnum'] > 1 ){
		//$postfields_amount = int(array('installments' => $_POST['installmentsnum'],'totalAmount' => $_POST['amount'],))*100;
	}
	elseif( (int)$_POST['installmentsnum'] === 1 ){
		//$postfields_amount = int(array('amount' => $_POST['amount'],))*100;
	}
	$amount = ((int)$_POST['amount'])*100;
	// Cobrança avulsa
	if($_POST['cardissuenum']){
		$card = [
			'myId'=> $_POST['pay_method_id'],
		];
	}
	if(!$_POST['cardissuenum']){
		$card = [
			'myId'=> (string)((int)$_POST['pay_method_id']+1),
			'hash'=> '',
			'number'=> $_POST['cardnum'],
			'holder'=> $customer['name'],
			'expiresAt'=> $_POST['expiresAt'],
			'cvv'=> $_POST['cccvv'],
		];
	}
	$postfields = array(
		'access_token'=> $access_token,
		'charge'=> ['additionalInfo'=> substr( implode("\n",$line_items),  0, 400),
			'myId'=> $_POST['invoiceid'].time(),
			'value' => $amount,
			'payday'=>date("Y-m-d"),
			'payedOutsideGalaxPay' => false,
			'mainPaymentMethodId' => "creditcard",
			'Customer' => [
				'myId'=> $customer['id'],
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
    		    'Card'=> $card,
    		    'preAuthorize'=> false,
    		    'qtdInstallments'=> $_POST['installmentsnum']
    		],
		],
		'notificationUrl' => $ggpcwhmcsurl . '/modules/gateways/gofasgalaxpaycartao/includes/callback.php',
		//'creditCardHash' => urldecode($_POST['cardHash']),
		'creditCardStore' => $storecard,
		//'credit_card_id'=> $_POST['credit_card_id'],
	);
	$charge = ggpc_charge($postfields);
	// Capturado
	if( (string)$charge['result']['Charge']['Transactions']['0']['status'] === (string)'captured'){
		if( (int)$_POST['installmentsnum'] > 1 ){
			$trans_desc = "Pagamento Aprovado - Parcelado em ".(int)$_POST['installmentsnum']."x R$".number_format( $_POST['amount'] / (int)$_POST['installmentsnum'] ,  2, ',', '.')." - ".$_POST['cardtype'].'-'.$_POST['cclastfour'];
		}
		else {
			$trans_desc = "Pagamento Aprovado - ".$_POST['cardtype'].'-'.$_POST['cclastfour'];
		}
		//
		$fee = (($_POST['amount'] * $params['fee']) / 100);
		$ggpc_add_trans = ggpc_add_trans(
			$_POST['userid'],
			$_POST['invoiceid'],
			$_POST['amount'],
			$fee,
			'ggpc-'.$charge['result']['Charge']['galaxPayId'].'-'.$params_api['api_mode'].'-'.$charge['result']['Charge']['Transactions']['0']['galaxPayId'].'.',
			$trans_desc
			);	
		if($ggpc_add_trans['error']){
			$error .= $ggpc_add_trans['error'];
		}
		// save card
		if((string)$_POST['storeCard'] === (string)'yes' and $charge['result']['Charge']['Transactions']['0']['CreditCard']['Card']['myId'] and !$_POST['cardissuenum']){
			$card_to_add = [
				'userid'=>$_POST['userid'],
				'cclastfour'=>$_POST['cclastfour'],
				'cardexp'=>$_POST['cardexp'],
				'cardtype'=>$_POST['cardtype'],
				'cardissuenum'=>$charge['result']['Charge']['Transactions']['0']['CreditCard']['Card']['galaxPayId'],//$_POST['issuenumber'],
				'myId'=> (string)((int)$_POST['pay_method_id']+1),
			];
			$ggpc_add_card = ggpc_card_add($card_to_add,$_POST['pay_method_id']);
			if((string)$ggpc_add_card !== (string)'success'){
				$error .= $ggpc_add_card;
			}
		}
		if(((string)$_POST['storeCard'] !== (string)'yes' || (string)$ggpc_add_card !== (string)'success') and !$_POST['cardissuenum']){
			$ggpc_card_del = ggpc_card_del($_POST['pay_method_id']);
			if((string)$ggpc_card_del !== (string)'success'){
				$error .= $ggpc_card_del;
			}
		}
	}
	if( (string)$charge['result']['Charge']['Transactions']['0']['status'] !== (string)'captured'){
		$error .= $charge['result']['Charge']['Transactions']['0']['statusDescription'];
	}
	if( $charge['result']['error']){
		$error .= $charge['result']['error']['message'];
		$error .= implode(', ',$charge['result']['error']['details']);
	}
	
}
if($_POST['error']){
	$error .= $_POST['error'];
}
if($params['log']){	
	$log_request = [
		'post'=>$_POST,
		'params'=> $params,
		'access_token'=> $access_token,
		'customer'=> $customer,
		'postfields'=> $postfields,
	];
	$log_response = [
		 'charge'=> $charge,
		 'charge_capture'=>$charge_capture,
		 'ggpc_add_card'=>$ggpc_add_card,
		 'ggpc_card_del'=> $ggpc_card_del,
		 'error'=>$error,
	];
	//if($log['POST']['cardnum']){
		//$log['POST']['cardnum'] = 'xxxx xxxx xxxx '.$_POST['cclastfour'];
	//}
	//if($log['POST']['expiresAt']){
		//$log['POST']['expiresAt'] = 'xxxx-xx';
	//}
	//if($log['POST']['cardexp']){
		//$log['POST']['cardexp'] = 'xxxx';
	//}
	//if($log['POST']['cccvv']){
		//$log['POST']['cccvv'] = 'xxx';
	//}
	//if($log['Postfields']['charge']['PaymentMethodCreditCard']['Card']['number']){
		//$log['Postfields']['charge']['PaymentMethodCreditCard']['Card']['number'] = 'xxxx xxxx xxxx '.$_POST['cclastfour'];
	//}
    //if($log['Postfields']['charge']['PaymentMethodCreditCard']['Card']['expiresAt']){
		//$log['Postfields']['charge']['PaymentMethodCreditCard']['Card']['expiresAt'] = 'xxxx-xx';
	//}
	//if($log['Postfields']['charge']['PaymentMethodCreditCard']['Card']['cvv']){
    	//$log['Postfields']['charge']['PaymentMethodCreditCard']['Card']['cvv']= 'xxx';
	//}
	//if($log['Charge']['result']['Charge']['Transactions']['0']['CreditCard']['Card']['number']){
		//$log['Charge']['result']['Charge']['Transactions']['0']['CreditCard']['Card']['number'] = 'xxxx xxxx xxxx '.$_POST['cclastfour'];
	//}
	//if($log['Charge']['result']['Charge']['PaymentMethodCreditCard']['0']['CreditCard']['Card']['number']){
		//$log['Charge']['result']['Charge']['PaymentMethodCreditCard']['0']['CreditCard']['Card']['number'] = 'xxxx xxxx xxxx '.$_POST['cclastfour'];
	//}
	
	//echo '<pre style="height:250px;">',print_r([$log_request,$log_response]),'</pre>';
	logModuleCall('gofasgalaxpaycartao', 'iframe_payment', ['module_version'=>ggpc_version(),'request'=>$log_request],'post',['response'=>$log_response],'replaceVars');
}
if(!$error){
	$invoice_page =json_encode($ggpcwhmcsurl.'/viewinvoice.php?id='.$_POST['invoiceid'].'&paymentsuccess=true');
	echo '<script>window.top.location.href='.$invoice_page.'</script>';
}
if($error){
	echo '<div style="background-color: #f2dede; border-color: #ebccd1; padding: 15px 15px 22px 15px; border-radius: 3px; position: absolute;top: 0;width: 100%;font-size: 16px;color: #a94442;text-align: center;font-family: Verdana, Thaoma, SANS-SERIF;line-height: 30px;">'.$error;
	$invoice_page =$ggpcwhmcsurl.'/viewinvoice.php?id='.$_POST['invoiceid'].'&paymentfailed=true';
	echo '<br><a target="_top" class="btn btn-success btn-sm" href="'.$invoice_page.'">Voltar</a></div>';
}