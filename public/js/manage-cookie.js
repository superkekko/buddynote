//create/delete/get cookie (option: {domain: 'domain.com', Secure: true, 'max-age': 1, SameSite: 'lax'}) !! the time is in days !!
function setCookie(name, value, options = {}) {
  options = {
    path: '/',
    // aggiungi altri percorsi di default se necessario
    ...options
  };
  
  if (options.expires != null) {
  	let date = new Date();
    date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
    options.expires = date.toUTCString();
  }
  
  var maxAge = options["max-age"];
  if (maxAge != null) {
    options["max-age"] = maxAge * 24 * 60 * 60;
  }

  let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

  for (let optionKey in options) {
    updatedCookie += "; " + optionKey;
    let optionValue = options[optionKey];
    if (optionValue !== true) {
      updatedCookie += "=" + optionValue;
    }
  }

  document.cookie = updatedCookie;
}

function deleteCookie(name,host) {
	setCookie(name, '',
		{expires:-1,
		domain: host,
		Secure: true,
		'max-age': -3600,
		SameSite: 'strict'
	});
}

function getCookie(name) {
    var i,x,y,ARRcookies=document.cookie.split(";");

    for (i=0;i<ARRcookies.length;i++)
    {
        x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
        y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
        x=x.replace(/^\s+|\s+$/g,"");
        if (x==name)
        {
            return unescape(y);
        }
     }
}