/**
 * Módulo iugu Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14946
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		1.0.0
 */

function inputstorefunc_2(){
  	var checkBox = document.getElementById("nostore");
	var gicCheckIcon = document.getElementById("gicCheckIcon");
  	if(checkBox.value == "yes"){
		sessionStorage.setItem("nostore", "no");
		checkBox.value = "no";
		gicCheckIcon.className = "gicCheckIconOff fas fa-check"
	}
	else if(checkBox.value == "no"){
	 	sessionStorage.setItem("nostore", "yes");
		checkBox.value = "yes";
		gicCheckIcon.className = "gicCheckIcon fas fa-check"
  	}
	console.log("nostore: "+sessionStorage.getItem("nostore"));
}
function gic_inputs_2(){
	sessionStorage.setItem("nostore", "yes");
	sessionStorage.setItem('installments_', 1);
	var gic_input = '<style>.gicCheckIconOff:hover:before {border: 2px solid #3e89c5;padding: 4px;}.gicCheckIcon:before {background-color: #3e89c5; font-size: 11px; color: #ffffff; padding: 5px; border: 1px solid #3e89c5; line-height: 0; border-radius: 50%; margin: 1px;}.gicCheckIconOff:before {background-color: #ffffff; font-size: 11px; color: #ffffff; padding: 5px; border: 1px solid #c6c3bf; line-height: 0; border-radius: 50%; margin: 1px;}</style><label style="cursor: pointer;" onclick="inputstorefunc_2();"><span ><i id="gicCheckIcon" class="gicCheckIcon fas fa-check"></i></span>&nbsp;&nbsp;Automatizar pagamentos futuros</label><input type="hidden" id="nostore" value="yes">';
	document.getElementById('inputNoStoreContainer').innerHTML = gic_input;
	document.getElementById('inputDescriptionContainer').style.display = "none";
}
window.onload=gic_inputs_2();