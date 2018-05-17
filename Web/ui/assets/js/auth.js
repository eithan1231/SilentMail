var Auth = (new function()
{
	this.autoChangePassword = function() {
		let current_password = document.getElementById('password-change-current');
		let new_password = document.getElementById('password-change-new');
		let new_password_verify = document.getElementById('password-change-verifiction');

		if(
			current_password === null ||
			new_password === null ||
			new_password_verify === null
		) {
			Library.alert('Auth.autoChangePassword has null variables.\n\nError');
		}

		Auth.changePassword(
			current_password.value, new_password.value, new_password_verify.value,
			function(response) {
				current_password.value = '';
				new_password.value = '';
				new_password_verify.value = '';

				Library.alert(response);
			}
		);
	};

	this.changePassword = function(
		current_password, new_password, new_password_verify,
		callback
	) {
		let url = Library.getPostRoute('settings-password-change');

		let postData = JSON.stringify({
			'current_password': current_password,
			'new_password': new_password,
			'new_password_verify': new_password_verify
		});

		Library.doAjax(url, function(status, content) {
			if(status === 200) {
				let parsedResponse = JSON.parse(content);
				if(parsedResponse['success']) {
					// TODO: Handle success?
				}
				callback(parsedResponse['data']['message']);
			}
			else {
				callback('Server Error');
			}
		}, 'POST', postData);
	};

	this.autoLogin = function()
	{
		let username = document.getElementById('login-username').value;
		let password = document.getElementById('login-password').value;

		Auth.setStatus('');
		Auth.login(username, password, function(status) {
			Auth.setStatus(status);
		});
	};

	this.login = function(username, password, callback)
	{
		if(!Auth.areValidCredentials(username, password)) {
			callback("The entered username or password are invalid.");
			return;
		}

		// Getting the url for the post route
		let url = Library.getPostRoute("login");

		// Getting the post data
		let postData = JSON.stringify({
			'username': username,
			'password': password
		});

		// And doing web request...
		Library.doAjax(url, function(status, content) {
			if(status === 200) {
				let contentParsed = JSON.parse(content);
				callback(contentParsed['data']['message']);

				if(contentParsed['success']) {
					location.href = contentParsed['data']['redirect'];
				}
			}
			else {
				callback("Internal server error");
			}
		}, "POST", postData);
	};

	this.autoRegister = function()
	{
		let username = document.getElementById('register-username').value;
		let password = document.getElementById('register-password').value;
		let security_question = document.getElementById('register-question').value;
		let security_answer = document.getElementById('register-answer').value;
		let security_hint = document.getElementById('register-hint').value;
		let first_name = document.getElementById('register-first-name').value;
		let last_name = document.getElementById('register-last-name').value;

		Auth.setStatus('');
		Auth.register(username, password, security_question, security_answer, security_hint, first_name, last_name, function(status) {
			Auth.setStatus(status);
		});
	};

	this.register = function(
		username, password,
		security_question, security_answer, security_hint,
		first_name, last_name,
		callback
	) {
		if(!Auth.areValidCredentials(username, password)) {
			callback("The entered username or password is invalid.");
			return;
		}

		if(!Auth.isValidSecurityQuestion(security_question)) {
			callback("Invalid security question. Must be within 64 and 5 characters.");
			return;
		}

		if(!Auth.isValidSecurityAnswer(security_answer)) {
			callback("Invalid security answer. Must be within 128 and 2 characters.");
			return;
		}

		if(!Auth.isValidSecurityHint(security_hint)) {
			callback("Invalid security hint. Must be within 64 characters.");
			return;
		}

		// Getting the url for the post route
		let url = Library.getPostRoute("register");

		// Getting the post data
		let postData = JSON.stringify({
			'username': username,
			'password': password,
			'security_question': security_question,
			'security_answer': security_answer,
			'security_hint': security_hint,
			'first_name': first_name,
			'last_name': last_name
		});

		// And doing web request...
		Library.doAjax(url, function(status, content) {
			if(status === 200) {
				let contentParsed = JSON.parse(content);
				callback(contentParsed['data']['message']);

				if(contentParsed['success']) {
					location.href = contentParsed['data']['redirect'];
				}
			}
			else {
				callback("Internal server error");
			}
		}, "POST", postData);
	};

	this.isValidSecurityQuestion = function(question)
	{
		if(question.length > 64) {
			return false;
		}
		if(question.length < 5) {
			return false;
		}

		return true;
	}

	this.isValidSecurityAnswer = function(answer)
	{
		if(answer.length > 128) {
			return false;
		}
		if(answer.length < 2) {
			return false;
		}

		return true;
	}

	this.isValidSecurityHint = function(hint)
	{
		if(hint.length > 64) {
			return false;
		}

		return true;
	}

	this.areValidCredentials = function(username, password)
	{
		if(username === false || password === false) {
			return false;
		}

		if(username.legnth <= 0 || password.length <= 0) {
			return false;
		}

		return true;
	};

	this.setStatus = function(statusString, color = false)
	{
		let statusElement = document.getElementById('auth-status');

		if(statusString.length > 0) {
			statusElement.hidden = false;

			let statusTextNode = document.createTextNode(statusString);
			statusElement.innerHTML = '';
			statusElement.appendChild(statusTextNode);

			if(color !== false) {
				statusElement.style.color = color;
			}
		}
		else {
			statusElement.innerHTML = '';
			statusElement.hidden = true;
		}
	};
});
