import './auth/api-platform-auth.js';

function log(message) {
    console.log('API docs: ' + message);
}

function useToken(token) {
    var authContainer = null;
    var btn = document.getElementsByClassName('btn authorize unlocked')[0];
    if (btn !== undefined) {
        btn.click();
        authContainer = document.getElementsByClassName('auth-container')[0];
    } else {
        btn = document.getElementsByClassName('btn authorize locked')[0];
        btn.click();
        authContainer = document.getElementsByClassName('auth-container')[0];
        authContainer.children[0].children[1].children[0].click(); // Logout
    }

    var pwInput = authContainer.children[0].children[0].children[0].children[4].children[1].children[0];
    var nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, "value").set;
    if (token)
        token = 'Bearer ' + token;
    nativeInputValueSetter.call(pwInput, token); //'react 16 value');

    var ev2 = new Event('input', { bubbles: true});
    pwInput.dispatchEvent(ev2);

    // --------
    var btnLogin = authContainer.children[0].children[1].children[0];
    btnLogin.click(); // Login
    authContainer.children[0].children[1].children[1].click(); // Close
    if (token)
        log('New token set');
}

var delayInsertTimer = 0;

function insertDBPContainer() {
    let target = document.getElementsByClassName('scheme-container')[0];
    if (target === undefined)
        return;

    // see ../auth/README.md
    var element = document.createElement('api-platform-auth');
    let config = window.keycloakConfig;

    element.setAttribute('lang', 'en');
    element.setAttribute('url', config.url);
    element.setAttribute('realm', config.realm);
    element.setAttribute('client-id', config.clientId);
    element.setAttribute('silent-check-sso-redirect-uri', new URL("auth/silent-check-sso.html", import.meta.url).href);
    element.setAttribute('entry-point-url', new URL('../..', import.meta.url).href);
    element.setAttribute('auth', '');
    element.setAttribute('requested-login-status', '');

    var section = target.children[0];
    section.insertBefore(element, section.children[0]);
    window.clearInterval(delayInsertTimer);
    log('insertDBPContainer done');
}

function delayInsert() {
    delayInsertTimer = window.setInterval(insertDBPContainer, 10);
}

document.addEventListener('DOMContentLoaded', delayInsert);

function onAuthUpdate(e) {
    useToken(e.detail.token);
}

window.addEventListener("api-platform-auth-update", onAuthUpdate);