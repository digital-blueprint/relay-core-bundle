import {assert} from 'chai';

import '../src/api-platform-auth';

/** @typedef {import('../src/api-platform-auth').ApiPlatformAuth} ApiPlatformAuth */

suite('api-platform-auth basics', () => {
  /** @type {ApiPlatformAuth} */
  let node;

  setup(async () => {
    node = /** @type {ApiPlatformAuth} */ (document.createElement('api-platform-auth'));
    node.setAttribute('url', 'someurl');
    node.setAttribute('realm', 'somerealm');
    node.setAttribute('client-id', 'someId');
    document.body.appendChild(node);
    await node.updateComplete;
  });

  teardown(() => {
    node.remove();
  });

  test('should render', () => {
    assert.isNotNull(node.renderRoot);
  });
});
