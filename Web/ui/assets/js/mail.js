/**
* This is the tab control for the centeral tab on the mail page.
*/
var Tab = new function() {
	this.changeTab = function(templateName, tabButtonId, tabBodyId, templateQuery = false, callback = false) {
		TemplateEngine.getAndSetTemplate(templateName, tabBodyId, templateQuery, function(content) {
			Tab.setSelectedButton(tabButtonId);

			if(callback !== false) {
				callback();
			}
		});

		/*TemplateEngine.getTemplate(templateName, function(content) {
			Tab.setTabContent(content, tabBodyId);

		}, templateQuery);*/
	}

	this.setTabContent = function(content, tabBodyId) {
		let tabBody = document.getElementById(tabBodyId);
		tabBody.innerHTML = content;
	}

	this.setSelectedButton = function(tabButtonId) {
		let tabButtons = document.getElementsByClassName('tab-control');

		for(var i = 0; i < tabButtons.length; i++) {
			if(tabButtons[i].id == tabButtonId) {
				tabButtons[i].classList.remove('normal');
				tabButtons[i].classList.add('selected');
			}
			else {
				tabButtons[i].classList.remove('selected');
				tabButtons[i].classList.add('normal');
			}
		}
	}
};
