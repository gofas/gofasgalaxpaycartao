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
	function ggpc_get_token($client_id,$client_secret,$sandbox){
		if($sandbox){
			$galaxIdPartner = "20";
			$galaxHashPartner = "ea045be1";
		}
		else{
			$galaxIdPartner = "20";
			$galaxHashPartner = "34c8f0bb";
		}
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
				//'AuthorizationPartner: '.base64_encode($galaxIdPartner.':'. $galaxHashPartner),
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

//////////


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.sandbox.cloud.galaxpay.com.br/v2/charges',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_HTTPHEADER => array(
    'Authorization: Bearer '.$postfields['token'],
    'Content-Type: application/json'
  ),
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
    "myId": "pay-62d75de8acfc98.90621609",
    "value": 12999,
    "additionalInfo": "Lorem ipsum dolor sit amet.",
    "payday": "2022-07-19",
    "payedOutsideGalaxPay": false,
    "mainPaymentMethodId": "creditcard",
    "Customer": {
        "myId": "pay-62d75de8b364f4.13984475",
        "name": "Lorem ipsum dolor sit amet.",
        "document": "45686560447",
        "emails": [
            "teste4519email2203@galaxpay.com.br",
            "teste1489email8541@galaxpay.com.br"
        ],
        "phones": [
            3140201512,
            31983890110
        ]
    },
    "PaymentMethodCreditCard": {
        "Link": {
            "minInstallment": 1,
            "maxInstallment": 12
        },
        "Card": {
            "myId": "pay-62d75de8cf9d89.87917599",
            "hash": "ABCD-1234-EFGH-5678-ABCD-1234-EFGH-5678",
            "number": "4111 1111 1111 1111",
            "holder": "JOAO J J DA SILVA",
            "expiresAt": "2022-07",
            "cvv": "363"
        },
        "cardOperatorId": "rede",
        "preAuthorize": false,
        "qtdInstallments": 12
    },
}'
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;



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
if( !function_exists('ggpc_config') ){
	function ggpc_config($set = false){
		$setting = array();
		foreach( Capsule::table('tblpaymentgateways') -> where( 'gateway', '=', 'gofasgalaxpaycartao') -> get( array( 'setting', 'value') ) as $settings ){
			$setting[$settings->setting] = $settings->value;
		}
		if($set){
			return $setting[$set];
		}
		return $setting;
	}
}
if(!function_exists('ggpc_customer') ){
	function ggpc_customer($client_id){
		//Determine custom fields id
		$client = localAPI('GetClientsDetails',array( 'clientid' => $client_id, 'stats' => false, ), ggpc_config('admin'));
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