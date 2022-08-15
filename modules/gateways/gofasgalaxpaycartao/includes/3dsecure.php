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
function gofasgalaxpaycartao_3dsecure($params){
	define('CLIENTAREA', true);
	require __DIR__.'/functions.php';
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggpcwhmcsurl') -> get( array( 'value','created_at') ) as $ggpcwhmcsurl_ ){
		$ggpcwhmcsurl					= $ggpcwhmcsurl_->value;
		$ggpcwhmcsurl_created_at		= $ggpcwhmcsurl_->created_at;
	}
    $url = $ggpcwhmcsurl.'/modules/gateways/gofasgalaxpaycartao/includes/iframe.php';
	if( $params['amount'] >= $params['minimunamount']){
		$Params = json_decode( json_encode($params), true);
		$pay_method_id = $Params['payMethod']['payment']['pay_method_id'];
		$invoice_duedate					= $params['duedate'];
		if( (int)date('Ymd', strtotime($params['duedate'])) >= (int)date('Ymd') ){
			$billet_duedate			= date('Y-m-d', strtotime($invoice_duedate));
		}
		elseif( $invoice_duedate < date('Y-m-d') and !$days_for_due ){
			$billet_duedate			= date('Y-m-d', strtotime('+1 day'));	
		}
		$customer = ggpc_customer($params['clientdetails']['id']);
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
				'cardissuenum'=>$params['cardissuenum'],
				'cardnum'=>$params['cardnum'],
				'expiresAt'=> '20'.substr($params['cardexp'], 2, 2)."-".substr($params["cardexp"], 0, 2),
				'cardexp'=>$params['cardexp'],
				'cccvv'=>$params['cccvv'],
				'cardtype'=>$params['cardtype'],
				'pay_method_id' => $pay_method_id,
				//'credit_card_id'=>$credit_card_id,
			);
			$htmlOutput = '<form method="post" action="' . $url . '">';
			foreach ($postfields as $k => $v){
        		$htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . urlencode($v) . '" />';
    		}
			
			//$htmlOutput .= '<input type="hidden" name="cardHash" id="cardHash" value="" />';			
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
    	$htmlOutput .= '</form>';
		return $htmlOutput;
	}	
}