<?php
/**
 * Módulo Galax Pay Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14641
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		0.1.0
 */
if (!defined('WHMCS')){
    die();
}
use WHMCS\Database\Capsule;
$customer = array();
$customfields = array();
$address_number = preg_replace('/[^0-9]/', '', $params['clientdetails']['address1']);
foreach (Capsule::table('tblcustomfields')->where('type','=','client')->get(array('fieldname','id')) as $customfield){
    $customfield_id = $customfield->id;
    $customfield_name = ' ' . strtolower($customfield->fieldname);
    if (strpos($customfield_name, 'cpf') and !strpos($customfield_name, 'cnpj')){
        foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $customfield_id)->where('relid', '=', $params['clientdetails']['id'])->get(array(
            'value'
        )) as $customfieldvalue)
        {
            $cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
        }
    }
    if (strpos($customfield_name, 'cnpj') and !strpos($customfield_name, 'cpf')){
        foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $customfield_id)->where('relid', '=', $params['clientdetails']['id'])->get(array(
            'value'
        )) as $customfieldvalue)
        {
            $cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
        }
    }
    if (strpos($customfield_name, 'cpf') and strpos($customfield_name, 'cnpj')){
        foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $customfield_id)->where('relid', '=', $params['clientdetails']['id'])->get(array(
            'value'
        )) as $customfieldvalue)
        {
            $cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
            $cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
        }
    }
	// Número Custom Field
	if( strpos( $customfield_name, 'numero') || strpos( $customfield_name, 'número')){
		foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $params['clientdetails']['id']) -> get( array( 'value') ) as $customfieldvalue ){
			if(!empty($customfieldvalue->value)){
                $address_number = $customfieldvalue->value;
	        }
        }
	}
    // Complemento Custom Field
	if( strpos( $customfield_name, 'complemento') !== false){
		foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $params['clientdetails']['id']) -> get( array( 'value') ) as $customfieldvalue ){
			$address_complement = $customfieldvalue->value;
		}
	}
}
//$customer['number'] = $number;
if (strlen($cpf_customfield_value) === 10){
    $cpf = '0' . $cpf_customfield_value;
}
elseif (strlen($cpf_customfield_value) === 11){
    $cpf = $cpf_customfield_value;
}
elseif (strlen($cpf_customfield_value) === 13){
    $cpf = false;
    $cnpj = '0' . $cpf_customfield_value;
}
elseif (strlen($cpf_customfield_value) === 14){
    $cpf = false;
    $cnpj = $cpf_customfield_value;
}
elseif (!$cpf_customfield_value || strlen($cpf_customfield_value) !== 10 || strlen($cpf_customfield_value) !== 11 || strlen($cpf_customfield_value) !== 13 || strlen($cpf_customfield_value) !== 14){
    $cpf = false;
}
if (strlen($cnpj_customfield_value) === 13){
    $cnpj = '0' . $cnpj_customfield_value;
}
elseif (strlen($cnpj_customfield_value) === 14){
    $cnpj = $cnpj_customfield_value;
}
elseif (!$cnpj_customfield_value and strlen($cnpj_customfield_value) !== 14 and strlen($cnpj_customfield_value) !== 13 and strlen($cpf_customfield_value) !== 13 and strlen($cpf_customfield_value) !== 14){
    $cnpj = false;
}
if (($cpf and $cnpj) or (!$cpf and $cnpj)){
    $customer['doc_type'] = 2;
    $customer['document'] = $cnpj;
    if ($params['clientdetails']['companyname']){
        $customer['name'] = $params['clientdetails']['companyname'];
    }
    elseif (!$params['clientdetails']['companyname']){
        $customer['name'] = $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'];
    }
}
elseif ($cpf and !$cnpj){
    $customer['doc_type'] = 1;
    $customer['document'] = $cpf;
    $customer['name'] = $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'];
}
if (!$cpf and !$cnpj){
    $customer['doc_type'] = NULL;
    $customer['document'] = NULL;
}
$public_token = $params['public_token'];
if ($params['sandbox']){
    $api_mode = 'sandbox';
    $galax_id = $params['sandbox_galax_id'];
    $galax_hash = $params['sandbox_galax_hash'];
    $charge_url = 'https://api.sandbox.cloud.galaxpay.com.br/v2';
    $referralToken = '34c8f0bb';
}
elseif (!$params['sandbox']){
    $api_mode = 'live';
    $galax_id = $params['galax_id'];
    $galax_hash = $params['galax_hash'];
    $charge_url = 'https://api.galaxpay.com.br/v2';
    $referralToken = '34c8f0bb';
}