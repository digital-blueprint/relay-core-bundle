import '@webcomponents/scoped-custom-element-registry';
import {html} from 'lit';
import {ScopedElementsMixin} from '@open-wc/scoped-elements';
import {AuthKeycloak} from '@dbp-toolkit/auth/src/auth-keycloak';
import {LoginButton} from '@dbp-toolkit/auth/src/login-button';
import * as commonUtils from '@dbp-toolkit/common/utils';
import {AdapterLitElement} from "@dbp-toolkit/common";

export class ApiPlatformAuth extends ScopedElementsMixin(AdapterLitElement) {
    constructor() {
        super();
        this.auth = {};
        this.lang = 'en';
        this.url = '';
        this.realm = '';
        this.clientId = '';
        this.silentCheckSsoRedirectUri = '';
        this.entryPointUrl = '';
    }

    static get scopedElements() {
        return {
          'dbp-auth-keycloak': AuthKeycloak,
          'dbp-login-button': LoginButton,
        };
    }

    static get properties() {
        return {
            ...super.properties,
            auth: { type: Object },
            lang: { type: String },
            url: { type: String },
            realm: { type: String },
            clientId: { type: String, attribute: 'client-id' },
            silentCheckSsoRedirectUri: { type: String, attribute: 'silent-check-sso-redirect-uri' },
            entryPointUrl: { type: String, attribute: 'entry-point-url' },
        };
    }

    update(changedProperties) {
        changedProperties.forEach((oldValue, propName) => {
            if (propName === "auth") {
                const event = new CustomEvent("api-platform-auth-update", { "detail": this.auth, bubbles: true, composed: true });
                window.dispatchEvent(event);
            }
        });

        super.update(changedProperties);
    }

    render() {
        return html`
            <dbp-auth-keycloak subscribe="requested-login-status"
                               lang="${this.lang}"
                               entry-point-url="${this.entryPointUrl}"
                               silent-check-sso-redirect-uri="${this.silentCheckSsoRedirectUri}"
                               url="${this.url}"
                               realm="${this.realm}"
                               client-id="${this.clientId}"
                               try-login></dbp-auth-keycloak>
            <dbp-login-button subscribe="auth" lang="${this.lang}"></dbp-login-button>
        `;
    }
}

commonUtils.defineCustomElement('api-platform-auth', ApiPlatformAuth);
