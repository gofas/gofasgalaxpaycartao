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
	//echo 'Processando o pagamento...';
	require __DIR__.'/functions.php';
	$params = getGatewayVariables('gofasgalaxpaycartao');
	$params_api = ggpc_api_connect();
	//$errormessage = str_replace("INVOICEID", $_POST['invoiceid'], html_entity_decode($params['errormessage']));
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
	
	if( $_POST['storeCard'] === 'yes'){
		$storecard = true;
	}
	if( $_POST['storeCard'] === 'no'){
		$storecard = false;
	}
	
	if( (int)$_POST['installmentsnum'] > 1 ){
		//$postfields_amount = int(array('installments' => $_POST['installmentsnum'],'totalAmount' => $_POST['amount'],))*100;
	}
	elseif( (int)$_POST['installmentsnum'] === 1 ){
		//$postfields_amount = int(array('amount' => $_POST['amount'],))*100;
	}
	$amount = ((int)$_POST['amount'])*100;
	// Cobrança avulsa
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
    		    'Card'=> [
    		        'myId'=> $_POST['pay_method_id'],
    		        'hash'=> $_POST['cardHash'],
    		        'number'=> $_POST['cardnum'],
    		        'holder'=> $customer['name'],
    		        'expiresAt'=> $_POST['expiresAt'],
    		        'cvv'=> $_POST['cccvv'],
    		    ],
    		    //'cardOperatorId'=> 'rede',
    		    'preAuthorize'=> false,
    		    'qtdInstallments'=> $_POST['installmentsnum']
    		],
		],
		'notificationUrl' => $ggpcwhmcsurl . '/modules/gateways/gofasgalaxpaycartao/includes/callback.php',
		'creditCardHash' => urldecode($_POST['cardHash']),
		'creditCardStore' => $storecard,
		'creditCardId'=> $_POST['credit_card_id'],
	);
	//$postfields = array_merge($postfields_,$postfields_amount);
	$charge = ggpc_charge($postfields);
	//$charge_capture = ggpc_charge_capture($charge['response']['Charge']['galaxPayId'],$access_token);

	if( (string)$charge['result']['Charge']['Transactions']['0']['status'] === (string)'captured'){
		if( (int)$_POST['installmentsnum'] > 1 ){
			$trans_desc = "Pagamento Aprovado - Parcelado em ".(int)$_POST['installmentsnum']."x R$".number_format( $_POST['amount'] / (int)$_POST['installmentsnum'] ,  2, ',', '.')." - ".$_POST['cardtype'].'-'.$_POST['cclastfour'];
		}
		else {
			$trans_desc = "Pagamento Aprovado - ".$_POST['cardtype'].'-'.$_POST['cclastfour'];
		}
		$ggpc_add_trans = ggpc_add_trans(
			$_POST['userid'],
			$_POST['invoiceid'],
			$_POST['amount'],
			$params['fee'] * $_POST['installmentsnum'],
			'ggpc-'.$charge['result']['Charge']['galaxPayId'].'-'.$params_api['api_mode'].'-'.$charge['result']['Charge']['Transactions']['0']['galaxPayId'].'.',
			$trans_desc
			);	
		if($ggpc_add_trans['error']){
			$error .= $ggpc_add_trans['error'];
		}
	}
	if( $charge['result']['error']){
		$error .= $charge['result']['error']['message'];
		$error .= implode(', ',$charge['result']['error']['details']);
	}
	if( (string)$charge['result']['Charge']['Transactions']['0']['status'] !== (string)'captured'){
		$error .= $charge['result']['Charge']['Transactions']['0']['statusDescription'];
	}
	/*
	// Store/Update card
	if( $_POST['storeCard'] === 'yes' and $_POST['pay_method_id'] and $_POST['cardHash'] and (string)$charge['result']['Charge']['Transactions']['0']['status'] === (string)'captured' ){
		$card_postfields = [
			'myId'=> $_POST['pay_method_id'], //$charge['result']['Charge']['Transactions']['0']['CreditCard']['Card']['myId'],
			'hash'=> $_POST['cardHash'],
			'number'=>$_POST['cardnum'],
			'payerName'=> $_POST['cardHash'],
			'expiresAt'=> $_POST['expiresAt'],
			'cvv'=> $_POST['cccvv'],
		];
		//$create_card = ggpc_card_create($card_postfields,$access_token);
		
		
		try {
			Capsule::table('tblcreditcards')->where( 'pay_method_id', $_POST['pay_method_id'])->delete();
		}
		catch (\Exception $e){
			$error .= $e->getMessage();
		}
		try {
			Capsule::table('tblpaymethods')->where( 'id', $_POST['pay_method_id'])->delete();
		}
		catch (\Exception $e){
			$error .= $e->getMessage();
		}	
		
		try {
			$createCardPayMethod = createCardPayMethod( // Function available in WHMCS 7.9 and later
                $_POST['userid'],
                'gofasgalaxpaycartao',
                '000000000'.$_POST['cclastfour'],
                $_POST['cardexp'],
                $_POST['cardtype'],
               	NULL, //start date
                NULL, //issue number
                $charge['result']['data']['charges']['0']['payments']['0']['creditCardId']
            );
        }
		catch (Exception $e){
            $error .= $e->getMessage();
        }
		//
		try {
			Capsule::table('gofasgalaxpaycartao')->insert(
				array(
					'user_id' => $_POST['userid'],
					'credit_card_id'=>$charge['result']['data']['charges']['0']['payments']['0']['creditCardId'],
					'pay_method_id'=>$_POST['pay_method_id']+1,
					'card_type' => $_POST['cardtype'],
					'last_four'=> $_POST['cclastfour'],
					'api_mode'=>$api_mode,
					'updated_at' => date("Y-m-d H:i:s")
				)
			);
		}
		catch (\Exception $e){
			$error .= $e->getMessage();
		}
		
	}
	elseif( $_POST['storeCard'] === 'no' and (!$_POST['pay_method_id'] || !$_POST['cardHash']) ){
		try {
			Capsule::table('tblcreditcards')->where( 'pay_method_id', $_POST['pay_method_id'])->delete();
		}
		catch (\Exception $e){
			$error .= $e->getMessage();
		}
		try {
			Capsule::table('tblpaymethods')->where( 'id', $_POST['pay_method_id'])->delete();
		}
		catch (\Exception $e){
			$error .= $e->getMessage();
		}
	}
	*/
	
}
if($params['log']){	
	$log = [
		'POST'=>$_POST,
		 'params'=> $params,
		 'access_token'=> $access_token,
		 'customer'=> $customer,
		 'Postfields'=> $postfields,
		 'Charge'=> $charge, 
		 'create_card'=> $create_card, 
	];
	echo '<pre style="height:250px;">',print_r($log),'</pre>';
}
elseif($_POST['error']){
	$error = $_POST['error'];
}
if(!$error){
	if($params['log']){
		logModuleCall('gofasgalaxpaycartao', 'process_payment', array('module_version'=>ggpc_version(),'params'=> $params, 'POST'=>$_POST, 'postfields'=> $postfields,), 'post',  array('charge'=>$charge, 'charge_payments'=>$charge_payments,'charge_payments_'=>$charge_payments_, "$AddPayMethod"=>$AddPayMethod), 'replaceVars');
	}
	$invoice_page =json_encode($ggpcwhmcsurl.'/viewinvoice.php?id='.$_POST['invoiceid'].'&paymentsuccess=true');
	echo '<script>window.top.location.href='.$invoice_page.'</script>';
}
if($error and !$params['onlycustomerrormessage']){
	echo '<div style="background-color: #f2dede; border-color: #ebccd1; padding: 15px 15px 22px 15px; border-radius: 3px; position: absolute;top: 0;width: 100%;font-size: 16px;color: #a94442;text-align: center;font-family: Verdana, Thaoma, SANS-SERIF;line-height: 30px;">'.$error.'<br>'.$errormessage.'</div>';
	if($params['log']){
		logModuleCall('gofasgalaxpaycartao', 'process_payment', array('module_version'=>ggpc_version(),'params'=> $params, 'POST'=>$_POST, 'postfields'=> $postfields), 'post',  array('charge'=>$charge), 'replaceVars');
	}
}
if($error and $params['onlycustomerrormessage']){
	echo '<div style="background-color: #f2dede; border-color: #ebccd1; padding: 15px 15px 22px 15px; border-radius: 3px; position: absolute;top: 0;width: 100%;font-size: 16px;color: #a94442;text-align: center;font-family: Verdana, Thaoma, SANS-SERIF;line-height: 30px;">'.$errormessage.'</div>';
	if($params['log']){
		logModuleCall('gofasgalaxpaycartao', 'process_payment', array('module_version'=>ggpc_version(),'params'=> $params, 'POST'=>$_POST, 'postfields'=> $postfields), 'post',  array('charge'=>$charge), 'replaceVars');
	}
}