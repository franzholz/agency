function tx_agency_encrypt(form) {
	var rsa = new RSAKey();
	rsa.setPublic(form.n.value, form.e.value);

		// For login forms
	if (typeof form.pass !== 'undefined') {
		var pass = form.pass.value;
		var encryptedPass = rsa.encrypt(pass);
		form.pass.value = '';
		if (encryptedPass) {
			form.pass.value = 'rsa:' + hex2b64(encryptedPass);
		}
	}
		// For password and password_again entry forms
	if (typeof form['FE[fe_users][password]'] !== 'undefined') {
		var password = form['FE[fe_users][password]'].value;
		form['FE[fe_users][password]'].value = '';
		if (password && password.length > 0) {
			var encryptedPassword = rsa.encrypt(password);
			if (encryptedPassword) {
				form['FE[fe_users][password]'].value = 'rsa:' + hex2b64(encryptedPassword);
			}
		}
	}
	if (typeof form['FE[fe_users][password_again]'] !== 'undefined') {
		var password_again = form['FE[fe_users][password_again]'].value;
		form['FE[fe_users][password_again]'].value = '';
		if (password_again && password_again.length > 0) {
			var encryptedPassword_again = rsa.encrypt(password_again);
			if (encryptedPassword_again) {
				form['FE[fe_users][password_again]'].value = 'rsa:' + hex2b64(encryptedPassword_again);
			}
		}
	}

	form.e.value = '';
	form.n.value = '';
}
