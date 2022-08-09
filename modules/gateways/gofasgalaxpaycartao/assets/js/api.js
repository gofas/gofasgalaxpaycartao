//  <script type="text/javascript" src="https://js.galaxpay.com.br/checkout.min.js"></script>
const token = "675A14116E92260664C4F3F398B490BD2D43FD7BC1055FBE98ED9B5E0A44926AEFCB2953A8336ADC4F94766685A6567457B66B028756913D729C380509C0AF85";
var galaxPay = new GalaxPay(token, false);
const card = galaxPay.newCard({
    number: "5454545454545454",
    holder: "Goldduck Silver",
    expiresAt: "2028-12",
    cvv: "123"
});
galaxPay.hashCreditCard(card, function(hash) {
     //document.getElementById("cardHash").value = hash;
    console.log(hash);
}, function (error) {
    //document.getElementById("error").value = error;
    console.log(error);
});