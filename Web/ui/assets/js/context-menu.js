var ContextMenu = new function()
{
	this.isFromButton = false;

	this.checkCollisions = function()
	{
		let contentMenuElements = document.getElementsByClassName('dropdown');

		for(let i = 0; i < contentMenuElements.length; i++) {
			if(!contentMenuElements[i].hidden) {
				contentMenuElements[i].hidden = !Library.colliding(
					contentMenuElements[i].offsetLeft,
					contentMenuElements[i].offsetTop,
					contentMenuElements[i].offsetWidth,
					contentMenuElements[i].offsetHeight,

					// cursor will have a 5px padding around it.
					Library.mouseX,
					Library.mouseY,
					1,
					1
				);
			}
		}
	};

	this.initialize = function()
	{
		document.body.addEventListener('click', function(e) {
			if(ContextMenu.isFromButton) {
				ContextMenu.isFromButton = false;
				return;
			}

			ContextMenu.checkCollisions();
		});

		document.body.addEventListener('scroll', function() {
			ContextMenu.checkCollisions();
		});
	};

	this.open = function(divId)
	{
		ContextMenu.isFromButton = true;
		document.getElementById(divId).hidden = false;
	};
};
