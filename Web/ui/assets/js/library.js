var Library = new function() {
	this.mouseX = 0;
	this.mouseY = 0;
	this.onMouseMoveInterval = null;

	this.mouseMovements = [];
	this.mouseMovementsCount = 0;

	/**
	* Alternate Alert
	*/
	this.alert = function(string, buttons = {'ok': 'transparent'}, callback = false)
	{
		let backgroundElem = document.createElement('div');
		backgroundElem.style.position = 'fixed';
		backgroundElem.style.width = '100%';
		backgroundElem.style.height = '100%';
		backgroundElem.style.left = '0px';
		backgroundElem.style.top = '0px';
		backgroundElem.style.backgroundColor = '#f3f3f329';
		backgroundElem.style.zIndex = 1000;

		let alertElem = document.createElement('div');
		alertElem.style.zIndex = 1001;
		alertElem.classList.add('alert');

		// exit button
		let exitElem = document.createElement('div');
		exitElem.classList.add('exit');
		exitElem.classList.add('noselect');
		exitElem.textContent = 'x';
		exitElem.addEventListener('click', function() {
			Library.fadeElement(alertElem, function() {
				alertElem.parentNode.removeChild(alertElem);
				backgroundElem.parentNode.removeChild(backgroundElem);
			});
		});

		// body
		let bodyElem = document.createElement('p');
		bodyElem.classList.add('body');
		bodyElem.innerHTML = Library.escapeHtml(string);

		// buttons
		let buttonContainerElem = document.createElement('div');
		buttonContainerElem.style.marginTop = '14px'
		let button_keys = Object.keys(buttons);
		for (let i = 0; i < button_keys.length; i++) {
			let buttonElem = document.createElement('button');

			buttonElem.textContent = button_keys[i];
			buttonElem.style.backgroundColor = buttons[i];
			buttonElem.classList.add('al-button');
			buttonElem.classList.add('noselect');
			buttonElem.addEventListener('click', function() {
				exitElem.click();
				if(callback !== false) {
					callback(button_keys[i]);
				}
			});

			buttonContainerElem.appendChild(buttonElem);
		}

		// Appending all child elements
		alertElem.appendChild(exitElem);
		alertElem.appendChild(bodyElem);
		alertElem.appendChild(buttonContainerElem);
		backgroundElem.appendChild(alertElem);

		document.body.appendChild(backgroundElem);
	};

	/**
	* Fades an element
	*/
	this.fadeElement = function(element, onhidden = false)
	{
		let opacity = 1;
		let faceInterval = setInterval(function() {
			if(opacity <= 0) {
				clearInterval(faceInterval);
				element.hidden = true;

				if(onhidden !== false) {
					onhidden();
				}
			}

			opacity -= .1;

			// TODO: IE compatible.
			element.style.opacity = opacity;
		}, 10);
	};

	/**
	* Mines mouse movements. Used for cryptographic functions on server and client.
	*/
	this.mouseMiner = function()
	{
		let mousemoveHandler = function(pos) {
			console.log('mouse');

			Library.mouseMovements.push({
				x: pos.screenX,
				y: pos.screenY
			});
			Library.mouseMovementsCount++;

			if(
				Library.mouseMovementsCount % 128 == 0 &&
				Library.mouseMovementsCount <= 1024
			) {
				Library.doAjax(
					Library.getPostRoute('mouse-data'),
					function(status, content) { },
					"POST",
					JSON.stringify(Library.mouseMovements)
				);

				Library.mouseMovements = [];
			}
		};

		window.addEventListener('mousemove', mousemoveHandler);
	}

	this.random = function(length, specialchars = false)
	{
		let ret = '';
		let chars = 'abcdefghijklmnopqrstuvwxyz';
		chars += chars.toUpperCase();
		if(specialchars) {
			chars += '!@#$%^&*()';
		}

		let chars_len = chars.length;

		for (let i = 0; i < length; i++) {
			ret += chars[rand(0, chars_len - 1)];
		}

		return ret;
	};

	this.initialize = function() {
		document.body.addEventListener('mousemove', function(e) {
			Library.mouseX = e.clientX;
			Library.mouseY = e.clientY;
		});
	}

	this.getPostRoute = function(action) {
		return post_route.replace('__replaceme__', encodeURIComponent(action));
	}

	this.doAjax = function(url, callback, method = "GET", data = false, headers = false) {
		// creating new http object
		let xhttp = Library.getHttp();

		// opening connection
		xhttp.open(method, url, true);

		// Setting headers
		if(headers !== false) {
			for(let i = 0; i < headers.length; i++) {
				xhttp.setRequestHeader(
					headers[i]['name'],
					headers[i]['data']
				);
			}
		}

		// event listener
		xhttp.onreadystatechange = function() {
			if(this.readyState == 4) {
				callback(this.status, this.responseText);
			}
		}

		// sending request
		if(data !== false) {
			xhttp.send(data);
		}
		else {
			xhttp.send();
		}

		return xhttp;
	}

	this.getHttp = function() {
		return new XMLHttpRequest();
	}

	this.time = function() {
		return Math.round((new Date()).getTime() / 1000);
	}

	this.elementsColliding = function(elem1, elem2) {
		return Library.colliding(
			elem1.offsetLeft,
			elem1.offsetTop,
			elem1.offsetWidth,
			elem1.offsetHeight,

			elem2.offsetLeft,
			elem2.offsetTop,
			elem2.offsetWidth,
			elem2.offsetHeight
		);
	}

	this.colliding = function(
		elem1x, elem1y, elem1w, elem1h,
		elem2x, elem2y, elem2w, elem2h
	) {
		return !(
			(elem1x + elem1w) < elem2x ||
			elem1x > (elem2x + elem2w) ||
			(elem1y + elem1h) < elem2y ||
			elem1y > (elem2y + elem2h)
		);
	}

	this.escapeHtml = function(text)
	{
		let element = document.createElement('span');
		element.textContent = text;

		return element.innerHTML.replace("\n", "<br />");
	}
}
Library.mouseMiner();
