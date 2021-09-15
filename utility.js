function getViewportHeight(theWin) 
{
	if (typeof(theWin.innerHeight) != "undefined") 
        	return theWin.innerHeight;

	if (theWin.document.compatMode == 'CSS1Compat') 
        	return theWin.document.documentElement.clientHeight;

	if (theWin.document.body) 
        	return theWin.document.body.clientHeight; 

	return window.undefined;
}

function getViewportWidth(theWin) 
{
	if (typeof(theWin.innerWidth) != "undefined") 
        	return theWin.innerWidth;

	if (theWin.document.compatMode == 'CSS1Compat') 
        	return theWin.document.documentElement.clientWidth;

	if (theWin.document.body) 
        	return theWin.document.body.clientWidth; 

	return window.undefined;
}

function setCookie(name, value, days) 
{
	if (days) 
    {
		var date = new Date();
		date.setTime(date.getTime() + (days*24*60*60*1000));
		var expires = "; expires=" + date.toGMTString();
	}
	else 
	{
        var expires = "";
    }
	document.cookie = name + "=" + value + expires + "; path=/";
}

function getCookie(name)
{
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');

	for (var i = 0; i < ca.length; i++) 
	{
		var c = ca[i];

		while (c.charAt(0) == ' ') 
			c = c.substring(1, c.length);

		if (c.indexOf(nameEQ) == 0) 
			return c.substring(nameEQ.length, c.length);
	}
	return null;
}

function eraseCookie(name) 
{
	setCookie(name, "", -1);
}
