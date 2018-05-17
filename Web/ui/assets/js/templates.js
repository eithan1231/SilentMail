var TemplateEngine = (new function() {
	this.cachedTemplates = {};

	this.purgeCache = function() {
		TemplateEngine.cachedTemplates = {};
	}

	/** Gets template from server, and sets it in a specified element.*/
	this.getAndSetTemplate = function(templateName, templateId, requestQuery = false) {
		let tmpSubjectTemplate = document.getElementById(templateId)

		tmpSubjectTemplate.innerHTML = "loading";
		TemplateEngine.getTemplate(templateName, function(content) {
			tmpSubjectTemplate.innerHTML = content;
		}, requestQuery);
	};

	/** Gets template off server. */
	this.getTemplate = function(templateName, callback, requestQuery = false) {
		let getRemoteTemplate = function() {
			callback('<div style="margin:12px;"><span style="font-size:14;">Loading...</span></div>');

			let url = TemplateEngine.getTemplateUrl(templateName);
			if(requestQuery !== false) {
				url += "?" + requestQuery;
			}

			Library.doAjax(url, function(status, content) {
				if(status == 200) {
					callback(content);

					if(requestQuery === false) {
						TemplateEngine.cachedTemplates[templateName] = {
							time: Library.time(),
							content: content
						};
					}
				}
				else {
					Library.alert("Failed to get template.")
				}
			}, "GET");
		};

		if(requestQuery !== false) {
			// Has a query string, if there's a query string we should always re-fetch
			// the template even if it's cached.
			return getRemoteTemplate();
		}

		if(typeof TemplateEngine.cachedTemplates[templateName] === 'undefined') {
			return getRemoteTemplate();
		}
		else if(TemplateEngine.cachedTemplates[templateName]['time'] > Library.time() - 60) {
			callback(TemplateEngine.cachedTemplates[templateName]['content']);
			return;
		}
		else {
			return getRemoteTemplate();
		}
	};

	this.getTemplateUrl = function(template) {
		return (template_route
			.replace('__token__', encodeURIComponent(template_token))
			.replace('__name__', encodeURIComponent(template))
		);

		return (working_directory + "t/" + encodeURIComponent(template_token)
			+ "/" + encodeURIComponent(template)
		);
	};
});
