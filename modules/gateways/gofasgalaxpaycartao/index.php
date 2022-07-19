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
require_once __DIR__.'/includes/configuration.php';
function gofasgalaxpaycard_3dsecure($params){
	define('CLIENTAREA', true);
	require __DIR__.'/includes/functions.php';		
	require __DIR__.'/includes/params.php';
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggpwhmcsurl') -> get( array( 'value','created_at') ) as $ggpwhmcsurl_ ){
		$ggpwhmcsurl					= $ggpwhmcsurl_->value;
		$ggpwhmcsurl_created_at			= $ggpwhmcsurl_->created_at;
	}
    $url = $ggpwhmcsurl.'/modules/gateways/gofasgalaxpay/includes/iframe.php';
	if( $params['amount'] >= $params['minimunamount']){
		 $Params = json_decode( json_encode($params), true);
		 $pay_method_id = $Params['payMethod']['payment']['pay_method_id'];
		 if($pay_method_id){
			foreach( Capsule::table('gofasgalaxpay') -> where('pay_method_id', '=', $pay_method_id) -> where('user_id', '=', $params['clientdetails']['id']) ->
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
			if($params['sandbox']){
				$htmlOutput .= '<script type="text/javascript" src="https://sandbox.boletobancario.com/boletofacil/wro/direct-checkout.min.js"></script>';
				$trueOrFalse = 'false';
			}
			elseif(!$params['sandbox']){
				$htmlOutput .= '<script type="text/javascript" src="https://www.boletobancario.com/boletofacil/wro/direct-checkout.min.js"></script>';
				$trueOrFalse = 'true';
			}
			if(!$credit_card_id){
				$htmlOutput .=  '<script type="text/javascript">
  			var checkout = new DirectCheckout("'.$public_token.'", '.$trueOrFalse.'); /* Em sandbox utilizar o construtor new DirectCheckout("SEU TOKEN PUBLICO", false); */
  			var cardData = {
      			cardNumber: "'.$params['cardnum'].'",
      			holderName: "'.$customer['name'].'",
      			securityCode: "'.$params['cccvv'].'",
      			expirationMonth: "'.substr($params['cardexp'], 0, 2).'",
      			expirationYear: "20'.substr($params['cardexp'], 2, 2).'",
  			};
			checkout.getCardHash(cardData, function(cardHash){
				document.getElementById("cardHash").value = cardHash;
			}, function(error){
				document.getElementById("error").value = error;
			});</script>';
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
		$error .= '<br><a target="_top" style="color: #a94442;" href="'.$ggpwhmcsurl.'/viewinvoice.php?id='.$params['invoiceid'].'" >Clique aqui e selecione outro método de pagamento</a>.';
		$invoice_page =json_encode($ggpwhmcsurl.'/viewinvoice.php?id='.$_POST['invoiceid'].'&paymentfailed=true');
		$error .= '<script>
		function ggp_redir_to_invoice(){
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
function gofasgalaxpaycard_capture($params){
	require __DIR__.'/includes/params.php';
	require __DIR__.'/includes/functions.php';
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggpwhmcsurl') -> get( array( 'value','created_at') ) as $ggpwhmcsurl_ ){
		$ggpwhmcsurl					= $ggpwhmcsurl_->value;
	}
	$Params = json_decode( json_encode($params), true);
	
	if($Params['payMethod']['payment']['pay_method_id']){
		 $pay_method_id = $Params['payMethod']['payment']['pay_method_id'];
		foreach( Capsule::table('gofasgalaxpay')->
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
		'token'=> $token,
		'description'=> substr( implode("\n",$line_items),  0, 400),
		'referralToken'=>$toKenrApearysikOpal,
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
		'notificationUrl' => $ggpwhmcsurl.'/modules/gateways/gofasgalaxpay/includes/callback.php', //$_POST['returnurl'],
		'responseType' => 'json',
		'paymentTypes' => 'credit_card',
		'notifyPayer' => false,
		'creditCardId'=> $credit_card_id,
		'paymentAdvance'=>$paymentadvance,
	);
	$charge_ = ggp_charge($charge_url,$postfields);
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
		logModuleCall('gofasgalaxpay', 'capture_payment', array('module_version'=>'1.4.0','pay_method_id'=>$pay_method_id, 'params'=> $Params), 'post',  array('postfields'=>$postfields,'charge'=>$charge), 'replaceVars');
	}
	if(!$error and (string)$charge['result']['data']['charges']['0']['payments']['0']['status'] === (string)'CONFIRMED'){
		return array(
                    'status' => 'success',
                    'transid' => 'ggp-'.$charge['result']['data']['charges']['0']['code'].'-'.$api_mode.'-'.$charge['result']['data']['charges']['0']['payments']['0']['id'].'.',
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
function gofasgalaxpaycard_refund($params){
    require_once __DIR__.'/includes/params.php';
	require_once __DIR__.'/includes/functions.php';
	if($params['sandbox']){
		$refund_url='https://sandbox.boletobancario.com/boletofacil/integration/api/v1/refund-credit-card-payment';
		$api_mode = 'sandbox';
	}
	elseif(!$params['sandbox']){
		$refund_url='https://www.boletobancario.com/boletofacil/integration/api/v1/refund-credit-card-payment';
		$api_mode = 'live';
	}
	if( !function_exists('ggp_get_string_between') ){
	function ggp_get_string_between($string, $start, $end){
		$string = " ".$string;
		$ini = strpos($string,$start);
		if($ini == 0) return "";
		$ini += strlen($start);   
		$len = strpos($string,$end,$ini) - $ini;
		return substr($string,$ini,$len);
	}}
	$trans_id = ggp_get_string_between($params['transid'], $api_mode.'-', '.');
	$trans_code = ggp_get_string_between($params['transid'], 'ggp-', '-'.$api_mode);
	$refund_= ggp_charge($refund_url,array('token'=> $token,'id'=>  $trans_id,));	
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
		logModuleCall('gofasgalaxpay', 'refund_payment', array('module_version'=>'1.4.0','GetTransactions'=>$GetTransactions), 'post',  array('postfields'=>array('token'=> $token,'id'=>  $trans_id,),'refund'=>$refund), 'replaceVars');
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
        	'transid' => 'ggp-'.$trans_code.'-'.$api_mode.'-refund-'.$trans_id,
        	'fee' => $fee,
    	);
	}
}