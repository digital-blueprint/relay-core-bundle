# ApiPlatform Auth Web Component

This component acts as login button for ApiPlatform and sends out an `api-platform-auth-update` event
to `windows` every time the auth information changes (e.g. when the token is updated).

## Example usage

```html
<script type="module" src="api-platform-auth.js"></script>
<api-platform-auth auth requested-login-status lang="de" entry-point-url="http://127.0.0.1:8000"
                   silent-check-sso-redirect-uri="/dist/silent-check-sso.html"
                   url="https://auth-dev.tugraz.at/auth" realm="tugraz"
                   client-id="auth-dev-mw-frontend-local"
></api-platform-auth>
```

## Local development

```bash
# install dependencies
npm install

# constantly build dist/bundle.js and run a local web-server on port 8002 
npm run watch

# build local packages in dist directory
npm run build
```

Jump to <http://localhost:8002> and you should get a page with a login button.
