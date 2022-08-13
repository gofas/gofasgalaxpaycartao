<?php
/**
 * Módulo Galax Pay Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14641
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		0.1.0
 */
require_once __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../../includes/invoicefunctions.php';
if(!defined("WHMCS")){die();}
use WHMCS\Database\Capsule;
if(!function_exists('ggpc_version')){
	function ggpc_version($int=false){
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggpc_version') -> get( array( 'value','created_at') ) as $ggpc_version_ ){
			$ggpc_version				= $ggpc_version_->value;
			$ggpc_version_created_at	= $ggpc_version_->created_at;
		}
		if(!$int){
			return $ggpc_version;
		}
		if($int){
			return (int)preg_replace("/[^0-9]/", "", $ggpc_version);
		}
	}
}
if(!function_exists('ggpc_api_connect')){
	function ggpc_api_connect(){
		$params = getGatewayVariables('gofasgalaxpaycartao');
		if($params['sandbox']){
			$params_api = [
				'api_mode' => 'sandbox',
				'galax_id' => $params['sandbox_galax_id'],
				'galax_hash' => $params['sandbox_galax_hash'],
				'public_token' => $params['sandbox_public_token'],
				'charge_url' => 'https://api.sandbox.cloud.galaxpay.com.br/v2',
				//'sandbox' => true,
				'galaxIdPartner' => '5473',
				'galaxHashPartner' => '83Mw5u8988Qj6fZqS4Z8K7LzOo1j28S706R0BeFe',
			];
		}
		if(!$params['sandbox']){
			$params_api = [
				'api_mode' => 'live',												// $params_api['api_mode']
				'galax_id' => $params['galax_id'],									// $params_api['galax_id']
				'galax_hash' => $params['galax_hash'],								// $params_api['galax_hash']
				'public_token' => $params['public_token'],							// $params_api['public_token']
				'charge_url' => 'https://api.galaxpay.com.br/v2',					// $params_api['charge_url']
				//'sandbox' => false,													// $params_api['sandbox']
				'galaxIdPartner' => '29009',										// $params_api['galaxIdPartner']
				'galaxHashPartner' => 'U9F6YvKgI77gVqJ60kHk6qOd04RhLfN0YyJ8AfA6',	// $params_api['galaxHashPartner']
			];
		}
		return $params_api;
	}
}
if( !function_exists('ggpc_get_token') ){
	function ggpc_get_token(){
		$params_api = ggpc_api_connect();
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $params_api['charge_url'].'/token',
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
		    	'Authorization: Basic '.base64_encode((string)$params_api['galax_id'].':'.(string)$params_api['galax_hash']),
				'AuthorizationPartner: '.base64_encode($params_api['galaxIdPartner'].':'. $params_api['galaxHashPartner']),
		    	'Content-Type: application/json'
		  	)
		));
		$response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$response = json_decode(curl_exec($curl), true);
		//$response = curl_exec($curl);
		curl_close($curl);
		return ['response_code'=>$response_code,'response'=>$response];
	}
}
if( !function_exists('ggpc_charge') ){
	function ggpc_charge($postfields){
		$params_api = ggpc_api_connect();
    	$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $params_api['charge_url'].'/charges',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPHEADER => array(
			  'Authorization: Bearer '.$postfields['access_token'],
			  'Content-Type: application/json'
			),
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($postfields['charge']),
		));
		$result = json_decode(curl_exec($curl),true);
		$result_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return ['result_code'=>$result_code,'result'=>$result];
	}
}
/*
if( !function_exists('ggpc_charge_verify') ){
	function ggpc_charge_verify($postfields){
		$params_api = ggpc_api_connect();
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $params_api['charge_url'].'/transactions?galaxPayIds='.$postfields['galaxPayIds'].'&limit=1&startAt=0',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
			  'Authorization: Bearer '.$postfields['access_token']
			),
		));
		$result = json_decode(curl_exec($curl),true);
		$result_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return ['result_code'=>$result_code,'result'=>$result];
	}
}
if( !function_exists('ggpc_get_string_between') ){
	function ggpc_get_string_between($string, $start, $end){
		$string = " ".$string;
		$ini = strpos($string,$start);
		if ($ini == 0) return "";
		$ini += strlen($start);   
		$len = strpos($string,$end,$ini) - $ini;
		return substr($string,$ini,$len);
	}
}

if( !function_exists('ggpc_charge_capture') ){
	function ggpc_charge_capture($charge_id,$access_token){
		$params_api = ggpc_api_connect();
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $params_api['charge_url'].'/charges/'.$charge_id.'/galaxPayId/capture',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'PUT',
			//CURLOPT_POSTFIELDS =>'[]',
			CURLOPT_HTTPHEADER => array(
			  'Authorization: Bearer '.$access_token,
			  'Content-Type: application/json'
			),
		  ));
		$result = json_decode(curl_exec($curl),true);
		$result_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return ['result_code'=>$response_code,'result'=>$result];
	}
}

if( !function_exists('ggpc_card_create') ){
	function ggpc_card_create($card_postfields,$access_token){
		$params_api = ggpc_api_connect();
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $params_api['charge_url'].'/cards/'.$card_postfields['userid'].'/myId',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>json_encode($card_postfields),
			CURLOPT_HTTPHEADER => array(
			  'Authorization: Bearer '.$access_token,
			  'Content-Type: application/json'
			),
		  ));
		$result = json_decode(curl_exec($curl),true);
		$result_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return ['result_code'=>$result_code,'result'=>$result];
	}
}
*/
if( !function_exists('ggpc_card_add') ){
	function ggpc_card_add($card,$pay_method_id){
		try {
			Capsule::table('tblcreditcards')->where( 'pay_method_id', $pay_method_id)->delete();
		}
		catch (\Exception $e){
			$error .= $e->getMessage();
		}
		try {
			Capsule::table('tblpaymethods')->where( 'id', $pay_method_id)->delete();
		}
		catch (\Exception $e){
			$error .= $e->getMessage();
		}	
		try {
			$createCardPayMethod = createCardPayMethod( // Function available in WHMCS 7.9 and later
				$card['userid'],
				'gofasgalaxpaycartao',
				'000000000'.$card['cclastfour'],
				$card['cardexp'],
				$card['cardtype'],
				NULL, //start date
				$card['cardissuenum'],//NULL, //issue number
				$card['myId']
			);
		}
		catch (Exception $e){
			$error .= $e->getMessage();
		}
		if($error){
			ggpc_card_del($card['myId']);
			return $error;
		}
		return 'success';
	}
}
if( !function_exists('ggpc_card_del') ){
	function ggpc_card_del($pay_method_id){
		try {
			Capsule::table('tblcreditcards')->where( 'pay_method_id', $pay_method_id)->delete();
		}
		catch (\Exception $e){
			$error .= $e->getMessage();
		}
		try {
			Capsule::table('tblpaymethods')->where( 'id', $pay_method_id)->delete();
		}
		catch (\Exception $e){
			$error .= $e->getMessage();
		}
		if($error){
			return $error;
		}
		return 'success';
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

if(!function_exists('ggpc_customer') ){
	function ggpc_customer($client_id){
		//Determine custom fields id
		$params = getGatewayVariables('gofasgalaxpaycartao');
		$client = localAPI('GetClientsDetails',array( 'clientid' => $client_id, 'stats' => false, ), $params['admin']);
		foreach( Capsule::table('tblcustomfields')->where('type','=','client')->get() as $customfield ){
			$customfield_id = $customfield->id;
			$customfield_name = strtolower($customfield->fieldname);
			// cpf
			if(strpos($customfield_name, 'cpf') !== false and strpos($customfield_name,'cnpj') === false){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}	
			// cnpj
			if(strpos($customfield_name, 'cnpj') !== false and strpos($customfield_name,'cpf') === false){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}
			// cpf + cnpj
			if( strpos( $customfield_name, 'cpf') !== false and strpos( $customfield_name, 'cnpj') !== false ){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
					$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}
			// Inscrição Estadual
			if( strpos( $customfield_name, 'inscrição estadual') !== false){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$ie = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}
			// Complemento Custom Field
			if( strpos( $customfield_name, 'complemento') !== false){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$complement = $customfieldvalue->value;
				}
			}
			// Número Custom Field
			if( strpos( $customfield_name, 'numero')!== false ||  strpos( $customfield_name, 'número')!== false ){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$number = $customfieldvalue->value;
				}
				if(!$number){
					$number = preg_replace('/[^0-9]/', '', $client['address1']);
				}
			}
			else {
				$number = preg_replace('/[^0-9]/', '', $client['address1']);
			}
			// Emitir Custom Field
			if( strpos( $customfield_name, 'emitir nfe')!== false || strpos( $customfield_name, 'emitir nfse')!== false || strpos( $customfield_name, 'emitir nfs-e')!== false || strpos( $customfield_name, 'emitir nf-e')!== false){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$issue_nfe = $customfieldvalue->value;
				}
				if(!$issue_nfe){
					$issue_nfe = false;
				}
			}
			// nascimento
			if( strpos( $customfield_name, 'nascimento') ){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$birt_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
					$birthday_pre			= preg_replace('/[^\da-z]/i', '', $birt_customfield_value);
					if(strlen($birthday_pre) === 8){
						$birth_ = $birthday_pre;
					}
					elseif( strlen($birthday_pre) === 7 ){
						$birth_ = '0'.$birthday_pre;
					}
					$birth_Y					= substr($birth_, -4);
					$birth_m					= substr($birth_, 2, -4);
					$birth_d					= substr($birth_, 0, -6);
					$birthday_us = $birth_Y.'-'.$birth_m.'-'.$birth_d; // 2021-02-20
					$birthday_br = $birth_d.'/'.$birth_m.'/'.$birth_Y; // 20/02/2021
					$birthday_raw = $customfieldvalue->value;
				}
			}
			foreach(Capsule::table('tblcustomfieldsvalues')->where('fieldid','=',$customfield_id)->where('relid','=',$client_id)->get(array('value')) as $customfieldvalue ){
				$custom_fields[$customfield_name] = $customfieldvalue->value;
			}
		}
		//
		// Cliente possui CPF e CNPJ
		// CPF com 1 nº a menos, adiciona 0 antes do documento
		if( strlen( $cpf_customfield_value ) === 10 ){
			$cpf = '0'.$cpf_customfield_value;
		}
		// CPF com 11 dígitos
		elseif( strlen( $cpf_customfield_value ) === 11){
			$cpf = $cpf_customfield_value;
		}
		// CNPJ no campo de CPF com um dígito a menos
		elseif( strlen( $cpf_customfield_value ) === 13 ){
			$cpf = false; 
			$cnpj = '0'.$cpf_customfield_value;
		}
		// CNPJ no campo de CPF
		elseif( strlen( $cpf_customfield_value ) === 14 ){
			$cpf 				= false;
			$cnpj				= $cpf_customfield_value;
		}
		// cadastro não possui CPF
		elseif( !$cpf_customfield_value || strlen( $cpf_customfield_value ) !== 10 || strlen($cpf_customfield_value) !== 11 || strlen( $cpf_customfield_value ) !== 13 || strlen($cpf_customfield_value) !== 14 ){	
			$cpf = false;
		}
		// CNPJ com 1 nº a menos, adiciona 0 antes do documento
		if( strlen($cnpj_customfield_value) === 13 ){
			$cnpj = '0'.$cnpj_customfield_value;
		}
		// CNPJ com nº de dígitos correto
		elseif( strlen($cnpj_customfield_value) === 14 ){
			$cnpj = $cnpj_customfield_value;
		}
		// Cliente não possui CNPJ
		elseif( !$cnpj_customfield_value and strlen( $cnpj_customfield_value ) !== 14 and strlen($cnpj_customfield_value) !== 13 and strlen( $cpf_customfield_value ) !== 13 and strlen( $cpf_customfield_value ) !== 14  ){
			$cnpj = false;
		}

		if( ( $cpf and $cnpj ) or ( !$cpf and $cnpj ) ){
			if( $client['companyname'] ){
				$name	= $client['companyname'];
			}
			elseif( !$client['companyname'] ){
				$name	= $client['firstname'].' '.$client['lastname'];
			}
			$doc_type	= 'J';
			$document	= $cnpj;
		}
		elseif( $cpf and !$cnpj ){
			$name	= $client['firstname'].' '.$client['lastname'];
			$doc_type	= 'F';
			$document	= $cpf;
		}
		/// Formated Array
		$customer=[
			'id'=>$client_id,
			'email'=>$client['email'],
			'name'=>$name,
			'names'=>['firstname'=>$client['firstname'],'lastname'=>$client['lastname'],'companyname'=>$client['companyname']],
			'address'=>str_replace(',','',preg_replace('/[0-9]+/i','',$client['address1'],1)),
			'number'=>$number,
			'neighborhood'=>$client['address2'],
			'complement'=>$complement,
			'city'=>$client['city'],
			'state'=>$client['state'],
			'postcode'=>preg_replace("/[^\da-z]/i", "",$client['postcode']),
			'phone'=>preg_replace('/[^\da-z]/i', '', $client['phonenumber']),
			'doc_type'=>$doc_type,
			'document'=>$document,
			'ie'=>$ie,
			'issue_nfe'=>$issue_nfe,
			'birthday'=>['raw'=>$birthday_raw,'br'=>$birthday_br,'us'=>$birthday_us],
			'custom_fields'=>$custom_fields,
		];
		return $customer;
	}
}