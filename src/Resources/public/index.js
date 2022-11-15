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

function getKeycloakServerUrl() {
    let config = window.oidcConfig;
    if (config.keycloakUrl.length) {
        // deprecated config value, remove once removed in the auth/oidc bundle
        return config.keycloakUrl;
    } else if (config.oidcServer.length) {
        let url = config.oidcServer;
        // XXX: extract the base url from the server url, hacky put works..
        // In the future we might want to use a non-keycloak specific component here,
        // and fetch .well-known/openid-configuration
        let match = url.match(/(?<base>.*)\/realms\/(?<realm>[^/]*)/);
        if (match !== null) {
            return match.groups.base;
        }
    }
    return '';
}

function getKeycloakRealm()
{
    let config = window.oidcConfig;
    if (config.keycloakRealm.length) {
        // deprecated config value, remove once removed in the auth/oidc bundle
        return config.keycloakRealm;
    } else if (config.oidcServer.length) {
        let url = config.oidcServer;
        // XXX: extract the realm from the server url, hacky put works..
        // In the future we might want to use a non-keycloak specific component here,
        // and fetch .well-known/openid-configuration
        let match = url.match(/(?<base>.*)\/realms\/(?<realm>[^/]*)/);
        if (match !== null) {
            return match.groups.realm;
        }
    }
    return '';
}

function getKeycloakClientId() {
    let config = window.oidcConfig;
    if (config.keycloakClientId.length) {
        // deprecated config value, remove once removed in the auth/oidc bundle
        return config.keycloakClientId;
    } else if (config.oidcFrontendClientId.length) {
        return config.oidcFrontendClientId;
    }
    return '';
}

function insertDBPContainer() {
    let target = document.getElementsByClassName('scheme-container')[0];
    if (target === undefined)
        return;

    // see ../auth/README.md
    var element = document.createElement('api-platform-auth');

    element.setAttribute('lang', 'en');
    element.setAttribute('url', getKeycloakServerUrl());
    element.setAttribute('realm', getKeycloakRealm());
    element.setAttribute('client-id', getKeycloakClientId());
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