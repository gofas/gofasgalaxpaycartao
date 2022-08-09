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
$params	= getGatewayVariables('gofasgalaxpaycartao');

$errormessage = str_replace("INVOICEID", $_POST['invoiceid'], html_entity_decode($params['errormessage']));

if($_POST and !$_POST['error'] ){
	//echo 'Processando o pagamento...';
	require __DIR__.'/functions.php';
	require __DIR__.'/params.php';
	$customer = ggpc_customer($_POST['userid']);
	if($params['sandbox']){
		$api_mode = 'sandbox';
		$galax_id = $params['sandbox_galax_id'];
		$galax_hash = $params['sandbox_galax_hash'];
		$public_token = $params['sandbox_public_token'];
		$charge_url = 'https://api.sandbox.cloud.galaxpay.com.br/v2';
		$sandbox			= true;
	}
	elseif(!$params['sandbox']){
		$api_mode = 'live';
    	$galax_id = $params['galax_id'];
    	$galax_hash = $params['galax_hash'];
		$public_token = $params['public_token'];
    	$charge_url = 'https://api.galaxpay.com.br/v2';
		$sandbox			= false;
	}

	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggpcwhmcsurl') -> get( array( 'value','created_at') ) as $ggpcwhmcsurl_ ){
		$ggpcwhmcsurl					= $ggpcwhmcsurl_->value;
	}
	$token = ggpc_get_token($galax_id,$galax_hash,$sandbox);
	if($params['log']){
		echo '<pre style="height:250px;">token:',print_r([$_POST,$token]);
		//echo 'Postfields:', print_r($postfields);
		echo '</pre>';
	}
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
		$postfields_amount = array('installments' => $_POST['installmentsnum'],'totalAmount' => $_POST['amount'],);
	}
	elseif( (int)$_POST['installmentsnum'] === 1 ){
		$postfields_amount = array('amount' => $_POST['amount'],);
	}
	
	$postfields_ = array(
		'token'=> $token['response']['access_token'],
		'charge'=> ['additionalInfo'=> substr( implode("\n",$line_items),  0, 400),
			'myId'=> $_POST['invoiceid'],
			'value' => $postfields_amount,
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
    		        'myId'=> 'pay-62d75de8cf9d89.87917599',
    		        'hash'=> 'ABCD-1234-EFGH-5678-ABCD-1234-EFGH-5678',
    		        'number'=> '4111 1111 1111 1111',
    		        'holder'=> 'JOAO J J DA SILVA',
    		        'expiresAt'=> '2022-07',
    		        'cvv'=> '363'
    		    ],
    		    'cardOperatorId'=> 'rede',
    		    'preAuthorize'=> false,
    		    'qtdInstallments'=> 12
    		],
		],
		'notificationUrl' => $ggpcwhmcsurl . '/modules/gateways/gofasgalaxpaycartao/includes/callback.php',
		'creditCardHash' => urldecode($_POST['cardHash']),
		'creditCardStore' => $storecard,
		'creditCardId'=> $_POST['credit_card_id'],
	);
	$postfields = array_merge($postfields_,$postfields_amount);
	$charge_ = ggpc_charge($charge_url,$postfields);
	$charge = json_decode( json_encode($charge_), true);
	if( (string)$charge['result']['data']['charges']['0']['payments']['0']['status'] === (string)'CONFIRMED'){
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
			$charge['result']['data']['charges']['0']['payments']['0']['fee'] * $_POST['installmentsnum'],
			'ggpcc-'.$charge['result']['data']['charges']['0']['code'].'-'.$api_mode.'-'.$charge['result']['data']['charges']['0']['payments']['0']['id'].'.',
			$trans_desc
			);	
		if($ggpc_add_trans['error']){
			$error .= $ggpc_add_trans['error'];
		}
	}
	if( $charge['result']['errorMessage']){
		$error .= $charge['result']['errorMessage'];
	}
	// Store/Update card
	if( $_POST['storeCard'] === 'yes' and $_POST['pay_method_id'] and $_POST['cardHash'] and $charge['result']['data']['charges']['0']['payments']['0']['creditCardId'] and ($charge['result']['data']['charges']['0']['payments']['0']['creditCardId'] !== $_POST['credit_card_id']) ){
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
	elseif( $_POST['storeCard'] === 'no' and $_POST['cardHash']){
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
	if($params['debug']){	
		echo '<pre style="height:250px;">$_POST:', print_r($_POST);
		echo 'Postfields:', print_r($postfields);
		echo 'Charge:', print_r($charge), '</pre>';
	}
}
elseif($_POST['error']){
	$error = base64_decode($_POST['error']);
}
if(!$error){
	if($params['log']){
		logModuleCall('gofasgalaxpaycartao', 'process_payment', array('module_version'=>'1.4.0','params'=> $params, 'POST'=>$_POST, 'postfields'=> $postfields,), 'post',  array('charge'=>$charge, 'charge_payments'=>$charge_payments,'charge_payments_'=>$charge_payments_, "$AddPayMethod"=>$AddPayMethod), 'replaceVars');
	}
	$invoice_page =json_encode($ggpcwhmcsurl.'/viewinvoice.php?id='.$_POST['invoiceid'].'&paymentsuccess=true');
	//echo '<script>window.top.location.href='.$invoice_page.'</script>';
}
if($error and !$params['onlycustomerrormessage']){
	echo '<div style="background-color: #f2dede; border-color: #ebccd1; padding: 15px 15px 22px 15px; border-radius: 3px; position: absolute;top: 0;width: 100%;font-size: 16px;color: #a94442;text-align: center;font-family: Verdana, Thaoma, SANS-SERIF;line-height: 30px;">'.$error.'<br>'.$errormessage.'</div>';
	if($params['log']){
		logModuleCall('gofasgalaxpaycartao', 'process_payment', array('module_version'=>'1.4.0','params'=> $params, 'POST'=>$_POST, 'postfields'=> $postfields), 'post',  array('charge'=>$charge), 'replaceVars');
	}
}
if($error and $params['onlycustomerrormessage']){
	echo '<div style="background-color: #f2dede; border-color: #ebccd1; padding: 15px 15px 22px 15px; border-radius: 3px; position: absolute;top: 0;width: 100%;font-size: 16px;color: #a94442;text-align: center;font-family: Verdana, Thaoma, SANS-SERIF;line-height: 30px;">'.$errormessage.'</div>';
	if($params['log']){
		logModuleCall('gofasgalaxpaycartao', 'process_payment', array('module_version'=>'1.4.0','params'=> $params, 'POST'=>$_POST, 'postfields'=> $postfields), 'post',  array('charge'=>$charge), 'replaceVars');
	}
}