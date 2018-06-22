var TemplateEngine = (new function() {
	// Hisory of templates.
	// WARNING: this is untrusted input. Be cautious.
	this.templateHistory = [];
	this.templateHistoryResetID = null;
	// Var saying whether or not we are logging history. Will be enabled when
	// loadFromHash is called.
	this.templateHistoryEnabled = false;

	this.cachedTemplates = {};

	this.purgeCache = function() {
		TemplateEngine.cachedTemplates = {};
	}

	this.loadFromHash = function(errorCallback = null) {
		this.templateHistoryEnabled = true;

		// Checking the hash exists
		if(!window.location.hash) {
			if(typeof errorCallback == 'function') {
				errorCallback();
			}
			return;
		}

		// Getting and parsing template history.
		this.templateHistory = JSON.parse(atob(window.location.hash.substr(1)));

		// And setting templates.
		var templateHistoryResetIndex = 0;
		if(this.templateHistory[templateHistoryResetIndex]) {
			var templateHistoryCallback = function(data) {
				document.getElementById(TemplateEngine.templateHistory[templateHistoryResetIndex].id).innerHTML = data;

				templateHistoryResetIndex++;
				if(templateHistoryResetIndex > TemplateEngine.templateHistory.length - 1) {
					return;
				}

				TemplateEngine.getTemplate(
					TemplateEngine.templateHistory[templateHistoryResetIndex].name,
					templateHistoryCallback,
					TemplateEngine.templateHistory[templateHistoryResetIndex].query,
					false
				);
			};

			this.getTemplate(
				this.templateHistory[templateHistoryResetIndex].name,
				templateHistoryCallback,
				this.templateHistory[templateHistoryResetIndex].query,
				false
			);
		}
		else {
			if(typeof errorCallback == 'function') {
				errorCallback();
			}
		}
	}

	this.setHash = function() {
		window.location.hash = btoa(JSON.stringify(this.templateHistory));
	}

	/** Gets template from server, and sets it in a specified element.*/
	this.getAndSetTemplate = function(templateName, templateId, requestQuery = false, callback = false) {
		let tmpSubjectTemplate = document.getElementById(templateId);

		if(this.templateHistoryEnabled) {
			// Setting main page thing.
			if(this.templateHistoryResetID === null) {
				if(typeof main_page_id !== 'undefined') {
					this.templateHistoryResetID = main_page_id;
				}
				else if(this.templateHistory.length > 0) {
					// Assume its the first set template.
					this.templateHistoryResetID = this.templateHistory[0].id;
				}
			}

			if(templateId == this.templateHistoryResetID) {
				this.templateHistory = [];
			}
			if(
				this.templateHistory.length > 0 &&
				this.templateHistory[this.templateHistory.length - 1].id == templateId
			) {
				this.templateHistory[this.templateHistory.length - 1].name = templateName;
				this.templateHistory[this.templateHistory.length - 1].query = requestQuery;
			}
			else {
				this.templateHistory.push({
					name: templateName,
					id: templateId,
					query: requestQuery
				});
			}
			this.setHash();
		}

		tmpSubjectTemplate.innerHTML = "loading";
		TemplateEngine.getTemplate(templateName, function(content) {
			tmpSubjectTemplate.innerHTML = content;

			if(typeof callback == 'function') {
				callback(content);
			}
		}, requestQuery);
	};

	/** Gets template off server. */
	this.getTemplate = function(templateName, callback, requestQuery = false, allowLoadingCallback = true) {
		let getRemoteTemplate = function() {
			if(allowLoadingCallback) {
				callback('<div style="margin:12px;"><span style="font-size:14;">Loading...</span></div>');
			}

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
