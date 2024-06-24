import './auth/api-platform-auth.js';

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
}

function delayInsert() {
    delayInsertTimer = window.setInterval(insertDBPContainer, 10);
}

document.addEventListener('DOMContentLoaded', delayInsert);

function onAuthUpdate(e) {
    window.swaggerUI.preauthorizeApiKey('apiKey', e.detail.token);
}

window.addEventListener("api-platform-auth-update", onAuthUpdate);