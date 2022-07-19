/**
 * Módulo Juno Cartão para WHMCS
 * @copyright	2020 Gofas Software
 * @see			https://gofas.net/?p=12042
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=12349
 * @version		1.2.2
 */

function inputstorefunc_2(){
  	var checkBox = document.getElementById("nostore");
	var gjcCheckIcon = document.getElementById("gjcCheckIcon");
  	if(checkBox.value == "yes"){
		sessionStorage.setItem("nostore", "no");
		checkBox.value = "no";
		gjcCheckIcon.className = "gjcCheckIconOff fas fa-check"
	}
	else if(checkBox.value == "no"){
	 	sessionStorage.setItem("nostore", "yes");
		checkBox.value = "yes";
		gjcCheckIcon.className = "gjcCheckIcon fas fa-check"
  	}
	console.log("nostore: "+sessionStorage.getItem("nostore"));
}
function gjc_inputs_2(){
	sessionStorage.setItem("nostore", "yes");
	sessionStorage.setItem('installments_', 1);
	var gjc_input = '<style>.gjcCheckIconOff:hover:before {border: 2px solid #3e89c5;padding: 4px;}.gjcCheckIcon:before {background-color: #3e89c5; font-size: 11px; color: #ffffff; padding: 5px; border: 1px solid #3e89c5; line-height: 0; border-radius: 50%; margin: 1px;}.gjcCheckIconOff:before {background-color: #ffffff; font-size: 11px; color: #ffffff; padding: 5px; border: 1px solid #c6c3bf; line-height: 0; border-radius: 50%; margin: 1px;}</style><label style="cursor: pointer;" onclick="inputstorefunc_2();"><span ><i id="gjcCheckIcon" class="gjcCheckIcon fas fa-check"></i></span>&nbsp;&nbsp;Automatizar pagamentos futuros</label><input type="hidden" id="nostore" value="yes">';
	document.getElementById('inputNoStoreContainer').innerHTML = gjc_input;
	document.getElementById('inputDescriptionContainer').style.display = "none";
}
window.onload=gjc_inputs_2();