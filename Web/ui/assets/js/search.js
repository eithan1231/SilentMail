var Search = (new function() {
	this.onKeyUpInputHandler = function(event) {
		if(event.keyCode == 13) {
			Search.autoSearch();
		}
	}

	this.autoSearch = function()
	{
		let searchBox = document.getElementById('search-query');
		Search.doSearch(searchBox.value);
	}

	this.doSearch = function(query)
	{
		let query_string = 'q=' + encodeURIComponent(query);
		Tab.changeTab('template-search', false, 'tab-body', query_string, false);
	}
});
