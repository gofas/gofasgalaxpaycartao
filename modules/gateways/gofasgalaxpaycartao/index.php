<?php
/**
 * Módulo Juno Cartão para WHMCS
 * @copyright	2020 Gofas Software
 * @see			https://gofas.net/?p=12042
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		1.4.0
 */
use WHMCS\Database\Capsule;
require __DIR__.'/includes/hooks.php';
require_once __DIR__.'/includes/config.php';
function gofasgalaxpaycartao_3dsecure($params){
	define('CLIENTAREA', true);
	require __DIR__.'/includes/functions.php';		
	require __DIR__.'/includes/params.php';
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggpcwhmcsurl') -> get( array( 'value','created_at') ) as $ggpcwhmcsurl_ ){
		$ggpcwhmcsurl					= $ggpcwhmcsurl_->value;
		$ggpcwhmcsurl_created_at			= $ggpcwhmcsurl_->created_at;
	}
    $url = $ggpcwhmcsurl.'/modules/gateways/gofasgalaxpaycartao/includes/iframe.php';
	if( $params['amount'] >= $params['minimunamount']){
		$token = ggpc_get_token($galax_id,$galax_hash);
		echo '<pre style="height:250px;">token:', print_r($token);
		//echo 'Postfields:', print_r($postfields);
		echo '</pre>';
		 $Params = json_decode( json_encode($params), true);
		 $pay_method_id = $Params['payMethod']['payment']['pay_method_id'];
		 if($pay_method_id){
			foreach( Capsule::table('gofasgalaxpaycartao') -> where('pay_method_id', '=', $pay_method_id) -> where('user_id', '=', $params['clientdetails']['id']) ->
				get( array( 'credit_card_id','api_mode') ) as $stored_card ){
				$credit_card_id_api_mode		= $stored_card->api_mode;
				if((string)$api_mode === (string)$credit_card_id_api_mode){
					$credit_card_id				= $stored_card->credit_card_id;
				}
			}
		}
		$invoice_duedate					= $params['duedate'];
		if( (int)date('Ymd', strtotime($params['duedate'])) >= (int)date('Ymd') ){
			$billet_duedate			= date('Y-m-d', strtotime($invoice_duedate));
		}
		elseif( $invoice_duedate < date('Y-m-d') and !$days_for_due ){
			$billet_duedate			= date('Y-m-d', strtotime('+1 day'));	
		}
		if($params['paymentadvance']){
			$paymentadvance = '1';
		}
		else{
			$paymentadvance = false;
		}
		$postfields = array(
				'userid'=>$params['clientdetails']['id'],
				'invoiceid'=>$params['invoiceid'],
				'amount'=>$params['amount'],
				'payerName'=>$customer['name'],
				'payerCpfCnpj' => $customer['document'],
				'address'=>preg_replace('/[0-9]+/i', '', $params['clientdetails']['address1']),
				'addressNumber'=> $address_number, //preg_replace('/[^0-9]/', '', $params['clientdetails']['address1']),
				'addressComplement'=> $address_complement,
				'neighborhood'=> $params['clientdetails']['address2'],
				'city'=>$params['clientdetails']['city'],
				'state'=>$params['clientdetails']['state'],
				'postcode'=>$params['clientdetails']['postcode'],
				'phonenumber'=>$params['clientdetails']['phonenumber'],
				'email'=>$params['clientdetails']['email'],
				'cclastfour'=>$params['clientdetails']['cclastfour'],
				'cardexp'=>$params['cardexp'],
				'cardtype'=>$params['cardtype'],
				'pay_method_id' => $pay_method_id,
				'credit_card_id'=>$credit_card_id,
				'paymentadvance'=>$paymentadvance,
			);
			$htmlOutput = '<form method="post" action="' . $url . '">';
			foreach ($postfields as $k => $v){
        		$htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . urlencode($v) . '" />';
    		}
			if(!$card_data){
				$htmlOutput .= '<input type="hidden" name="cardHash" id="cardHash" value="" />';
			}
			$htmlOutput .= '<input type="hidden" name="storeCard" id="storeCard" value="yes" />';
			$htmlOutput .= '<input type="hidden" name="installmentsnum" id="installmentsnum" value="1" />';
			$htmlOutput .= '<input type="hidden" name="error" id="error" value="" />';
    		$htmlOutput .= '</form>';
			$htmlOutput .= '<script type="text/javascript" src="https://js.galaxpay.com.br/checkout.min.js"></script>';
			if($params['sandbox']){
				$environment = 'false';
			}
			elseif(!$params['sandbox']){
				$environment = 'true';
			}
			if(!$credit_card_id){
				$htmlOutput .=  '<script type="text/javascript">
				const token = "'.$public_token.'";
				var galaxPay = new GalaxPay(token, '.$environment.');

				const card = galaxPay.newCard({
					number: "'.$params['cardnum'].'",
					holder: "'.$customer['name'].'",
					expiresAt: "20'.substr($params['cardexp'], 2, 2).'-'.substr($params['cardexp'], 0, 2).'",
					cvv: "'.$params['cccvv'].'"
				});
				galaxPay.hashCreditCard(card, function(hash) {
					document.getElementById("cardHash").value = hash;
					console.log(hash);
				}, function (error) {
					document.getElementById("error").value = error;
					console.log(error);
				});
			</script>';
			}
			$htmlOutput .= '<script type="text/javascript">
				document.getElementById("storeCard").value = sessionStorage.getItem("nostore");
				if(sessionStorage.getItem("installments_") > 1 ){
					document.getElementById("installmentsnum").value = sessionStorage.getItem("installments_");
				}
		</script>';
    		return $htmlOutput;
	}
	elseif( $params['amount'] < $params['minimunamount']){
		$error .= 'O valor mínimo para utilizar esse método de pagamento é '.number_format( $params['minimunamount'] ,  2, ',', '.').'.';
		$error .= '<br><a target="_top" style="color: #a94442;" href="'.$ggpcwhmcsurl.'/viewinvoice.php?id='.$params['invoiceid'].'" >Clique aqui e selecione outro método de pagamento</a>.';
		$invoice_page =json_encode($ggpcwhmcsurl.'/viewinvoice.php?id='.$_POST['invoiceid'].'&paymentfailed=true');
		$error .= '<script>
		function ggpc_redir_to_invoice(){
			window.top.location.href='.$invoice_page.'
		}
		</script>';
		$htmlOutput = '<form method="post" action="' . $url . '">';
		$htmlOutput .= '<input type="hidden" name="error" id="error" value="'.base64_encode($error).'" />';
    	$htmlOutput .= '<input type="hidden" name="invoiceid" id="invoiceid" value="'.$params['invoiceid'].'" />';
		$htmlOutput .= '</form>';
		return $htmlOutput;
	}
}
function gofasgalaxpaycartao_capture($params){
	require __DIR__.'/includes/params.php';
	require __DIR__.'/includes/functions.php';
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggpcwhmcsurl') -> get( array( 'value','created_at') ) as $ggpcwhmcsurl_ ){
		$ggpcwhmcsurl					= $ggpcwhmcsurl_->value;
	}
	$Params = json_decode( json_encode($params), true);
	
	if($Params['payMethod']['payment']['pay_method_id']){
		 $pay_method_id = $Params['payMethod']['payment']['pay_method_id'];
		foreach( Capsule::table('gofasgalaxpaycartao')->
			where('pay_method_id', '=', $pay_method_id)->
			where('user_id', '=', $params['clientdetails']['id'])->
			get( array( 'credit_card_id','api_mode') ) as $stored_card ){
				$credit_card_id_api_mode		= $stored_card->api_mode;
				if( (string)$api_mode === (string)$credit_card_id_api_mode ){
					$credit_card_id				= $stored_card->credit_card_id;
			}
		}
	}
	$GetInvoiceResults			= localAPI('getinvoice',array('invoiceid'=>$params['invoiceid'] ), (int)$params['admin'] );
	$line_items = array();
	foreach( $GetInvoiceResults['items']['item'] as $Value){
		$line_items[]	= substr( $Value['description'],  0, 80).' | R$ '.number_format( $Value['amount'],  2, ',', '.');	
	}
	if($params['paymentadvance']){
		$paymentadvance = true;
	}
	else{
		$paymentadvance = false;
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
		'creditCardId'=> $credit_card_id,
		'paymentAdvance'=>$paymentadvance,
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
		logModuleCall('gofasgalaxpaycartao', 'capture_payment', array('module_version'=>'1.4.0','pay_method_id'=>$pay_method_id, 'params'=> $Params), 'post',  array('postfields'=>$postfields,'charge'=>$charge), 'replaceVars');
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