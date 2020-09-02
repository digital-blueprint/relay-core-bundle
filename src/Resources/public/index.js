import './dbp-auth/dbp-auth.js';

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

    var element = document.createElement('dbp-auth-keycloak');
    element.setAttribute('lang', 'en');

    let config = window.keycloakConfig;
    element.setAttribute('url', config.url);
    element.setAttribute('realm', config.realm);
    element.setAttribute('client-id', config.clientId);
    element.setAttribute('silent-check-sso-redirect-uri', new URL("silent-check-sso.html", import.meta.url).href);
    element.setAttribute('entry-point-url', new URL('../..', import.meta.url).href);
    element.setAttribute('try-login', '');
    element.setAttribute('load-person', '');

    let button = document.createElement('dbp-login-button');
    button.setAttribute('lang', 'en');

    var section = target.children[0];
    section.insertBefore(element, section.children[0]);
    section.insertBefore(button, section.children[0]);
    window.clearInterval(delayInsertTimer);
    log('insertDBPContainer done');
}

function delayInsert() {
    delayInsertTimer = window.setInterval(insertDBPContainer, 10);
}

document.addEventListener('DOMContentLoaded', delayInsert);

function onAuthUpdate(e) {
    useToken(e.target.token);
}

window.addEventListener("dbp-auth-keycloak-data-update", onAuthUpdate);