/**
 * Módulo Galax Pay Cartão para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14641
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14644
 * @version		1.0.0
 */
function inputstorefunc(){
  	var checkBox = document.getElementById("nostore");
	var ggpcCheckIcon = document.getElementById("ggpcCheckIcon");
  	if(checkBox.value == "yes"){
		sessionStorage.setItem("nostore", "no");
		checkBox.value = "no";
		ggpcCheckIcon.className = "ggpcCheckIconOff fas fa-check"
	}
	else if(checkBox.value == "no"){
	 	sessionStorage.setItem("nostore", "yes");
		checkBox.value = "yes";
		ggpcCheckIcon.className = "ggpcCheckIcon fas fa-check"
  	}
	console.log("nostore: "+sessionStorage.getItem("nostore"));
}
function ggpc_inputs(){
	sessionStorage.setItem("nostore", "yes");
	sessionStorage.setItem('installments_', 1);
	var inputDescriptionContainer = document.getElementById('inputDescriptionContainer');	
	var ggpc_input = '<style>.ggpcCheckIconOff:hover:before {border: 2px solid #3e89c5;padding: 4px;}.ggpcCheckIcon:before {background-color: #3e89c5; font-size: 11px; color: #ffffff; padding: 5px; border: 1px solid #3e89c5; line-height: 0; border-radius: 50%; margin: 1px;}.ggpcCheckIconOff:before {background-color: #ffffff; font-size: 11px; color: #ffffff; padding: 5px; border: 1px solid #c6c3bf; line-height: 0; border-radius: 50%; margin: 1px;}</style><label class="col-sm-4 control-label"></label><div class="col-sm-8" onclick="inputstorefunc();" style="margin-bottom: 15px;margin-top: 6px;cursor: pointer;"><i id="ggpcCheckIcon" class="ggpcCheckIcon fas fa-check"></i><span>&nbsp;&nbsp;Automatizar pagamentos futuros</span></div><input type="hidden" id="nostore" value="yes">';
	inputDescriptionContainer.innerHTML = ggpc_input;// + inputDescriptionContainer.innerHTML;
}
window.onload = ggpc_inputs();