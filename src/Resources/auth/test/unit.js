import {assert} from 'chai';

import '../src/api-platform-auth';

suite('api-platform-auth basics', () => {
  let node;

  setup(async () => {
    node = document.createElement('api-platform-auth');
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
    assert.isNotNull(node.shadowRoot);
  });
});
