var setCookie = function(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    } else var expires = "";
    document.cookie = name + "=" + value + expires + "; path=/; domain=" + window.location.host.replace('www', '');
};

var getCookie = function(c_name) {
    var i, x, y, ARRcookies = document.cookie.split(";");
    for (i = 0; i < ARRcookies.length; i++) {
        x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
        y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
        x = x.replace(/^\s+|\s+$/g, "");
        if (x == c_name) {
            try {
                return JSON.parse(decodeURIComponent(y));
            } catch (e) {
                return false;
            }

        }
    }
    return false;
};

var createPixleeAnalyticsCookie = function() {
    var iframe = document.createElement('iframe');
    iframe.style.display = "none";
    iframe.src = 'https://photos.pixlee.com/getDUH';
    document.body.appendChild(iframe);

    window.addEventListener("message", function receiveMessage(event) {
        try {
            var eventData = JSON.parse(event.data);
            if (eventData.function == "pixlee_distinct_user_hash") {
                if (eventData.data) {
                    var distinct_user_hash_linker = eventData.value;
                    if (!getCookie('pixlee_analytics_cookie')) {
                        setCookie('pixlee_analytics_cookie', encodeURIComponent(JSON.stringify({
                            CURRENT_PIXLEE_USER_ID: distinct_user_hash_linker
                        })), 30);
                    }
                }
            }
        } catch (e) {
            console.log("Exception " + e);
        };
    }, false);
}

var checkPixleeAnalyticsCookie = function(event) {
    if (!getCookie('pixlee_analytics_cookie')) {
        createPixleeAnalyticsCookie();
    }
};

window.addEventListener('load', checkPixleeAnalyticsCookie);