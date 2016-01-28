var keenClient = new Keen({
  projectId: '53fe3c9d7d8cb9502c000000',
  readKey: 'a5057d538dfbe84b5283f9b34a82b1bee1e4fe5416408a2d691180efdafea1423a0161311c17e1ee25cd2f20ad7a4617989ec77ee8e97f61667bb0edc2e39d66ae5bc199dc1307339e6f50190109f4c863a40dc8508580618ffb84a63fead8f6cd954e85b4ffec8b031ff51ec5b325b8'
});

var setCookie = function(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/; domain=" + window.location.host.replace('www', '');
};

var getCookie = function(c_name) {
  var i,x,y,ARRcookies=document.cookie.split(";");
  for (i=0;i<ARRcookies.length;i++) {
    x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
    y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
    x=x.replace(/^\s+|\s+$/g,"");
    if (x==c_name) {
      try {
        return JSON.parse(decodeURIComponent(y));
      } catch(e) {
        return false;
      }

    }
  }
  return false;
};

setTimeout(createPixleeAnalyticsCookie, 8000);

function createPixleeAnalyticsCookie() {
  // If pixlee_analytics_cookie does not exist then, there are 2 possibilities:
    // 1. The user came to this page from a tap2shop domain. In which case we query into keen.io to conform this.
    // 2. The user came here via some other link that is not related to Pixlee. In which case we only assign them a distinct_user_hash
  if (!getCookie('pixlee_analytics_cookie')) {
    var iframe = document.createElement('iframe');
    iframe.style.display = "none";
    iframe.src = 'https://limitless-beyond-4328.herokuapp.com/pixlee_linker';
    document.body.appendChild(iframe);

    var pixlee_linker;

    window.addEventListener("message", function receiveMessage(event){
      try {
        var eventData = JSON.parse(event.data);
        if (eventData.function == "pixlee_distinct_user_hash") {
          if(eventData.data) {
            pixlee_linker = eventData.value;
            console.log(pixlee_linker);
            var extraction = new Keen.Query("extraction", {
              eventCollection: "Action Clicked",
              timeframe: "this_30_days",
              filters: [
                {
                  "property_name": "api_key",
                  "operator": "eq",
                  "property_value": API_KEY
                },
                {
                  "property_name": "pixlee_linker",
                  "operator": "eq",
                  "property_value": pixlee_linker
                }
              ]
            });
            keenClient.run(extraction, function(err, res){
              if (err) {
                console.log(err);
              } else if (res.result.length === 0) {
                setCookie('pixlee_analytics_cookie', encodeURIComponent(JSON.stringify({
                  CURRENT_PIXLEE_USER_ID: pixlee_linker,
                  CURRENT_PIXLEE_ALBUM_PHOTOS: [],
                  CURRENT_PIXLEE_ALBUM_PHOTOS_TIMESTAMP: []
                })), 30);
              } else {
                setCookie('pixlee_analytics_cookie', encodeURIComponent(JSON.stringify({
                  CURRENT_PIXLEE_USER_ID: res.result[0]['distinct_user_hash'],
                  fingerprint: res.result[0]['fingerprint'],
                  CURRENT_PIXLEE_ALBUM_PHOTOS: res.result[0]['CURRENT_PIXLEE_ALBUM_PHOTOS'],
                  CURRENT_PIXLEE_ALBUM_PHOTOS_TIMESTAMP: res.result[0]['CURRENT_PIXLEE_ALBUM_PHOTOS_TIMESTAMP']
                })), 30);
              }
            });
          }
        }
      } catch(e) {};
    }, false);
  }
};