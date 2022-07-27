<?php
/**
 * Módulo Galax Pay Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14641
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		0.1.0
 */
if(!defined("WHMCS")){die();}
use WHMCS\Database\Capsule;
if( !function_exists('ggpc_get_token') ){
	function ggpc_get_token($client_id,$client_secret){
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://api.sandbox.cloud.galaxpay.com.br/v2/token',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>'{
			  "grant_type": "authorization_code",
			  "scope": "customers.read customers.write plans.read plans.write transactions.read transactions.write webhooks.write cards.read cards.write card-brands.read subscriptions.read subscriptions.write charges.read charges.write boletos.read carnes.read payment-methods.read"
			}',
			CURLOPT_HTTPHEADER => array(
		    	'Authorization: Basic '.base64_encode((string)$client_id.':'.(string)$client_secret),
		    	'Content-Type: application/json'
		  	),
		));
		$response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$response = json_decode(curl_exec($curl), true);
		//$response = curl_exec($curl);
		curl_close($curl);
		return ['response_code'=>$response_code,'response'=>$response];
	}
}
if( !function_exists('ggpc_charge') ){
	function ggpc_charge($charge_url,$postfields){
    	$curl = curl_init();
		$query = $charge_url;
		curl_setopt($curl, CURLOPT_URL, $charge_url);
    	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,1);
    	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postfields) );
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		$result = json_decode(curl_exec($curl));
    	$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return array('result'=>$result,'http_status'=>$http_status);
	}
}
if( !function_exists('ggpc_add_trans') ){
	function ggpc_add_trans( $user_id, $invoice_id, $amount, $fee, $charge_id, $description ){	
 		$addtransvalues['userid'] = $user_id;
 		$addtransvalues['invoiceid'] = $invoice_id;
 		$addtransvalues['description'] = $description;
 		$addtransvalues['amountin'] = $amount;
 		$addtransvalues['fees'] = $fee;
 		$addtransvalues['paymentmethod'] = 'gofasgalaxpaycartao';
 		$addtransvalues['transid'] = $charge_id;
 		$addtransvalues['date'] = date('d/m/Y');
		$addtransresults = localAPI( "addtransaction", $addtransvalues, (int)$params['admin']);
		if( $addtransresults['result'] === 'success'){
			return array('values'=>$addtransvalues, 'result'=>$addtransresults);
		}
		elseif($addtransresults['result'] !== 'success'){
			$error = '<b>Não foi possível gravar a transação.</b>';
			return array('error'=>$error, 'values'=>$addtransvalues, 'result'=>$addtransresults);
		}
	}
}
if( !function_exists('ggpc_config') ){
	function ggpc_config($set = false){
		$setting = array();
		foreach( Capsule::table('tbladdonmodules') -> where( 'module', '=', 'gofasgalaxpaycartao') -> get( array( 'setting', 'value') ) as $settings ){
			$setting[$settings->setting] = $settings->value;
		}
		if($set){
			return $setting[$set];
		}
		return $setting;
	}
}