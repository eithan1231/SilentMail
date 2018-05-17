var newmail = (new function() {
	this.autoSubmit = function()
	{
		let recipients = document.getElementById('componse-recipients').value;
		let subject = document.getElementById('componse-subject').value;
		let body = document.getElementById('componse-body').value;
		let attachments = false;// TODO: document.getElementById('componse-recipients').innerHTML;
		newmail.submit(recipients, subject, body, attachments);
	};

	/**
	* Submits a new email. Recipients will be an array of recipients, Subject being
	* the subject of attachment, and attachments will the a list of attachment id's.
	*/
	this.submit = function(recipients, subject, body, attachments)
	{
		if(attachments !== false) {
			alert("We currently have no support for attachments.");
		}

		let postData = JSON.stringify({
			'recipients': recipients,
			'subject': subject,
			'body': body,
			'attachments': attachments
		});

		Library.doAjax(
			Library.getPostRoute("send-mail"),
			function(status, content) {
				if(status === 200) {
					let content_parsed = JSON.parse(content);

					if(content_parsed['success']) {
						Tab.changeTab('template-sent', 'tab-sent', 'tab-body');
					}
					else {
						Library.alert(content_parsed['data']['message']);
					}
				}
				else {
					Library.alert("Unable to send mail. Remote server returned an unknown status.", {
						'Retry': 'blue',
						'Cancel': 'red'
					}, function(s) {
						if(s == 'Retry') {
							// TODO: add some sort of loop detection, people can retry as
							// many times as they like.
							newmail.submit(recipients, subject, body, attachments);
						}
					});
				}
			}, 'POST', postData, [
				{
					'name': 'Content-type',
					'value': 'text/json'
				}
			]
		);
	};
});
