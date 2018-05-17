var vbox = new function() {
	/**
	* Disables a mailbox
	*/
	this.disableMailbox = function(vbox_id)
	{
		let url = Library.getPostRoute("vmail-disable");

		// Getting the post data
		let postData = JSON.stringify({
			'id': vbox_id
		});

		// And doing web request...
		Library.doAjax(url, function(status, content) {
			if(status === 200) {
				let contentParsed = JSON.parse(content);

				if(contentParsed['success']) {
					TemplateEngine.purgeCache();

					Tab.changeTab('template-virtual-emails', false, 'tab-body', function() {

						// Changes the current template...
						TemplateEngine.getAndSetTemplate(
							'template-virtual-manage',
							'virtual-email-body',
							'id=' + vbox_id
						);
					});
				}
			}
		}, "POST", postData);
	};

	/**
	* Enables a mailbox.
	*/
	this.enableMailbox = function(vbox_id)
	{
		let url = Library.getPostRoute("vmail-enable");

		// Getting the post data
		let postData = JSON.stringify({
			'id': vbox_id
		});

		// And doing web request...
		Library.doAjax(url, function(status, content) {
			if(status === 200) {
				let contentParsed = JSON.parse(content);

				if(contentParsed['success']) {
					TemplateEngine.purgeCache();

					Tab.changeTab('template-virtual-emails', false, 'tab-body', function() {

						// Changes the current template...
						TemplateEngine.getAndSetTemplate(
							'template-virtual-manage',
							'virtual-email-body',
							'id=' + vbox_id
						);
					});
				}
			}
		}, "POST", postData);
	};
};
