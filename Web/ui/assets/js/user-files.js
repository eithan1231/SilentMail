var userfiles = (new function() {
	this.upload = function(file, callback = false, progressEvent = false)
	{
		// dummy events
		progressEvent = progressEvent || function(xx, xx) { };
		callback = callback || function() { };

		// geting url
		let url = Library.getPostRoute('user-files');

		let http = Library.getHttp();
		http.open("POST", url, true);
		http.setRequestHeader('Content-type', 'multipart/form-data');
		http.addEventListener('readstatechange', function() {
			if(this.readyState == 4) {
				callback(this.status == 200, this.responseText);
			}
		});
		http.addEventListener('progress', function(progress) {
			if(progress.lengthComputable) {
				progressEvent(progress.loaded / progress.total * 100);
			}
			else {
				progressEvent(-1);
			}
		});
		http.send(file);
	};
});
