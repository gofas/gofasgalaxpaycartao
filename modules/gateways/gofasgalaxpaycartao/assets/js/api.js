const token = "'.$public_token.'";
				var galaxPay = new GalaxPay(token, '.$environment.');

				const card = galaxPay.newCard({
					number: "'.$params['cardnum'].'",
					holder: "'.$customer['name'].'",
					expiresAt: "20'.substr($params['cardexp'], 2, 2).'-'.substr($params['cardexp'], 0, 2).'",
					cvv: "'.$params['cccvv'].'"
				});
				galaxPay.hashCreditCard(card, function(hash) {
					document.getElementById("cardHash").value = cardHash;
					console.log(hash);
				}, function (error) {
					document.getElementById("error").value = error;
					console.log(error);
				});