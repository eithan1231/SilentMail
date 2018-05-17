var vmail = new function()
{
	this.autoCreate = function()
	{
		return vmail.create(document.getElementById('vbox-username').value);
	};

	this.create = function(username)
	{
		let url = Library.getPostRoute("vmail-create");

		// Getting the post data
		let postData = JSON.stringify({
			'username': username
		});

		// And doing web request...
		Library.doAjax(url, function(status, content) {
			if(status === 200) {
				let contentParsed = JSON.parse(content);

				vmail.setStatus(contentParsed['data']['message']);

				if(contentParsed['success']) {

					TemplateEngine.purgeCache();

					Tab.changeTab('template-virtual-emails', false, 'tab-body', function() {

						// Changes the current template...
						TemplateEngine.getAndSetTemplate(
							'template-virtual-manage',
							'virtual-email-body',
							'id=' + contentParsed['data']['id']
						);
					});
				}
			}
		}, "POST", postData);
	};

	this.setStatus = function(status)
	{
		document.getElementById('virtual-email-new-status-container').hidden = (status.length <= 0);

		// This is sanitized on server, dont worry.
		document.getElementById('virtual-email-new-status').innerHTML = status;
	}
};
