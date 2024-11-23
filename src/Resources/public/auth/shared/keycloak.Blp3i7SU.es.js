function e(o){if(!(this instanceof e))throw new Error("The 'Keycloak' constructor must be invoked with 'new'.");if("string"!=typeof o&&!n(o))throw new Error("The 'Keycloak' constructor must be provided with a configuration object, or a URL to a JSON configuration file.");if(n(o)){const e="oidcProvider"in o?["clientId"]:["url","realm","clientId"];for(const t of e)if(!o[t])throw new Error(`The configuration object is missing the required '${t}' property.`)}var r,i,s=this,a=[],c={enable:!0,callbackList:[],interval:5};s.didInitialize=!1;var u=!0,d=R(console.info),l=R(console.warn);function p(e){return function(e,t){for(var n=function(e){if("undefined"==typeof crypto||void 0===crypto.getRandomValues)throw new Error("Web Crypto API is not available.");return crypto.getRandomValues(new Uint8Array(e))}(e),o=new Array(e),r=0;r<e;r++)o[r]=t.charCodeAt(n[r]%t.length);return String.fromCharCode.apply(null,o)}(e,"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789")}async function f(e,t){if("S256"!==e)throw new TypeError(`Invalid value for 'pkceMethod', expected 'S256' but got '${e}'.`);return function(e){const t=String.fromCodePoint(...e);return btoa(t)}(new Uint8Array(await async function(e){const t=(new TextEncoder).encode(e);if("undefined"==typeof crypto||void 0===crypto.subtle)throw new Error("Web Crypto API is not available.");return await crypto.subtle.digest("SHA-256",t)}(t))).replace(/\+/g,"-").replace(/\//g,"_").replace(/\=/g,"")}function h(){return void 0!==s.authServerUrl?"/"==s.authServerUrl.charAt(s.authServerUrl.length-1)?s.authServerUrl+"realms/"+encodeURIComponent(s.realm):s.authServerUrl+"/realms/"+encodeURIComponent(s.realm):void 0}function m(e,t){var n=e.code,o=e.error,r=e.prompt,i=(new Date).getTime();if(e.kc_action_status&&s.onActionUpdate&&s.onActionUpdate(e.kc_action_status,e.kc_action),o)if("none"!=r)if(e.error_description&&"authentication_expired"===e.error_description)s.login(e.loginOptions);else{var a={error:o,error_description:e.error_description};s.onAuthError&&s.onAuthError(a),t&&t.setError(a)}else t&&t.setSuccess();else if("standard"!=s.flow&&(e.access_token||e.id_token)&&f(e.access_token,null,e.id_token,!0),"implicit"!=s.flow&&n){var c="code="+n+"&grant_type=authorization_code",l=s.endpoints.token(),p=new XMLHttpRequest;p.open("POST",l,!0),p.setRequestHeader("Content-type","application/x-www-form-urlencoded"),c+="&client_id="+encodeURIComponent(s.clientId),c+="&redirect_uri="+e.redirectUri,e.pkceCodeVerifier&&(c+="&code_verifier="+e.pkceCodeVerifier),p.withCredentials=!0,p.onreadystatechange=function(){if(4==p.readyState)if(200==p.status){var e=JSON.parse(p.responseText);f(e.access_token,e.refresh_token,e.id_token,"standard"===s.flow),U()}else s.onAuthError&&s.onAuthError(),t&&t.setError()},p.send(c)}function f(n,o,r,a){k(n,o,r,i=(i+(new Date).getTime())/2),u&&s.idTokenParsed&&s.idTokenParsed.nonce!=e.storedNonce?(d("[KEYCLOAK] Invalid nonce, clearing token"),s.clearToken(),t&&t.setError()):a&&(s.onAuthSuccess&&s.onAuthSuccess(),t&&t.setSuccess())}}function g(e){return 0==e.status&&e.responseText&&e.responseURL.startsWith("file:")}function k(e,n,o,r){if(s.tokenTimeoutHandle&&(clearTimeout(s.tokenTimeoutHandle),s.tokenTimeoutHandle=null),n?(s.refreshToken=n,s.refreshTokenParsed=t(n)):(delete s.refreshToken,delete s.refreshTokenParsed),o?(s.idToken=o,s.idTokenParsed=t(o)):(delete s.idToken,delete s.idTokenParsed),e){if(s.token=e,s.tokenParsed=t(e),s.sessionId=s.tokenParsed.sid,s.authenticated=!0,s.subject=s.tokenParsed.sub,s.realmAccess=s.tokenParsed.realm_access,s.resourceAccess=s.tokenParsed.resource_access,r&&(s.timeSkew=Math.floor(r/1e3)-s.tokenParsed.iat),null!=s.timeSkew&&(d("[KEYCLOAK] Estimated time difference between browser and server is "+s.timeSkew+" seconds"),s.onTokenExpired)){var i=1e3*(s.tokenParsed.exp-(new Date).getTime()/1e3+s.timeSkew);d("[KEYCLOAK] Token expires in "+Math.round(i/1e3)+" s"),i<=0?s.onTokenExpired():s.tokenTimeoutHandle=setTimeout(s.onTokenExpired,i)}}else delete s.token,delete s.tokenParsed,delete s.subject,delete s.realmAccess,delete s.resourceAccess,s.authenticated=!1}function w(){if("undefined"==typeof crypto||void 0===crypto.randomUUID)throw new Error("Web Crypto API is not available.");return crypto.randomUUID()}function v(e){var t=function(e){var t;switch(s.flow){case"standard":t=["code","state","session_state","kc_action_status","kc_action","iss"];break;case"implicit":t=["access_token","token_type","id_token","state","session_state","expires_in","kc_action_status","kc_action","iss"];break;case"hybrid":t=["access_token","token_type","id_token","code","state","session_state","expires_in","kc_action_status","kc_action","iss"]}t.push("error"),t.push("error_description"),t.push("error_uri");var n,o,r=e.indexOf("?"),i=e.indexOf("#");"query"===s.responseMode&&-1!==r?(n=e.substring(0,r),""!==(o=b(e.substring(r+1,-1!==i?i:e.length),t)).paramsString&&(n+="?"+o.paramsString),-1!==i&&(n+=e.substring(i))):"fragment"===s.responseMode&&-1!==i&&(n=e.substring(0,i),""!==(o=b(e.substring(i+1),t)).paramsString&&(n+="#"+o.paramsString));if(o&&o.oauthParams)if("standard"===s.flow||"hybrid"===s.flow){if((o.oauthParams.code||o.oauthParams.error)&&o.oauthParams.state)return o.oauthParams.newUrl=n,o.oauthParams}else if("implicit"===s.flow&&(o.oauthParams.access_token||o.oauthParams.error)&&o.oauthParams.state)return o.oauthParams.newUrl=n,o.oauthParams}(e);if(t){var n=i.get(t.state);return n&&(t.valid=!0,t.redirectUri=n.redirectUri,t.storedNonce=n.nonce,t.prompt=n.prompt,t.pkceCodeVerifier=n.pkceCodeVerifier,t.loginOptions=n.loginOptions),t}}function b(e,t){for(var n=e.split("&"),o={paramsString:"",oauthParams:{}},r=0;r<n.length;r++){var i=n[r].indexOf("="),s=n[r].slice(0,i);-1!==t.indexOf(s)?o.oauthParams[s]=n[r].slice(i+1):(""!==o.paramsString&&(o.paramsString+="&"),o.paramsString+=n[r])}return o}function y(){var e={setSuccess:function(t){e.resolve(t)},setError:function(t){e.reject(t)}};return e.promise=new Promise((function(t,n){e.resolve=t,e.reject=n})),e}function S(){var e=y();if(!c.enable)return e.setSuccess(),e.promise;if(c.iframe)return e.setSuccess(),e.promise;var t=document.createElement("iframe");c.iframe=t,t.onload=function(){var t=s.endpoints.authorize();"/"===t.charAt(0)?c.iframeOrigin=window.location.origin?window.location.origin:window.location.protocol+"//"+window.location.hostname+(window.location.port?":"+window.location.port:""):c.iframeOrigin=t.substring(0,t.indexOf("/",8)),e.setSuccess()};var n=s.endpoints.checkSessionIframe();t.setAttribute("src",n),t.setAttribute("sandbox","allow-storage-access-by-user-activation allow-scripts allow-same-origin"),t.setAttribute("title","keycloak-session-iframe"),t.style.display="none",document.body.appendChild(t);return window.addEventListener("message",(function(e){if(e.origin===c.iframeOrigin&&c.iframe.contentWindow===e.source&&("unchanged"==e.data||"changed"==e.data||"error"==e.data)){"unchanged"!=e.data&&s.clearToken();for(var t=c.callbackList.splice(0,c.callbackList.length),n=t.length-1;n>=0;--n){var o=t[n];"error"==e.data?o.setError():o.setSuccess("unchanged"==e.data)}}}),!1),e.promise}function U(){c.enable&&s.token&&setTimeout((function(){_().then((function(e){e&&U()}))}),1e3*c.interval)}function _(){var e=y();if(c.iframe&&c.iframeOrigin){var t=s.clientId+" "+(s.sessionId?s.sessionId:"");c.callbackList.push(e);var n=c.iframeOrigin;1==c.callbackList.length&&c.iframe.contentWindow.postMessage(t,n)}else e.setSuccess();return e.promise}function I(e){if(!e||"default"==e)return{login:async function(e){return window.location.assign(await s.createLoginUrl(e)),y().promise},logout:async function(e){if("GET"===(e?.logoutMethod??s.logoutMethod))return void window.location.replace(s.createLogoutUrl(e));const t=s.createLogoutUrl(e),n=await fetch(t,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:new URLSearchParams({id_token_hint:s.idToken,client_id:s.clientId,post_logout_redirect_uri:r.redirectUri(e,!1)})});if(n.redirected)window.location.href=n.url;else{if(!n.ok)throw new Error("Logout failed, request returned an error code.");window.location.reload()}},register:async function(e){return window.location.assign(await s.createRegisterUrl(e)),y().promise},accountManagement:function(){var e=s.createAccountUrl();if(void 0===e)throw"Not supported by the OIDC server";return window.location.href=e,y().promise},redirectUri:function(e,t){return e&&e.redirectUri?e.redirectUri:s.redirectUri?s.redirectUri:location.href}};if("cordova"==e){c.enable=!1;var t=function(e,t,n){return window.cordova&&window.cordova.InAppBrowser?window.cordova.InAppBrowser.open(e,t,n):window.open(e,t,n)},n=function(e){var t=function(e){return e&&e.cordovaOptions?Object.keys(e.cordovaOptions).reduce((function(t,n){return t[n]=e.cordovaOptions[n],t}),{}):{}}(e);return t.location="no",e&&"none"==e.prompt&&(t.hidden="yes"),function(e){return Object.keys(e).reduce((function(t,n){return t.push(n+"="+e[n]),t}),[]).join(",")}(t)},o=function(){return s.redirectUri||"http://localhost"};return{login:async function(e){var r=y(),i=n(e),a=await s.createLoginUrl(e),c=t(a,"_blank",i),u=!1,d=!1,l=function(){d=!0,c.close()};return c.addEventListener("loadstart",(function(e){0==e.url.indexOf(o())&&(m(v(e.url),r),l(),u=!0)})),c.addEventListener("loaderror",(function(e){u||(0==e.url.indexOf(o())?(m(v(e.url),r),l(),u=!0):(r.setError(),l()))})),c.addEventListener("exit",(function(e){d||r.setError({reason:"closed_by_user"})})),r.promise},logout:function(e){var n,r=y(),i=s.createLogoutUrl(e),a=t(i,"_blank","location=no,hidden=yes,clearcache=yes");return a.addEventListener("loadstart",(function(e){0==e.url.indexOf(o())&&a.close()})),a.addEventListener("loaderror",(function(e){0==e.url.indexOf(o())||(n=!0),a.close()})),a.addEventListener("exit",(function(e){n?r.setError():(s.clearToken(),r.setSuccess())})),r.promise},register:async function(e){var r=y(),i=await s.createRegisterUrl(),a=n(e),c=t(i,"_blank",a);return c.addEventListener("loadstart",(function(e){0==e.url.indexOf(o())&&(c.close(),m(v(e.url),r))})),r.promise},accountManagement:function(){var e=s.createAccountUrl();if(void 0===e)throw"Not supported by the OIDC server";var n=t(e,"_blank","location=no");n.addEventListener("loadstart",(function(e){0==e.url.indexOf(o())&&n.close()}))},redirectUri:function(e){return o()}}}if("cordova-native"==e)return c.enable=!1,{login:async function(e){var t=y(),n=await s.createLoginUrl(e);return universalLinks.subscribe("keycloak",(function(e){universalLinks.unsubscribe("keycloak"),window.cordova.plugins.browsertab.close(),m(v(e.url),t)})),window.cordova.plugins.browsertab.openUrl(n),t.promise},logout:function(e){var t=y(),n=s.createLogoutUrl(e);return universalLinks.subscribe("keycloak",(function(e){universalLinks.unsubscribe("keycloak"),window.cordova.plugins.browsertab.close(),s.clearToken(),t.setSuccess()})),window.cordova.plugins.browsertab.openUrl(n),t.promise},register:async function(e){var t=y(),n=await s.createRegisterUrl(e);return universalLinks.subscribe("keycloak",(function(e){universalLinks.unsubscribe("keycloak"),window.cordova.plugins.browsertab.close(),m(v(e.url),t)})),window.cordova.plugins.browsertab.openUrl(n),t.promise},accountManagement:function(){var e=s.createAccountUrl();if(void 0===e)throw"Not supported by the OIDC server";window.cordova.plugins.browsertab.openUrl(e)},redirectUri:function(e){return e&&e.redirectUri?e.redirectUri:s.redirectUri?s.redirectUri:"http://localhost"}};throw"invalid adapter type: "+e}globalThis.isSecureContext||l("[KEYCLOAK] Keycloak JS must be used in a 'secure context' to function properly as it relies on browser APIs that are otherwise not available.\nContinuing to run your application insecurely will lead to unexpected behavior and breakage.\n\nFor more information see: https://developer.mozilla.org/en-US/docs/Web/Security/Secure_Contexts"),s.init=function(e={}){if(s.didInitialize)throw new Error("A 'Keycloak' instance can only be initialized once.");s.didInitialize=!0,s.authenticated=!1,i=function(){try{return new C}catch(e){}return new E}();if(r=["default","cordova","cordova-native"].indexOf(e.adapter)>-1?I(e.adapter):"object"==typeof e.adapter?e.adapter:window.Cordova||window.cordova?I("cordova"):I(),void 0!==e.useNonce&&(u=e.useNonce),void 0!==e.checkLoginIframe&&(c.enable=e.checkLoginIframe),e.checkLoginIframeInterval&&(c.interval=e.checkLoginIframeInterval),"login-required"===e.onLoad&&(s.loginRequired=!0),e.responseMode){if("query"!==e.responseMode&&"fragment"!==e.responseMode)throw"Invalid value for responseMode";s.responseMode=e.responseMode}if(e.flow){switch(e.flow){case"standard":s.responseType="code";break;case"implicit":s.responseType="id_token token";break;case"hybrid":s.responseType="code id_token token";break;default:throw"Invalid value for flow"}s.flow=e.flow}if(null!=e.timeSkew&&(s.timeSkew=e.timeSkew),e.redirectUri&&(s.redirectUri=e.redirectUri),e.silentCheckSsoRedirectUri&&(s.silentCheckSsoRedirectUri=e.silentCheckSsoRedirectUri),"boolean"==typeof e.silentCheckSsoFallback?s.silentCheckSsoFallback=e.silentCheckSsoFallback:s.silentCheckSsoFallback=!0,void 0!==e.pkceMethod){if("S256"!==e.pkceMethod&&!1!==e.pkceMethod)throw new TypeError(`Invalid value for pkceMethod', expected 'S256' or false but got ${e.pkceMethod}.`);s.pkceMethod=e.pkceMethod}else s.pkceMethod="S256";"boolean"==typeof e.enableLogging?s.enableLogging=e.enableLogging:s.enableLogging=!1,"POST"===e.logoutMethod?s.logoutMethod="POST":s.logoutMethod="GET","string"==typeof e.scope&&(s.scope=e.scope),"string"==typeof e.acrValues&&(s.acrValues=e.acrValues),"number"==typeof e.messageReceiveTimeout&&e.messageReceiveTimeout>0?s.messageReceiveTimeout=e.messageReceiveTimeout:s.messageReceiveTimeout=1e4,s.responseMode||(s.responseMode="fragment"),s.responseType||(s.responseType="code",s.flow="standard");var t=y(),n=y();n.promise.then((function(){s.onReady&&s.onReady(s.authenticated),t.setSuccess(s.authenticated)})).catch((function(e){t.setError(e)}));var a=function(){var e,t=y();"string"==typeof o&&(e=o);function n(e){s.endpoints=e?{authorize:function(){return e.authorization_endpoint},token:function(){return e.token_endpoint},logout:function(){if(!e.end_session_endpoint)throw"Not supported by the OIDC server";return e.end_session_endpoint},checkSessionIframe:function(){if(!e.check_session_iframe)throw"Not supported by the OIDC server";return e.check_session_iframe},register:function(){throw'Redirection to "Register user" page not supported in standard OIDC mode'},userinfo:function(){if(!e.userinfo_endpoint)throw"Not supported by the OIDC server";return e.userinfo_endpoint}}:{authorize:function(){return h()+"/protocol/openid-connect/auth"},token:function(){return h()+"/protocol/openid-connect/token"},logout:function(){return h()+"/protocol/openid-connect/logout"},checkSessionIframe:function(){return h()+"/protocol/openid-connect/login-status-iframe.html"},thirdPartyCookiesIframe:function(){return h()+"/protocol/openid-connect/3p-cookies/step1.html"},register:function(){return h()+"/protocol/openid-connect/registrations"},userinfo:function(){return h()+"/protocol/openid-connect/userinfo"}}}if(e){(i=new XMLHttpRequest).open("GET",e,!0),i.setRequestHeader("Accept","application/json"),i.onreadystatechange=function(){if(4==i.readyState)if(200==i.status||g(i)){var e=JSON.parse(i.responseText);s.authServerUrl=e["auth-server-url"],s.realm=e.realm,s.clientId=e.resource,n(null),t.setSuccess()}else t.setError()},i.send()}else{s.clientId=o.clientId;var r,i,a=o.oidcProvider;if(a)if("string"==typeof a)r="/"==a.charAt(a.length-1)?a+".well-known/openid-configuration":a+"/.well-known/openid-configuration",(i=new XMLHttpRequest).open("GET",r,!0),i.setRequestHeader("Accept","application/json"),i.onreadystatechange=function(){4==i.readyState&&(200==i.status||g(i)?(n(JSON.parse(i.responseText)),t.setSuccess()):t.setError())},i.send();else n(a),t.setSuccess();else s.authServerUrl=o.url,s.realm=o.realm,n(null),t.setSuccess()}return t.promise}();function d(){var t=function(t){t||(r.prompt="none"),e.locale&&(r.locale=e.locale),s.login(r).then((function(){n.setSuccess()})).catch((function(e){n.setError(e)}))},o=async function(){var e=document.createElement("iframe"),t=await s.createLoginUrl({prompt:"none",redirectUri:s.silentCheckSsoRedirectUri});e.setAttribute("src",t),e.setAttribute("sandbox","allow-storage-access-by-user-activation allow-scripts allow-same-origin"),e.setAttribute("title","keycloak-silent-check-sso"),e.style.display="none",document.body.appendChild(e);var o=function(t){t.origin===window.location.origin&&e.contentWindow===t.source&&(m(v(t.data),n),document.body.removeChild(e),window.removeEventListener("message",o))};window.addEventListener("message",o)},r={};switch(e.onLoad){case"check-sso":c.enable?S().then((function(){_().then((function(e){e?n.setSuccess():s.silentCheckSsoRedirectUri?o():t(!1)})).catch((function(e){n.setError(e)}))})):s.silentCheckSsoRedirectUri?o():t(!1);break;case"login-required":t(!0);break;default:throw"Invalid value for onLoad"}}function p(){var t=v(window.location.href);if(t&&window.history.replaceState(window.history.state,null,t.newUrl),t&&t.valid)return S().then((function(){m(t,n)})).catch((function(e){n.setError(e)}));e.token&&e.refreshToken?(k(e.token,e.refreshToken,e.idToken),c.enable?S().then((function(){_().then((function(e){e?(s.onAuthSuccess&&s.onAuthSuccess(),n.setSuccess(),U()):n.setSuccess()})).catch((function(e){n.setError(e)}))})):s.updateToken(-1).then((function(){s.onAuthSuccess&&s.onAuthSuccess(),n.setSuccess()})).catch((function(t){s.onAuthError&&s.onAuthError(),e.onLoad?d():n.setError(t)}))):e.onLoad?d():n.setSuccess()}return a.then((function(){(function(){var e=y();if((c.enable||s.silentCheckSsoRedirectUri)&&"function"==typeof s.endpoints.thirdPartyCookiesIframe){var t=document.createElement("iframe");t.setAttribute("src",s.endpoints.thirdPartyCookiesIframe()),t.setAttribute("sandbox","allow-storage-access-by-user-activation allow-scripts allow-same-origin"),t.setAttribute("title","keycloak-3p-check-iframe"),t.style.display="none",document.body.appendChild(t);var n=function(o){t.contentWindow===o.source&&("supported"!==o.data&&"unsupported"!==o.data||("unsupported"===o.data&&(l("[KEYCLOAK] Your browser is blocking access to 3rd-party cookies, this means:\n\n - It is not possible to retrieve tokens without redirecting to the Keycloak server (a.k.a. no support for silent authentication).\n - It is not possible to automatically detect changes to the session status (such as the user logging out in another tab).\n\nFor more information see: https://www.keycloak.org/docs/latest/securing_apps/#_modern_browsers"),c.enable=!1,s.silentCheckSsoFallback&&(s.silentCheckSsoRedirectUri=!1)),document.body.removeChild(t),window.removeEventListener("message",n),e.setSuccess()))};window.addEventListener("message",n,!1)}else e.setSuccess();return function(e,t,n){var o=null,r=new Promise((function(e,r){o=setTimeout((function(){r({error:n})}),t)}));return Promise.race([e,r]).finally((function(){clearTimeout(o)}))}(e.promise,s.messageReceiveTimeout,"Timeout when waiting for 3rd party check iframe message.")})().then(p).catch((function(e){t.setError(e)}))})),a.catch((function(e){t.setError(e)})),t.promise},s.login=function(e){return r.login(e)},s.createLoginUrl=async function(e){var t,n=w(),o=w(),a=r.redirectUri(e),c={state:n,nonce:o,redirectUri:encodeURIComponent(a),loginOptions:e};e&&e.prompt&&(c.prompt=e.prompt),t=e&&"register"==e.action?s.endpoints.register():s.endpoints.authorize();var d=e&&e.scope||s.scope;d?-1===d.indexOf("openid")&&(d="openid "+d):d="openid";var l,h,m=t+"?client_id="+encodeURIComponent(s.clientId)+"&redirect_uri="+encodeURIComponent(a)+"&state="+encodeURIComponent(n)+"&response_mode="+encodeURIComponent(s.responseMode)+"&response_type="+encodeURIComponent(s.responseType)+"&scope="+encodeURIComponent(d);if(u&&(m=m+"&nonce="+encodeURIComponent(o)),e&&e.prompt&&(m+="&prompt="+encodeURIComponent(e.prompt)),e&&"number"==typeof e.maxAge&&(m+="&max_age="+encodeURIComponent(e.maxAge)),e&&e.loginHint&&(m+="&login_hint="+encodeURIComponent(e.loginHint)),e&&e.idpHint&&(m+="&kc_idp_hint="+encodeURIComponent(e.idpHint)),e&&e.action&&"register"!=e.action&&(m+="&kc_action="+encodeURIComponent(e.action)),e&&e.locale&&(m+="&ui_locales="+encodeURIComponent(e.locale)),e&&e.acr){var g=(l=e.acr,h={id_token:{acr:l}},JSON.stringify(h));m+="&claims="+encodeURIComponent(g)}if((e&&e.acrValues||s.acrValues)&&(m+="&acr_values="+encodeURIComponent(e.acrValues||s.acrValues)),s.pkceMethod)try{const e=p(96),t=await f(s.pkceMethod,e);c.pkceCodeVerifier=e,m+="&code_challenge="+t,m+="&code_challenge_method="+s.pkceMethod}catch(e){throw new Error("Failed to generate PKCE challenge.",{cause:e})}return i.add(c),m},s.logout=function(e){return r.logout(e)},s.createLogoutUrl=function(e){if("POST"===(e?.logoutMethod??s.logoutMethod))return s.endpoints.logout();var t=s.endpoints.logout()+"?client_id="+encodeURIComponent(s.clientId)+"&post_logout_redirect_uri="+encodeURIComponent(r.redirectUri(e,!1));return s.idToken&&(t+="&id_token_hint="+encodeURIComponent(s.idToken)),t},s.register=function(e){return r.register(e)},s.createRegisterUrl=async function(e){return e||(e={}),e.action="register",await s.createLoginUrl(e)},s.createAccountUrl=function(e){var t=h(),n=void 0;return void 0!==t&&(n=t+"/account?referrer="+encodeURIComponent(s.clientId)+"&referrer_uri="+encodeURIComponent(r.redirectUri(e))),n},s.accountManagement=function(){return r.accountManagement()},s.hasRealmRole=function(e){var t=s.realmAccess;return!!t&&t.roles.indexOf(e)>=0},s.hasResourceRole=function(e,t){if(!s.resourceAccess)return!1;var n=s.resourceAccess[t||s.clientId];return!!n&&n.roles.indexOf(e)>=0},s.loadUserProfile=function(){var e=h()+"/account",t=new XMLHttpRequest;t.open("GET",e,!0),t.setRequestHeader("Accept","application/json"),t.setRequestHeader("Authorization","bearer "+s.token);var n=y();return t.onreadystatechange=function(){4==t.readyState&&(200==t.status?(s.profile=JSON.parse(t.responseText),n.setSuccess(s.profile)):n.setError())},t.send(),n.promise},s.loadUserInfo=function(){var e=s.endpoints.userinfo(),t=new XMLHttpRequest;t.open("GET",e,!0),t.setRequestHeader("Accept","application/json"),t.setRequestHeader("Authorization","bearer "+s.token);var n=y();return t.onreadystatechange=function(){4==t.readyState&&(200==t.status?(s.userInfo=JSON.parse(t.responseText),n.setSuccess(s.userInfo)):n.setError())},t.send(),n.promise},s.isTokenExpired=function(e){if(!s.tokenParsed||!s.refreshToken&&"implicit"!=s.flow)throw"Not authenticated";if(null==s.timeSkew)return d("[KEYCLOAK] Unable to determine if token is expired as timeskew is not set"),!0;var t=s.tokenParsed.exp-Math.ceil((new Date).getTime()/1e3)+s.timeSkew;if(e){if(isNaN(e))throw"Invalid minValidity";t-=e}return t<0},s.updateToken=function(e){var t=y();if(!s.refreshToken)return t.setError(),t.promise;e=e||5;var n=function(){var n=!1;if(-1==e?(n=!0,d("[KEYCLOAK] Refreshing token: forced refresh")):s.tokenParsed&&!s.isTokenExpired(e)||(n=!0,d("[KEYCLOAK] Refreshing token: token expired")),n){var o="grant_type=refresh_token&refresh_token="+s.refreshToken,r=s.endpoints.token();if(a.push(t),1==a.length){var i=new XMLHttpRequest;i.open("POST",r,!0),i.setRequestHeader("Content-type","application/x-www-form-urlencoded"),i.withCredentials=!0,o+="&client_id="+encodeURIComponent(s.clientId);var c=(new Date).getTime();i.onreadystatechange=function(){if(4==i.readyState)if(200==i.status){d("[KEYCLOAK] Token refreshed"),c=(c+(new Date).getTime())/2;var e=JSON.parse(i.responseText);k(e.access_token,e.refresh_token,e.id_token,c),s.onAuthRefreshSuccess&&s.onAuthRefreshSuccess();for(var t=a.pop();null!=t;t=a.pop())t.setSuccess(!0)}else{l("[KEYCLOAK] Failed to refresh token"),400==i.status&&s.clearToken(),s.onAuthRefreshError&&s.onAuthRefreshError();for(t=a.pop();null!=t;t=a.pop())t.setError(!0)}},i.send(o)}}else t.setSuccess(!1)};c.enable?_().then((function(){n()})).catch((function(e){t.setError(e)})):n();return t.promise},s.clearToken=function(){s.token&&(k(null,null,null),s.onAuthLogout&&s.onAuthLogout(),s.loginRequired&&s.login())};const T="kc-callback-";var C=function(){if(!(this instanceof C))return new C;localStorage.setItem("kc-test","test"),localStorage.removeItem("kc-test");function e(){const e=Date.now();for(const[n,r]of t()){const t=o(r);(null===t||t<e)&&localStorage.removeItem(n)}}function t(){return Object.entries(localStorage).filter((([e])=>e.startsWith(T)))}function o(e){let t;try{t=JSON.parse(e)}catch(e){return null}return n(t)&&"expires"in t&&"number"==typeof t.expires?t.expires:null}this.get=function(t){if(t){var n=T+t,o=localStorage.getItem(n);return o&&(localStorage.removeItem(n),o=JSON.parse(o)),e(),o}},this.add=function(n){e();const o=T+n.state,r=JSON.stringify({...n,expires:Date.now()+36e5});try{localStorage.setItem(o,r)}catch(e){!function(){for(const[e]of t())localStorage.removeItem(e)}(),localStorage.setItem(o,r)}}},E=function(){if(!(this instanceof E))return new E;var e=this;e.get=function(e){if(e){var r=n(T+e);return o(T+e,"",t(-100)),r?JSON.parse(r):void 0}},e.add=function(e){o(T+e.state,JSON.stringify(e),t(60))},e.removeItem=function(e){o(e,"",t(-100))};var t=function(e){var t=new Date;return t.setTime(t.getTime()+60*e*1e3),t},n=function(e){for(var t=e+"=",n=document.cookie.split(";"),o=0;o<n.length;o++){for(var r=n[o];" "==r.charAt(0);)r=r.substring(1);if(0==r.indexOf(t))return r.substring(t.length,r.length)}return""},o=function(e,t,n){var o=e+"="+t+"; expires="+n.toUTCString()+"; ";document.cookie=o}};function R(e){return function(){s.enableLogging&&e.apply(console,Array.prototype.slice.call(arguments))}}}function t(e){const[t,n]=e.split(".");if("string"!=typeof n)throw new Error("Unable to decode token, payload not found.");let o;try{o=function(e){let t=e.replaceAll("-","+").replaceAll("_","/");switch(t.length%4){case 0:break;case 2:t+="==";break;case 3:t+="=";break;default:throw new Error("Input is not of the correct length.")}try{return function(e){return decodeURIComponent(atob(e).replace(/(.)/g,((e,t)=>{let n=t.charCodeAt(0).toString(16).toUpperCase();return n.length<2&&(n="0"+n),"%"+n})))}(t)}catch(e){return atob(t)}}(n)}catch(e){throw new Error("Unable to decode token, payload is not a valid Base64URL value.",{cause:e})}try{return JSON.parse(o)}catch(e){throw new Error("Unable to decode token, payload is not a valid JSON value.",{cause:e})}}function n(e){return"object"==typeof e&&null!==e}export{e as default};
//# sourceMappingURL=keycloak.Blp3i7SU.es.js.map