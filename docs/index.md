# About

The core bundle is the central bundle that needs to be installed in every Relay API
gateway and also is a dependency of every other API bundle.

* It provides functionality that is commonly needed by API bundles (error handling,
  logging, etc)
* It integrates the auth bundle with the Symfony security system
* It provides console commands that API bundles can subscribe to
* It configures all dependencies to our needs (api-platform, symfony, etc.)
* and more ...

For more information on how to configure and interface with the core bundle see
the [Developer Guide](https://dbp-demo.tugraz.at/handbook/relay/dev/)
