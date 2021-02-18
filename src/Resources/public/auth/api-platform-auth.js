/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
/**
 * True if the custom elements polyfill is in use.
 */
const isCEPolyfill = typeof window !== 'undefined' &&
    window.customElements != null &&
    window.customElements.polyfillWrapFlushCallback !==
        undefined;
/**
 * Removes nodes, starting from `start` (inclusive) to `end` (exclusive), from
 * `container`.
 */
const removeNodes = (container, start, end = null) => {
    while (start !== end) {
        const n = start.nextSibling;
        container.removeChild(start);
        start = n;
    }
};

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
/**
 * An expression marker with embedded unique key to avoid collision with
 * possible text in templates.
 */
const marker = `{{lit-${String(Math.random()).slice(2)}}}`;
/**
 * An expression marker used text-positions, multi-binding attributes, and
 * attributes with markup-like text values.
 */
const nodeMarker = `<!--${marker}-->`;
const markerRegex = new RegExp(`${marker}|${nodeMarker}`);
/**
 * Suffix appended to all bound attribute names.
 */
const boundAttributeSuffix = '$lit$';
/**
 * An updatable Template that tracks the location of dynamic parts.
 */
class Template {
    constructor(result, element) {
        this.parts = [];
        this.element = element;
        const nodesToRemove = [];
        const stack = [];
        // Edge needs all 4 parameters present; IE11 needs 3rd parameter to be null
        const walker = document.createTreeWalker(element.content, 133 /* NodeFilter.SHOW_{ELEMENT|COMMENT|TEXT} */, null, false);
        // Keeps track of the last index associated with a part. We try to delete
        // unnecessary nodes, but we never want to associate two different parts
        // to the same index. They must have a constant node between.
        let lastPartIndex = 0;
        let index = -1;
        let partIndex = 0;
        const { strings, values: { length } } = result;
        while (partIndex < length) {
            const node = walker.nextNode();
            if (node === null) {
                // We've exhausted the content inside a nested template element.
                // Because we still have parts (the outer for-loop), we know:
                // - There is a template in the stack
                // - The walker will find a nextNode outside the template
                walker.currentNode = stack.pop();
                continue;
            }
            index++;
            if (node.nodeType === 1 /* Node.ELEMENT_NODE */) {
                if (node.hasAttributes()) {
                    const attributes = node.attributes;
                    const { length } = attributes;
                    // Per
                    // https://developer.mozilla.org/en-US/docs/Web/API/NamedNodeMap,
                    // attributes are not guaranteed to be returned in document order.
                    // In particular, Edge/IE can return them out of order, so we cannot
                    // assume a correspondence between part index and attribute index.
                    let count = 0;
                    for (let i = 0; i < length; i++) {
                        if (endsWith(attributes[i].name, boundAttributeSuffix)) {
                            count++;
                        }
                    }
                    while (count-- > 0) {
                        // Get the template literal section leading up to the first
                        // expression in this attribute
                        const stringForPart = strings[partIndex];
                        // Find the attribute name
                        const name = lastAttributeNameRegex.exec(stringForPart)[2];
                        // Find the corresponding attribute
                        // All bound attributes have had a suffix added in
                        // TemplateResult#getHTML to opt out of special attribute
                        // handling. To look up the attribute value we also need to add
                        // the suffix.
                        const attributeLookupName = name.toLowerCase() + boundAttributeSuffix;
                        const attributeValue = node.getAttribute(attributeLookupName);
                        node.removeAttribute(attributeLookupName);
                        const statics = attributeValue.split(markerRegex);
                        this.parts.push({ type: 'attribute', index, name, strings: statics });
                        partIndex += statics.length - 1;
                    }
                }
                if (node.tagName === 'TEMPLATE') {
                    stack.push(node);
                    walker.currentNode = node.content;
                }
            }
            else if (node.nodeType === 3 /* Node.TEXT_NODE */) {
                const data = node.data;
                if (data.indexOf(marker) >= 0) {
                    const parent = node.parentNode;
                    const strings = data.split(markerRegex);
                    const lastIndex = strings.length - 1;
                    // Generate a new text node for each literal section
                    // These nodes are also used as the markers for node parts
                    for (let i = 0; i < lastIndex; i++) {
                        let insert;
                        let s = strings[i];
                        if (s === '') {
                            insert = createMarker();
                        }
                        else {
                            const match = lastAttributeNameRegex.exec(s);
                            if (match !== null && endsWith(match[2], boundAttributeSuffix)) {
                                s = s.slice(0, match.index) + match[1] +
                                    match[2].slice(0, -boundAttributeSuffix.length) + match[3];
                            }
                            insert = document.createTextNode(s);
                        }
                        parent.insertBefore(insert, node);
                        this.parts.push({ type: 'node', index: ++index });
                    }
                    // If there's no text, we must insert a comment to mark our place.
                    // Else, we can trust it will stick around after cloning.
                    if (strings[lastIndex] === '') {
                        parent.insertBefore(createMarker(), node);
                        nodesToRemove.push(node);
                    }
                    else {
                        node.data = strings[lastIndex];
                    }
                    // We have a part for each match found
                    partIndex += lastIndex;
                }
            }
            else if (node.nodeType === 8 /* Node.COMMENT_NODE */) {
                if (node.data === marker) {
                    const parent = node.parentNode;
                    // Add a new marker node to be the startNode of the Part if any of
                    // the following are true:
                    //  * We don't have a previousSibling
                    //  * The previousSibling is already the start of a previous part
                    if (node.previousSibling === null || index === lastPartIndex) {
                        index++;
                        parent.insertBefore(createMarker(), node);
                    }
                    lastPartIndex = index;
                    this.parts.push({ type: 'node', index });
                    // If we don't have a nextSibling, keep this node so we have an end.
                    // Else, we can remove it to save future costs.
                    if (node.nextSibling === null) {
                        node.data = '';
                    }
                    else {
                        nodesToRemove.push(node);
                        index--;
                    }
                    partIndex++;
                }
                else {
                    let i = -1;
                    while ((i = node.data.indexOf(marker, i + 1)) !== -1) {
                        // Comment node has a binding marker inside, make an inactive part
                        // The binding won't work, but subsequent bindings will
                        // TODO (justinfagnani): consider whether it's even worth it to
                        // make bindings in comments work
                        this.parts.push({ type: 'node', index: -1 });
                        partIndex++;
                    }
                }
            }
        }
        // Remove text binding nodes after the walk to not disturb the TreeWalker
        for (const n of nodesToRemove) {
            n.parentNode.removeChild(n);
        }
    }
}
const endsWith = (str, suffix) => {
    const index = str.length - suffix.length;
    return index >= 0 && str.slice(index) === suffix;
};
const isTemplatePartActive = (part) => part.index !== -1;
// Allows `document.createComment('')` to be renamed for a
// small manual size-savings.
const createMarker = () => document.createComment('');
/**
 * This regex extracts the attribute name preceding an attribute-position
 * expression. It does this by matching the syntax allowed for attributes
 * against the string literal directly preceding the expression, assuming that
 * the expression is in an attribute-value position.
 *
 * See attributes in the HTML spec:
 * https://www.w3.org/TR/html5/syntax.html#elements-attributes
 *
 * " \x09\x0a\x0c\x0d" are HTML space characters:
 * https://www.w3.org/TR/html5/infrastructure.html#space-characters
 *
 * "\0-\x1F\x7F-\x9F" are Unicode control characters, which includes every
 * space character except " ".
 *
 * So an attribute is:
 *  * The name: any character except a control character, space character, ('),
 *    ("), ">", "=", or "/"
 *  * Followed by zero or more space characters
 *  * Followed by "="
 *  * Followed by zero or more space characters
 *  * Followed by:
 *    * Any character except space, ('), ("), "<", ">", "=", (`), or
 *    * (") then any non-("), or
 *    * (') then any non-(')
 */
const lastAttributeNameRegex = 
// eslint-disable-next-line no-control-regex
/([ \x09\x0a\x0c\x0d])([^\0-\x1F\x7F-\x9F "'>=/]+)([ \x09\x0a\x0c\x0d]*=[ \x09\x0a\x0c\x0d]*(?:[^ \x09\x0a\x0c\x0d"'`<>=]*|"[^"]*|'[^']*))$/;

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
const walkerNodeFilter = 133 /* NodeFilter.SHOW_{ELEMENT|COMMENT|TEXT} */;
/**
 * Removes the list of nodes from a Template safely. In addition to removing
 * nodes from the Template, the Template part indices are updated to match
 * the mutated Template DOM.
 *
 * As the template is walked the removal state is tracked and
 * part indices are adjusted as needed.
 *
 * div
 *   div#1 (remove) <-- start removing (removing node is div#1)
 *     div
 *       div#2 (remove)  <-- continue removing (removing node is still div#1)
 *         div
 * div <-- stop removing since previous sibling is the removing node (div#1,
 * removed 4 nodes)
 */
function removeNodesFromTemplate(template, nodesToRemove) {
    const { element: { content }, parts } = template;
    const walker = document.createTreeWalker(content, walkerNodeFilter, null, false);
    let partIndex = nextActiveIndexInTemplateParts(parts);
    let part = parts[partIndex];
    let nodeIndex = -1;
    let removeCount = 0;
    const nodesToRemoveInTemplate = [];
    let currentRemovingNode = null;
    while (walker.nextNode()) {
        nodeIndex++;
        const node = walker.currentNode;
        // End removal if stepped past the removing node
        if (node.previousSibling === currentRemovingNode) {
            currentRemovingNode = null;
        }
        // A node to remove was found in the template
        if (nodesToRemove.has(node)) {
            nodesToRemoveInTemplate.push(node);
            // Track node we're removing
            if (currentRemovingNode === null) {
                currentRemovingNode = node;
            }
        }
        // When removing, increment count by which to adjust subsequent part indices
        if (currentRemovingNode !== null) {
            removeCount++;
        }
        while (part !== undefined && part.index === nodeIndex) {
            // If part is in a removed node deactivate it by setting index to -1 or
            // adjust the index as needed.
            part.index = currentRemovingNode !== null ? -1 : part.index - removeCount;
            // go to the next active part.
            partIndex = nextActiveIndexInTemplateParts(parts, partIndex);
            part = parts[partIndex];
        }
    }
    nodesToRemoveInTemplate.forEach((n) => n.parentNode.removeChild(n));
}
const countNodes = (node) => {
    let count = (node.nodeType === 11 /* Node.DOCUMENT_FRAGMENT_NODE */) ? 0 : 1;
    const walker = document.createTreeWalker(node, walkerNodeFilter, null, false);
    while (walker.nextNode()) {
        count++;
    }
    return count;
};
const nextActiveIndexInTemplateParts = (parts, startIndex = -1) => {
    for (let i = startIndex + 1; i < parts.length; i++) {
        const part = parts[i];
        if (isTemplatePartActive(part)) {
            return i;
        }
    }
    return -1;
};
/**
 * Inserts the given node into the Template, optionally before the given
 * refNode. In addition to inserting the node into the Template, the Template
 * part indices are updated to match the mutated Template DOM.
 */
function insertNodeIntoTemplate(template, node, refNode = null) {
    const { element: { content }, parts } = template;
    // If there's no refNode, then put node at end of template.
    // No part indices need to be shifted in this case.
    if (refNode === null || refNode === undefined) {
        content.appendChild(node);
        return;
    }
    const walker = document.createTreeWalker(content, walkerNodeFilter, null, false);
    let partIndex = nextActiveIndexInTemplateParts(parts);
    let insertCount = 0;
    let walkerIndex = -1;
    while (walker.nextNode()) {
        walkerIndex++;
        const walkerNode = walker.currentNode;
        if (walkerNode === refNode) {
            insertCount = countNodes(node);
            refNode.parentNode.insertBefore(node, refNode);
        }
        while (partIndex !== -1 && parts[partIndex].index === walkerIndex) {
            // If we've inserted the node, simply adjust all subsequent parts
            if (insertCount > 0) {
                while (partIndex !== -1) {
                    parts[partIndex].index += insertCount;
                    partIndex = nextActiveIndexInTemplateParts(parts, partIndex);
                }
                return;
            }
            partIndex = nextActiveIndexInTemplateParts(parts, partIndex);
        }
    }
}

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
const directives = new WeakMap();
/**
 * Brands a function as a directive factory function so that lit-html will call
 * the function during template rendering, rather than passing as a value.
 *
 * A _directive_ is a function that takes a Part as an argument. It has the
 * signature: `(part: Part) => void`.
 *
 * A directive _factory_ is a function that takes arguments for data and
 * configuration and returns a directive. Users of directive usually refer to
 * the directive factory as the directive. For example, "The repeat directive".
 *
 * Usually a template author will invoke a directive factory in their template
 * with relevant arguments, which will then return a directive function.
 *
 * Here's an example of using the `repeat()` directive factory that takes an
 * array and a function to render an item:
 *
 * ```js
 * html`<ul><${repeat(items, (item) => html`<li>${item}</li>`)}</ul>`
 * ```
 *
 * When `repeat` is invoked, it returns a directive function that closes over
 * `items` and the template function. When the outer template is rendered, the
 * return directive function is called with the Part for the expression.
 * `repeat` then performs it's custom logic to render multiple items.
 *
 * @param f The directive factory function. Must be a function that returns a
 * function of the signature `(part: Part) => void`. The returned function will
 * be called with the part object.
 *
 * @example
 *
 * import {directive, html} from 'lit-html';
 *
 * const immutable = directive((v) => (part) => {
 *   if (part.value !== v) {
 *     part.setValue(v)
 *   }
 * });
 */
const directive = (f) => ((...args) => {
    const d = f(...args);
    directives.set(d, true);
    return d;
});
const isDirective = (o) => {
    return typeof o === 'function' && directives.has(o);
};

/**
 * @license
 * Copyright (c) 2018 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
/**
 * A sentinel value that signals that a value was handled by a directive and
 * should not be written to the DOM.
 */
const noChange = {};
/**
 * A sentinel value that signals a NodePart to fully clear its content.
 */
const nothing = {};

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
/**
 * An instance of a `Template` that can be attached to the DOM and updated
 * with new values.
 */
class TemplateInstance {
    constructor(template, processor, options) {
        this.__parts = [];
        this.template = template;
        this.processor = processor;
        this.options = options;
    }
    update(values) {
        let i = 0;
        for (const part of this.__parts) {
            if (part !== undefined) {
                part.setValue(values[i]);
            }
            i++;
        }
        for (const part of this.__parts) {
            if (part !== undefined) {
                part.commit();
            }
        }
    }
    _clone() {
        // There are a number of steps in the lifecycle of a template instance's
        // DOM fragment:
        //  1. Clone - create the instance fragment
        //  2. Adopt - adopt into the main document
        //  3. Process - find part markers and create parts
        //  4. Upgrade - upgrade custom elements
        //  5. Update - set node, attribute, property, etc., values
        //  6. Connect - connect to the document. Optional and outside of this
        //     method.
        //
        // We have a few constraints on the ordering of these steps:
        //  * We need to upgrade before updating, so that property values will pass
        //    through any property setters.
        //  * We would like to process before upgrading so that we're sure that the
        //    cloned fragment is inert and not disturbed by self-modifying DOM.
        //  * We want custom elements to upgrade even in disconnected fragments.
        //
        // Given these constraints, with full custom elements support we would
        // prefer the order: Clone, Process, Adopt, Upgrade, Update, Connect
        //
        // But Safari does not implement CustomElementRegistry#upgrade, so we
        // can not implement that order and still have upgrade-before-update and
        // upgrade disconnected fragments. So we instead sacrifice the
        // process-before-upgrade constraint, since in Custom Elements v1 elements
        // must not modify their light DOM in the constructor. We still have issues
        // when co-existing with CEv0 elements like Polymer 1, and with polyfills
        // that don't strictly adhere to the no-modification rule because shadow
        // DOM, which may be created in the constructor, is emulated by being placed
        // in the light DOM.
        //
        // The resulting order is on native is: Clone, Adopt, Upgrade, Process,
        // Update, Connect. document.importNode() performs Clone, Adopt, and Upgrade
        // in one step.
        //
        // The Custom Elements v1 polyfill supports upgrade(), so the order when
        // polyfilled is the more ideal: Clone, Process, Adopt, Upgrade, Update,
        // Connect.
        const fragment = isCEPolyfill ?
            this.template.element.content.cloneNode(true) :
            document.importNode(this.template.element.content, true);
        const stack = [];
        const parts = this.template.parts;
        // Edge needs all 4 parameters present; IE11 needs 3rd parameter to be null
        const walker = document.createTreeWalker(fragment, 133 /* NodeFilter.SHOW_{ELEMENT|COMMENT|TEXT} */, null, false);
        let partIndex = 0;
        let nodeIndex = 0;
        let part;
        let node = walker.nextNode();
        // Loop through all the nodes and parts of a template
        while (partIndex < parts.length) {
            part = parts[partIndex];
            if (!isTemplatePartActive(part)) {
                this.__parts.push(undefined);
                partIndex++;
                continue;
            }
            // Progress the tree walker until we find our next part's node.
            // Note that multiple parts may share the same node (attribute parts
            // on a single element), so this loop may not run at all.
            while (nodeIndex < part.index) {
                nodeIndex++;
                if (node.nodeName === 'TEMPLATE') {
                    stack.push(node);
                    walker.currentNode = node.content;
                }
                if ((node = walker.nextNode()) === null) {
                    // We've exhausted the content inside a nested template element.
                    // Because we still have parts (the outer for-loop), we know:
                    // - There is a template in the stack
                    // - The walker will find a nextNode outside the template
                    walker.currentNode = stack.pop();
                    node = walker.nextNode();
                }
            }
            // We've arrived at our part's node.
            if (part.type === 'node') {
                const part = this.processor.handleTextExpression(this.options);
                part.insertAfterNode(node.previousSibling);
                this.__parts.push(part);
            }
            else {
                this.__parts.push(...this.processor.handleAttributeExpressions(node, part.name, part.strings, this.options));
            }
            partIndex++;
        }
        if (isCEPolyfill) {
            document.adoptNode(fragment);
            customElements.upgrade(fragment);
        }
        return fragment;
    }
}

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
/**
 * Our TrustedTypePolicy for HTML which is declared using the html template
 * tag function.
 *
 * That HTML is a developer-authored constant, and is parsed with innerHTML
 * before any untrusted expressions have been mixed in. Therefor it is
 * considered safe by construction.
 */
const policy = window.trustedTypes &&
    trustedTypes.createPolicy('lit-html', { createHTML: (s) => s });
const commentMarker = ` ${marker} `;
/**
 * The return type of `html`, which holds a Template and the values from
 * interpolated expressions.
 */
class TemplateResult {
    constructor(strings, values, type, processor) {
        this.strings = strings;
        this.values = values;
        this.type = type;
        this.processor = processor;
    }
    /**
     * Returns a string of HTML used to create a `<template>` element.
     */
    getHTML() {
        const l = this.strings.length - 1;
        let html = '';
        let isCommentBinding = false;
        for (let i = 0; i < l; i++) {
            const s = this.strings[i];
            // For each binding we want to determine the kind of marker to insert
            // into the template source before it's parsed by the browser's HTML
            // parser. The marker type is based on whether the expression is in an
            // attribute, text, or comment position.
            //   * For node-position bindings we insert a comment with the marker
            //     sentinel as its text content, like <!--{{lit-guid}}-->.
            //   * For attribute bindings we insert just the marker sentinel for the
            //     first binding, so that we support unquoted attribute bindings.
            //     Subsequent bindings can use a comment marker because multi-binding
            //     attributes must be quoted.
            //   * For comment bindings we insert just the marker sentinel so we don't
            //     close the comment.
            //
            // The following code scans the template source, but is *not* an HTML
            // parser. We don't need to track the tree structure of the HTML, only
            // whether a binding is inside a comment, and if not, if it appears to be
            // the first binding in an attribute.
            const commentOpen = s.lastIndexOf('<!--');
            // We're in comment position if we have a comment open with no following
            // comment close. Because <-- can appear in an attribute value there can
            // be false positives.
            isCommentBinding = (commentOpen > -1 || isCommentBinding) &&
                s.indexOf('-->', commentOpen + 1) === -1;
            // Check to see if we have an attribute-like sequence preceding the
            // expression. This can match "name=value" like structures in text,
            // comments, and attribute values, so there can be false-positives.
            const attributeMatch = lastAttributeNameRegex.exec(s);
            if (attributeMatch === null) {
                // We're only in this branch if we don't have a attribute-like
                // preceding sequence. For comments, this guards against unusual
                // attribute values like <div foo="<!--${'bar'}">. Cases like
                // <!-- foo=${'bar'}--> are handled correctly in the attribute branch
                // below.
                html += s + (isCommentBinding ? commentMarker : nodeMarker);
            }
            else {
                // For attributes we use just a marker sentinel, and also append a
                // $lit$ suffix to the name to opt-out of attribute-specific parsing
                // that IE and Edge do for style and certain SVG attributes.
                html += s.substr(0, attributeMatch.index) + attributeMatch[1] +
                    attributeMatch[2] + boundAttributeSuffix + attributeMatch[3] +
                    marker;
            }
        }
        html += this.strings[l];
        return html;
    }
    getTemplateElement() {
        const template = document.createElement('template');
        let value = this.getHTML();
        if (policy !== undefined) {
            // this is secure because `this.strings` is a TemplateStringsArray.
            // TODO: validate this when
            // https://github.com/tc39/proposal-array-is-template-object is
            // implemented.
            value = policy.createHTML(value);
        }
        template.innerHTML = value;
        return template;
    }
}

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
const isPrimitive = (value) => {
    return (value === null ||
        !(typeof value === 'object' || typeof value === 'function'));
};
const isIterable = (value) => {
    return Array.isArray(value) ||
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        !!(value && value[Symbol.iterator]);
};
/**
 * Writes attribute values to the DOM for a group of AttributeParts bound to a
 * single attribute. The value is only set once even if there are multiple parts
 * for an attribute.
 */
class AttributeCommitter {
    constructor(element, name, strings) {
        this.dirty = true;
        this.element = element;
        this.name = name;
        this.strings = strings;
        this.parts = [];
        for (let i = 0; i < strings.length - 1; i++) {
            this.parts[i] = this._createPart();
        }
    }
    /**
     * Creates a single part. Override this to create a differnt type of part.
     */
    _createPart() {
        return new AttributePart(this);
    }
    _getValue() {
        const strings = this.strings;
        const l = strings.length - 1;
        const parts = this.parts;
        // If we're assigning an attribute via syntax like:
        //    attr="${foo}"  or  attr=${foo}
        // but not
        //    attr="${foo} ${bar}" or attr="${foo} baz"
        // then we don't want to coerce the attribute value into one long
        // string. Instead we want to just return the value itself directly,
        // so that sanitizeDOMValue can get the actual value rather than
        // String(value)
        // The exception is if v is an array, in which case we do want to smash
        // it together into a string without calling String() on the array.
        //
        // This also allows trusted values (when using TrustedTypes) being
        // assigned to DOM sinks without being stringified in the process.
        if (l === 1 && strings[0] === '' && strings[1] === '') {
            const v = parts[0].value;
            if (typeof v === 'symbol') {
                return String(v);
            }
            if (typeof v === 'string' || !isIterable(v)) {
                return v;
            }
        }
        let text = '';
        for (let i = 0; i < l; i++) {
            text += strings[i];
            const part = parts[i];
            if (part !== undefined) {
                const v = part.value;
                if (isPrimitive(v) || !isIterable(v)) {
                    text += typeof v === 'string' ? v : String(v);
                }
                else {
                    for (const t of v) {
                        text += typeof t === 'string' ? t : String(t);
                    }
                }
            }
        }
        text += strings[l];
        return text;
    }
    commit() {
        if (this.dirty) {
            this.dirty = false;
            this.element.setAttribute(this.name, this._getValue());
        }
    }
}
/**
 * A Part that controls all or part of an attribute value.
 */
class AttributePart {
    constructor(committer) {
        this.value = undefined;
        this.committer = committer;
    }
    setValue(value) {
        if (value !== noChange && (!isPrimitive(value) || value !== this.value)) {
            this.value = value;
            // If the value is a not a directive, dirty the committer so that it'll
            // call setAttribute. If the value is a directive, it'll dirty the
            // committer if it calls setValue().
            if (!isDirective(value)) {
                this.committer.dirty = true;
            }
        }
    }
    commit() {
        while (isDirective(this.value)) {
            const directive = this.value;
            this.value = noChange;
            directive(this);
        }
        if (this.value === noChange) {
            return;
        }
        this.committer.commit();
    }
}
/**
 * A Part that controls a location within a Node tree. Like a Range, NodePart
 * has start and end locations and can set and update the Nodes between those
 * locations.
 *
 * NodeParts support several value types: primitives, Nodes, TemplateResults,
 * as well as arrays and iterables of those types.
 */
class NodePart {
    constructor(options) {
        this.value = undefined;
        this.__pendingValue = undefined;
        this.options = options;
    }
    /**
     * Appends this part into a container.
     *
     * This part must be empty, as its contents are not automatically moved.
     */
    appendInto(container) {
        this.startNode = container.appendChild(createMarker());
        this.endNode = container.appendChild(createMarker());
    }
    /**
     * Inserts this part after the `ref` node (between `ref` and `ref`'s next
     * sibling). Both `ref` and its next sibling must be static, unchanging nodes
     * such as those that appear in a literal section of a template.
     *
     * This part must be empty, as its contents are not automatically moved.
     */
    insertAfterNode(ref) {
        this.startNode = ref;
        this.endNode = ref.nextSibling;
    }
    /**
     * Appends this part into a parent part.
     *
     * This part must be empty, as its contents are not automatically moved.
     */
    appendIntoPart(part) {
        part.__insert(this.startNode = createMarker());
        part.__insert(this.endNode = createMarker());
    }
    /**
     * Inserts this part after the `ref` part.
     *
     * This part must be empty, as its contents are not automatically moved.
     */
    insertAfterPart(ref) {
        ref.__insert(this.startNode = createMarker());
        this.endNode = ref.endNode;
        ref.endNode = this.startNode;
    }
    setValue(value) {
        this.__pendingValue = value;
    }
    commit() {
        if (this.startNode.parentNode === null) {
            return;
        }
        while (isDirective(this.__pendingValue)) {
            const directive = this.__pendingValue;
            this.__pendingValue = noChange;
            directive(this);
        }
        const value = this.__pendingValue;
        if (value === noChange) {
            return;
        }
        if (isPrimitive(value)) {
            if (value !== this.value) {
                this.__commitText(value);
            }
        }
        else if (value instanceof TemplateResult) {
            this.__commitTemplateResult(value);
        }
        else if (value instanceof Node) {
            this.__commitNode(value);
        }
        else if (isIterable(value)) {
            this.__commitIterable(value);
        }
        else if (value === nothing) {
            this.value = nothing;
            this.clear();
        }
        else {
            // Fallback, will render the string representation
            this.__commitText(value);
        }
    }
    __insert(node) {
        this.endNode.parentNode.insertBefore(node, this.endNode);
    }
    __commitNode(value) {
        if (this.value === value) {
            return;
        }
        this.clear();
        this.__insert(value);
        this.value = value;
    }
    __commitText(value) {
        const node = this.startNode.nextSibling;
        value = value == null ? '' : value;
        // If `value` isn't already a string, we explicitly convert it here in case
        // it can't be implicitly converted - i.e. it's a symbol.
        const valueAsString = typeof value === 'string' ? value : String(value);
        if (node === this.endNode.previousSibling &&
            node.nodeType === 3 /* Node.TEXT_NODE */) {
            // If we only have a single text node between the markers, we can just
            // set its value, rather than replacing it.
            // TODO(justinfagnani): Can we just check if this.value is primitive?
            node.data = valueAsString;
        }
        else {
            this.__commitNode(document.createTextNode(valueAsString));
        }
        this.value = value;
    }
    __commitTemplateResult(value) {
        const template = this.options.templateFactory(value);
        if (this.value instanceof TemplateInstance &&
            this.value.template === template) {
            this.value.update(value.values);
        }
        else {
            // Make sure we propagate the template processor from the TemplateResult
            // so that we use its syntax extension, etc. The template factory comes
            // from the render function options so that it can control template
            // caching and preprocessing.
            const instance = new TemplateInstance(template, value.processor, this.options);
            const fragment = instance._clone();
            instance.update(value.values);
            this.__commitNode(fragment);
            this.value = instance;
        }
    }
    __commitIterable(value) {
        // For an Iterable, we create a new InstancePart per item, then set its
        // value to the item. This is a little bit of overhead for every item in
        // an Iterable, but it lets us recurse easily and efficiently update Arrays
        // of TemplateResults that will be commonly returned from expressions like:
        // array.map((i) => html`${i}`), by reusing existing TemplateInstances.
        // If _value is an array, then the previous render was of an
        // iterable and _value will contain the NodeParts from the previous
        // render. If _value is not an array, clear this part and make a new
        // array for NodeParts.
        if (!Array.isArray(this.value)) {
            this.value = [];
            this.clear();
        }
        // Lets us keep track of how many items we stamped so we can clear leftover
        // items from a previous render
        const itemParts = this.value;
        let partIndex = 0;
        let itemPart;
        for (const item of value) {
            // Try to reuse an existing part
            itemPart = itemParts[partIndex];
            // If no existing part, create a new one
            if (itemPart === undefined) {
                itemPart = new NodePart(this.options);
                itemParts.push(itemPart);
                if (partIndex === 0) {
                    itemPart.appendIntoPart(this);
                }
                else {
                    itemPart.insertAfterPart(itemParts[partIndex - 1]);
                }
            }
            itemPart.setValue(item);
            itemPart.commit();
            partIndex++;
        }
        if (partIndex < itemParts.length) {
            // Truncate the parts array so _value reflects the current state
            itemParts.length = partIndex;
            this.clear(itemPart && itemPart.endNode);
        }
    }
    clear(startNode = this.startNode) {
        removeNodes(this.startNode.parentNode, startNode.nextSibling, this.endNode);
    }
}
/**
 * Implements a boolean attribute, roughly as defined in the HTML
 * specification.
 *
 * If the value is truthy, then the attribute is present with a value of
 * ''. If the value is falsey, the attribute is removed.
 */
class BooleanAttributePart {
    constructor(element, name, strings) {
        this.value = undefined;
        this.__pendingValue = undefined;
        if (strings.length !== 2 || strings[0] !== '' || strings[1] !== '') {
            throw new Error('Boolean attributes can only contain a single expression');
        }
        this.element = element;
        this.name = name;
        this.strings = strings;
    }
    setValue(value) {
        this.__pendingValue = value;
    }
    commit() {
        while (isDirective(this.__pendingValue)) {
            const directive = this.__pendingValue;
            this.__pendingValue = noChange;
            directive(this);
        }
        if (this.__pendingValue === noChange) {
            return;
        }
        const value = !!this.__pendingValue;
        if (this.value !== value) {
            if (value) {
                this.element.setAttribute(this.name, '');
            }
            else {
                this.element.removeAttribute(this.name);
            }
            this.value = value;
        }
        this.__pendingValue = noChange;
    }
}
/**
 * Sets attribute values for PropertyParts, so that the value is only set once
 * even if there are multiple parts for a property.
 *
 * If an expression controls the whole property value, then the value is simply
 * assigned to the property under control. If there are string literals or
 * multiple expressions, then the strings are expressions are interpolated into
 * a string first.
 */
class PropertyCommitter extends AttributeCommitter {
    constructor(element, name, strings) {
        super(element, name, strings);
        this.single =
            (strings.length === 2 && strings[0] === '' && strings[1] === '');
    }
    _createPart() {
        return new PropertyPart(this);
    }
    _getValue() {
        if (this.single) {
            return this.parts[0].value;
        }
        return super._getValue();
    }
    commit() {
        if (this.dirty) {
            this.dirty = false;
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            this.element[this.name] = this._getValue();
        }
    }
}
class PropertyPart extends AttributePart {
}
// Detect event listener options support. If the `capture` property is read
// from the options object, then options are supported. If not, then the third
// argument to add/removeEventListener is interpreted as the boolean capture
// value so we should only pass the `capture` property.
let eventOptionsSupported = false;
// Wrap into an IIFE because MS Edge <= v41 does not support having try/catch
// blocks right into the body of a module
(() => {
    try {
        const options = {
            get capture() {
                eventOptionsSupported = true;
                return false;
            }
        };
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        window.addEventListener('test', options, options);
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        window.removeEventListener('test', options, options);
    }
    catch (_e) {
        // event options not supported
    }
})();
class EventPart {
    constructor(element, eventName, eventContext) {
        this.value = undefined;
        this.__pendingValue = undefined;
        this.element = element;
        this.eventName = eventName;
        this.eventContext = eventContext;
        this.__boundHandleEvent = (e) => this.handleEvent(e);
    }
    setValue(value) {
        this.__pendingValue = value;
    }
    commit() {
        while (isDirective(this.__pendingValue)) {
            const directive = this.__pendingValue;
            this.__pendingValue = noChange;
            directive(this);
        }
        if (this.__pendingValue === noChange) {
            return;
        }
        const newListener = this.__pendingValue;
        const oldListener = this.value;
        const shouldRemoveListener = newListener == null ||
            oldListener != null &&
                (newListener.capture !== oldListener.capture ||
                    newListener.once !== oldListener.once ||
                    newListener.passive !== oldListener.passive);
        const shouldAddListener = newListener != null && (oldListener == null || shouldRemoveListener);
        if (shouldRemoveListener) {
            this.element.removeEventListener(this.eventName, this.__boundHandleEvent, this.__options);
        }
        if (shouldAddListener) {
            this.__options = getOptions(newListener);
            this.element.addEventListener(this.eventName, this.__boundHandleEvent, this.__options);
        }
        this.value = newListener;
        this.__pendingValue = noChange;
    }
    handleEvent(event) {
        if (typeof this.value === 'function') {
            this.value.call(this.eventContext || this.element, event);
        }
        else {
            this.value.handleEvent(event);
        }
    }
}
// We copy options because of the inconsistent behavior of browsers when reading
// the third argument of add/removeEventListener. IE11 doesn't support options
// at all. Chrome 41 only reads `capture` if the argument is an object.
const getOptions = (o) => o &&
    (eventOptionsSupported ?
        { capture: o.capture, passive: o.passive, once: o.once } :
        o.capture);

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
/**
 * The default TemplateFactory which caches Templates keyed on
 * result.type and result.strings.
 */
function templateFactory(result) {
    let templateCache = templateCaches.get(result.type);
    if (templateCache === undefined) {
        templateCache = {
            stringsArray: new WeakMap(),
            keyString: new Map()
        };
        templateCaches.set(result.type, templateCache);
    }
    let template = templateCache.stringsArray.get(result.strings);
    if (template !== undefined) {
        return template;
    }
    // If the TemplateStringsArray is new, generate a key from the strings
    // This key is shared between all templates with identical content
    const key = result.strings.join(marker);
    // Check if we already have a Template for this key
    template = templateCache.keyString.get(key);
    if (template === undefined) {
        // If we have not seen this key before, create a new Template
        template = new Template(result, result.getTemplateElement());
        // Cache the Template for this key
        templateCache.keyString.set(key, template);
    }
    // Cache all future queries for this TemplateStringsArray
    templateCache.stringsArray.set(result.strings, template);
    return template;
}
const templateCaches = new Map();

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
const parts = new WeakMap();
/**
 * Renders a template result or other value to a container.
 *
 * To update a container with new values, reevaluate the template literal and
 * call `render` with the new result.
 *
 * @param result Any value renderable by NodePart - typically a TemplateResult
 *     created by evaluating a template tag like `html` or `svg`.
 * @param container A DOM parent to render to. The entire contents are either
 *     replaced, or efficiently updated if the same result type was previous
 *     rendered there.
 * @param options RenderOptions for the entire render tree rendered to this
 *     container. Render options must *not* change between renders to the same
 *     container, as those changes will not effect previously rendered DOM.
 */
const render = (result, container, options) => {
    let part = parts.get(container);
    if (part === undefined) {
        removeNodes(container, container.firstChild);
        parts.set(container, part = new NodePart(Object.assign({ templateFactory }, options)));
        part.appendInto(container);
    }
    part.setValue(result);
    part.commit();
};

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
/**
 * Creates Parts when a template is instantiated.
 */
class DefaultTemplateProcessor {
    /**
     * Create parts for an attribute-position binding, given the event, attribute
     * name, and string literals.
     *
     * @param element The element containing the binding
     * @param name  The attribute name
     * @param strings The string literals. There are always at least two strings,
     *   event for fully-controlled bindings with a single expression.
     */
    handleAttributeExpressions(element, name, strings, options) {
        const prefix = name[0];
        if (prefix === '.') {
            const committer = new PropertyCommitter(element, name.slice(1), strings);
            return committer.parts;
        }
        if (prefix === '@') {
            return [new EventPart(element, name.slice(1), options.eventContext)];
        }
        if (prefix === '?') {
            return [new BooleanAttributePart(element, name.slice(1), strings)];
        }
        const committer = new AttributeCommitter(element, name, strings);
        return committer.parts;
    }
    /**
     * Create parts for a text-position binding.
     * @param templateFactory
     */
    handleTextExpression(options) {
        return new NodePart(options);
    }
}
const defaultTemplateProcessor = new DefaultTemplateProcessor();

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
// IMPORTANT: do not change the property name or the assignment expression.
// This line will be used in regexes to search for lit-html usage.
// TODO(justinfagnani): inject version number at build time
if (typeof window !== 'undefined') {
    (window['litHtmlVersions'] || (window['litHtmlVersions'] = [])).push('1.3.0');
}
/**
 * Interprets a template literal as an HTML template that can efficiently
 * render to and update a container.
 */
const html = (strings, ...values) => new TemplateResult(strings, values, 'html', defaultTemplateProcessor);

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
// Get a key to lookup in `templateCaches`.
const getTemplateCacheKey = (type, scopeName) => `${type}--${scopeName}`;
let compatibleShadyCSSVersion = true;
if (typeof window.ShadyCSS === 'undefined') {
    compatibleShadyCSSVersion = false;
}
else if (typeof window.ShadyCSS.prepareTemplateDom === 'undefined') {
    console.warn(`Incompatible ShadyCSS version detected. ` +
        `Please update to at least @webcomponents/webcomponentsjs@2.0.2 and ` +
        `@webcomponents/shadycss@1.3.1.`);
    compatibleShadyCSSVersion = false;
}
/**
 * Template factory which scopes template DOM using ShadyCSS.
 * @param scopeName {string}
 */
const shadyTemplateFactory = (scopeName) => (result) => {
    const cacheKey = getTemplateCacheKey(result.type, scopeName);
    let templateCache = templateCaches.get(cacheKey);
    if (templateCache === undefined) {
        templateCache = {
            stringsArray: new WeakMap(),
            keyString: new Map()
        };
        templateCaches.set(cacheKey, templateCache);
    }
    let template = templateCache.stringsArray.get(result.strings);
    if (template !== undefined) {
        return template;
    }
    const key = result.strings.join(marker);
    template = templateCache.keyString.get(key);
    if (template === undefined) {
        const element = result.getTemplateElement();
        if (compatibleShadyCSSVersion) {
            window.ShadyCSS.prepareTemplateDom(element, scopeName);
        }
        template = new Template(result, element);
        templateCache.keyString.set(key, template);
    }
    templateCache.stringsArray.set(result.strings, template);
    return template;
};
const TEMPLATE_TYPES = ['html', 'svg'];
/**
 * Removes all style elements from Templates for the given scopeName.
 */
const removeStylesFromLitTemplates = (scopeName) => {
    TEMPLATE_TYPES.forEach((type) => {
        const templates = templateCaches.get(getTemplateCacheKey(type, scopeName));
        if (templates !== undefined) {
            templates.keyString.forEach((template) => {
                const { element: { content } } = template;
                // IE 11 doesn't support the iterable param Set constructor
                const styles = new Set();
                Array.from(content.querySelectorAll('style')).forEach((s) => {
                    styles.add(s);
                });
                removeNodesFromTemplate(template, styles);
            });
        }
    });
};
const shadyRenderSet = new Set();
/**
 * For the given scope name, ensures that ShadyCSS style scoping is performed.
 * This is done just once per scope name so the fragment and template cannot
 * be modified.
 * (1) extracts styles from the rendered fragment and hands them to ShadyCSS
 * to be scoped and appended to the document
 * (2) removes style elements from all lit-html Templates for this scope name.
 *
 * Note, <style> elements can only be placed into templates for the
 * initial rendering of the scope. If <style> elements are included in templates
 * dynamically rendered to the scope (after the first scope render), they will
 * not be scoped and the <style> will be left in the template and rendered
 * output.
 */
const prepareTemplateStyles = (scopeName, renderedDOM, template) => {
    shadyRenderSet.add(scopeName);
    // If `renderedDOM` is stamped from a Template, then we need to edit that
    // Template's underlying template element. Otherwise, we create one here
    // to give to ShadyCSS, which still requires one while scoping.
    const templateElement = !!template ? template.element : document.createElement('template');
    // Move styles out of rendered DOM and store.
    const styles = renderedDOM.querySelectorAll('style');
    const { length } = styles;
    // If there are no styles, skip unnecessary work
    if (length === 0) {
        // Ensure prepareTemplateStyles is called to support adding
        // styles via `prepareAdoptedCssText` since that requires that
        // `prepareTemplateStyles` is called.
        //
        // ShadyCSS will only update styles containing @apply in the template
        // given to `prepareTemplateStyles`. If no lit Template was given,
        // ShadyCSS will not be able to update uses of @apply in any relevant
        // template. However, this is not a problem because we only create the
        // template for the purpose of supporting `prepareAdoptedCssText`,
        // which doesn't support @apply at all.
        window.ShadyCSS.prepareTemplateStyles(templateElement, scopeName);
        return;
    }
    const condensedStyle = document.createElement('style');
    // Collect styles into a single style. This helps us make sure ShadyCSS
    // manipulations will not prevent us from being able to fix up template
    // part indices.
    // NOTE: collecting styles is inefficient for browsers but ShadyCSS
    // currently does this anyway. When it does not, this should be changed.
    for (let i = 0; i < length; i++) {
        const style = styles[i];
        style.parentNode.removeChild(style);
        condensedStyle.textContent += style.textContent;
    }
    // Remove styles from nested templates in this scope.
    removeStylesFromLitTemplates(scopeName);
    // And then put the condensed style into the "root" template passed in as
    // `template`.
    const content = templateElement.content;
    if (!!template) {
        insertNodeIntoTemplate(template, condensedStyle, content.firstChild);
    }
    else {
        content.insertBefore(condensedStyle, content.firstChild);
    }
    // Note, it's important that ShadyCSS gets the template that `lit-html`
    // will actually render so that it can update the style inside when
    // needed (e.g. @apply native Shadow DOM case).
    window.ShadyCSS.prepareTemplateStyles(templateElement, scopeName);
    const style = content.querySelector('style');
    if (window.ShadyCSS.nativeShadow && style !== null) {
        // When in native Shadow DOM, ensure the style created by ShadyCSS is
        // included in initially rendered output (`renderedDOM`).
        renderedDOM.insertBefore(style.cloneNode(true), renderedDOM.firstChild);
    }
    else if (!!template) {
        // When no style is left in the template, parts will be broken as a
        // result. To fix this, we put back the style node ShadyCSS removed
        // and then tell lit to remove that node from the template.
        // There can be no style in the template in 2 cases (1) when Shady DOM
        // is in use, ShadyCSS removes all styles, (2) when native Shadow DOM
        // is in use ShadyCSS removes the style if it contains no content.
        // NOTE, ShadyCSS creates its own style so we can safely add/remove
        // `condensedStyle` here.
        content.insertBefore(condensedStyle, content.firstChild);
        const removes = new Set();
        removes.add(condensedStyle);
        removeNodesFromTemplate(template, removes);
    }
};
/**
 * Extension to the standard `render` method which supports rendering
 * to ShadowRoots when the ShadyDOM (https://github.com/webcomponents/shadydom)
 * and ShadyCSS (https://github.com/webcomponents/shadycss) polyfills are used
 * or when the webcomponentsjs
 * (https://github.com/webcomponents/webcomponentsjs) polyfill is used.
 *
 * Adds a `scopeName` option which is used to scope element DOM and stylesheets
 * when native ShadowDOM is unavailable. The `scopeName` will be added to
 * the class attribute of all rendered DOM. In addition, any style elements will
 * be automatically re-written with this `scopeName` selector and moved out
 * of the rendered DOM and into the document `<head>`.
 *
 * It is common to use this render method in conjunction with a custom element
 * which renders a shadowRoot. When this is done, typically the element's
 * `localName` should be used as the `scopeName`.
 *
 * In addition to DOM scoping, ShadyCSS also supports a basic shim for css
 * custom properties (needed only on older browsers like IE11) and a shim for
 * a deprecated feature called `@apply` that supports applying a set of css
 * custom properties to a given location.
 *
 * Usage considerations:
 *
 * * Part values in `<style>` elements are only applied the first time a given
 * `scopeName` renders. Subsequent changes to parts in style elements will have
 * no effect. Because of this, parts in style elements should only be used for
 * values that will never change, for example parts that set scope-wide theme
 * values or parts which render shared style elements.
 *
 * * Note, due to a limitation of the ShadyDOM polyfill, rendering in a
 * custom element's `constructor` is not supported. Instead rendering should
 * either done asynchronously, for example at microtask timing (for example
 * `Promise.resolve()`), or be deferred until the first time the element's
 * `connectedCallback` runs.
 *
 * Usage considerations when using shimmed custom properties or `@apply`:
 *
 * * Whenever any dynamic changes are made which affect
 * css custom properties, `ShadyCSS.styleElement(element)` must be called
 * to update the element. There are two cases when this is needed:
 * (1) the element is connected to a new parent, (2) a class is added to the
 * element that causes it to match different custom properties.
 * To address the first case when rendering a custom element, `styleElement`
 * should be called in the element's `connectedCallback`.
 *
 * * Shimmed custom properties may only be defined either for an entire
 * shadowRoot (for example, in a `:host` rule) or via a rule that directly
 * matches an element with a shadowRoot. In other words, instead of flowing from
 * parent to child as do native css custom properties, shimmed custom properties
 * flow only from shadowRoots to nested shadowRoots.
 *
 * * When using `@apply` mixing css shorthand property names with
 * non-shorthand names (for example `border` and `border-width`) is not
 * supported.
 */
const render$1 = (result, container, options) => {
    if (!options || typeof options !== 'object' || !options.scopeName) {
        throw new Error('The `scopeName` option is required.');
    }
    const scopeName = options.scopeName;
    const hasRendered = parts.has(container);
    const needsScoping = compatibleShadyCSSVersion &&
        container.nodeType === 11 /* Node.DOCUMENT_FRAGMENT_NODE */ &&
        !!container.host;
    // Handle first render to a scope specially...
    const firstScopeRender = needsScoping && !shadyRenderSet.has(scopeName);
    // On first scope render, render into a fragment; this cannot be a single
    // fragment that is reused since nested renders can occur synchronously.
    const renderContainer = firstScopeRender ? document.createDocumentFragment() : container;
    render(result, renderContainer, Object.assign({ templateFactory: shadyTemplateFactory(scopeName) }, options));
    // When performing first scope render,
    // (1) We've rendered into a fragment so that there's a chance to
    // `prepareTemplateStyles` before sub-elements hit the DOM
    // (which might cause them to render based on a common pattern of
    // rendering in a custom element's `connectedCallback`);
    // (2) Scope the template with ShadyCSS one time only for this scope.
    // (3) Render the fragment into the container and make sure the
    // container knows its `part` is the one we just rendered. This ensures
    // DOM will be re-used on subsequent renders.
    if (firstScopeRender) {
        const part = parts.get(renderContainer);
        parts.delete(renderContainer);
        // ShadyCSS might have style sheets (e.g. from `prepareAdoptedCssText`)
        // that should apply to `renderContainer` even if the rendered value is
        // not a TemplateInstance. However, it will only insert scoped styles
        // into the document if `prepareTemplateStyles` has already been called
        // for the given scope name.
        const template = part.value instanceof TemplateInstance ?
            part.value.template :
            undefined;
        prepareTemplateStyles(scopeName, renderContainer, template);
        removeNodes(container, container.firstChild);
        container.appendChild(renderContainer);
        parts.set(container, part);
    }
    // After elements have hit the DOM, update styling if this is the
    // initial render to this container.
    // This is needed whenever dynamic changes are made so it would be
    // safest to do every render; however, this would regress performance
    // so we leave it up to the user to call `ShadyCSS.styleElement`
    // for dynamic changes.
    if (!hasRendered && needsScoping) {
        window.ShadyCSS.styleElement(container.host);
    }
};

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
var _a;
/**
 * Use this module if you want to create your own base class extending
 * [[UpdatingElement]].
 * @packageDocumentation
 */
/*
 * When using Closure Compiler, JSCompiler_renameProperty(property, object) is
 * replaced at compile time by the munged name for object[property]. We cannot
 * alias this function, so we have to use a small shim that has the same
 * behavior when not compiling.
 */
window.JSCompiler_renameProperty =
    (prop, _obj) => prop;
const defaultConverter = {
    toAttribute(value, type) {
        switch (type) {
            case Boolean:
                return value ? '' : null;
            case Object:
            case Array:
                // if the value is `null` or `undefined` pass this through
                // to allow removing/no change behavior.
                return value == null ? value : JSON.stringify(value);
        }
        return value;
    },
    fromAttribute(value, type) {
        switch (type) {
            case Boolean:
                return value !== null;
            case Number:
                return value === null ? null : Number(value);
            case Object:
            case Array:
                return JSON.parse(value);
        }
        return value;
    }
};
/**
 * Change function that returns true if `value` is different from `oldValue`.
 * This method is used as the default for a property's `hasChanged` function.
 */
const notEqual = (value, old) => {
    // This ensures (old==NaN, value==NaN) always returns false
    return old !== value && (old === old || value === value);
};
const defaultPropertyDeclaration = {
    attribute: true,
    type: String,
    converter: defaultConverter,
    reflect: false,
    hasChanged: notEqual
};
const STATE_HAS_UPDATED = 1;
const STATE_UPDATE_REQUESTED = 1 << 2;
const STATE_IS_REFLECTING_TO_ATTRIBUTE = 1 << 3;
const STATE_IS_REFLECTING_TO_PROPERTY = 1 << 4;
/**
 * The Closure JS Compiler doesn't currently have good support for static
 * property semantics where "this" is dynamic (e.g.
 * https://github.com/google/closure-compiler/issues/3177 and others) so we use
 * this hack to bypass any rewriting by the compiler.
 */
const finalized = 'finalized';
/**
 * Base element class which manages element properties and attributes. When
 * properties change, the `update` method is asynchronously called. This method
 * should be supplied by subclassers to render updates as desired.
 * @noInheritDoc
 */
class UpdatingElement extends HTMLElement {
    constructor() {
        super();
        this.initialize();
    }
    /**
     * Returns a list of attributes corresponding to the registered properties.
     * @nocollapse
     */
    static get observedAttributes() {
        // note: piggy backing on this to ensure we're finalized.
        this.finalize();
        const attributes = [];
        // Use forEach so this works even if for/of loops are compiled to for loops
        // expecting arrays
        this._classProperties.forEach((v, p) => {
            const attr = this._attributeNameForProperty(p, v);
            if (attr !== undefined) {
                this._attributeToPropertyMap.set(attr, p);
                attributes.push(attr);
            }
        });
        return attributes;
    }
    /**
     * Ensures the private `_classProperties` property metadata is created.
     * In addition to `finalize` this is also called in `createProperty` to
     * ensure the `@property` decorator can add property metadata.
     */
    /** @nocollapse */
    static _ensureClassProperties() {
        // ensure private storage for property declarations.
        if (!this.hasOwnProperty(JSCompiler_renameProperty('_classProperties', this))) {
            this._classProperties = new Map();
            // NOTE: Workaround IE11 not supporting Map constructor argument.
            const superProperties = Object.getPrototypeOf(this)._classProperties;
            if (superProperties !== undefined) {
                superProperties.forEach((v, k) => this._classProperties.set(k, v));
            }
        }
    }
    /**
     * Creates a property accessor on the element prototype if one does not exist
     * and stores a PropertyDeclaration for the property with the given options.
     * The property setter calls the property's `hasChanged` property option
     * or uses a strict identity check to determine whether or not to request
     * an update.
     *
     * This method may be overridden to customize properties; however,
     * when doing so, it's important to call `super.createProperty` to ensure
     * the property is setup correctly. This method calls
     * `getPropertyDescriptor` internally to get a descriptor to install.
     * To customize what properties do when they are get or set, override
     * `getPropertyDescriptor`. To customize the options for a property,
     * implement `createProperty` like this:
     *
     * static createProperty(name, options) {
     *   options = Object.assign(options, {myOption: true});
     *   super.createProperty(name, options);
     * }
     *
     * @nocollapse
     */
    static createProperty(name, options = defaultPropertyDeclaration) {
        // Note, since this can be called by the `@property` decorator which
        // is called before `finalize`, we ensure storage exists for property
        // metadata.
        this._ensureClassProperties();
        this._classProperties.set(name, options);
        // Do not generate an accessor if the prototype already has one, since
        // it would be lost otherwise and that would never be the user's intention;
        // Instead, we expect users to call `requestUpdate` themselves from
        // user-defined accessors. Note that if the super has an accessor we will
        // still overwrite it
        if (options.noAccessor || this.prototype.hasOwnProperty(name)) {
            return;
        }
        const key = typeof name === 'symbol' ? Symbol() : `__${name}`;
        const descriptor = this.getPropertyDescriptor(name, key, options);
        if (descriptor !== undefined) {
            Object.defineProperty(this.prototype, name, descriptor);
        }
    }
    /**
     * Returns a property descriptor to be defined on the given named property.
     * If no descriptor is returned, the property will not become an accessor.
     * For example,
     *
     *   class MyElement extends LitElement {
     *     static getPropertyDescriptor(name, key, options) {
     *       const defaultDescriptor =
     *           super.getPropertyDescriptor(name, key, options);
     *       const setter = defaultDescriptor.set;
     *       return {
     *         get: defaultDescriptor.get,
     *         set(value) {
     *           setter.call(this, value);
     *           // custom action.
     *         },
     *         configurable: true,
     *         enumerable: true
     *       }
     *     }
     *   }
     *
     * @nocollapse
     */
    static getPropertyDescriptor(name, key, options) {
        return {
            // tslint:disable-next-line:no-any no symbol in index
            get() {
                return this[key];
            },
            set(value) {
                const oldValue = this[name];
                this[key] = value;
                this
                    .requestUpdateInternal(name, oldValue, options);
            },
            configurable: true,
            enumerable: true
        };
    }
    /**
     * Returns the property options associated with the given property.
     * These options are defined with a PropertyDeclaration via the `properties`
     * object or the `@property` decorator and are registered in
     * `createProperty(...)`.
     *
     * Note, this method should be considered "final" and not overridden. To
     * customize the options for a given property, override `createProperty`.
     *
     * @nocollapse
     * @final
     */
    static getPropertyOptions(name) {
        return this._classProperties && this._classProperties.get(name) ||
            defaultPropertyDeclaration;
    }
    /**
     * Creates property accessors for registered properties and ensures
     * any superclasses are also finalized.
     * @nocollapse
     */
    static finalize() {
        // finalize any superclasses
        const superCtor = Object.getPrototypeOf(this);
        if (!superCtor.hasOwnProperty(finalized)) {
            superCtor.finalize();
        }
        this[finalized] = true;
        this._ensureClassProperties();
        // initialize Map populated in observedAttributes
        this._attributeToPropertyMap = new Map();
        // make any properties
        // Note, only process "own" properties since this element will inherit
        // any properties defined on the superClass, and finalization ensures
        // the entire prototype chain is finalized.
        if (this.hasOwnProperty(JSCompiler_renameProperty('properties', this))) {
            const props = this.properties;
            // support symbols in properties (IE11 does not support this)
            const propKeys = [
                ...Object.getOwnPropertyNames(props),
                ...(typeof Object.getOwnPropertySymbols === 'function') ?
                    Object.getOwnPropertySymbols(props) :
                    []
            ];
            // This for/of is ok because propKeys is an array
            for (const p of propKeys) {
                // note, use of `any` is due to TypeSript lack of support for symbol in
                // index types
                // tslint:disable-next-line:no-any no symbol in index
                this.createProperty(p, props[p]);
            }
        }
    }
    /**
     * Returns the property name for the given attribute `name`.
     * @nocollapse
     */
    static _attributeNameForProperty(name, options) {
        const attribute = options.attribute;
        return attribute === false ?
            undefined :
            (typeof attribute === 'string' ?
                attribute :
                (typeof name === 'string' ? name.toLowerCase() : undefined));
    }
    /**
     * Returns true if a property should request an update.
     * Called when a property value is set and uses the `hasChanged`
     * option for the property if present or a strict identity check.
     * @nocollapse
     */
    static _valueHasChanged(value, old, hasChanged = notEqual) {
        return hasChanged(value, old);
    }
    /**
     * Returns the property value for the given attribute value.
     * Called via the `attributeChangedCallback` and uses the property's
     * `converter` or `converter.fromAttribute` property option.
     * @nocollapse
     */
    static _propertyValueFromAttribute(value, options) {
        const type = options.type;
        const converter = options.converter || defaultConverter;
        const fromAttribute = (typeof converter === 'function' ? converter : converter.fromAttribute);
        return fromAttribute ? fromAttribute(value, type) : value;
    }
    /**
     * Returns the attribute value for the given property value. If this
     * returns undefined, the property will *not* be reflected to an attribute.
     * If this returns null, the attribute will be removed, otherwise the
     * attribute will be set to the value.
     * This uses the property's `reflect` and `type.toAttribute` property options.
     * @nocollapse
     */
    static _propertyValueToAttribute(value, options) {
        if (options.reflect === undefined) {
            return;
        }
        const type = options.type;
        const converter = options.converter;
        const toAttribute = converter && converter.toAttribute ||
            defaultConverter.toAttribute;
        return toAttribute(value, type);
    }
    /**
     * Performs element initialization. By default captures any pre-set values for
     * registered properties.
     */
    initialize() {
        this._updateState = 0;
        this._updatePromise =
            new Promise((res) => this._enableUpdatingResolver = res);
        this._changedProperties = new Map();
        this._saveInstanceProperties();
        // ensures first update will be caught by an early access of
        // `updateComplete`
        this.requestUpdateInternal();
    }
    /**
     * Fixes any properties set on the instance before upgrade time.
     * Otherwise these would shadow the accessor and break these properties.
     * The properties are stored in a Map which is played back after the
     * constructor runs. Note, on very old versions of Safari (<=9) or Chrome
     * (<=41), properties created for native platform properties like (`id` or
     * `name`) may not have default values set in the element constructor. On
     * these browsers native properties appear on instances and therefore their
     * default value will overwrite any element default (e.g. if the element sets
     * this.id = 'id' in the constructor, the 'id' will become '' since this is
     * the native platform default).
     */
    _saveInstanceProperties() {
        // Use forEach so this works even if for/of loops are compiled to for loops
        // expecting arrays
        this.constructor
            ._classProperties.forEach((_v, p) => {
            if (this.hasOwnProperty(p)) {
                const value = this[p];
                delete this[p];
                if (!this._instanceProperties) {
                    this._instanceProperties = new Map();
                }
                this._instanceProperties.set(p, value);
            }
        });
    }
    /**
     * Applies previously saved instance properties.
     */
    _applyInstanceProperties() {
        // Use forEach so this works even if for/of loops are compiled to for loops
        // expecting arrays
        // tslint:disable-next-line:no-any
        this._instanceProperties.forEach((v, p) => this[p] = v);
        this._instanceProperties = undefined;
    }
    connectedCallback() {
        // Ensure first connection completes an update. Updates cannot complete
        // before connection.
        this.enableUpdating();
    }
    enableUpdating() {
        if (this._enableUpdatingResolver !== undefined) {
            this._enableUpdatingResolver();
            this._enableUpdatingResolver = undefined;
        }
    }
    /**
     * Allows for `super.disconnectedCallback()` in extensions while
     * reserving the possibility of making non-breaking feature additions
     * when disconnecting at some point in the future.
     */
    disconnectedCallback() {
    }
    /**
     * Synchronizes property values when attributes change.
     */
    attributeChangedCallback(name, old, value) {
        if (old !== value) {
            this._attributeToProperty(name, value);
        }
    }
    _propertyToAttribute(name, value, options = defaultPropertyDeclaration) {
        const ctor = this.constructor;
        const attr = ctor._attributeNameForProperty(name, options);
        if (attr !== undefined) {
            const attrValue = ctor._propertyValueToAttribute(value, options);
            // an undefined value does not change the attribute.
            if (attrValue === undefined) {
                return;
            }
            // Track if the property is being reflected to avoid
            // setting the property again via `attributeChangedCallback`. Note:
            // 1. this takes advantage of the fact that the callback is synchronous.
            // 2. will behave incorrectly if multiple attributes are in the reaction
            // stack at time of calling. However, since we process attributes
            // in `update` this should not be possible (or an extreme corner case
            // that we'd like to discover).
            // mark state reflecting
            this._updateState = this._updateState | STATE_IS_REFLECTING_TO_ATTRIBUTE;
            if (attrValue == null) {
                this.removeAttribute(attr);
            }
            else {
                this.setAttribute(attr, attrValue);
            }
            // mark state not reflecting
            this._updateState = this._updateState & ~STATE_IS_REFLECTING_TO_ATTRIBUTE;
        }
    }
    _attributeToProperty(name, value) {
        // Use tracking info to avoid deserializing attribute value if it was
        // just set from a property setter.
        if (this._updateState & STATE_IS_REFLECTING_TO_ATTRIBUTE) {
            return;
        }
        const ctor = this.constructor;
        // Note, hint this as an `AttributeMap` so closure clearly understands
        // the type; it has issues with tracking types through statics
        // tslint:disable-next-line:no-unnecessary-type-assertion
        const propName = ctor._attributeToPropertyMap.get(name);
        if (propName !== undefined) {
            const options = ctor.getPropertyOptions(propName);
            // mark state reflecting
            this._updateState = this._updateState | STATE_IS_REFLECTING_TO_PROPERTY;
            this[propName] =
                // tslint:disable-next-line:no-any
                ctor._propertyValueFromAttribute(value, options);
            // mark state not reflecting
            this._updateState = this._updateState & ~STATE_IS_REFLECTING_TO_PROPERTY;
        }
    }
    /**
     * This protected version of `requestUpdate` does not access or return the
     * `updateComplete` promise. This promise can be overridden and is therefore
     * not free to access.
     */
    requestUpdateInternal(name, oldValue, options) {
        let shouldRequestUpdate = true;
        // If we have a property key, perform property update steps.
        if (name !== undefined) {
            const ctor = this.constructor;
            options = options || ctor.getPropertyOptions(name);
            if (ctor._valueHasChanged(this[name], oldValue, options.hasChanged)) {
                if (!this._changedProperties.has(name)) {
                    this._changedProperties.set(name, oldValue);
                }
                // Add to reflecting properties set.
                // Note, it's important that every change has a chance to add the
                // property to `_reflectingProperties`. This ensures setting
                // attribute + property reflects correctly.
                if (options.reflect === true &&
                    !(this._updateState & STATE_IS_REFLECTING_TO_PROPERTY)) {
                    if (this._reflectingProperties === undefined) {
                        this._reflectingProperties = new Map();
                    }
                    this._reflectingProperties.set(name, options);
                }
            }
            else {
                // Abort the request if the property should not be considered changed.
                shouldRequestUpdate = false;
            }
        }
        if (!this._hasRequestedUpdate && shouldRequestUpdate) {
            this._updatePromise = this._enqueueUpdate();
        }
    }
    /**
     * Requests an update which is processed asynchronously. This should
     * be called when an element should update based on some state not triggered
     * by setting a property. In this case, pass no arguments. It should also be
     * called when manually implementing a property setter. In this case, pass the
     * property `name` and `oldValue` to ensure that any configured property
     * options are honored. Returns the `updateComplete` Promise which is resolved
     * when the update completes.
     *
     * @param name {PropertyKey} (optional) name of requesting property
     * @param oldValue {any} (optional) old value of requesting property
     * @returns {Promise} A Promise that is resolved when the update completes.
     */
    requestUpdate(name, oldValue) {
        this.requestUpdateInternal(name, oldValue);
        return this.updateComplete;
    }
    /**
     * Sets up the element to asynchronously update.
     */
    async _enqueueUpdate() {
        this._updateState = this._updateState | STATE_UPDATE_REQUESTED;
        try {
            // Ensure any previous update has resolved before updating.
            // This `await` also ensures that property changes are batched.
            await this._updatePromise;
        }
        catch (e) {
            // Ignore any previous errors. We only care that the previous cycle is
            // done. Any error should have been handled in the previous update.
        }
        const result = this.performUpdate();
        // If `performUpdate` returns a Promise, we await it. This is done to
        // enable coordinating updates with a scheduler. Note, the result is
        // checked to avoid delaying an additional microtask unless we need to.
        if (result != null) {
            await result;
        }
        return !this._hasRequestedUpdate;
    }
    get _hasRequestedUpdate() {
        return (this._updateState & STATE_UPDATE_REQUESTED);
    }
    get hasUpdated() {
        return (this._updateState & STATE_HAS_UPDATED);
    }
    /**
     * Performs an element update. Note, if an exception is thrown during the
     * update, `firstUpdated` and `updated` will not be called.
     *
     * You can override this method to change the timing of updates. If this
     * method is overridden, `super.performUpdate()` must be called.
     *
     * For instance, to schedule updates to occur just before the next frame:
     *
     * ```
     * protected async performUpdate(): Promise<unknown> {
     *   await new Promise((resolve) => requestAnimationFrame(() => resolve()));
     *   super.performUpdate();
     * }
     * ```
     */
    performUpdate() {
        // Abort any update if one is not pending when this is called.
        // This can happen if `performUpdate` is called early to "flush"
        // the update.
        if (!this._hasRequestedUpdate) {
            return;
        }
        // Mixin instance properties once, if they exist.
        if (this._instanceProperties) {
            this._applyInstanceProperties();
        }
        let shouldUpdate = false;
        const changedProperties = this._changedProperties;
        try {
            shouldUpdate = this.shouldUpdate(changedProperties);
            if (shouldUpdate) {
                this.update(changedProperties);
            }
            else {
                this._markUpdated();
            }
        }
        catch (e) {
            // Prevent `firstUpdated` and `updated` from running when there's an
            // update exception.
            shouldUpdate = false;
            // Ensure element can accept additional updates after an exception.
            this._markUpdated();
            throw e;
        }
        if (shouldUpdate) {
            if (!(this._updateState & STATE_HAS_UPDATED)) {
                this._updateState = this._updateState | STATE_HAS_UPDATED;
                this.firstUpdated(changedProperties);
            }
            this.updated(changedProperties);
        }
    }
    _markUpdated() {
        this._changedProperties = new Map();
        this._updateState = this._updateState & ~STATE_UPDATE_REQUESTED;
    }
    /**
     * Returns a Promise that resolves when the element has completed updating.
     * The Promise value is a boolean that is `true` if the element completed the
     * update without triggering another update. The Promise result is `false` if
     * a property was set inside `updated()`. If the Promise is rejected, an
     * exception was thrown during the update.
     *
     * To await additional asynchronous work, override the `_getUpdateComplete`
     * method. For example, it is sometimes useful to await a rendered element
     * before fulfilling this Promise. To do this, first await
     * `super._getUpdateComplete()`, then any subsequent state.
     *
     * @returns {Promise} The Promise returns a boolean that indicates if the
     * update resolved without triggering another update.
     */
    get updateComplete() {
        return this._getUpdateComplete();
    }
    /**
     * Override point for the `updateComplete` promise.
     *
     * It is not safe to override the `updateComplete` getter directly due to a
     * limitation in TypeScript which means it is not possible to call a
     * superclass getter (e.g. `super.updateComplete.then(...)`) when the target
     * language is ES5 (https://github.com/microsoft/TypeScript/issues/338).
     * This method should be overridden instead. For example:
     *
     *   class MyElement extends LitElement {
     *     async _getUpdateComplete() {
     *       await super._getUpdateComplete();
     *       await this._myChild.updateComplete;
     *     }
     *   }
     */
    _getUpdateComplete() {
        return this._updatePromise;
    }
    /**
     * Controls whether or not `update` should be called when the element requests
     * an update. By default, this method always returns `true`, but this can be
     * customized to control when to update.
     *
     * @param _changedProperties Map of changed properties with old values
     */
    shouldUpdate(_changedProperties) {
        return true;
    }
    /**
     * Updates the element. This method reflects property values to attributes.
     * It can be overridden to render and keep updated element DOM.
     * Setting properties inside this method will *not* trigger
     * another update.
     *
     * @param _changedProperties Map of changed properties with old values
     */
    update(_changedProperties) {
        if (this._reflectingProperties !== undefined &&
            this._reflectingProperties.size > 0) {
            // Use forEach so this works even if for/of loops are compiled to for
            // loops expecting arrays
            this._reflectingProperties.forEach((v, k) => this._propertyToAttribute(k, this[k], v));
            this._reflectingProperties = undefined;
        }
        this._markUpdated();
    }
    /**
     * Invoked whenever the element is updated. Implement to perform
     * post-updating tasks via DOM APIs, for example, focusing an element.
     *
     * Setting properties inside this method will trigger the element to update
     * again after this update cycle completes.
     *
     * @param _changedProperties Map of changed properties with old values
     */
    updated(_changedProperties) {
    }
    /**
     * Invoked when the element is first updated. Implement to perform one time
     * work on the element after update.
     *
     * Setting properties inside this method will trigger the element to update
     * again after this update cycle completes.
     *
     * @param _changedProperties Map of changed properties with old values
     */
    firstUpdated(_changedProperties) {
    }
}
_a = finalized;
/**
 * Marks class as having finished creating properties.
 */
UpdatingElement[_a] = true;

/**
@license
Copyright (c) 2019 The Polymer Project Authors. All rights reserved.
This code may only be used under the BSD style license found at
http://polymer.github.io/LICENSE.txt The complete set of authors may be found at
http://polymer.github.io/AUTHORS.txt The complete set of contributors may be
found at http://polymer.github.io/CONTRIBUTORS.txt Code distributed by Google as
part of the polymer project is also subject to an additional IP rights grant
found at http://polymer.github.io/PATENTS.txt
*/
/**
 * Whether the current browser supports `adoptedStyleSheets`.
 */
const supportsAdoptingStyleSheets = (window.ShadowRoot) &&
    (window.ShadyCSS === undefined || window.ShadyCSS.nativeShadow) &&
    ('adoptedStyleSheets' in Document.prototype) &&
    ('replace' in CSSStyleSheet.prototype);
const constructionToken = Symbol();
class CSSResult {
    constructor(cssText, safeToken) {
        if (safeToken !== constructionToken) {
            throw new Error('CSSResult is not constructable. Use `unsafeCSS` or `css` instead.');
        }
        this.cssText = cssText;
    }
    // Note, this is a getter so that it's lazy. In practice, this means
    // stylesheets are not created until the first element instance is made.
    get styleSheet() {
        if (this._styleSheet === undefined) {
            // Note, if `supportsAdoptingStyleSheets` is true then we assume
            // CSSStyleSheet is constructable.
            if (supportsAdoptingStyleSheets) {
                this._styleSheet = new CSSStyleSheet();
                this._styleSheet.replaceSync(this.cssText);
            }
            else {
                this._styleSheet = null;
            }
        }
        return this._styleSheet;
    }
    toString() {
        return this.cssText;
    }
}
/**
 * Wrap a value for interpolation in a [[`css`]] tagged template literal.
 *
 * This is unsafe because untrusted CSS text can be used to phone home
 * or exfiltrate data to an attacker controlled site. Take care to only use
 * this with trusted input.
 */
const unsafeCSS = (value) => {
    return new CSSResult(String(value), constructionToken);
};
const textFromCSSResult = (value) => {
    if (value instanceof CSSResult) {
        return value.cssText;
    }
    else if (typeof value === 'number') {
        return value;
    }
    else {
        throw new Error(`Value passed to 'css' function must be a 'css' function result: ${value}. Use 'unsafeCSS' to pass non-literal values, but
            take care to ensure page security.`);
    }
};
/**
 * Template tag which which can be used with LitElement's [[LitElement.styles |
 * `styles`]] property to set element styles. For security reasons, only literal
 * string values may be used. To incorporate non-literal values [[`unsafeCSS`]]
 * may be used inside a template string part.
 */
const css = (strings, ...values) => {
    const cssText = values.reduce((acc, v, idx) => acc + textFromCSSResult(v) + strings[idx + 1], strings[0]);
    return new CSSResult(cssText, constructionToken);
};

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
// IMPORTANT: do not change the property name or the assignment expression.
// This line will be used in regexes to search for LitElement usage.
// TODO(justinfagnani): inject version number at build time
(window['litElementVersions'] || (window['litElementVersions'] = []))
    .push('2.4.0');
/**
 * Sentinal value used to avoid calling lit-html's render function when
 * subclasses do not implement `render`
 */
const renderNotImplemented = {};
/**
 * Base element class that manages element properties and attributes, and
 * renders a lit-html template.
 *
 * To define a component, subclass `LitElement` and implement a
 * `render` method to provide the component's template. Define properties
 * using the [[`properties`]] property or the [[`property`]] decorator.
 */
class LitElement extends UpdatingElement {
    /**
     * Return the array of styles to apply to the element.
     * Override this method to integrate into a style management system.
     *
     * @nocollapse
     */
    static getStyles() {
        return this.styles;
    }
    /** @nocollapse */
    static _getUniqueStyles() {
        // Only gather styles once per class
        if (this.hasOwnProperty(JSCompiler_renameProperty('_styles', this))) {
            return;
        }
        // Take care not to call `this.getStyles()` multiple times since this
        // generates new CSSResults each time.
        // TODO(sorvell): Since we do not cache CSSResults by input, any
        // shared styles will generate new stylesheet objects, which is wasteful.
        // This should be addressed when a browser ships constructable
        // stylesheets.
        const userStyles = this.getStyles();
        if (Array.isArray(userStyles)) {
            // De-duplicate styles preserving the _last_ instance in the set.
            // This is a performance optimization to avoid duplicated styles that can
            // occur especially when composing via subclassing.
            // The last item is kept to try to preserve the cascade order with the
            // assumption that it's most important that last added styles override
            // previous styles.
            const addStyles = (styles, set) => styles.reduceRight((set, s) => 
            // Note: On IE set.add() does not return the set
            Array.isArray(s) ? addStyles(s, set) : (set.add(s), set), set);
            // Array.from does not work on Set in IE, otherwise return
            // Array.from(addStyles(userStyles, new Set<CSSResult>())).reverse()
            const set = addStyles(userStyles, new Set());
            const styles = [];
            set.forEach((v) => styles.unshift(v));
            this._styles = styles;
        }
        else {
            this._styles = userStyles === undefined ? [] : [userStyles];
        }
        // Ensure that there are no invalid CSSStyleSheet instances here. They are
        // invalid in two conditions.
        // (1) the sheet is non-constructible (`sheet` of a HTMLStyleElement), but
        //     this is impossible to check except via .replaceSync or use
        // (2) the ShadyCSS polyfill is enabled (:. supportsAdoptingStyleSheets is
        //     false)
        this._styles = this._styles.map((s) => {
            if (s instanceof CSSStyleSheet && !supportsAdoptingStyleSheets) {
                // Flatten the cssText from the passed constructible stylesheet (or
                // undetectable non-constructible stylesheet). The user might have
                // expected to update their stylesheets over time, but the alternative
                // is a crash.
                const cssText = Array.prototype.slice.call(s.cssRules)
                    .reduce((css, rule) => css + rule.cssText, '');
                return unsafeCSS(cssText);
            }
            return s;
        });
    }
    /**
     * Performs element initialization. By default this calls
     * [[`createRenderRoot`]] to create the element [[`renderRoot`]] node and
     * captures any pre-set values for registered properties.
     */
    initialize() {
        super.initialize();
        this.constructor._getUniqueStyles();
        this.renderRoot = this.createRenderRoot();
        // Note, if renderRoot is not a shadowRoot, styles would/could apply to the
        // element's getRootNode(). While this could be done, we're choosing not to
        // support this now since it would require different logic around de-duping.
        if (window.ShadowRoot && this.renderRoot instanceof window.ShadowRoot) {
            this.adoptStyles();
        }
    }
    /**
     * Returns the node into which the element should render and by default
     * creates and returns an open shadowRoot. Implement to customize where the
     * element's DOM is rendered. For example, to render into the element's
     * childNodes, return `this`.
     * @returns {Element|DocumentFragment} Returns a node into which to render.
     */
    createRenderRoot() {
        return this.attachShadow({ mode: 'open' });
    }
    /**
     * Applies styling to the element shadowRoot using the [[`styles`]]
     * property. Styling will apply using `shadowRoot.adoptedStyleSheets` where
     * available and will fallback otherwise. When Shadow DOM is polyfilled,
     * ShadyCSS scopes styles and adds them to the document. When Shadow DOM
     * is available but `adoptedStyleSheets` is not, styles are appended to the
     * end of the `shadowRoot` to [mimic spec
     * behavior](https://wicg.github.io/construct-stylesheets/#using-constructed-stylesheets).
     */
    adoptStyles() {
        const styles = this.constructor._styles;
        if (styles.length === 0) {
            return;
        }
        // There are three separate cases here based on Shadow DOM support.
        // (1) shadowRoot polyfilled: use ShadyCSS
        // (2) shadowRoot.adoptedStyleSheets available: use it
        // (3) shadowRoot.adoptedStyleSheets polyfilled: append styles after
        // rendering
        if (window.ShadyCSS !== undefined && !window.ShadyCSS.nativeShadow) {
            window.ShadyCSS.ScopingShim.prepareAdoptedCssText(styles.map((s) => s.cssText), this.localName);
        }
        else if (supportsAdoptingStyleSheets) {
            this.renderRoot.adoptedStyleSheets =
                styles.map((s) => s instanceof CSSStyleSheet ? s : s.styleSheet);
        }
        else {
            // This must be done after rendering so the actual style insertion is done
            // in `update`.
            this._needsShimAdoptedStyleSheets = true;
        }
    }
    connectedCallback() {
        super.connectedCallback();
        // Note, first update/render handles styleElement so we only call this if
        // connected after first update.
        if (this.hasUpdated && window.ShadyCSS !== undefined) {
            window.ShadyCSS.styleElement(this);
        }
    }
    /**
     * Updates the element. This method reflects property values to attributes
     * and calls `render` to render DOM via lit-html. Setting properties inside
     * this method will *not* trigger another update.
     * @param _changedProperties Map of changed properties with old values
     */
    update(changedProperties) {
        // Setting properties in `render` should not trigger an update. Since
        // updates are allowed after super.update, it's important to call `render`
        // before that.
        const templateResult = this.render();
        super.update(changedProperties);
        // If render is not implemented by the component, don't call lit-html render
        if (templateResult !== renderNotImplemented) {
            this.constructor
                .render(templateResult, this.renderRoot, { scopeName: this.localName, eventContext: this });
        }
        // When native Shadow DOM is used but adoptedStyles are not supported,
        // insert styling after rendering to ensure adoptedStyles have highest
        // priority.
        if (this._needsShimAdoptedStyleSheets) {
            this._needsShimAdoptedStyleSheets = false;
            this.constructor._styles.forEach((s) => {
                const style = document.createElement('style');
                style.textContent = s.cssText;
                this.renderRoot.appendChild(style);
            });
        }
    }
    /**
     * Invoked on each update to perform rendering tasks. This method may return
     * any value renderable by lit-html's `NodePart` - typically a
     * `TemplateResult`. Setting properties inside this method will *not* trigger
     * the element to update.
     */
    render() {
        return renderNotImplemented;
    }
}
/**
 * Ensure this class is marked as `finalized` as an optimization ensuring
 * it will not needlessly try to `finalize`.
 *
 * Note this property name is a string to prevent breaking Closure JS Compiler
 * optimizations. See updating-element.ts for more information.
 */
LitElement['finalized'] = true;
/**
 * Reference to the underlying library method used to render the element's
 * DOM. By default, points to the `render` method from lit-html's shady-render
 * module.
 *
 * **Most users will never need to touch this property.**
 *
 * This  property should not be confused with the `render` instance method,
 * which should be overridden to define a template for the element.
 *
 * Advanced users creating a new base class based on LitElement can override
 * this property to point to a custom render method with a signature that
 * matches [shady-render's `render`
 * method](https://lit-html.polymer-project.org/api/modules/shady_render.html#render).
 *
 * @nocollapse
 */
LitElement.render = render$1;

const appliedClassMixins = new WeakMap();

/** Vefify if the Mixin was previously applyed
 * @private
 * @param {function} mixin      Mixin being applyed
 * @param {object} superClass   Class receiving the new mixin
 * @returns {boolean}
 */
function wasMixinPreviouslyApplied(mixin, superClass) {
  let klass = superClass;
  while (klass) {
    if (appliedClassMixins.get(klass) === mixin) {
      return true;
    }
    klass = Object.getPrototypeOf(klass);
  }
  return false;
}

/** Apply each mixin in the chain to make sure they are not applied more than once to the final class.
 * @export
 * @param {function} mixin      Mixin to be applyed
 * @returns {object}            Mixed class with mixin applied
 */
function dedupeMixin(mixin) {
  return superClass => {
    if (wasMixinPreviouslyApplied(mixin, superClass)) {
      return superClass;
    }
    const mixedClass = mixin(superClass);
    appliedClassMixins.set(mixedClass, mixin);
    return mixedClass;
  };
}

/**
 * Cache class that allows to search in a cache hierarchy.
 * @template T, Q
 */
class Cache {
  /**
   * Creates a Cache instance
   * @param {Cache} [parent]
   */
  constructor(parent) {
    this._parent = parent;
    this._cache = new Map();
  }

  /**
   * Returns a boolean indicating whether an element with the specified key exists or not.
   *
   * @param {T} key - The key of the element to test for presence in the Cache object.
   * @return {boolean}
   */
  has(key) {
    return !!(this._cache.has(key) || (this._parent && this._parent._cache.has(key)));
  }

  /**
   * Adds or updates an element with a specified key and a value to a Cache object.
   *
   * @param {T} key - The key of the element to add to the Cache object.
   * @param {Q} value - The value of the element to add to the Cache object.
   * @return {Cache<T, Q>} the cache object
   */
  set(key, value) {
    this._cache.set(key, value);

    return this;
  }

  /**
   * Returns a specified element from a Map object. If the value that is associated to the provided key is an
   * object, then you will get a reference to that object and any change made to that object will effectively modify
   * it inside the Map object.
   *
   * @param {T} key - The key of the element to return from the Cache object.
   * @return {Q}
   */
  get(key) {
    return this._cache.get(key) || (this._parent && this._parent._cache.get(key));
  }
}

/**
 * Global counter to scope the custom elements
 *
 * @type {number}
 */
let counter = Math.round(Math.random() * 100000);

/**
 * Allowed tag name chars
 *
 * @type {string}
 */
const chars = `-|\\.|[0-9]|[a-z]`;

/**
 * Regular expression to check if a value is a valid tag name
 *
 * @type {RegExp}
 */
const tagRegExp = new RegExp(`[a-z](${chars})*-(${chars})*`);

/**
 * Checks if the tag name is valid
 *
 * @param {string} tag
 * @returns {boolean}
 */
const isValid = tag => tagRegExp.exec(tag) !== null;

/**
 * Checks if the tag is already registered
 *
 * @param {string} name
 * @param {CustomElementRegistry} registry
 * @returns {boolean}
 */
const isTagRegistered = (name, registry) => !!registry.get(name);

/**
 * Given a tag name scopes it with a number suffix
 *
 * @param {string} tagName
 * @param {CustomElementRegistry} registry
 * @returns {string} scoped tag name
 */
const incrementTagName = (tagName, registry) => {
  const newTagName = `${tagName}-${(counter += 1)}`;

  if (isTagRegistered(newTagName, registry)) {
    return incrementTagName(tagName, registry);
  }

  return newTagName;
};

/**
 * Creates a unique scoped tag name
 *
 * @exports
 * @param {string} tagName - tag name to scope
 * @param {CustomElementRegistry} registry
 * @returns {string} scoped tag name
 */
function createUniqueTag(tagName, registry = customElements) {
  if (!isValid(tagName)) {
    throw new Error('tagName is invalid');
  }

  return incrementTagName(tagName, registry);
}

/**
 * The global cache for tag names
 *
 * @type {WeakMap<typeof HTMLElement, string>}
 */
const globalTagsCache = new WeakMap();

/**
 * Adds a tag to the global tags cache
 *
 * @param {string} tag
 * @param {typeof HTMLElement} klass
 */
const addToGlobalTagsCache = (tag, klass) => globalTagsCache.set(klass, tag);

/**
 * Gets a tag from the global tags cache
 *
 * @exports
 * @param {typeof HTMLElement} klass
 * @returns {undefined|string}
 */
const getFromGlobalTagsCache = klass => globalTagsCache.get(klass);

/**
 * Checks if klass is a subclass of HTMLElement
 *
 * @param {typeof HTMLElement} klass
 * @returns {boolean}
 */
const extendsHTMLElement = klass => Object.prototype.isPrototypeOf.call(HTMLElement, klass);

/**
 * Defines a custom element
 *
 * @param {string} tagName
 * @param {typeof HTMLElement} klass
 * @param {CustomElementRegistry} registry
 */
const defineElement = (tagName, klass, registry = customElements) => {
  addToGlobalTagsCache(tagName, klass);
  registry.define(tagName, class extends klass {});
};

/**
 * Stores a lazy element in the cache to be used in future
 *
 * @param {string} tagName
 * @param {CustomElementRegistry} registry
 * @param {import('./Cache.js').Cache<string, string>} tagsCache
 * @returns {string}
 */
const storeLazyElementInCache = (tagName, registry, tagsCache) => {
  const tag = createUniqueTag(tagName, registry);

  if (!tagsCache) {
    throw new Error('Lazy scoped elements requires the use of tags cache');
  }

  tagsCache.set(tagName, tag);

  return tag;
};

/**
 * Define a scoped custom element storing the scoped tag name in the cache
 *
 * @param {string} tagName
 * @param {typeof HTMLElement} klass
 * @param {import('./Cache.js').Cache<string, string>} tagsCache
 * @returns {string}
 */
const defineElementAndStoreInCache = (tagName, klass, tagsCache) => {
  const registry = customElements;

  if (!extendsHTMLElement(klass)) {
    return storeLazyElementInCache(tagName, registry, tagsCache);
  }

  if (klass === customElements.get(tagName)) {
    addToGlobalTagsCache(tagName, klass);

    return tagName;
  }

  const tag = createUniqueTag(tagName, registry);
  // @ts-ignore
  // we extend it just in case the class has been defined manually
  defineElement(tag, klass, registry);

  return tag;
};

/**
 * Gets a scoped tag name from the cache or generates a new one and defines the element if needed
 *
 * @exports
 * @param {string} tagName
 * @param {typeof HTMLElement} klass
 * @param {import('./Cache.js').Cache<string, string>} tagsCache
 * @returns {string}
 */
function registerElement(tagName, klass, tagsCache = undefined) {
  const tag =
    getFromGlobalTagsCache(klass) ||
    (tagsCache && tagsCache.get(tagName)) ||
    defineElementAndStoreInCache(tagName, klass, tagsCache);

  return tag;
}

/**
 * Defines a lazy element
 *
 * @param {string} tagName
 * @param {typeof HTMLElement} klass
 * @param {import('./Cache.js').Cache<string, string>} tagsCache
 */
function defineScopedElement(tagName, klass, tagsCache) {
  const tag = tagsCache.get(tagName);

  if (tag) {
    if (customElements.get(tag) === undefined) {
      defineElement(tag, klass, customElements);
    }
  } else {
    tagsCache.set(tagName, registerElement(tagName, klass, tagsCache));
  }
}

/**
 * @typedef {import('./types').ScopedElementsMap} ScopedElementsMap
 */

/**
 * Allowed tag name chars
 *
 * @type {string}
 */
const chars$1 = `-|\\.|[0-9]|[a-z]`;

/**
 * Regular Expression to find a custom element tag
 *
 * @type {RegExp}
 */
const re = new RegExp(`<\\/?([a-z](${chars$1})*-(${chars$1})*)`, 'g');

/**
 * The global cache of processed string arrays
 *
 * @type {Cache<TemplateStringsArray, TemplateStringsArray>}
 */
const globalCache = new Cache();

/**
 * Find custom element tags in the string
 *
 * @param {string} str
 * @returns {RegExpExecArray[]}
 */
const matchAll = str => {
  const matches = [];
  let result;
  // eslint-disable-next-line no-cond-assign
  while ((result = re.exec(str)) !== null) {
    matches.push(result);
  }

  return matches;
};

/**
 * Transforms a string array into another one with resolved scoped elements and caches it for future references
 *
 * @param {TemplateStringsArray} strings
 * @param {ScopedElementsMap} scopedElements
 * @param {Cache<TemplateStringsArray, TemplateStringsArray>} templateCache
 * @param {Cache<string, string>} tagsCache
 * @returns {TemplateStringsArray}
 */
const transformTemplate = (strings, scopedElements, templateCache, tagsCache) => {
  const transformedStrings = strings.map(str => {
    let acc = str;
    const matches = matchAll(str);

    for (let i = matches.length - 1; i >= 0; i -= 1) {
      const item = matches[i];
      const [block, tagName] = item;
      const tag = registerElement(tagName, scopedElements[tagName], tagsCache);
      const start = item.index + block.length - tagName.length;
      const end = start + tagName.length;
      const isClosingTag = block.indexOf('</') === 0;

      acc =
        acc.slice(0, start) +
        (isClosingTag ? tag : `${tag} data-tag-name="${tagName}"`) +
        acc.slice(end);
    }

    return acc;
  });

  // @ts-ignore
  // noinspection JSCheckFunctionSignatures
  templateCache.set(strings, transformedStrings);

  // @ts-ignore
  // noinspection JSValidateTypes
  return transformedStrings;
};

/**
 * Obtains the cached strings array with resolved scoped elements or creates it
 *
 * @exports
 * @param {TemplateStringsArray} strings
 * @param {ScopedElementsMap} scopedElements
 * @param {import('./Cache.js').Cache<TemplateStringsArray, TemplateStringsArray>} templateCache
 * @param {import('./Cache.js').Cache<string, string>} tagsCache
 * @returns {TemplateStringsArray}
 */
function transform(strings, scopedElements, templateCache = globalCache, tagsCache) {
  return (
    templateCache.get(strings) ||
    transformTemplate(strings, scopedElements, templateCache, tagsCache)
  );
}

const getTemplateCacheKey$1 = (type, scopeName) => `${type}--${scopeName}`;

let compatibleShadyCSSVersion$1 = true;

// @ts-ignore
const { ShadyCSS } = window;

if (typeof ShadyCSS === 'undefined') {
  compatibleShadyCSSVersion$1 = false;
} else if (typeof ShadyCSS.prepareTemplateDom === 'undefined') {
  compatibleShadyCSSVersion$1 = false;
}

/**
 * Template factory which scopes template DOM using ShadyCSS.
 * @param scopeName {string}
 */
const shadyTemplateFactory$1 = scopeName => result => {
  const cacheKey = getTemplateCacheKey$1(result.type, scopeName);
  let templateCache = templateCaches.get(cacheKey);
  if (templateCache === undefined) {
    templateCache = {
      stringsArray: new WeakMap(),
      keyString: new Map(),
    };
    templateCaches.set(cacheKey, templateCache);
  }
  let template = templateCache.stringsArray.get(result.strings);
  if (template !== undefined) {
    return template;
  }
  const key = result.strings.join(marker);
  template = templateCache.keyString.get(key);
  if (template === undefined) {
    const element = result.getTemplateElement();
    if (compatibleShadyCSSVersion$1) {
      ShadyCSS.prepareTemplateDom(element, scopeName);
    }
    template = new Template(result, element);
    templateCache.keyString.set(key, template);
  }
  templateCache.stringsArray.set(result.strings, template);
  return template;
};

/* eslint-disable no-use-before-define */

/**
 * @typedef {import('./types').ScopedElementsMixin} ScopedElementsMixin
 * @typedef {import('./types').ScopedElementsMap} ScopedElementsMap
 * @typedef {import("lit-element").LitElement} LitElement
 * @typedef {import('lit-html/lib/shady-render').ShadyRenderOptions} ShadyRenderOptions
 * @typedef {function(TemplateResult, Element|DocumentFragment|ShadowRoot, ShadyRenderOptions): void} RenderFunction
 */

/**
 * Template caches
 *
 * @type {WeakMap<Function, Cache<TemplateStringsArray, TemplateStringsArray>>}
 */
const templateCaches$1 = new WeakMap();

/**
 * Retrieves or creates a templateCache for a specific key
 *
 * @param {Function} key
 * @returns {Cache<TemplateStringsArray, TemplateStringsArray>}
 */
const getTemplateCache = key => {
  if (!templateCaches$1.has(key)) {
    // @ts-ignore
    templateCaches$1.set(key, new Cache(templateCaches$1.get(key.constructor)));
  }

  return templateCaches$1.get(key);
};

/**
 * Tags caches
 *
 * @type {WeakMap<object, Cache<string, string>>}
 */
const tagsCaches = new WeakMap();

/**
 * Retrieves or creates a tagsCache for a specific key
 * @param {object} key
 * @returns {Cache<string, string>}
 */
const getTagsCache = key => {
  if (!tagsCaches.has(key)) {
    tagsCaches.set(key, new Cache(tagsCaches.get(key.constructor)));
  }

  return tagsCaches.get(key);
};

/**
 * Transforms an array of TemplateResults or arrays into another one with resolved scoped elements
 *
 * @param {ReadonlyArray} items
 * @param {ScopedElementsMap} scopedElements
 * @param {Cache<TemplateStringsArray, TemplateStringsArray>} templateCache
 * @param {Cache<string, string>} tagsCache
 * @returns {ReadonlyArray}
 */
const transformArray = (items, scopedElements, templateCache, tagsCache) =>
  items.map(value => {
    if (value instanceof TemplateResult) {
      return transformTemplate$1(value, scopedElements, templateCache, tagsCache);
    }

    if (Array.isArray(value)) {
      return transformArray(value, scopedElements, templateCache, tagsCache);
    }

    return value;
  });

/**
 * Transforms a TemplateResult into another one with resolved scoped elements
 *
 * @param {TemplateResult} template
 * @param {ScopedElementsMap} scopedElements
 * @param {Cache<TemplateStringsArray, TemplateStringsArray>} templateCache
 * @param {Cache<string, string>} tagsCache
 * @returns {TemplateResult}
 */
const transformTemplate$1 = (template, scopedElements, templateCache, tagsCache) =>
  new TemplateResult(
    transform(template.strings, scopedElements, templateCache, tagsCache),
    transformArray(template.values, scopedElements, templateCache, tagsCache),
    template.type,
    template.processor,
  );

/**
 * Gets an instance of the ScopedElementsTemplateFactory
 *
 * @param {string} scopeName
 * @param {ScopedElementsMap} scopedElements
 * @param {Cache<TemplateStringsArray, TemplateStringsArray>} templateCache
 * @param {Cache<string, string>} tagsCache
 * @returns {function(any): any}
 */
const scopedElementsTemplateFactory = (
  scopeName,
  scopedElements,
  templateCache,
  tagsCache,
) => template => {
  const newTemplate = transformTemplate$1(template, scopedElements, templateCache, tagsCache);

  return shadyTemplateFactory$1(scopeName)(newTemplate);
};

/** @type {ScopedElementsMixin} */
const ScopedElementsMixinImplementation = superclass =>
  class ScopedElementsHost extends superclass {
    /**
     * Obtains the scoped elements definitions map
     *
     * @returns {ScopedElementsMap}
     */
    static get scopedElements() {
      return {};
    }

    /** @override */
    static render(template, container, options) {
      if (!options || typeof options !== 'object' || !options.scopeName) {
        throw new Error('The `scopeName` option is required.');
      }
      const { scopeName, eventContext } = options;

      const templateCache = getTemplateCache(eventContext);
      const tagsCache = getTagsCache(eventContext);
      const { scopedElements } = this;

      return super.render(template, container, {
        ...options,
        templateFactory: scopedElementsTemplateFactory(
          scopeName,
          scopedElements,
          templateCache,
          tagsCache,
        ),
      });
    }

    /**
     * Defines a scoped element
     *
     * @param {string} tagName
     * @param {typeof HTMLElement} klass
     */
    defineScopedElement(tagName, klass) {
      return defineScopedElement(tagName, klass, getTagsCache(this));
    }

    /**
     * Returns a scoped tag name
     *
     * @deprecated Please, use the instance method instead of the static one. This static method is not able to
     * obtain the tagName of lazy defined elements, while the instance one is.
     * @param {string} tagName
     * @returns {string|undefined}
     */
    static getScopedTagName(tagName) {
      // @ts-ignore
      const klass = this.scopedElements[tagName];

      return klass
        ? registerElement(tagName, klass, getTagsCache(this))
        : getTagsCache(this).get(tagName);
    }

    /**
     * Returns a scoped tag name
     *
     * @param {string} tagName
     * @returns {string|undefined}
     */
    getScopedTagName(tagName) {
      // @ts-ignore
      const klass = this.constructor.scopedElements[tagName];

      return klass
        ? registerElement(tagName, klass, getTagsCache(this))
        : getTagsCache(this).get(tagName);
    }
  };

const ScopedElementsMixin = dedupeMixin(ScopedElementsMixinImplementation);

function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    _typeof = function _typeof(obj) {
      return typeof obj;
    };
  } else {
    _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };
  }

  return _typeof(obj);
}

function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}

function _objectSpread(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? Object(arguments[i]) : {};
    var ownKeys = Object.keys(source);

    if (typeof Object.getOwnPropertySymbols === 'function') {
      ownKeys = ownKeys.concat(Object.getOwnPropertySymbols(source).filter(function (sym) {
        return Object.getOwnPropertyDescriptor(source, sym).enumerable;
      }));
    }

    ownKeys.forEach(function (key) {
      _defineProperty(target, key, source[key]);
    });
  }

  return target;
}

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  }

  return _assertThisInitialized(self);
}

function _getPrototypeOf(o) {
  _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}

function _setPrototypeOf(o, p) {
  _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  if (superClass) _setPrototypeOf(subClass, superClass);
}

var consoleLogger = {
  type: 'logger',
  log: function log(args) {
    this.output('log', args);
  },
  warn: function warn(args) {
    this.output('warn', args);
  },
  error: function error(args) {
    this.output('error', args);
  },
  output: function output(type, args) {
    if (console && console[type]) console[type].apply(console, args);
  }
};

var Logger = function () {
  function Logger(concreteLogger) {
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    _classCallCheck(this, Logger);

    this.init(concreteLogger, options);
  }

  _createClass(Logger, [{
    key: "init",
    value: function init(concreteLogger) {
      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      this.prefix = options.prefix || 'i18next:';
      this.logger = concreteLogger || consoleLogger;
      this.options = options;
      this.debug = options.debug;
    }
  }, {
    key: "setDebug",
    value: function setDebug(bool) {
      this.debug = bool;
    }
  }, {
    key: "log",
    value: function log() {
      for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }

      return this.forward(args, 'log', '', true);
    }
  }, {
    key: "warn",
    value: function warn() {
      for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
        args[_key2] = arguments[_key2];
      }

      return this.forward(args, 'warn', '', true);
    }
  }, {
    key: "error",
    value: function error() {
      for (var _len3 = arguments.length, args = new Array(_len3), _key3 = 0; _key3 < _len3; _key3++) {
        args[_key3] = arguments[_key3];
      }

      return this.forward(args, 'error', '');
    }
  }, {
    key: "deprecate",
    value: function deprecate() {
      for (var _len4 = arguments.length, args = new Array(_len4), _key4 = 0; _key4 < _len4; _key4++) {
        args[_key4] = arguments[_key4];
      }

      return this.forward(args, 'warn', 'WARNING DEPRECATED: ', true);
    }
  }, {
    key: "forward",
    value: function forward(args, lvl, prefix, debugOnly) {
      if (debugOnly && !this.debug) return null;
      if (typeof args[0] === 'string') args[0] = "".concat(prefix).concat(this.prefix, " ").concat(args[0]);
      return this.logger[lvl](args);
    }
  }, {
    key: "create",
    value: function create(moduleName) {
      return new Logger(this.logger, _objectSpread({}, {
        prefix: "".concat(this.prefix, ":").concat(moduleName, ":")
      }, this.options));
    }
  }]);

  return Logger;
}();

var baseLogger = new Logger();

var EventEmitter = function () {
  function EventEmitter() {
    _classCallCheck(this, EventEmitter);

    this.observers = {};
  }

  _createClass(EventEmitter, [{
    key: "on",
    value: function on(events, listener) {
      var _this = this;

      events.split(' ').forEach(function (event) {
        _this.observers[event] = _this.observers[event] || [];

        _this.observers[event].push(listener);
      });
      return this;
    }
  }, {
    key: "off",
    value: function off(event, listener) {
      if (!this.observers[event]) return;

      if (!listener) {
        delete this.observers[event];
        return;
      }

      this.observers[event] = this.observers[event].filter(function (l) {
        return l !== listener;
      });
    }
  }, {
    key: "emit",
    value: function emit(event) {
      for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }

      if (this.observers[event]) {
        var cloned = [].concat(this.observers[event]);
        cloned.forEach(function (observer) {
          observer.apply(void 0, args);
        });
      }

      if (this.observers['*']) {
        var _cloned = [].concat(this.observers['*']);

        _cloned.forEach(function (observer) {
          observer.apply(observer, [event].concat(args));
        });
      }
    }
  }]);

  return EventEmitter;
}();

function defer() {
  var res;
  var rej;
  var promise = new Promise(function (resolve, reject) {
    res = resolve;
    rej = reject;
  });
  promise.resolve = res;
  promise.reject = rej;
  return promise;
}
function makeString(object) {
  if (object == null) return '';
  return '' + object;
}
function copy(a, s, t) {
  a.forEach(function (m) {
    if (s[m]) t[m] = s[m];
  });
}

function getLastOfPath(object, path, Empty) {
  function cleanKey(key) {
    return key && key.indexOf('###') > -1 ? key.replace(/###/g, '.') : key;
  }

  function canNotTraverseDeeper() {
    return !object || typeof object === 'string';
  }

  var stack = typeof path !== 'string' ? [].concat(path) : path.split('.');

  while (stack.length > 1) {
    if (canNotTraverseDeeper()) return {};
    var key = cleanKey(stack.shift());
    if (!object[key] && Empty) object[key] = new Empty();

    if (Object.prototype.hasOwnProperty.call(object, key)) {
      object = object[key];
    } else {
      object = {};
    }
  }

  if (canNotTraverseDeeper()) return {};
  return {
    obj: object,
    k: cleanKey(stack.shift())
  };
}

function setPath(object, path, newValue) {
  var _getLastOfPath = getLastOfPath(object, path, Object),
      obj = _getLastOfPath.obj,
      k = _getLastOfPath.k;

  obj[k] = newValue;
}
function pushPath(object, path, newValue, concat) {
  var _getLastOfPath2 = getLastOfPath(object, path, Object),
      obj = _getLastOfPath2.obj,
      k = _getLastOfPath2.k;

  obj[k] = obj[k] || [];
  if (concat) obj[k] = obj[k].concat(newValue);
  if (!concat) obj[k].push(newValue);
}
function getPath(object, path) {
  var _getLastOfPath3 = getLastOfPath(object, path),
      obj = _getLastOfPath3.obj,
      k = _getLastOfPath3.k;

  if (!obj) return undefined;
  return obj[k];
}
function getPathWithDefaults(data, defaultData, key) {
  var value = getPath(data, key);

  if (value !== undefined) {
    return value;
  }

  return getPath(defaultData, key);
}
function deepExtend(target, source, overwrite) {
  for (var prop in source) {
    if (prop !== '__proto__' && prop !== 'constructor') {
      if (prop in target) {
        if (typeof target[prop] === 'string' || target[prop] instanceof String || typeof source[prop] === 'string' || source[prop] instanceof String) {
          if (overwrite) target[prop] = source[prop];
        } else {
          deepExtend(target[prop], source[prop], overwrite);
        }
      } else {
        target[prop] = source[prop];
      }
    }
  }

  return target;
}
function regexEscape(str) {
  return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
}
var _entityMap = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;',
  '/': '&#x2F;'
};
function escape(data) {
  if (typeof data === 'string') {
    return data.replace(/[&<>"'\/]/g, function (s) {
      return _entityMap[s];
    });
  }

  return data;
}
var isIE10 = typeof window !== 'undefined' && window.navigator && window.navigator.userAgent && window.navigator.userAgent.indexOf('MSIE') > -1;

var ResourceStore = function (_EventEmitter) {
  _inherits(ResourceStore, _EventEmitter);

  function ResourceStore(data) {
    var _this;

    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {
      ns: ['translation'],
      defaultNS: 'translation'
    };

    _classCallCheck(this, ResourceStore);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(ResourceStore).call(this));

    if (isIE10) {
      EventEmitter.call(_assertThisInitialized(_this));
    }

    _this.data = data || {};
    _this.options = options;

    if (_this.options.keySeparator === undefined) {
      _this.options.keySeparator = '.';
    }

    return _this;
  }

  _createClass(ResourceStore, [{
    key: "addNamespaces",
    value: function addNamespaces(ns) {
      if (this.options.ns.indexOf(ns) < 0) {
        this.options.ns.push(ns);
      }
    }
  }, {
    key: "removeNamespaces",
    value: function removeNamespaces(ns) {
      var index = this.options.ns.indexOf(ns);

      if (index > -1) {
        this.options.ns.splice(index, 1);
      }
    }
  }, {
    key: "getResource",
    value: function getResource(lng, ns, key) {
      var options = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};
      var keySeparator = options.keySeparator !== undefined ? options.keySeparator : this.options.keySeparator;
      var path = [lng, ns];
      if (key && typeof key !== 'string') path = path.concat(key);
      if (key && typeof key === 'string') path = path.concat(keySeparator ? key.split(keySeparator) : key);

      if (lng.indexOf('.') > -1) {
        path = lng.split('.');
      }

      return getPath(this.data, path);
    }
  }, {
    key: "addResource",
    value: function addResource(lng, ns, key, value) {
      var options = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : {
        silent: false
      };
      var keySeparator = this.options.keySeparator;
      if (keySeparator === undefined) keySeparator = '.';
      var path = [lng, ns];
      if (key) path = path.concat(keySeparator ? key.split(keySeparator) : key);

      if (lng.indexOf('.') > -1) {
        path = lng.split('.');
        value = ns;
        ns = path[1];
      }

      this.addNamespaces(ns);
      setPath(this.data, path, value);
      if (!options.silent) this.emit('added', lng, ns, key, value);
    }
  }, {
    key: "addResources",
    value: function addResources(lng, ns, resources) {
      var options = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {
        silent: false
      };

      for (var m in resources) {
        if (typeof resources[m] === 'string' || Object.prototype.toString.apply(resources[m]) === '[object Array]') this.addResource(lng, ns, m, resources[m], {
          silent: true
        });
      }

      if (!options.silent) this.emit('added', lng, ns, resources);
    }
  }, {
    key: "addResourceBundle",
    value: function addResourceBundle(lng, ns, resources, deep, overwrite) {
      var options = arguments.length > 5 && arguments[5] !== undefined ? arguments[5] : {
        silent: false
      };
      var path = [lng, ns];

      if (lng.indexOf('.') > -1) {
        path = lng.split('.');
        deep = resources;
        resources = ns;
        ns = path[1];
      }

      this.addNamespaces(ns);
      var pack = getPath(this.data, path) || {};

      if (deep) {
        deepExtend(pack, resources, overwrite);
      } else {
        pack = _objectSpread({}, pack, resources);
      }

      setPath(this.data, path, pack);
      if (!options.silent) this.emit('added', lng, ns, resources);
    }
  }, {
    key: "removeResourceBundle",
    value: function removeResourceBundle(lng, ns) {
      if (this.hasResourceBundle(lng, ns)) {
        delete this.data[lng][ns];
      }

      this.removeNamespaces(ns);
      this.emit('removed', lng, ns);
    }
  }, {
    key: "hasResourceBundle",
    value: function hasResourceBundle(lng, ns) {
      return this.getResource(lng, ns) !== undefined;
    }
  }, {
    key: "getResourceBundle",
    value: function getResourceBundle(lng, ns) {
      if (!ns) ns = this.options.defaultNS;
      if (this.options.compatibilityAPI === 'v1') return _objectSpread({}, {}, this.getResource(lng, ns));
      return this.getResource(lng, ns);
    }
  }, {
    key: "getDataByLanguage",
    value: function getDataByLanguage(lng) {
      return this.data[lng];
    }
  }, {
    key: "toJSON",
    value: function toJSON() {
      return this.data;
    }
  }]);

  return ResourceStore;
}(EventEmitter);

var postProcessor = {
  processors: {},
  addPostProcessor: function addPostProcessor(module) {
    this.processors[module.name] = module;
  },
  handle: function handle(processors, value, key, options, translator) {
    var _this = this;

    processors.forEach(function (processor) {
      if (_this.processors[processor]) value = _this.processors[processor].process(value, key, options, translator);
    });
    return value;
  }
};

var checkedLoadedFor = {};

var Translator = function (_EventEmitter) {
  _inherits(Translator, _EventEmitter);

  function Translator(services) {
    var _this;

    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    _classCallCheck(this, Translator);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Translator).call(this));

    if (isIE10) {
      EventEmitter.call(_assertThisInitialized(_this));
    }

    copy(['resourceStore', 'languageUtils', 'pluralResolver', 'interpolator', 'backendConnector', 'i18nFormat', 'utils'], services, _assertThisInitialized(_this));
    _this.options = options;

    if (_this.options.keySeparator === undefined) {
      _this.options.keySeparator = '.';
    }

    _this.logger = baseLogger.create('translator');
    return _this;
  }

  _createClass(Translator, [{
    key: "changeLanguage",
    value: function changeLanguage(lng) {
      if (lng) this.language = lng;
    }
  }, {
    key: "exists",
    value: function exists(key) {
      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {
        interpolation: {}
      };
      var resolved = this.resolve(key, options);
      return resolved && resolved.res !== undefined;
    }
  }, {
    key: "extractFromKey",
    value: function extractFromKey(key, options) {
      var nsSeparator = options.nsSeparator !== undefined ? options.nsSeparator : this.options.nsSeparator;
      if (nsSeparator === undefined) nsSeparator = ':';
      var keySeparator = options.keySeparator !== undefined ? options.keySeparator : this.options.keySeparator;
      var namespaces = options.ns || this.options.defaultNS;

      if (nsSeparator && key.indexOf(nsSeparator) > -1) {
        var m = key.match(this.interpolator.nestingRegexp);

        if (m && m.length > 0) {
          return {
            key: key,
            namespaces: namespaces
          };
        }

        var parts = key.split(nsSeparator);
        if (nsSeparator !== keySeparator || nsSeparator === keySeparator && this.options.ns.indexOf(parts[0]) > -1) namespaces = parts.shift();
        key = parts.join(keySeparator);
      }

      if (typeof namespaces === 'string') namespaces = [namespaces];
      return {
        key: key,
        namespaces: namespaces
      };
    }
  }, {
    key: "translate",
    value: function translate(keys, options, lastKey) {
      var _this2 = this;

      if (_typeof(options) !== 'object' && this.options.overloadTranslationOptionHandler) {
        options = this.options.overloadTranslationOptionHandler(arguments);
      }

      if (!options) options = {};
      if (keys === undefined || keys === null) return '';
      if (!Array.isArray(keys)) keys = [String(keys)];
      var keySeparator = options.keySeparator !== undefined ? options.keySeparator : this.options.keySeparator;

      var _this$extractFromKey = this.extractFromKey(keys[keys.length - 1], options),
          key = _this$extractFromKey.key,
          namespaces = _this$extractFromKey.namespaces;

      var namespace = namespaces[namespaces.length - 1];
      var lng = options.lng || this.language;
      var appendNamespaceToCIMode = options.appendNamespaceToCIMode || this.options.appendNamespaceToCIMode;

      if (lng && lng.toLowerCase() === 'cimode') {
        if (appendNamespaceToCIMode) {
          var nsSeparator = options.nsSeparator || this.options.nsSeparator;
          return namespace + nsSeparator + key;
        }

        return key;
      }

      var resolved = this.resolve(keys, options);
      var res = resolved && resolved.res;
      var resUsedKey = resolved && resolved.usedKey || key;
      var resExactUsedKey = resolved && resolved.exactUsedKey || key;
      var resType = Object.prototype.toString.apply(res);
      var noObject = ['[object Number]', '[object Function]', '[object RegExp]'];
      var joinArrays = options.joinArrays !== undefined ? options.joinArrays : this.options.joinArrays;
      var handleAsObjectInI18nFormat = !this.i18nFormat || this.i18nFormat.handleAsObject;
      var handleAsObject = typeof res !== 'string' && typeof res !== 'boolean' && typeof res !== 'number';

      if (handleAsObjectInI18nFormat && res && handleAsObject && noObject.indexOf(resType) < 0 && !(typeof joinArrays === 'string' && resType === '[object Array]')) {
        if (!options.returnObjects && !this.options.returnObjects) {
          this.logger.warn('accessing an object - but returnObjects options is not enabled!');
          return this.options.returnedObjectHandler ? this.options.returnedObjectHandler(resUsedKey, res, options) : "key '".concat(key, " (").concat(this.language, ")' returned an object instead of string.");
        }

        if (keySeparator) {
          var resTypeIsArray = resType === '[object Array]';
          var copy = resTypeIsArray ? [] : {};
          var newKeyToUse = resTypeIsArray ? resExactUsedKey : resUsedKey;

          for (var m in res) {
            if (Object.prototype.hasOwnProperty.call(res, m)) {
              var deepKey = "".concat(newKeyToUse).concat(keySeparator).concat(m);
              copy[m] = this.translate(deepKey, _objectSpread({}, options, {
                joinArrays: false,
                ns: namespaces
              }));
              if (copy[m] === deepKey) copy[m] = res[m];
            }
          }

          res = copy;
        }
      } else if (handleAsObjectInI18nFormat && typeof joinArrays === 'string' && resType === '[object Array]') {
        res = res.join(joinArrays);
        if (res) res = this.extendTranslation(res, keys, options, lastKey);
      } else {
        var usedDefault = false;
        var usedKey = false;

        if (!this.isValidLookup(res) && options.defaultValue !== undefined) {
          usedDefault = true;

          if (options.count !== undefined) {
            var suffix = this.pluralResolver.getSuffix(lng, options.count);
            res = options["defaultValue".concat(suffix)];
          }

          if (!res) res = options.defaultValue;
        }

        if (!this.isValidLookup(res)) {
          usedKey = true;
          res = key;
        }

        var updateMissing = options.defaultValue && options.defaultValue !== res && this.options.updateMissing;

        if (usedKey || usedDefault || updateMissing) {
          this.logger.log(updateMissing ? 'updateKey' : 'missingKey', lng, namespace, key, updateMissing ? options.defaultValue : res);

          if (keySeparator) {
            var fk = this.resolve(key, _objectSpread({}, options, {
              keySeparator: false
            }));
            if (fk && fk.res) this.logger.warn('Seems the loaded translations were in flat JSON format instead of nested. Either set keySeparator: false on init or make sure your translations are published in nested format.');
          }

          var lngs = [];
          var fallbackLngs = this.languageUtils.getFallbackCodes(this.options.fallbackLng, options.lng || this.language);

          if (this.options.saveMissingTo === 'fallback' && fallbackLngs && fallbackLngs[0]) {
            for (var i = 0; i < fallbackLngs.length; i++) {
              lngs.push(fallbackLngs[i]);
            }
          } else if (this.options.saveMissingTo === 'all') {
            lngs = this.languageUtils.toResolveHierarchy(options.lng || this.language);
          } else {
            lngs.push(options.lng || this.language);
          }

          var send = function send(l, k) {
            if (_this2.options.missingKeyHandler) {
              _this2.options.missingKeyHandler(l, namespace, k, updateMissing ? options.defaultValue : res, updateMissing, options);
            } else if (_this2.backendConnector && _this2.backendConnector.saveMissing) {
              _this2.backendConnector.saveMissing(l, namespace, k, updateMissing ? options.defaultValue : res, updateMissing, options);
            }

            _this2.emit('missingKey', l, namespace, k, res);
          };

          if (this.options.saveMissing) {
            var needsPluralHandling = options.count !== undefined && typeof options.count !== 'string';

            if (this.options.saveMissingPlurals && needsPluralHandling) {
              lngs.forEach(function (l) {
                var plurals = _this2.pluralResolver.getPluralFormsOfKey(l, key);

                plurals.forEach(function (p) {
                  return send([l], p);
                });
              });
            } else {
              send(lngs, key);
            }
          }
        }

        res = this.extendTranslation(res, keys, options, resolved, lastKey);
        if (usedKey && res === key && this.options.appendNamespaceToMissingKey) res = "".concat(namespace, ":").concat(key);
        if (usedKey && this.options.parseMissingKeyHandler) res = this.options.parseMissingKeyHandler(res);
      }

      return res;
    }
  }, {
    key: "extendTranslation",
    value: function extendTranslation(res, key, options, resolved, lastKey) {
      var _this3 = this;

      if (this.i18nFormat && this.i18nFormat.parse) {
        res = this.i18nFormat.parse(res, options, resolved.usedLng, resolved.usedNS, resolved.usedKey, {
          resolved: resolved
        });
      } else if (!options.skipInterpolation) {
        if (options.interpolation) this.interpolator.init(_objectSpread({}, options, {
          interpolation: _objectSpread({}, this.options.interpolation, options.interpolation)
        }));
        var skipOnVariables = options.interpolation && options.interpolation.skipOnVariables || this.options.interpolation.skipOnVariables;
        var nestBef;

        if (skipOnVariables) {
          var nb = res.match(this.interpolator.nestingRegexp);
          nestBef = nb && nb.length;
        }

        var data = options.replace && typeof options.replace !== 'string' ? options.replace : options;
        if (this.options.interpolation.defaultVariables) data = _objectSpread({}, this.options.interpolation.defaultVariables, data);
        res = this.interpolator.interpolate(res, data, options.lng || this.language, options);

        if (skipOnVariables) {
          var na = res.match(this.interpolator.nestingRegexp);
          var nestAft = na && na.length;
          if (nestBef < nestAft) options.nest = false;
        }

        if (options.nest !== false) res = this.interpolator.nest(res, function () {
          for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
            args[_key] = arguments[_key];
          }

          if (lastKey && lastKey[0] === args[0] && !options.context) {
            _this3.logger.warn("It seems you are nesting recursively key: ".concat(args[0], " in key: ").concat(key[0]));

            return null;
          }

          return _this3.translate.apply(_this3, args.concat([key]));
        }, options);
        if (options.interpolation) this.interpolator.reset();
      }

      var postProcess = options.postProcess || this.options.postProcess;
      var postProcessorNames = typeof postProcess === 'string' ? [postProcess] : postProcess;

      if (res !== undefined && res !== null && postProcessorNames && postProcessorNames.length && options.applyPostProcessor !== false) {
        res = postProcessor.handle(postProcessorNames, res, key, this.options && this.options.postProcessPassResolved ? _objectSpread({
          i18nResolved: resolved
        }, options) : options, this);
      }

      return res;
    }
  }, {
    key: "resolve",
    value: function resolve(keys) {
      var _this4 = this;

      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      var found;
      var usedKey;
      var exactUsedKey;
      var usedLng;
      var usedNS;
      if (typeof keys === 'string') keys = [keys];
      keys.forEach(function (k) {
        if (_this4.isValidLookup(found)) return;

        var extracted = _this4.extractFromKey(k, options);

        var key = extracted.key;
        usedKey = key;
        var namespaces = extracted.namespaces;
        if (_this4.options.fallbackNS) namespaces = namespaces.concat(_this4.options.fallbackNS);
        var needsPluralHandling = options.count !== undefined && typeof options.count !== 'string';
        var needsContextHandling = options.context !== undefined && typeof options.context === 'string' && options.context !== '';
        var codes = options.lngs ? options.lngs : _this4.languageUtils.toResolveHierarchy(options.lng || _this4.language, options.fallbackLng);
        namespaces.forEach(function (ns) {
          if (_this4.isValidLookup(found)) return;
          usedNS = ns;

          if (!checkedLoadedFor["".concat(codes[0], "-").concat(ns)] && _this4.utils && _this4.utils.hasLoadedNamespace && !_this4.utils.hasLoadedNamespace(usedNS)) {
            checkedLoadedFor["".concat(codes[0], "-").concat(ns)] = true;

            _this4.logger.warn("key \"".concat(usedKey, "\" for languages \"").concat(codes.join(', '), "\" won't get resolved as namespace \"").concat(usedNS, "\" was not yet loaded"), 'This means something IS WRONG in your setup. You access the t function before i18next.init / i18next.loadNamespace / i18next.changeLanguage was done. Wait for the callback or Promise to resolve before accessing it!!!');
          }

          codes.forEach(function (code) {
            if (_this4.isValidLookup(found)) return;
            usedLng = code;
            var finalKey = key;
            var finalKeys = [finalKey];

            if (_this4.i18nFormat && _this4.i18nFormat.addLookupKeys) {
              _this4.i18nFormat.addLookupKeys(finalKeys, key, code, ns, options);
            } else {
              var pluralSuffix;
              if (needsPluralHandling) pluralSuffix = _this4.pluralResolver.getSuffix(code, options.count);
              if (needsPluralHandling && needsContextHandling) finalKeys.push(finalKey + pluralSuffix);
              if (needsContextHandling) finalKeys.push(finalKey += "".concat(_this4.options.contextSeparator).concat(options.context));
              if (needsPluralHandling) finalKeys.push(finalKey += pluralSuffix);
            }

            var possibleKey;

            while (possibleKey = finalKeys.pop()) {
              if (!_this4.isValidLookup(found)) {
                exactUsedKey = possibleKey;
                found = _this4.getResource(code, ns, possibleKey, options);
              }
            }
          });
        });
      });
      return {
        res: found,
        usedKey: usedKey,
        exactUsedKey: exactUsedKey,
        usedLng: usedLng,
        usedNS: usedNS
      };
    }
  }, {
    key: "isValidLookup",
    value: function isValidLookup(res) {
      return res !== undefined && !(!this.options.returnNull && res === null) && !(!this.options.returnEmptyString && res === '');
    }
  }, {
    key: "getResource",
    value: function getResource(code, ns, key) {
      var options = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};
      if (this.i18nFormat && this.i18nFormat.getResource) return this.i18nFormat.getResource(code, ns, key, options);
      return this.resourceStore.getResource(code, ns, key, options);
    }
  }]);

  return Translator;
}(EventEmitter);

function capitalize(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

var LanguageUtil = function () {
  function LanguageUtil(options) {
    _classCallCheck(this, LanguageUtil);

    this.options = options;
    this.whitelist = this.options.supportedLngs || false;
    this.supportedLngs = this.options.supportedLngs || false;
    this.logger = baseLogger.create('languageUtils');
  }

  _createClass(LanguageUtil, [{
    key: "getScriptPartFromCode",
    value: function getScriptPartFromCode(code) {
      if (!code || code.indexOf('-') < 0) return null;
      var p = code.split('-');
      if (p.length === 2) return null;
      p.pop();
      if (p[p.length - 1].toLowerCase() === 'x') return null;
      return this.formatLanguageCode(p.join('-'));
    }
  }, {
    key: "getLanguagePartFromCode",
    value: function getLanguagePartFromCode(code) {
      if (!code || code.indexOf('-') < 0) return code;
      var p = code.split('-');
      return this.formatLanguageCode(p[0]);
    }
  }, {
    key: "formatLanguageCode",
    value: function formatLanguageCode(code) {
      if (typeof code === 'string' && code.indexOf('-') > -1) {
        var specialCases = ['hans', 'hant', 'latn', 'cyrl', 'cans', 'mong', 'arab'];
        var p = code.split('-');

        if (this.options.lowerCaseLng) {
          p = p.map(function (part) {
            return part.toLowerCase();
          });
        } else if (p.length === 2) {
          p[0] = p[0].toLowerCase();
          p[1] = p[1].toUpperCase();
          if (specialCases.indexOf(p[1].toLowerCase()) > -1) p[1] = capitalize(p[1].toLowerCase());
        } else if (p.length === 3) {
          p[0] = p[0].toLowerCase();
          if (p[1].length === 2) p[1] = p[1].toUpperCase();
          if (p[0] !== 'sgn' && p[2].length === 2) p[2] = p[2].toUpperCase();
          if (specialCases.indexOf(p[1].toLowerCase()) > -1) p[1] = capitalize(p[1].toLowerCase());
          if (specialCases.indexOf(p[2].toLowerCase()) > -1) p[2] = capitalize(p[2].toLowerCase());
        }

        return p.join('-');
      }

      return this.options.cleanCode || this.options.lowerCaseLng ? code.toLowerCase() : code;
    }
  }, {
    key: "isWhitelisted",
    value: function isWhitelisted(code) {
      this.logger.deprecate('languageUtils.isWhitelisted', 'function "isWhitelisted" will be renamed to "isSupportedCode" in the next major - please make sure to rename it\'s usage asap.');
      return this.isSupportedCode(code);
    }
  }, {
    key: "isSupportedCode",
    value: function isSupportedCode(code) {
      if (this.options.load === 'languageOnly' || this.options.nonExplicitSupportedLngs) {
        code = this.getLanguagePartFromCode(code);
      }

      return !this.supportedLngs || !this.supportedLngs.length || this.supportedLngs.indexOf(code) > -1;
    }
  }, {
    key: "getBestMatchFromCodes",
    value: function getBestMatchFromCodes(codes) {
      var _this = this;

      if (!codes) return null;
      var found;
      codes.forEach(function (code) {
        if (found) return;

        var cleanedLng = _this.formatLanguageCode(code);

        if (!_this.options.supportedLngs || _this.isSupportedCode(cleanedLng)) found = cleanedLng;
      });

      if (!found && this.options.supportedLngs) {
        codes.forEach(function (code) {
          if (found) return;

          var lngOnly = _this.getLanguagePartFromCode(code);

          if (_this.isSupportedCode(lngOnly)) return found = lngOnly;
          found = _this.options.supportedLngs.find(function (supportedLng) {
            if (supportedLng.indexOf(lngOnly) === 0) return supportedLng;
          });
        });
      }

      if (!found) found = this.getFallbackCodes(this.options.fallbackLng)[0];
      return found;
    }
  }, {
    key: "getFallbackCodes",
    value: function getFallbackCodes(fallbacks, code) {
      if (!fallbacks) return [];
      if (typeof fallbacks === 'function') fallbacks = fallbacks(code);
      if (typeof fallbacks === 'string') fallbacks = [fallbacks];
      if (Object.prototype.toString.apply(fallbacks) === '[object Array]') return fallbacks;
      if (!code) return fallbacks["default"] || [];
      var found = fallbacks[code];
      if (!found) found = fallbacks[this.getScriptPartFromCode(code)];
      if (!found) found = fallbacks[this.formatLanguageCode(code)];
      if (!found) found = fallbacks[this.getLanguagePartFromCode(code)];
      if (!found) found = fallbacks["default"];
      return found || [];
    }
  }, {
    key: "toResolveHierarchy",
    value: function toResolveHierarchy(code, fallbackCode) {
      var _this2 = this;

      var fallbackCodes = this.getFallbackCodes(fallbackCode || this.options.fallbackLng || [], code);
      var codes = [];

      var addCode = function addCode(c) {
        if (!c) return;

        if (_this2.isSupportedCode(c)) {
          codes.push(c);
        } else {
          _this2.logger.warn("rejecting language code not found in supportedLngs: ".concat(c));
        }
      };

      if (typeof code === 'string' && code.indexOf('-') > -1) {
        if (this.options.load !== 'languageOnly') addCode(this.formatLanguageCode(code));
        if (this.options.load !== 'languageOnly' && this.options.load !== 'currentOnly') addCode(this.getScriptPartFromCode(code));
        if (this.options.load !== 'currentOnly') addCode(this.getLanguagePartFromCode(code));
      } else if (typeof code === 'string') {
        addCode(this.formatLanguageCode(code));
      }

      fallbackCodes.forEach(function (fc) {
        if (codes.indexOf(fc) < 0) addCode(_this2.formatLanguageCode(fc));
      });
      return codes;
    }
  }]);

  return LanguageUtil;
}();

var sets = [{
  lngs: ['ach', 'ak', 'am', 'arn', 'br', 'fil', 'gun', 'ln', 'mfe', 'mg', 'mi', 'oc', 'pt', 'pt-BR', 'tg', 'ti', 'tr', 'uz', 'wa'],
  nr: [1, 2],
  fc: 1
}, {
  lngs: ['af', 'an', 'ast', 'az', 'bg', 'bn', 'ca', 'da', 'de', 'dev', 'el', 'en', 'eo', 'es', 'et', 'eu', 'fi', 'fo', 'fur', 'fy', 'gl', 'gu', 'ha', 'hi', 'hu', 'hy', 'ia', 'it', 'kn', 'ku', 'lb', 'mai', 'ml', 'mn', 'mr', 'nah', 'nap', 'nb', 'ne', 'nl', 'nn', 'no', 'nso', 'pa', 'pap', 'pms', 'ps', 'pt-PT', 'rm', 'sco', 'se', 'si', 'so', 'son', 'sq', 'sv', 'sw', 'ta', 'te', 'tk', 'ur', 'yo'],
  nr: [1, 2],
  fc: 2
}, {
  lngs: ['ay', 'bo', 'cgg', 'fa', 'ht', 'id', 'ja', 'jbo', 'ka', 'kk', 'km', 'ko', 'ky', 'lo', 'ms', 'sah', 'su', 'th', 'tt', 'ug', 'vi', 'wo', 'zh'],
  nr: [1],
  fc: 3
}, {
  lngs: ['be', 'bs', 'cnr', 'dz', 'hr', 'ru', 'sr', 'uk'],
  nr: [1, 2, 5],
  fc: 4
}, {
  lngs: ['ar'],
  nr: [0, 1, 2, 3, 11, 100],
  fc: 5
}, {
  lngs: ['cs', 'sk'],
  nr: [1, 2, 5],
  fc: 6
}, {
  lngs: ['csb', 'pl'],
  nr: [1, 2, 5],
  fc: 7
}, {
  lngs: ['cy'],
  nr: [1, 2, 3, 8],
  fc: 8
}, {
  lngs: ['fr'],
  nr: [1, 2],
  fc: 9
}, {
  lngs: ['ga'],
  nr: [1, 2, 3, 7, 11],
  fc: 10
}, {
  lngs: ['gd'],
  nr: [1, 2, 3, 20],
  fc: 11
}, {
  lngs: ['is'],
  nr: [1, 2],
  fc: 12
}, {
  lngs: ['jv'],
  nr: [0, 1],
  fc: 13
}, {
  lngs: ['kw'],
  nr: [1, 2, 3, 4],
  fc: 14
}, {
  lngs: ['lt'],
  nr: [1, 2, 10],
  fc: 15
}, {
  lngs: ['lv'],
  nr: [1, 2, 0],
  fc: 16
}, {
  lngs: ['mk'],
  nr: [1, 2],
  fc: 17
}, {
  lngs: ['mnk'],
  nr: [0, 1, 2],
  fc: 18
}, {
  lngs: ['mt'],
  nr: [1, 2, 11, 20],
  fc: 19
}, {
  lngs: ['or'],
  nr: [2, 1],
  fc: 2
}, {
  lngs: ['ro'],
  nr: [1, 2, 20],
  fc: 20
}, {
  lngs: ['sl'],
  nr: [5, 1, 2, 3],
  fc: 21
}, {
  lngs: ['he', 'iw'],
  nr: [1, 2, 20, 21],
  fc: 22
}];
var _rulesPluralsTypes = {
  1: function _(n) {
    return Number(n > 1);
  },
  2: function _(n) {
    return Number(n != 1);
  },
  3: function _(n) {
    return 0;
  },
  4: function _(n) {
    return Number(n % 10 == 1 && n % 100 != 11 ? 0 : n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2);
  },
  5: function _(n) {
    return Number(n == 0 ? 0 : n == 1 ? 1 : n == 2 ? 2 : n % 100 >= 3 && n % 100 <= 10 ? 3 : n % 100 >= 11 ? 4 : 5);
  },
  6: function _(n) {
    return Number(n == 1 ? 0 : n >= 2 && n <= 4 ? 1 : 2);
  },
  7: function _(n) {
    return Number(n == 1 ? 0 : n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2);
  },
  8: function _(n) {
    return Number(n == 1 ? 0 : n == 2 ? 1 : n != 8 && n != 11 ? 2 : 3);
  },
  9: function _(n) {
    return Number(n >= 2);
  },
  10: function _(n) {
    return Number(n == 1 ? 0 : n == 2 ? 1 : n < 7 ? 2 : n < 11 ? 3 : 4);
  },
  11: function _(n) {
    return Number(n == 1 || n == 11 ? 0 : n == 2 || n == 12 ? 1 : n > 2 && n < 20 ? 2 : 3);
  },
  12: function _(n) {
    return Number(n % 10 != 1 || n % 100 == 11);
  },
  13: function _(n) {
    return Number(n !== 0);
  },
  14: function _(n) {
    return Number(n == 1 ? 0 : n == 2 ? 1 : n == 3 ? 2 : 3);
  },
  15: function _(n) {
    return Number(n % 10 == 1 && n % 100 != 11 ? 0 : n % 10 >= 2 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2);
  },
  16: function _(n) {
    return Number(n % 10 == 1 && n % 100 != 11 ? 0 : n !== 0 ? 1 : 2);
  },
  17: function _(n) {
    return Number(n == 1 || n % 10 == 1 && n % 100 != 11 ? 0 : 1);
  },
  18: function _(n) {
    return Number(n == 0 ? 0 : n == 1 ? 1 : 2);
  },
  19: function _(n) {
    return Number(n == 1 ? 0 : n == 0 || n % 100 > 1 && n % 100 < 11 ? 1 : n % 100 > 10 && n % 100 < 20 ? 2 : 3);
  },
  20: function _(n) {
    return Number(n == 1 ? 0 : n == 0 || n % 100 > 0 && n % 100 < 20 ? 1 : 2);
  },
  21: function _(n) {
    return Number(n % 100 == 1 ? 1 : n % 100 == 2 ? 2 : n % 100 == 3 || n % 100 == 4 ? 3 : 0);
  },
  22: function _(n) {
    return Number(n == 1 ? 0 : n == 2 ? 1 : (n < 0 || n > 10) && n % 10 == 0 ? 2 : 3);
  }
};

function createRules() {
  var rules = {};
  sets.forEach(function (set) {
    set.lngs.forEach(function (l) {
      rules[l] = {
        numbers: set.nr,
        plurals: _rulesPluralsTypes[set.fc]
      };
    });
  });
  return rules;
}

var PluralResolver = function () {
  function PluralResolver(languageUtils) {
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    _classCallCheck(this, PluralResolver);

    this.languageUtils = languageUtils;
    this.options = options;
    this.logger = baseLogger.create('pluralResolver');
    this.rules = createRules();
  }

  _createClass(PluralResolver, [{
    key: "addRule",
    value: function addRule(lng, obj) {
      this.rules[lng] = obj;
    }
  }, {
    key: "getRule",
    value: function getRule(code) {
      return this.rules[code] || this.rules[this.languageUtils.getLanguagePartFromCode(code)];
    }
  }, {
    key: "needsPlural",
    value: function needsPlural(code) {
      var rule = this.getRule(code);
      return rule && rule.numbers.length > 1;
    }
  }, {
    key: "getPluralFormsOfKey",
    value: function getPluralFormsOfKey(code, key) {
      var _this = this;

      var ret = [];
      var rule = this.getRule(code);
      if (!rule) return ret;
      rule.numbers.forEach(function (n) {
        var suffix = _this.getSuffix(code, n);

        ret.push("".concat(key).concat(suffix));
      });
      return ret;
    }
  }, {
    key: "getSuffix",
    value: function getSuffix(code, count) {
      var _this2 = this;

      var rule = this.getRule(code);

      if (rule) {
        var idx = rule.noAbs ? rule.plurals(count) : rule.plurals(Math.abs(count));
        var suffix = rule.numbers[idx];

        if (this.options.simplifyPluralSuffix && rule.numbers.length === 2 && rule.numbers[0] === 1) {
          if (suffix === 2) {
            suffix = 'plural';
          } else if (suffix === 1) {
            suffix = '';
          }
        }

        var returnSuffix = function returnSuffix() {
          return _this2.options.prepend && suffix.toString() ? _this2.options.prepend + suffix.toString() : suffix.toString();
        };

        if (this.options.compatibilityJSON === 'v1') {
          if (suffix === 1) return '';
          if (typeof suffix === 'number') return "_plural_".concat(suffix.toString());
          return returnSuffix();
        } else if (this.options.compatibilityJSON === 'v2') {
          return returnSuffix();
        } else if (this.options.simplifyPluralSuffix && rule.numbers.length === 2 && rule.numbers[0] === 1) {
          return returnSuffix();
        }

        return this.options.prepend && idx.toString() ? this.options.prepend + idx.toString() : idx.toString();
      }

      this.logger.warn("no plural rule found for: ".concat(code));
      return '';
    }
  }]);

  return PluralResolver;
}();

var Interpolator = function () {
  function Interpolator() {
    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    _classCallCheck(this, Interpolator);

    this.logger = baseLogger.create('interpolator');
    this.options = options;

    this.format = options.interpolation && options.interpolation.format || function (value) {
      return value;
    };

    this.init(options);
  }

  _createClass(Interpolator, [{
    key: "init",
    value: function init() {
      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      if (!options.interpolation) options.interpolation = {
        escapeValue: true
      };
      var iOpts = options.interpolation;
      this.escape = iOpts.escape !== undefined ? iOpts.escape : escape;
      this.escapeValue = iOpts.escapeValue !== undefined ? iOpts.escapeValue : true;
      this.useRawValueToEscape = iOpts.useRawValueToEscape !== undefined ? iOpts.useRawValueToEscape : false;
      this.prefix = iOpts.prefix ? regexEscape(iOpts.prefix) : iOpts.prefixEscaped || '{{';
      this.suffix = iOpts.suffix ? regexEscape(iOpts.suffix) : iOpts.suffixEscaped || '}}';
      this.formatSeparator = iOpts.formatSeparator ? iOpts.formatSeparator : iOpts.formatSeparator || ',';
      this.unescapePrefix = iOpts.unescapeSuffix ? '' : iOpts.unescapePrefix || '-';
      this.unescapeSuffix = this.unescapePrefix ? '' : iOpts.unescapeSuffix || '';
      this.nestingPrefix = iOpts.nestingPrefix ? regexEscape(iOpts.nestingPrefix) : iOpts.nestingPrefixEscaped || regexEscape('$t(');
      this.nestingSuffix = iOpts.nestingSuffix ? regexEscape(iOpts.nestingSuffix) : iOpts.nestingSuffixEscaped || regexEscape(')');
      this.nestingOptionsSeparator = iOpts.nestingOptionsSeparator ? iOpts.nestingOptionsSeparator : iOpts.nestingOptionsSeparator || ',';
      this.maxReplaces = iOpts.maxReplaces ? iOpts.maxReplaces : 1000;
      this.alwaysFormat = iOpts.alwaysFormat !== undefined ? iOpts.alwaysFormat : false;
      this.resetRegExp();
    }
  }, {
    key: "reset",
    value: function reset() {
      if (this.options) this.init(this.options);
    }
  }, {
    key: "resetRegExp",
    value: function resetRegExp() {
      var regexpStr = "".concat(this.prefix, "(.+?)").concat(this.suffix);
      this.regexp = new RegExp(regexpStr, 'g');
      var regexpUnescapeStr = "".concat(this.prefix).concat(this.unescapePrefix, "(.+?)").concat(this.unescapeSuffix).concat(this.suffix);
      this.regexpUnescape = new RegExp(regexpUnescapeStr, 'g');
      var nestingRegexpStr = "".concat(this.nestingPrefix, "(.+?)").concat(this.nestingSuffix);
      this.nestingRegexp = new RegExp(nestingRegexpStr, 'g');
    }
  }, {
    key: "interpolate",
    value: function interpolate(str, data, lng, options) {
      var _this = this;

      var match;
      var value;
      var replaces;
      var defaultData = this.options && this.options.interpolation && this.options.interpolation.defaultVariables || {};

      function regexSafe(val) {
        return val.replace(/\$/g, '$$$$');
      }

      var handleFormat = function handleFormat(key) {
        if (key.indexOf(_this.formatSeparator) < 0) {
          var path = getPathWithDefaults(data, defaultData, key);
          return _this.alwaysFormat ? _this.format(path, undefined, lng) : path;
        }

        var p = key.split(_this.formatSeparator);
        var k = p.shift().trim();
        var f = p.join(_this.formatSeparator).trim();
        return _this.format(getPathWithDefaults(data, defaultData, k), f, lng, options);
      };

      this.resetRegExp();
      var missingInterpolationHandler = options && options.missingInterpolationHandler || this.options.missingInterpolationHandler;
      var skipOnVariables = options && options.interpolation && options.interpolation.skipOnVariables || this.options.interpolation.skipOnVariables;
      var todos = [{
        regex: this.regexpUnescape,
        safeValue: function safeValue(val) {
          return regexSafe(val);
        }
      }, {
        regex: this.regexp,
        safeValue: function safeValue(val) {
          return _this.escapeValue ? regexSafe(_this.escape(val)) : regexSafe(val);
        }
      }];
      todos.forEach(function (todo) {
        replaces = 0;

        while (match = todo.regex.exec(str)) {
          value = handleFormat(match[1].trim());

          if (value === undefined) {
            if (typeof missingInterpolationHandler === 'function') {
              var temp = missingInterpolationHandler(str, match, options);
              value = typeof temp === 'string' ? temp : '';
            } else if (skipOnVariables) {
              value = match[0];
              continue;
            } else {
              _this.logger.warn("missed to pass in variable ".concat(match[1], " for interpolating ").concat(str));

              value = '';
            }
          } else if (typeof value !== 'string' && !_this.useRawValueToEscape) {
            value = makeString(value);
          }

          str = str.replace(match[0], todo.safeValue(value));
          todo.regex.lastIndex = 0;
          replaces++;

          if (replaces >= _this.maxReplaces) {
            break;
          }
        }
      });
      return str;
    }
  }, {
    key: "nest",
    value: function nest(str, fc) {
      var _this2 = this;

      var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
      var match;
      var value;

      var clonedOptions = _objectSpread({}, options);

      clonedOptions.applyPostProcessor = false;
      delete clonedOptions.defaultValue;

      function handleHasOptions(key, inheritedOptions) {
        var sep = this.nestingOptionsSeparator;
        if (key.indexOf(sep) < 0) return key;
        var c = key.split(new RegExp("".concat(sep, "[ ]*{")));
        var optionsString = "{".concat(c[1]);
        key = c[0];
        optionsString = this.interpolate(optionsString, clonedOptions);
        optionsString = optionsString.replace(/'/g, '"');

        try {
          clonedOptions = JSON.parse(optionsString);
          if (inheritedOptions) clonedOptions = _objectSpread({}, inheritedOptions, clonedOptions);
        } catch (e) {
          this.logger.warn("failed parsing options string in nesting for key ".concat(key), e);
          return "".concat(key).concat(sep).concat(optionsString);
        }

        delete clonedOptions.defaultValue;
        return key;
      }

      while (match = this.nestingRegexp.exec(str)) {
        var formatters = [];
        var doReduce = false;

        if (match[0].includes(this.formatSeparator) && !/{.*}/.test(match[1])) {
          var r = match[1].split(this.formatSeparator).map(function (elem) {
            return elem.trim();
          });
          match[1] = r.shift();
          formatters = r;
          doReduce = true;
        }

        value = fc(handleHasOptions.call(this, match[1].trim(), clonedOptions), clonedOptions);
        if (value && match[0] === str && typeof value !== 'string') return value;
        if (typeof value !== 'string') value = makeString(value);

        if (!value) {
          this.logger.warn("missed to resolve ".concat(match[1], " for nesting ").concat(str));
          value = '';
        }

        if (doReduce) {
          value = formatters.reduce(function (v, f) {
            return _this2.format(v, f, options.lng, options);
          }, value.trim());
        }

        str = str.replace(match[0], value);
        this.regexp.lastIndex = 0;
      }

      return str;
    }
  }]);

  return Interpolator;
}();

function remove(arr, what) {
  var found = arr.indexOf(what);

  while (found !== -1) {
    arr.splice(found, 1);
    found = arr.indexOf(what);
  }
}

var Connector = function (_EventEmitter) {
  _inherits(Connector, _EventEmitter);

  function Connector(backend, store, services) {
    var _this;

    var options = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};

    _classCallCheck(this, Connector);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Connector).call(this));

    if (isIE10) {
      EventEmitter.call(_assertThisInitialized(_this));
    }

    _this.backend = backend;
    _this.store = store;
    _this.services = services;
    _this.languageUtils = services.languageUtils;
    _this.options = options;
    _this.logger = baseLogger.create('backendConnector');
    _this.state = {};
    _this.queue = [];

    if (_this.backend && _this.backend.init) {
      _this.backend.init(services, options.backend, options);
    }

    return _this;
  }

  _createClass(Connector, [{
    key: "queueLoad",
    value: function queueLoad(languages, namespaces, options, callback) {
      var _this2 = this;

      var toLoad = [];
      var pending = [];
      var toLoadLanguages = [];
      var toLoadNamespaces = [];
      languages.forEach(function (lng) {
        var hasAllNamespaces = true;
        namespaces.forEach(function (ns) {
          var name = "".concat(lng, "|").concat(ns);

          if (!options.reload && _this2.store.hasResourceBundle(lng, ns)) {
            _this2.state[name] = 2;
          } else if (_this2.state[name] < 0) ; else if (_this2.state[name] === 1) {
            if (pending.indexOf(name) < 0) pending.push(name);
          } else {
            _this2.state[name] = 1;
            hasAllNamespaces = false;
            if (pending.indexOf(name) < 0) pending.push(name);
            if (toLoad.indexOf(name) < 0) toLoad.push(name);
            if (toLoadNamespaces.indexOf(ns) < 0) toLoadNamespaces.push(ns);
          }
        });
        if (!hasAllNamespaces) toLoadLanguages.push(lng);
      });

      if (toLoad.length || pending.length) {
        this.queue.push({
          pending: pending,
          loaded: {},
          errors: [],
          callback: callback
        });
      }

      return {
        toLoad: toLoad,
        pending: pending,
        toLoadLanguages: toLoadLanguages,
        toLoadNamespaces: toLoadNamespaces
      };
    }
  }, {
    key: "loaded",
    value: function loaded(name, err, data) {
      var s = name.split('|');
      var lng = s[0];
      var ns = s[1];
      if (err) this.emit('failedLoading', lng, ns, err);

      if (data) {
        this.store.addResourceBundle(lng, ns, data);
      }

      this.state[name] = err ? -1 : 2;
      var loaded = {};
      this.queue.forEach(function (q) {
        pushPath(q.loaded, [lng], ns);
        remove(q.pending, name);
        if (err) q.errors.push(err);

        if (q.pending.length === 0 && !q.done) {
          Object.keys(q.loaded).forEach(function (l) {
            if (!loaded[l]) loaded[l] = [];

            if (q.loaded[l].length) {
              q.loaded[l].forEach(function (ns) {
                if (loaded[l].indexOf(ns) < 0) loaded[l].push(ns);
              });
            }
          });
          q.done = true;

          if (q.errors.length) {
            q.callback(q.errors);
          } else {
            q.callback();
          }
        }
      });
      this.emit('loaded', loaded);
      this.queue = this.queue.filter(function (q) {
        return !q.done;
      });
    }
  }, {
    key: "read",
    value: function read(lng, ns, fcName) {
      var _this3 = this;

      var tried = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 0;
      var wait = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : 350;
      var callback = arguments.length > 5 ? arguments[5] : undefined;
      if (!lng.length) return callback(null, {});
      return this.backend[fcName](lng, ns, function (err, data) {
        if (err && data && tried < 5) {
          setTimeout(function () {
            _this3.read.call(_this3, lng, ns, fcName, tried + 1, wait * 2, callback);
          }, wait);
          return;
        }

        callback(err, data);
      });
    }
  }, {
    key: "prepareLoading",
    value: function prepareLoading(languages, namespaces) {
      var _this4 = this;

      var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
      var callback = arguments.length > 3 ? arguments[3] : undefined;

      if (!this.backend) {
        this.logger.warn('No backend was added via i18next.use. Will not load resources.');
        return callback && callback();
      }

      if (typeof languages === 'string') languages = this.languageUtils.toResolveHierarchy(languages);
      if (typeof namespaces === 'string') namespaces = [namespaces];
      var toLoad = this.queueLoad(languages, namespaces, options, callback);

      if (!toLoad.toLoad.length) {
        if (!toLoad.pending.length) callback();
        return null;
      }

      toLoad.toLoad.forEach(function (name) {
        _this4.loadOne(name);
      });
    }
  }, {
    key: "load",
    value: function load(languages, namespaces, callback) {
      this.prepareLoading(languages, namespaces, {}, callback);
    }
  }, {
    key: "reload",
    value: function reload(languages, namespaces, callback) {
      this.prepareLoading(languages, namespaces, {
        reload: true
      }, callback);
    }
  }, {
    key: "loadOne",
    value: function loadOne(name) {
      var _this5 = this;

      var prefix = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
      var s = name.split('|');
      var lng = s[0];
      var ns = s[1];
      this.read(lng, ns, 'read', undefined, undefined, function (err, data) {
        if (err) _this5.logger.warn("".concat(prefix, "loading namespace ").concat(ns, " for language ").concat(lng, " failed"), err);
        if (!err && data) _this5.logger.log("".concat(prefix, "loaded namespace ").concat(ns, " for language ").concat(lng), data);

        _this5.loaded(name, err, data);
      });
    }
  }, {
    key: "saveMissing",
    value: function saveMissing(languages, namespace, key, fallbackValue, isUpdate) {
      var options = arguments.length > 5 && arguments[5] !== undefined ? arguments[5] : {};

      if (this.services.utils && this.services.utils.hasLoadedNamespace && !this.services.utils.hasLoadedNamespace(namespace)) {
        this.logger.warn("did not save key \"".concat(key, "\" as the namespace \"").concat(namespace, "\" was not yet loaded"), 'This means something IS WRONG in your setup. You access the t function before i18next.init / i18next.loadNamespace / i18next.changeLanguage was done. Wait for the callback or Promise to resolve before accessing it!!!');
        return;
      }

      if (key === undefined || key === null || key === '') return;

      if (this.backend && this.backend.create) {
        this.backend.create(languages, namespace, key, fallbackValue, null, _objectSpread({}, options, {
          isUpdate: isUpdate
        }));
      }

      if (!languages || !languages[0]) return;
      this.store.addResource(languages[0], namespace, key, fallbackValue);
    }
  }]);

  return Connector;
}(EventEmitter);

function get() {
  return {
    debug: false,
    initImmediate: true,
    ns: ['translation'],
    defaultNS: ['translation'],
    fallbackLng: ['dev'],
    fallbackNS: false,
    whitelist: false,
    nonExplicitWhitelist: false,
    supportedLngs: false,
    nonExplicitSupportedLngs: false,
    load: 'all',
    preload: false,
    simplifyPluralSuffix: true,
    keySeparator: '.',
    nsSeparator: ':',
    pluralSeparator: '_',
    contextSeparator: '_',
    partialBundledLanguages: false,
    saveMissing: false,
    updateMissing: false,
    saveMissingTo: 'fallback',
    saveMissingPlurals: true,
    missingKeyHandler: false,
    missingInterpolationHandler: false,
    postProcess: false,
    postProcessPassResolved: false,
    returnNull: true,
    returnEmptyString: true,
    returnObjects: false,
    joinArrays: false,
    returnedObjectHandler: false,
    parseMissingKeyHandler: false,
    appendNamespaceToMissingKey: false,
    appendNamespaceToCIMode: false,
    overloadTranslationOptionHandler: function handle(args) {
      var ret = {};
      if (_typeof(args[1]) === 'object') ret = args[1];
      if (typeof args[1] === 'string') ret.defaultValue = args[1];
      if (typeof args[2] === 'string') ret.tDescription = args[2];

      if (_typeof(args[2]) === 'object' || _typeof(args[3]) === 'object') {
        var options = args[3] || args[2];
        Object.keys(options).forEach(function (key) {
          ret[key] = options[key];
        });
      }

      return ret;
    },
    interpolation: {
      escapeValue: true,
      format: function format(value, _format, lng, options) {
        return value;
      },
      prefix: '{{',
      suffix: '}}',
      formatSeparator: ',',
      unescapePrefix: '-',
      nestingPrefix: '$t(',
      nestingSuffix: ')',
      nestingOptionsSeparator: ',',
      maxReplaces: 1000,
      skipOnVariables: false
    }
  };
}
function transformOptions(options) {
  if (typeof options.ns === 'string') options.ns = [options.ns];
  if (typeof options.fallbackLng === 'string') options.fallbackLng = [options.fallbackLng];
  if (typeof options.fallbackNS === 'string') options.fallbackNS = [options.fallbackNS];

  if (options.whitelist) {
    if (options.whitelist && options.whitelist.indexOf('cimode') < 0) {
      options.whitelist = options.whitelist.concat(['cimode']);
    }

    options.supportedLngs = options.whitelist;
  }

  if (options.nonExplicitWhitelist) {
    options.nonExplicitSupportedLngs = options.nonExplicitWhitelist;
  }

  if (options.supportedLngs && options.supportedLngs.indexOf('cimode') < 0) {
    options.supportedLngs = options.supportedLngs.concat(['cimode']);
  }

  return options;
}

function noop() {}

var I18n = function (_EventEmitter) {
  _inherits(I18n, _EventEmitter);

  function I18n() {
    var _this;

    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var callback = arguments.length > 1 ? arguments[1] : undefined;

    _classCallCheck(this, I18n);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(I18n).call(this));

    if (isIE10) {
      EventEmitter.call(_assertThisInitialized(_this));
    }

    _this.options = transformOptions(options);
    _this.services = {};
    _this.logger = baseLogger;
    _this.modules = {
      external: []
    };

    if (callback && !_this.isInitialized && !options.isClone) {
      if (!_this.options.initImmediate) {
        _this.init(options, callback);

        return _possibleConstructorReturn(_this, _assertThisInitialized(_this));
      }

      setTimeout(function () {
        _this.init(options, callback);
      }, 0);
    }

    return _this;
  }

  _createClass(I18n, [{
    key: "init",
    value: function init() {
      var _this2 = this;

      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      var callback = arguments.length > 1 ? arguments[1] : undefined;

      if (typeof options === 'function') {
        callback = options;
        options = {};
      }

      if (options.whitelist && !options.supportedLngs) {
        this.logger.deprecate('whitelist', 'option "whitelist" will be renamed to "supportedLngs" in the next major - please make sure to rename this option asap.');
      }

      if (options.nonExplicitWhitelist && !options.nonExplicitSupportedLngs) {
        this.logger.deprecate('whitelist', 'options "nonExplicitWhitelist" will be renamed to "nonExplicitSupportedLngs" in the next major - please make sure to rename this option asap.');
      }

      this.options = _objectSpread({}, get(), this.options, transformOptions(options));
      this.format = this.options.interpolation.format;
      if (!callback) callback = noop;

      function createClassOnDemand(ClassOrObject) {
        if (!ClassOrObject) return null;
        if (typeof ClassOrObject === 'function') return new ClassOrObject();
        return ClassOrObject;
      }

      if (!this.options.isClone) {
        if (this.modules.logger) {
          baseLogger.init(createClassOnDemand(this.modules.logger), this.options);
        } else {
          baseLogger.init(null, this.options);
        }

        var lu = new LanguageUtil(this.options);
        this.store = new ResourceStore(this.options.resources, this.options);
        var s = this.services;
        s.logger = baseLogger;
        s.resourceStore = this.store;
        s.languageUtils = lu;
        s.pluralResolver = new PluralResolver(lu, {
          prepend: this.options.pluralSeparator,
          compatibilityJSON: this.options.compatibilityJSON,
          simplifyPluralSuffix: this.options.simplifyPluralSuffix
        });
        s.interpolator = new Interpolator(this.options);
        s.utils = {
          hasLoadedNamespace: this.hasLoadedNamespace.bind(this)
        };
        s.backendConnector = new Connector(createClassOnDemand(this.modules.backend), s.resourceStore, s, this.options);
        s.backendConnector.on('*', function (event) {
          for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
            args[_key - 1] = arguments[_key];
          }

          _this2.emit.apply(_this2, [event].concat(args));
        });

        if (this.modules.languageDetector) {
          s.languageDetector = createClassOnDemand(this.modules.languageDetector);
          s.languageDetector.init(s, this.options.detection, this.options);
        }

        if (this.modules.i18nFormat) {
          s.i18nFormat = createClassOnDemand(this.modules.i18nFormat);
          if (s.i18nFormat.init) s.i18nFormat.init(this);
        }

        this.translator = new Translator(this.services, this.options);
        this.translator.on('*', function (event) {
          for (var _len2 = arguments.length, args = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
            args[_key2 - 1] = arguments[_key2];
          }

          _this2.emit.apply(_this2, [event].concat(args));
        });
        this.modules.external.forEach(function (m) {
          if (m.init) m.init(_this2);
        });
      }

      if (this.options.fallbackLng && !this.services.languageDetector && !this.options.lng) {
        var codes = this.services.languageUtils.getFallbackCodes(this.options.fallbackLng);
        if (codes.length > 0 && codes[0] !== 'dev') this.options.lng = codes[0];
      }

      if (!this.services.languageDetector && !this.options.lng) {
        this.logger.warn('init: no languageDetector is used and no lng is defined');
      }

      var storeApi = ['getResource', 'hasResourceBundle', 'getResourceBundle', 'getDataByLanguage'];
      storeApi.forEach(function (fcName) {
        _this2[fcName] = function () {
          var _this2$store;

          return (_this2$store = _this2.store)[fcName].apply(_this2$store, arguments);
        };
      });
      var storeApiChained = ['addResource', 'addResources', 'addResourceBundle', 'removeResourceBundle'];
      storeApiChained.forEach(function (fcName) {
        _this2[fcName] = function () {
          var _this2$store2;

          (_this2$store2 = _this2.store)[fcName].apply(_this2$store2, arguments);

          return _this2;
        };
      });
      var deferred = defer();

      var load = function load() {
        var finish = function finish(err, t) {
          _this2.isInitialized = true;
          if (!_this2.options.isClone) _this2.logger.log('initialized', _this2.options);

          _this2.emit('initialized', _this2.options);

          deferred.resolve(t);
          callback(err, t);
        };

        if (_this2.languages && _this2.options.compatibilityAPI !== 'v1') return finish(null, _this2.t.bind(_this2));

        _this2.changeLanguage(_this2.options.lng, finish);
      };

      if (this.options.resources || !this.options.initImmediate) {
        load();
      } else {
        setTimeout(load, 0);
      }

      return deferred;
    }
  }, {
    key: "loadResources",
    value: function loadResources(language) {
      var _this3 = this;

      var callback = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : noop;
      var usedCallback = callback;
      var usedLng = typeof language === 'string' ? language : this.language;
      if (typeof language === 'function') usedCallback = language;

      if (!this.options.resources || this.options.partialBundledLanguages) {
        if (usedLng && usedLng.toLowerCase() === 'cimode') return usedCallback();
        var toLoad = [];

        var append = function append(lng) {
          if (!lng) return;

          var lngs = _this3.services.languageUtils.toResolveHierarchy(lng);

          lngs.forEach(function (l) {
            if (toLoad.indexOf(l) < 0) toLoad.push(l);
          });
        };

        if (!usedLng) {
          var fallbacks = this.services.languageUtils.getFallbackCodes(this.options.fallbackLng);
          fallbacks.forEach(function (l) {
            return append(l);
          });
        } else {
          append(usedLng);
        }

        if (this.options.preload) {
          this.options.preload.forEach(function (l) {
            return append(l);
          });
        }

        this.services.backendConnector.load(toLoad, this.options.ns, usedCallback);
      } else {
        usedCallback(null);
      }
    }
  }, {
    key: "reloadResources",
    value: function reloadResources(lngs, ns, callback) {
      var deferred = defer();
      if (!lngs) lngs = this.languages;
      if (!ns) ns = this.options.ns;
      if (!callback) callback = noop;
      this.services.backendConnector.reload(lngs, ns, function (err) {
        deferred.resolve();
        callback(err);
      });
      return deferred;
    }
  }, {
    key: "use",
    value: function use(module) {
      if (!module) throw new Error('You are passing an undefined module! Please check the object you are passing to i18next.use()');
      if (!module.type) throw new Error('You are passing a wrong module! Please check the object you are passing to i18next.use()');

      if (module.type === 'backend') {
        this.modules.backend = module;
      }

      if (module.type === 'logger' || module.log && module.warn && module.error) {
        this.modules.logger = module;
      }

      if (module.type === 'languageDetector') {
        this.modules.languageDetector = module;
      }

      if (module.type === 'i18nFormat') {
        this.modules.i18nFormat = module;
      }

      if (module.type === 'postProcessor') {
        postProcessor.addPostProcessor(module);
      }

      if (module.type === '3rdParty') {
        this.modules.external.push(module);
      }

      return this;
    }
  }, {
    key: "changeLanguage",
    value: function changeLanguage(lng, callback) {
      var _this4 = this;

      this.isLanguageChangingTo = lng;
      var deferred = defer();
      this.emit('languageChanging', lng);

      var done = function done(err, l) {
        if (l) {
          _this4.language = l;
          _this4.languages = _this4.services.languageUtils.toResolveHierarchy(l);

          _this4.translator.changeLanguage(l);

          _this4.isLanguageChangingTo = undefined;

          _this4.emit('languageChanged', l);

          _this4.logger.log('languageChanged', l);
        } else {
          _this4.isLanguageChangingTo = undefined;
        }

        deferred.resolve(function () {
          return _this4.t.apply(_this4, arguments);
        });
        if (callback) callback(err, function () {
          return _this4.t.apply(_this4, arguments);
        });
      };

      var setLng = function setLng(lngs) {
        var l = typeof lngs === 'string' ? lngs : _this4.services.languageUtils.getBestMatchFromCodes(lngs);

        if (l) {
          if (!_this4.language) {
            _this4.language = l;
            _this4.languages = _this4.services.languageUtils.toResolveHierarchy(l);
          }

          if (!_this4.translator.language) _this4.translator.changeLanguage(l);
          if (_this4.services.languageDetector) _this4.services.languageDetector.cacheUserLanguage(l);
        }

        _this4.loadResources(l, function (err) {
          done(err, l);
        });
      };

      if (!lng && this.services.languageDetector && !this.services.languageDetector.async) {
        setLng(this.services.languageDetector.detect());
      } else if (!lng && this.services.languageDetector && this.services.languageDetector.async) {
        this.services.languageDetector.detect(setLng);
      } else {
        setLng(lng);
      }

      return deferred;
    }
  }, {
    key: "getFixedT",
    value: function getFixedT(lng, ns) {
      var _this5 = this;

      var fixedT = function fixedT(key, opts) {
        var options;

        if (_typeof(opts) !== 'object') {
          for (var _len3 = arguments.length, rest = new Array(_len3 > 2 ? _len3 - 2 : 0), _key3 = 2; _key3 < _len3; _key3++) {
            rest[_key3 - 2] = arguments[_key3];
          }

          options = _this5.options.overloadTranslationOptionHandler([key, opts].concat(rest));
        } else {
          options = _objectSpread({}, opts);
        }

        options.lng = options.lng || fixedT.lng;
        options.lngs = options.lngs || fixedT.lngs;
        options.ns = options.ns || fixedT.ns;
        return _this5.t(key, options);
      };

      if (typeof lng === 'string') {
        fixedT.lng = lng;
      } else {
        fixedT.lngs = lng;
      }

      fixedT.ns = ns;
      return fixedT;
    }
  }, {
    key: "t",
    value: function t() {
      var _this$translator;

      return this.translator && (_this$translator = this.translator).translate.apply(_this$translator, arguments);
    }
  }, {
    key: "exists",
    value: function exists() {
      var _this$translator2;

      return this.translator && (_this$translator2 = this.translator).exists.apply(_this$translator2, arguments);
    }
  }, {
    key: "setDefaultNamespace",
    value: function setDefaultNamespace(ns) {
      this.options.defaultNS = ns;
    }
  }, {
    key: "hasLoadedNamespace",
    value: function hasLoadedNamespace(ns) {
      var _this6 = this;

      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

      if (!this.isInitialized) {
        this.logger.warn('hasLoadedNamespace: i18next was not initialized', this.languages);
        return false;
      }

      if (!this.languages || !this.languages.length) {
        this.logger.warn('hasLoadedNamespace: i18n.languages were undefined or empty', this.languages);
        return false;
      }

      var lng = this.languages[0];
      var fallbackLng = this.options ? this.options.fallbackLng : false;
      var lastLng = this.languages[this.languages.length - 1];
      if (lng.toLowerCase() === 'cimode') return true;

      var loadNotPending = function loadNotPending(l, n) {
        var loadState = _this6.services.backendConnector.state["".concat(l, "|").concat(n)];

        return loadState === -1 || loadState === 2;
      };

      if (options.precheck) {
        var preResult = options.precheck(this, loadNotPending);
        if (preResult !== undefined) return preResult;
      }

      if (this.hasResourceBundle(lng, ns)) return true;
      if (!this.services.backendConnector.backend) return true;
      if (loadNotPending(lng, ns) && (!fallbackLng || loadNotPending(lastLng, ns))) return true;
      return false;
    }
  }, {
    key: "loadNamespaces",
    value: function loadNamespaces(ns, callback) {
      var _this7 = this;

      var deferred = defer();

      if (!this.options.ns) {
        callback && callback();
        return Promise.resolve();
      }

      if (typeof ns === 'string') ns = [ns];
      ns.forEach(function (n) {
        if (_this7.options.ns.indexOf(n) < 0) _this7.options.ns.push(n);
      });
      this.loadResources(function (err) {
        deferred.resolve();
        if (callback) callback(err);
      });
      return deferred;
    }
  }, {
    key: "loadLanguages",
    value: function loadLanguages(lngs, callback) {
      var deferred = defer();
      if (typeof lngs === 'string') lngs = [lngs];
      var preloaded = this.options.preload || [];
      var newLngs = lngs.filter(function (lng) {
        return preloaded.indexOf(lng) < 0;
      });

      if (!newLngs.length) {
        if (callback) callback();
        return Promise.resolve();
      }

      this.options.preload = preloaded.concat(newLngs);
      this.loadResources(function (err) {
        deferred.resolve();
        if (callback) callback(err);
      });
      return deferred;
    }
  }, {
    key: "dir",
    value: function dir(lng) {
      if (!lng) lng = this.languages && this.languages.length > 0 ? this.languages[0] : this.language;
      if (!lng) return 'rtl';
      var rtlLngs = ['ar', 'shu', 'sqr', 'ssh', 'xaa', 'yhd', 'yud', 'aao', 'abh', 'abv', 'acm', 'acq', 'acw', 'acx', 'acy', 'adf', 'ads', 'aeb', 'aec', 'afb', 'ajp', 'apc', 'apd', 'arb', 'arq', 'ars', 'ary', 'arz', 'auz', 'avl', 'ayh', 'ayl', 'ayn', 'ayp', 'bbz', 'pga', 'he', 'iw', 'ps', 'pbt', 'pbu', 'pst', 'prp', 'prd', 'ug', 'ur', 'ydd', 'yds', 'yih', 'ji', 'yi', 'hbo', 'men', 'xmn', 'fa', 'jpr', 'peo', 'pes', 'prs', 'dv', 'sam'];
      return rtlLngs.indexOf(this.services.languageUtils.getLanguagePartFromCode(lng)) >= 0 ? 'rtl' : 'ltr';
    }
  }, {
    key: "createInstance",
    value: function createInstance() {
      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      var callback = arguments.length > 1 ? arguments[1] : undefined;
      return new I18n(options, callback);
    }
  }, {
    key: "cloneInstance",
    value: function cloneInstance() {
      var _this8 = this;

      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      var callback = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : noop;

      var mergedOptions = _objectSpread({}, this.options, options, {
        isClone: true
      });

      var clone = new I18n(mergedOptions);
      var membersToCopy = ['store', 'services', 'language'];
      membersToCopy.forEach(function (m) {
        clone[m] = _this8[m];
      });
      clone.services = _objectSpread({}, this.services);
      clone.services.utils = {
        hasLoadedNamespace: clone.hasLoadedNamespace.bind(clone)
      };
      clone.translator = new Translator(clone.services, clone.options);
      clone.translator.on('*', function (event) {
        for (var _len4 = arguments.length, args = new Array(_len4 > 1 ? _len4 - 1 : 0), _key4 = 1; _key4 < _len4; _key4++) {
          args[_key4 - 1] = arguments[_key4];
        }

        clone.emit.apply(clone, [event].concat(args));
      });
      clone.init(mergedOptions, callback);
      clone.translator.options = clone.options;
      clone.translator.backendConnector.services.utils = {
        hasLoadedNamespace: clone.hasLoadedNamespace.bind(clone)
      };
      return clone;
    }
  }]);

  return I18n;
}(EventEmitter);

var i18next = new I18n();

/**
 * Creates a new i18next instance that is fully initialized.
 *
 * Call changeLanguage() on the returned object to change the language.
 *
 * @param {object} languages - Mapping from languages to translation objects
 * @param {string} lng - The default language
 * @param {string} fallback - The fallback language to use for unknown languages or untranslated keys
 * @returns {i18next.i18n} A new independent i18next instance
 */
function createInstance(languages, lng, fallback) {
    var options = {
        lng: lng,
        fallbackLng: fallback,
        debug: false,
        initImmediate: false, // Don't init async
        resources: {},
    };

    Object.keys(languages).forEach(function(key) {
        options['resources'][key] = {translation: languages[key]};
    });

    var i18n = i18next.createInstance();
    i18n.init(options);
    console.assert(i18n.isInitialized);

    return i18n;
}

var login = "Einloggen";
var logout = "Ausloggen";
var profile = "Profil";
var de = {
	login: login,
	logout: logout,
	profile: profile
};

var login$1 = "Login";
var logout$1 = "Logout";
var profile$1 = "Profile";
var en = {
	login: login$1,
	logout: logout$1,
	profile: profile$1
};

const i18n = createInstance({en: en, de: de}, 'de', 'en');

/**
 * Sends a notification via the event
 *
 * Type can be info/success/warning/danger
 *
 * example options:
 *
 * {
 *   "summary": "Item deleted",
 *   "body": "Item foo was deleted!",
 *   "type": "info",
 *   "timeout": 5,
 * }
 *
 * @param options
 */
function send(options) {
    const event = new CustomEvent("dbp-notification-send", {
        bubbles: true,
        cancelable: true,
        detail: options,
    });

    const result = window.dispatchEvent(event);

    // true means the event was not handled
    if (result) {
        alert((options.summary !== undefined && options.summary !== "" ? options.summary + ": " : "") + options.body);
        console.log("Use the web component dbp-notification to show fancy notifications.");
    }
}

/**
 * Parses a link header
 *
 * The node module parse-link-header didn't work, so https://gist.github.com/niallo/3109252 became handy
 *
 * @param header
 */
const parseLinkHeader = (header) => {
    if (header.length === 0) {
        throw new Error("input must not be of zero length");
    }

    // Split parts by comma
    const parts = header.split(',');
    const links = {};

    // Parse each part into a named link
    for(let i=0; i<parts.length; i++) {
        const section = parts[i].split(';');
        if (section.length !== 2) {
            throw new Error("section could not be split on ';'");
        }
        const url = section[0].replace(/<(.*)>/, '$1').trim();
        const name = section[1].replace(/rel="(.*)"/, '$1').trim();
        links[name] = url;
    }

    return links;
};

/**
 * Parses the base url from an url
 *
 * @param url
 * @returns {string}
 */
const parseBaseUrl = (url) => {
    const pathArray = url.split('/');
    const protocol = pathArray[0];
    const host = pathArray[2];
    return protocol + '//' + host;
};

/**
 * Like customElements.define() but tries to display an error in case the browser doesn't
 * support everything we need.
 *
 * Returns false in case define failed, true otherwise.
 *
 * @returns {boolean}
 */

/**
 * 
 * @param {string} name 
 * @param {Function} constructor 
 * @param {object} options 
 */
const defineCustomElement = (name, constructor, options) => {
    // In case the constructor is already defined just do nothing
    if (customElements.get(name) === constructor) {
        return true;
    }
    // Checks taken from https://github.com/webcomponents/webcomponentsjs/blob/master/webcomponents-loader.js
    if (!('attachShadow' in Element.prototype && 'getRootNode' in Element.prototype && window.customElements)) {
        var elements = document.getElementsByTagName(name);
        for(var i=0; i < elements.length; i++) {
            elements[i].innerHTML = "<span style='border: 1px solid red; font-size: 0.8em; "
                + "opacity: 0.5; padding: 0.2em;'>☹ Your browser is not supported ☹</span>";
        }
       return false;
    }
    customElements.define(name, constructor, options);
    return true;
};

var error = {
	summary: "Ein Fehler ist aufgetreten",
	"connection-to-server-refused": "Verbindungs zum Server verweigert!"
};
var jsonld = {
	"error-api-server": "Verbindung zum API Server {{apiUrl}} fehlgeschlagen!",
	"error-hydra-documentation-url-not-set": "Hydra apiDocumentation URL wurden für server {{apiUrl}} nicht gesetzt!",
	"api-documentation-server": "Verbindung zum apiDocumentation API Server {{apiDocUrl}} fehlgeschlagen!"
};
var de$1 = {
	error: error,
	jsonld: jsonld
};

var error$1 = {
	summary: "An error occurred",
	"connection-to-server-refused": "Connection to server refused!"
};
var jsonld$1 = {
	"error-api-server": "Connection to api server {{apiUrl}} failed!",
	"error-hydra-documentation-url-not-set": "Hydra apiDocumentation url was not set for server {{apiUrl}}!",
	"api-documentation-server": "Connection to apiDocumentation server {{apiDocUrl}} failed!"
};
var en$1 = {
	error: error$1,
	jsonld: jsonld$1
};

const i18n$1 = createInstance({en: en$1, de: de$1}, 'de', 'en');

// "module.exports = class JSONLD" doesn't work with rollup because of above "import"
class JSONLD {
    constructor(baseApiUrl, entities) {
        this.entities = entities;
        this.baseApiUrl = baseApiUrl;

        let idToEntityNameMatchList = {};
        for (const entityName in entities) {
            const id = entities[entityName]["@id"];
            idToEntityNameMatchList[id] = entityName;
        }

        this.idToEntityNameMatchList = idToEntityNameMatchList;
    }

    static initialize(apiUrl, successFnc, failureFnc, lang = 'de') {
        if (lang !== 'de') {
            i18n$1.changeLanguage(lang);
        }

        // if init api call was already successfully finished execute the success function
        if (JSONLD.instances[apiUrl] !== undefined) {
            if (typeof successFnc == 'function') successFnc(JSONLD.instances[apiUrl]);

            return;
        }

        // init the arrays
        if (JSONLD.successFunctions[apiUrl] === undefined) JSONLD.successFunctions[apiUrl] = [];
        if (JSONLD.failureFunctions[apiUrl] === undefined) JSONLD.failureFunctions[apiUrl] = [];

        // add success and failure functions
        if (typeof successFnc == 'function') JSONLD.successFunctions[apiUrl].push(successFnc);
        if (typeof failureFnc == 'function') JSONLD.failureFunctions[apiUrl].push(failureFnc);
    }

    /**
     * This should be run as soon as an api token is available (can be run multiple times)
     *
     * @param apiUrl
     * @param token
     */
    static doInitializationOnce(apiUrl, token) {
        // console.log("doInitializationOnce", apiUrl, token);

        // check if token is not set or api call was already started
        if (!apiUrl || !token || JSONLD.initStarted[apiUrl] !== undefined) {
            return;
        }

        JSONLD.initStarted[apiUrl] = true;
        JSONLD.doInitialization(apiUrl, token);
        // console.log("doInitializationOnce Done", apiUrl, token);
    }

    static doInitialization(apiUrl, token) {
        const xhr = new XMLHttpRequest();
        xhr.open("GET", apiUrl, true);
        xhr.setRequestHeader('Authorization', 'Bearer ' + token);

        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }

            if (xhr.status === 200) {
                const json = JSON.parse(xhr.responseText);

                let entryPoints = {};
                for (let property in json) {
                    // for some reason the properties start with a lower case character
                    if (!property.startsWith("@")) entryPoints[property.toLowerCase()] = json[property];
                }

                // read the link header of the api response
                //                const utils = require("./utils");
                const links = parseLinkHeader(this.getResponseHeader("link"));

                // get the hydra apiDocumentation url
                const apiDocUrl = links["http://www.w3.org/ns/hydra/core#apiDocumentation"];

                if (apiDocUrl !== undefined) {
                    // load the hydra apiDocumentation
                    const docXhr = new XMLHttpRequest();
                    docXhr.open("GET", apiDocUrl, true);
                    docXhr.setRequestHeader("Content-Type", "application/json");
                    docXhr.onreadystatechange = function () {
                        if (docXhr.readyState !== 4) {
                            return;
                        }

                        if (docXhr.status === 200) {
                            JSONLD.gatherEntities(docXhr, apiUrl, entryPoints);
                        } else {
                            JSONLD.executeFailureFunctions(apiUrl, i18n$1.t('jsonld.api-documentation-server', {apiUrl: apiDocUrl}));
                        }
                    };

                    docXhr.send();
                } else {
                    JSONLD.executeFailureFunctions(apiUrl, i18n$1.t('jsonld.error-hydra-documentation-url-not-set', {apiUrl: apiUrl}));
                }
            } else {
                JSONLD.executeFailureFunctions(apiUrl, i18n$1.t('jsonld.error-api-server', {apiUrl: apiUrl}));
            }
        };

        xhr.send();
    }

    /**
     * Gather the entities
     *
     * @param docXhr
     * @param apiUrl
     * @param entryPoints
     */
    static gatherEntities(docXhr, apiUrl, entryPoints) {
        const json = JSON.parse(docXhr.responseText);
        const supportedClasses = json["hydra:supportedClass"];

        let entities = {};
        const baseUrl = parseBaseUrl(apiUrl);

        // gather the entities
        supportedClasses.forEach(function (classData) {
            // add entry point url
            const entityName = classData["hydra:title"];
            let entryPoint = entryPoints[entityName.toLowerCase()];
            if (entryPoint !== undefined && !entryPoint.startsWith("http")) entryPoint = baseUrl + entryPoint;
            classData["@entryPoint"] = entryPoint;

            entities[entityName] = classData;
        });

        const instance = new JSONLD(baseUrl, entities);
        JSONLD.instances[apiUrl] = instance;

        // return the initialized JSONLD object
        for (const fnc of JSONLD.successFunctions[apiUrl]) if (typeof fnc == 'function') fnc(instance);
        JSONLD.successFunctions[apiUrl] = [];
    }

    /**
     * Execute failure functions and send general notification
     *
     * @param apiUrl
     * @param message
     */
    static executeFailureFunctions(apiUrl, message = "") {
        for (const fnc of JSONLD.failureFunctions[apiUrl]) if (typeof fnc == 'function') fnc();
        JSONLD.failureFunctions[apiUrl] = [];

        if (message !== "") {
            send({
                "summary": i18n$1.t('error.summary'),
                "body": message,
                "type": "danger",
            });
        }
    }

    static getInstance(apiUrl) {
        return JSONLD.instances[apiUrl];
    }

    getEntityForIdentifier(identifier) {
        let entityName = this.getEntityNameForIdentifier(identifier);
        return this.getEntityForEntityName(entityName);
    }

    getEntityForEntityName(entityName) {
        return this.entities[entityName];
    }

    getApiUrlForIdentifier(identifier) {
        const entity = this.getEntityForIdentifier(identifier);

        if (entity === undefined || entity["@entryPoint"] === undefined) {
            throw new Error(`Entity with identifier "${identifier}" not found!`);
        }

        return entity["@entryPoint"];
    }

    getApiUrlForEntityName(entityName) {
        const entity = this.getEntityForEntityName(entityName);

        if (entity === undefined || entity["@entryPoint"] === undefined) {
            throw new Error(`Entity "${entityName}" not found!`);
        }

        return entity["@entryPoint"];
    }

    getEntityNameForIdentifier(identifier) {
        return this.idToEntityNameMatchList[identifier];
    }

    getApiIdentifierList() {
        let keys = [];
        for (const property in this.idToEntityNameMatchList) {
            keys.push(property);
        }

        return keys;
    }

    /**
     * Expands a member of a list to a object with schema.org properties
     *
     * @param member
     * @param [context]
     */
    expandMember(member, context) {
        if (context === undefined) {
            context = member["@context"];
        }

        let result = {"@id": member["@id"]};
        for (const key of Object.keys(context)) {
            const value = context[key];
            if (member[key] !== undefined)
                result[value] = member[key];
        }

        return result;
    }

    /**
     * Compacts an expanded member of a list to a object with local properties
     *
     * @param member
     * @param localContext
     */
    compactMember(member, localContext) {
        let result = {};

        for (const property in localContext) {
            const value = member[localContext[property]];

            if (value !== undefined) {
                result[property] = value;
            }
        }

        return result;
    }

    /**
     * Transforms hydra members to a local context
     *
     * @param data
     * @param localContext
     * @returns {Array} An array of transformed objects
     */
    transformMembers(data, localContext) {
        const members = data['hydra:member'];

        if (members === undefined || members.length === 0) {
            return [];
        }

        const otherContext = data['@context'];
        let results = [];
        let that = this;

        members.forEach(function (member) {
            results.push(that.compactMember(that.expandMember(member, otherContext), localContext));
        });

        return results;
    }
}

JSONLD.instances = {};
JSONLD.successFunctions = {};
JSONLD.failureFunctions = {};
JSONLD.initStarted = {};

/**
 * Assert a condition.
 * @param condition The condition that it should satisfy.
 * @param message The error message.
 * @param args The arguments for replacing placeholders in the message.
 */
function assertType(condition, message, ...args) {
    if (!condition) {
        throw new TypeError(format(message, args));
    }
}
/**
 * Convert a text and arguments to one string.
 * @param message The formating text
 * @param args The arguments.
 */
function format(message, args) {
    let i = 0;
    return message.replace(/%[os]/gu, () => anyToString(args[i++]));
}
/**
 * Convert a value to a string representation.
 * @param x The value to get the string representation.
 */
function anyToString(x) {
    if (typeof x !== "object" || x === null) {
        return String(x);
    }
    return Object.prototype.toString.call(x);
}

let currentErrorHandler;
/**
 * Print a error message.
 * @param maybeError The error object.
 */
function reportError(maybeError) {
    try {
        const error = maybeError instanceof Error
            ? maybeError
            : new Error(anyToString(maybeError));
        // Call the user-defined error handler if exists.
        if (currentErrorHandler) ;
        // Dispatch an `error` event if this is on a browser.
        if (typeof dispatchEvent === "function" &&
            typeof ErrorEvent === "function") {
            dispatchEvent(new ErrorEvent("error", { error, message: error.message }));
        }
        // Emit an `uncaughtException` event if this is on Node.js.
        //istanbul ignore else
        else if (typeof process !== "undefined" &&
            typeof process.emit === "function") {
            process.emit("uncaughtException", error);
            return;
        }
        // Otherwise, print the error.
        console.error(error);
    }
    catch (_a) {
        // ignore.
    }
}

/**
 * The global object.
 */
//istanbul ignore next
const Global = typeof window !== "undefined"
    ? window
    : typeof self !== "undefined"
        ? self
        : typeof global !== "undefined"
            ? global
            : typeof globalThis !== "undefined"
                ? globalThis
                : undefined;

let currentWarnHandler;
/**
 * The warning information.
 */
class Warning {
    constructor(code, message) {
        this.code = code;
        this.message = message;
    }
    /**
     * Report this warning.
     * @param args The arguments of the warning.
     */
    warn(...args) {
        var _a;
        try {
            // Call the user-defined warning handler if exists.
            if (currentWarnHandler) ;
            // Otherwise, print the warning.
            const stack = ((_a = new Error().stack) !== null && _a !== void 0 ? _a : "").replace(/^(?:.+?\n){2}/gu, "\n");
            console.warn(this.message, ...args, stack);
        }
        catch (_b) {
            // Ignore.
        }
    }
}

const InitEventWasCalledWhileDispatching = new Warning("W01", "Unable to initialize event under dispatching.");
const FalsyWasAssignedToCancelBubble = new Warning("W02", "Assigning any falsy value to 'cancelBubble' property has no effect.");
const TruthyWasAssignedToReturnValue = new Warning("W03", "Assigning any truthy value to 'returnValue' property has no effect.");
const NonCancelableEventWasCanceled = new Warning("W04", "Unable to preventDefault on non-cancelable events.");
const CanceledInPassiveListener = new Warning("W05", "Unable to preventDefault inside passive event listener invocation.");
const EventListenerWasDuplicated = new Warning("W06", "An event listener wasn't added because it has been added already: %o, %o");
const OptionWasIgnored = new Warning("W07", "The %o option value was abandoned because the event listener wasn't added as duplicated.");
const InvalidEventListener = new Warning("W08", "The 'callback' argument must be a function or an object that has 'handleEvent' method: %o");

/*eslint-disable class-methods-use-this */
/**
 * An implementation of `Event` interface, that wraps a given event object.
 * `EventTarget` shim can control the internal state of this `Event` objects.
 * @see https://dom.spec.whatwg.org/#event
 */
class Event {
    /**
     * @see https://dom.spec.whatwg.org/#dom-event-none
     */
    static get NONE() {
        return NONE;
    }
    /**
     * @see https://dom.spec.whatwg.org/#dom-event-capturing_phase
     */
    static get CAPTURING_PHASE() {
        return CAPTURING_PHASE;
    }
    /**
     * @see https://dom.spec.whatwg.org/#dom-event-at_target
     */
    static get AT_TARGET() {
        return AT_TARGET;
    }
    /**
     * @see https://dom.spec.whatwg.org/#dom-event-bubbling_phase
     */
    static get BUBBLING_PHASE() {
        return BUBBLING_PHASE;
    }
    /**
     * Initialize this event instance.
     * @param type The type of this event.
     * @param eventInitDict Options to initialize.
     * @see https://dom.spec.whatwg.org/#dom-event-event
     */
    constructor(type, eventInitDict) {
        Object.defineProperty(this, "isTrusted", {
            value: false,
            enumerable: true,
        });
        const opts = eventInitDict !== null && eventInitDict !== void 0 ? eventInitDict : {};
        internalDataMap.set(this, {
            type: String(type),
            bubbles: Boolean(opts.bubbles),
            cancelable: Boolean(opts.cancelable),
            composed: Boolean(opts.composed),
            target: null,
            currentTarget: null,
            stopPropagationFlag: false,
            stopImmediatePropagationFlag: false,
            canceledFlag: false,
            inPassiveListenerFlag: false,
            dispatchFlag: false,
            timeStamp: Date.now(),
        });
    }
    /**
     * The type of this event.
     * @see https://dom.spec.whatwg.org/#dom-event-type
     */
    get type() {
        return $(this).type;
    }
    /**
     * The event target of the current dispatching.
     * @see https://dom.spec.whatwg.org/#dom-event-target
     */
    get target() {
        return $(this).target;
    }
    /**
     * The event target of the current dispatching.
     * @deprecated Use the `target` property instead.
     * @see https://dom.spec.whatwg.org/#dom-event-srcelement
     */
    get srcElement() {
        return $(this).target;
    }
    /**
     * The event target of the current dispatching.
     * @see https://dom.spec.whatwg.org/#dom-event-currenttarget
     */
    get currentTarget() {
        return $(this).currentTarget;
    }
    /**
     * The event target of the current dispatching.
     * This doesn't support node tree.
     * @see https://dom.spec.whatwg.org/#dom-event-composedpath
     */
    composedPath() {
        const currentTarget = $(this).currentTarget;
        if (currentTarget) {
            return [currentTarget];
        }
        return [];
    }
    /**
     * @see https://dom.spec.whatwg.org/#dom-event-none
     */
    get NONE() {
        return NONE;
    }
    /**
     * @see https://dom.spec.whatwg.org/#dom-event-capturing_phase
     */
    get CAPTURING_PHASE() {
        return CAPTURING_PHASE;
    }
    /**
     * @see https://dom.spec.whatwg.org/#dom-event-at_target
     */
    get AT_TARGET() {
        return AT_TARGET;
    }
    /**
     * @see https://dom.spec.whatwg.org/#dom-event-bubbling_phase
     */
    get BUBBLING_PHASE() {
        return BUBBLING_PHASE;
    }
    /**
     * The current event phase.
     * @see https://dom.spec.whatwg.org/#dom-event-eventphase
     */
    get eventPhase() {
        return $(this).dispatchFlag ? 2 : 0;
    }
    /**
     * Stop event bubbling.
     * Because this shim doesn't support node tree, this merely changes the `cancelBubble` property value.
     * @see https://dom.spec.whatwg.org/#dom-event-stoppropagation
     */
    stopPropagation() {
        $(this).stopPropagationFlag = true;
    }
    /**
     * `true` if event bubbling was stopped.
     * @deprecated
     * @see https://dom.spec.whatwg.org/#dom-event-cancelbubble
     */
    get cancelBubble() {
        return $(this).stopPropagationFlag;
    }
    /**
     * Stop event bubbling if `true` is set.
     * @deprecated Use the `stopPropagation()` method instead.
     * @see https://dom.spec.whatwg.org/#dom-event-cancelbubble
     */
    set cancelBubble(value) {
        if (value) {
            $(this).stopPropagationFlag = true;
        }
        else {
            FalsyWasAssignedToCancelBubble.warn();
        }
    }
    /**
     * Stop event bubbling and subsequent event listener callings.
     * @see https://dom.spec.whatwg.org/#dom-event-stopimmediatepropagation
     */
    stopImmediatePropagation() {
        const data = $(this);
        data.stopPropagationFlag = data.stopImmediatePropagationFlag = true;
    }
    /**
     * `true` if this event will bubble.
     * @see https://dom.spec.whatwg.org/#dom-event-bubbles
     */
    get bubbles() {
        return $(this).bubbles;
    }
    /**
     * `true` if this event can be canceled by the `preventDefault()` method.
     * @see https://dom.spec.whatwg.org/#dom-event-cancelable
     */
    get cancelable() {
        return $(this).cancelable;
    }
    /**
     * `true` if the default behavior will act.
     * @deprecated Use the `defaultPrevented` proeprty instead.
     * @see https://dom.spec.whatwg.org/#dom-event-returnvalue
     */
    get returnValue() {
        return !$(this).canceledFlag;
    }
    /**
     * Cancel the default behavior if `false` is set.
     * @deprecated Use the `preventDefault()` method instead.
     * @see https://dom.spec.whatwg.org/#dom-event-returnvalue
     */
    set returnValue(value) {
        if (!value) {
            setCancelFlag($(this));
        }
        else {
            TruthyWasAssignedToReturnValue.warn();
        }
    }
    /**
     * Cancel the default behavior.
     * @see https://dom.spec.whatwg.org/#dom-event-preventdefault
     */
    preventDefault() {
        setCancelFlag($(this));
    }
    /**
     * `true` if the default behavior was canceled.
     * @see https://dom.spec.whatwg.org/#dom-event-defaultprevented
     */
    get defaultPrevented() {
        return $(this).canceledFlag;
    }
    /**
     * @see https://dom.spec.whatwg.org/#dom-event-composed
     */
    get composed() {
        return $(this).composed;
    }
    /**
     * @see https://dom.spec.whatwg.org/#dom-event-istrusted
     */
    //istanbul ignore next
    get isTrusted() {
        return false;
    }
    /**
     * @see https://dom.spec.whatwg.org/#dom-event-timestamp
     */
    get timeStamp() {
        return $(this).timeStamp;
    }
    /**
     * @deprecated Don't use this method. The constructor did initialization.
     */
    initEvent(type, bubbles = false, cancelable = false) {
        const data = $(this);
        if (data.dispatchFlag) {
            InitEventWasCalledWhileDispatching.warn();
            return;
        }
        internalDataMap.set(this, {
            ...data,
            type: String(type),
            bubbles: Boolean(bubbles),
            cancelable: Boolean(cancelable),
            target: null,
            currentTarget: null,
            stopPropagationFlag: false,
            stopImmediatePropagationFlag: false,
            canceledFlag: false,
        });
    }
}
//------------------------------------------------------------------------------
// Helpers
//------------------------------------------------------------------------------
const NONE = 0;
const CAPTURING_PHASE = 1;
const AT_TARGET = 2;
const BUBBLING_PHASE = 3;
/**
 * Private data for event wrappers.
 */
const internalDataMap = new WeakMap();
/**
 * Get private data.
 * @param event The event object to get private data.
 * @param name The variable name to report.
 * @returns The private data of the event.
 */
function $(event, name = "this") {
    const retv = internalDataMap.get(event);
    assertType(retv != null, "'%s' must be an object that Event constructor created, but got another one: %o", name, event);
    return retv;
}
/**
 * https://dom.spec.whatwg.org/#set-the-canceled-flag
 * @param data private data.
 */
function setCancelFlag(data) {
    if (data.inPassiveListenerFlag) {
        CanceledInPassiveListener.warn();
        return;
    }
    if (!data.cancelable) {
        NonCancelableEventWasCanceled.warn();
        return;
    }
    data.canceledFlag = true;
}
// Set enumerable
Object.defineProperty(Event, "NONE", { enumerable: true });
Object.defineProperty(Event, "CAPTURING_PHASE", { enumerable: true });
Object.defineProperty(Event, "AT_TARGET", { enumerable: true });
Object.defineProperty(Event, "BUBBLING_PHASE", { enumerable: true });
const keys = Object.getOwnPropertyNames(Event.prototype);
for (let i = 0; i < keys.length; ++i) {
    if (keys[i] === "constructor") {
        continue;
    }
    Object.defineProperty(Event.prototype, keys[i], { enumerable: true });
}
// Ensure `event instanceof window.Event` is `true`.
if (typeof Global !== "undefined" && typeof Global.Event !== "undefined") {
    Object.setPrototypeOf(Event.prototype, Global.Event.prototype);
}

/**
 * Create a new InvalidStateError instance.
 * @param message The error message.
 */
function createInvalidStateError(message) {
    if (Global.DOMException) {
        return new Global.DOMException(message, "InvalidStateError");
    }
    if (DOMException == null) {
        DOMException = class DOMException extends Error {
            constructor(msg) {
                super(msg);
                if (Error.captureStackTrace) {
                    Error.captureStackTrace(this, DOMException);
                }
            }
            // eslint-disable-next-line class-methods-use-this
            get code() {
                return 11;
            }
            // eslint-disable-next-line class-methods-use-this
            get name() {
                return "InvalidStateError";
            }
        };
        Object.defineProperties(DOMException.prototype, {
            code: { enumerable: true },
            name: { enumerable: true },
        });
        defineErrorCodeProperties(DOMException);
        defineErrorCodeProperties(DOMException.prototype);
    }
    return new DOMException(message);
}
//------------------------------------------------------------------------------
// Helpers
//------------------------------------------------------------------------------
let DOMException;
const ErrorCodeMap = {
    INDEX_SIZE_ERR: 1,
    DOMSTRING_SIZE_ERR: 2,
    HIERARCHY_REQUEST_ERR: 3,
    WRONG_DOCUMENT_ERR: 4,
    INVALID_CHARACTER_ERR: 5,
    NO_DATA_ALLOWED_ERR: 6,
    NO_MODIFICATION_ALLOWED_ERR: 7,
    NOT_FOUND_ERR: 8,
    NOT_SUPPORTED_ERR: 9,
    INUSE_ATTRIBUTE_ERR: 10,
    INVALID_STATE_ERR: 11,
    SYNTAX_ERR: 12,
    INVALID_MODIFICATION_ERR: 13,
    NAMESPACE_ERR: 14,
    INVALID_ACCESS_ERR: 15,
    VALIDATION_ERR: 16,
    TYPE_MISMATCH_ERR: 17,
    SECURITY_ERR: 18,
    NETWORK_ERR: 19,
    ABORT_ERR: 20,
    URL_MISMATCH_ERR: 21,
    QUOTA_EXCEEDED_ERR: 22,
    TIMEOUT_ERR: 23,
    INVALID_NODE_TYPE_ERR: 24,
    DATA_CLONE_ERR: 25,
};
function defineErrorCodeProperties(obj) {
    const keys = Object.keys(ErrorCodeMap);
    for (let i = 0; i < keys.length; ++i) {
        const key = keys[i];
        const value = ErrorCodeMap[key];
        Object.defineProperty(obj, key, {
            get() {
                return value;
            },
            configurable: true,
            enumerable: true,
        });
    }
}

/**
 * An implementation of `Event` interface, that wraps a given event object.
 * This class controls the internal state of `Event`.
 * @see https://dom.spec.whatwg.org/#interface-event
 */
class EventWrapper extends Event {
    /**
     * Wrap a given event object to control states.
     * @param event The event-like object to wrap.
     */
    static wrap(event) {
        return new (getWrapperClassOf(event))(event);
    }
    constructor(event) {
        super(event.type, {
            bubbles: event.bubbles,
            cancelable: event.cancelable,
            composed: event.composed,
        });
        if (event.cancelBubble) {
            super.stopPropagation();
        }
        if (event.defaultPrevented) {
            super.preventDefault();
        }
        internalDataMap$1.set(this, { original: event });
        // Define accessors
        const keys = Object.keys(event);
        for (let i = 0; i < keys.length; ++i) {
            const key = keys[i];
            if (!(key in this)) {
                Object.defineProperty(this, key, defineRedirectDescriptor(event, key));
            }
        }
    }
    stopPropagation() {
        super.stopPropagation();
        const { original } = $$1(this);
        if ("stopPropagation" in original) {
            original.stopPropagation();
        }
    }
    get cancelBubble() {
        return super.cancelBubble;
    }
    set cancelBubble(value) {
        super.cancelBubble = value;
        const { original } = $$1(this);
        if ("cancelBubble" in original) {
            original.cancelBubble = value;
        }
    }
    stopImmediatePropagation() {
        super.stopImmediatePropagation();
        const { original } = $$1(this);
        if ("stopImmediatePropagation" in original) {
            original.stopImmediatePropagation();
        }
    }
    get returnValue() {
        return super.returnValue;
    }
    set returnValue(value) {
        super.returnValue = value;
        const { original } = $$1(this);
        if ("returnValue" in original) {
            original.returnValue = value;
        }
    }
    preventDefault() {
        super.preventDefault();
        const { original } = $$1(this);
        if ("preventDefault" in original) {
            original.preventDefault();
        }
    }
    get timeStamp() {
        const { original } = $$1(this);
        if ("timeStamp" in original) {
            return original.timeStamp;
        }
        return super.timeStamp;
    }
}
/**
 * Private data for event wrappers.
 */
const internalDataMap$1 = new WeakMap();
/**
 * Get private data.
 * @param event The event object to get private data.
 * @returns The private data of the event.
 */
function $$1(event) {
    const retv = internalDataMap$1.get(event);
    assertType(retv != null, "'this' is expected an Event object, but got", event);
    return retv;
}
/**
 * Cache for wrapper classes.
 * @type {WeakMap<Object, Function>}
 * @private
 */
const wrapperClassCache = new WeakMap();
// Make association for wrappers.
wrapperClassCache.set(Object.prototype, EventWrapper);
if (typeof Global !== "undefined" && typeof Global.Event !== "undefined") {
    wrapperClassCache.set(Global.Event.prototype, EventWrapper);
}
/**
 * Get the wrapper class of a given prototype.
 * @param originalEvent The event object to wrap.
 */
function getWrapperClassOf(originalEvent) {
    const prototype = Object.getPrototypeOf(originalEvent);
    if (prototype == null) {
        return EventWrapper;
    }
    let wrapper = wrapperClassCache.get(prototype);
    if (wrapper == null) {
        wrapper = defineWrapper(getWrapperClassOf(prototype), prototype);
        wrapperClassCache.set(prototype, wrapper);
    }
    return wrapper;
}
/**
 * Define new wrapper class.
 * @param BaseEventWrapper The base wrapper class.
 * @param originalPrototype The prototype of the original event.
 */
function defineWrapper(BaseEventWrapper, originalPrototype) {
    class CustomEventWrapper extends BaseEventWrapper {
    }
    const keys = Object.keys(originalPrototype);
    for (let i = 0; i < keys.length; ++i) {
        Object.defineProperty(CustomEventWrapper.prototype, keys[i], defineRedirectDescriptor(originalPrototype, keys[i]));
    }
    return CustomEventWrapper;
}
/**
 * Get the property descriptor to redirect a given property.
 */
function defineRedirectDescriptor(obj, key) {
    const d = Object.getOwnPropertyDescriptor(obj, key);
    return {
        get() {
            const original = $$1(this).original;
            const value = original[key];
            if (typeof value === "function") {
                return value.bind(original);
            }
            return value;
        },
        set(value) {
            const original = $$1(this).original;
            original[key] = value;
        },
        configurable: d.configurable,
        enumerable: d.enumerable,
    };
}

/**
 * Create a new listener.
 * @param callback The callback function.
 * @param capture The capture flag.
 * @param passive The passive flag.
 * @param once The once flag.
 * @param signal The abort signal.
 * @param signalListener The abort event listener for the abort signal.
 */
function createListener(callback, capture, passive, once, signal, signalListener) {
    return {
        callback,
        flags: (capture ? 1 /* Capture */ : 0) |
            (passive ? 2 /* Passive */ : 0) |
            (once ? 4 /* Once */ : 0),
        signal,
        signalListener,
    };
}
/**
 * Set the `removed` flag to the given listener.
 * @param listener The listener to check.
 */
function setRemoved(listener) {
    listener.flags |= 8 /* Removed */;
}
/**
 * Check if the given listener has the `capture` flag or not.
 * @param listener The listener to check.
 */
function isCapture(listener) {
    return (listener.flags & 1 /* Capture */) === 1 /* Capture */;
}
/**
 * Check if the given listener has the `passive` flag or not.
 * @param listener The listener to check.
 */
function isPassive(listener) {
    return (listener.flags & 2 /* Passive */) === 2 /* Passive */;
}
/**
 * Check if the given listener has the `once` flag or not.
 * @param listener The listener to check.
 */
function isOnce(listener) {
    return (listener.flags & 4 /* Once */) === 4 /* Once */;
}
/**
 * Check if the given listener has the `removed` flag or not.
 * @param listener The listener to check.
 */
function isRemoved(listener) {
    return (listener.flags & 8 /* Removed */) === 8 /* Removed */;
}
/**
 * Call an event listener.
 * @param listener The listener to call.
 * @param target The event target object for `thisArg`.
 * @param event The event object for the first argument.
 * @param attribute `true` if this callback is an event attribute handler.
 */
function invokeCallback({ callback }, target, event) {
    try {
        if (typeof callback === "function") {
            callback.call(target, event);
        }
        else if (typeof callback.handleEvent === "function") {
            callback.handleEvent(event);
        }
    }
    catch (thrownError) {
        reportError(thrownError);
    }
}

/**
 * Find the index of given listener.
 * This returns `-1` if not found.
 * @param list The listener list.
 * @param callback The callback function to find.
 * @param capture The capture flag to find.
 */
function findIndexOfListener({ listeners }, callback, capture) {
    for (let i = 0; i < listeners.length; ++i) {
        if (listeners[i].callback === callback &&
            isCapture(listeners[i]) === capture) {
            return i;
        }
    }
    return -1;
}
/**
 * Add the given listener.
 * Does copy-on-write if needed.
 * @param list The listener list.
 * @param callback The callback function.
 * @param capture The capture flag.
 * @param passive The passive flag.
 * @param once The once flag.
 * @param signal The abort signal.
 */
function addListener(list, callback, capture, passive, once, signal) {
    let signalListener;
    if (signal) {
        signalListener = removeListener.bind(null, list, callback, capture);
        signal.addEventListener("abort", signalListener);
    }
    const listener = createListener(callback, capture, passive, once, signal, signalListener);
    if (list.cow) {
        list.cow = false;
        list.listeners = [...list.listeners, listener];
    }
    else {
        list.listeners.push(listener);
    }
    return listener;
}
/**
 * Remove a listener.
 * @param list The listener list.
 * @param callback The callback function to find.
 * @param capture The capture flag to find.
 * @returns `true` if it mutated the list directly.
 */
function removeListener(list, callback, capture) {
    const index = findIndexOfListener(list, callback, capture);
    if (index !== -1) {
        return removeListenerAt(list, index);
    }
    return false;
}
/**
 * Remove a listener.
 * @param list The listener list.
 * @param index The index of the target listener.
 * @param disableCow Disable copy-on-write if true.
 * @returns `true` if it mutated the `listeners` array directly.
 */
function removeListenerAt(list, index, disableCow = false) {
    const listener = list.listeners[index];
    // Set the removed flag.
    setRemoved(listener);
    // Dispose the abort signal listener if exists.
    if (listener.signal) {
        listener.signal.removeEventListener("abort", listener.signalListener);
    }
    // Remove it from the array.
    if (list.cow && !disableCow) {
        list.cow = false;
        list.listeners = list.listeners.filter((_, i) => i !== index);
        return false;
    }
    list.listeners.splice(index, 1);
    return true;
}

/**
 * Create a new `ListenerListMap` object.
 */
function createListenerListMap() {
    return Object.create(null);
}
/**
 * Get the listener list of the given type.
 * If the listener list has not been initialized, initialize and return it.
 * @param listenerMap The listener list map.
 * @param type The event type to get.
 */
function ensureListenerList(listenerMap, type) {
    var _a;
    return ((_a = listenerMap[type]) !== null && _a !== void 0 ? _a : (listenerMap[type] = {
        attrCallback: undefined,
        attrListener: undefined,
        cow: false,
        listeners: [],
    }));
}

/**
 * An implementation of the `EventTarget` interface.
 * @see https://dom.spec.whatwg.org/#eventtarget
 */
class EventTarget {
    /**
     * Initialize this instance.
     */
    constructor() {
        internalDataMap$2.set(this, createListenerListMap());
    }
    // Implementation
    addEventListener(type0, callback0, options0) {
        const listenerMap = $$2(this);
        const { callback, capture, once, passive, signal, type, } = normalizeAddOptions(type0, callback0, options0);
        if (callback == null || (signal === null || signal === void 0 ? void 0 : signal.aborted)) {
            return;
        }
        const list = ensureListenerList(listenerMap, type);
        // Find existing listener.
        const i = findIndexOfListener(list, callback, capture);
        if (i !== -1) {
            warnDuplicate(list.listeners[i], passive, once, signal);
            return;
        }
        // Add the new listener.
        addListener(list, callback, capture, passive, once, signal);
    }
    // Implementation
    removeEventListener(type0, callback0, options0) {
        const listenerMap = $$2(this);
        const { callback, capture, type } = normalizeOptions(type0, callback0, options0);
        const list = listenerMap[type];
        if (callback != null && list) {
            removeListener(list, callback, capture);
        }
    }
    // Implementation
    dispatchEvent(e) {
        const list = $$2(this)[String(e.type)];
        if (list == null) {
            return true;
        }
        const event = e instanceof Event ? e : EventWrapper.wrap(e);
        const eventData = $(event, "event");
        if (eventData.dispatchFlag) {
            throw createInvalidStateError("This event has been in dispatching.");
        }
        eventData.dispatchFlag = true;
        eventData.target = eventData.currentTarget = this;
        if (!eventData.stopPropagationFlag) {
            const { cow, listeners } = list;
            // Set copy-on-write flag.
            list.cow = true;
            // Call listeners.
            for (let i = 0; i < listeners.length; ++i) {
                const listener = listeners[i];
                // Skip if removed.
                if (isRemoved(listener)) {
                    continue;
                }
                // Remove this listener if has the `once` flag.
                if (isOnce(listener) && removeListenerAt(list, i, !cow)) {
                    // Because this listener was removed, the next index is the
                    // same as the current value.
                    i -= 1;
                }
                // Call this listener with the `passive` flag.
                eventData.inPassiveListenerFlag = isPassive(listener);
                invokeCallback(listener, this, event);
                eventData.inPassiveListenerFlag = false;
                // Stop if the `event.stopImmediatePropagation()` method was called.
                if (eventData.stopImmediatePropagationFlag) {
                    break;
                }
            }
            // Restore copy-on-write flag.
            if (!cow) {
                list.cow = false;
            }
        }
        eventData.target = null;
        eventData.currentTarget = null;
        eventData.stopImmediatePropagationFlag = false;
        eventData.stopPropagationFlag = false;
        eventData.dispatchFlag = false;
        return !eventData.canceledFlag;
    }
}
/**
 * Internal data.
 */
const internalDataMap$2 = new WeakMap();
/**
 * Get private data.
 * @param target The event target object to get private data.
 * @param name The variable name to report.
 * @returns The private data of the event.
 */
function $$2(target, name = "this") {
    const retv = internalDataMap$2.get(target);
    assertType(retv != null, "'%s' must be an object that EventTarget constructor created, but got another one: %o", name, target);
    return retv;
}
/**
 * Normalize options.
 * @param options The options to normalize.
 */
function normalizeAddOptions(type, callback, options) {
    var _a;
    assertCallback(callback);
    if (typeof options === "object" && options !== null) {
        return {
            type: String(type),
            callback: callback !== null && callback !== void 0 ? callback : undefined,
            capture: Boolean(options.capture),
            passive: Boolean(options.passive),
            once: Boolean(options.once),
            signal: (_a = options.signal) !== null && _a !== void 0 ? _a : undefined,
        };
    }
    return {
        type: String(type),
        callback: callback !== null && callback !== void 0 ? callback : undefined,
        capture: Boolean(options),
        passive: false,
        once: false,
        signal: undefined,
    };
}
/**
 * Normalize options.
 * @param options The options to normalize.
 */
function normalizeOptions(type, callback, options) {
    assertCallback(callback);
    if (typeof options === "object" && options !== null) {
        return {
            type: String(type),
            callback: callback !== null && callback !== void 0 ? callback : undefined,
            capture: Boolean(options.capture),
        };
    }
    return {
        type: String(type),
        callback: callback !== null && callback !== void 0 ? callback : undefined,
        capture: Boolean(options),
    };
}
/**
 * Assert the type of 'callback' argument.
 * @param callback The callback to check.
 */
function assertCallback(callback) {
    if (typeof callback === "function" ||
        (typeof callback === "object" &&
            callback !== null &&
            typeof callback.handleEvent === "function")) {
        return;
    }
    if (callback == null || typeof callback === "object") {
        InvalidEventListener.warn(callback);
        return;
    }
    throw new TypeError(format(InvalidEventListener.message, [callback]));
}
/**
 * Print warning for duplicated.
 * @param listener The current listener that is duplicated.
 * @param passive The passive flag of the new duplicated listener.
 * @param once The once flag of the new duplicated listener.
 * @param signal The signal object of the new duplicated listener.
 */
function warnDuplicate(listener, passive, once, signal) {
    EventListenerWasDuplicated.warn(isCapture(listener) ? "capture" : "bubble", listener.callback);
    if (isPassive(listener) !== passive) {
        OptionWasIgnored.warn("passive");
    }
    if (isOnce(listener) !== once) {
        OptionWasIgnored.warn("once");
    }
    if (listener.signal !== signal) {
        OptionWasIgnored.warn("signal");
    }
}
// Set enumerable
const keys$1 = Object.getOwnPropertyNames(EventTarget.prototype);
for (let i = 0; i < keys$1.length; ++i) {
    if (keys$1[i] === "constructor") {
        continue;
    }
    Object.defineProperty(EventTarget.prototype, keys$1[i], { enumerable: true });
}
// Ensure `eventTarget instanceof window.EventTarget` is `true`.
if (typeof Global !== "undefined" &&
    typeof Global.EventTarget !== "undefined") {
    Object.setPrototypeOf(EventTarget.prototype, Global.EventTarget.prototype);
}

/**
 * Imports the keycloak JS API as if it was a module.
 *
 * @param baseUrl {string}
 */
async function importKeycloak(baseUrl) {
    const keycloakSrc = baseUrl + '/js/keycloak.min.js';
    // Importing will write it to window so we take it from there
    await import(keycloakSrc);
    if (importKeycloak._keycloakMod !== undefined)
        return importKeycloak._keycloakMod;
    importKeycloak._keycloakMod = window.Keycloak;
    delete window.Keycloak;
    return importKeycloak._keycloakMod;
}


const promiseTimeout = function(ms, promise) {
    let timeout = new Promise((resolve, reject) => {
      let id = setTimeout(() => {
        clearTimeout(id);
        reject('Timed out in '+ ms + 'ms.');
      }, ms);
    });

    return Promise.race([
      promise,
      timeout
    ]);
};


/**
 * Returns a URL for a relative path or URL
 *
 * @param {string} urlOrPath
 */
const ensureURL = function(urlOrPath) {
    try {
        return new URL(urlOrPath).href;
    } catch (e) {
        return new URL(urlOrPath, window.location.href).href;
    }
};


/**
 * Wraps the keycloak API to support async/await, adds auto token refreshing and consolidates all
 * events into one native "changed" event
 * 
 * The "changed" event has the real keycloak instance as "detail"
 */
class KeycloakWrapper extends EventTarget {

    constructor(baseURL, realm, clientId, silentCheckSsoUri, idpHint) {
        super();

        this._baseURL = baseURL;
        this._realm = realm;
        this._clientId = clientId;
        this._keycloak = null;
        this._initDone = false;
        this._silentCheckSsoUri = silentCheckSsoUri;
        this._idpHint = idpHint;
        this._checkId = null;

        /* Minimum validity of the token in seconds */
        this.MIN_VALIDITY = 20;

        /* Interval at which the token validity is checked, in seconds */
        this.CHECK_INTERVAL = 10;

        /* Enables extra debug logging */
        this.DEBUG = false;

        this._onVisibilityChanged = this._onVisibilityChanged.bind(this);
        document.addEventListener("visibilitychange", this._onVisibilityChanged);
    }

    /**
     * This needs to be called or the instance will leak;
     */
    close() {
        document.removeEventListener("visibilitychange", this._onVisibilityChanged);
    }

    _onVisibilityChanged() {
        let isVisible = (document.visibilityState === 'visible');
        if (isVisible && this._keycloak.authenticated) {
            this._checkTokeHasExpired();
        }
    }

    _onChanged() {
        const event = new CustomEvent("changed", {
            detail: this._keycloak,
            bubbles: true,
            composed: true
        });
        this.dispatchEvent(event);
    }

    _onReady(authenticated) {
        // Avoid emitting changed when nothing has changed on init()
        if (authenticated)
            this._onChanged();
    }

    async _onTokenExpired() {
        console.log('Token has expired');
        let refreshed = false;

        try {
            // -1 means force a refresh
            refreshed = await this._keycloak.updateToken(-1);
        } catch (error) {
            console.log('Failed to refresh the token', error);
            return;
        }

        console.assert(refreshed, "token should have been refreshed");
    }

    async _checkTokeHasExpired() {
        let refreshed;

        let minValidity = this.MIN_VALIDITY + this.CHECK_INTERVAL;
        if (this.DEBUG) {
            console.log(`Updating token if not valid for at least ${minValidity}s`);
        }
        try {
            refreshed = await this._keycloak.updateToken(minValidity);
        } catch (error) {
            console.log('Failed to refresh the token', error);
        }

        if (this.DEBUG && refreshed)
            console.log("token has been refreshed");
    }

    async _onAuthSuccess() {
        // We check every once in a while if the token is still valid and
        // and refresh it if needed.
        if (this._checkId !== null) {
            clearInterval(this._checkId);
            this._checkId = null;
        }
        this._checkId = setInterval(this._checkTokeHasExpired.bind(this), this.CHECK_INTERVAL * 1000);

        this._onChanged();
    }

    async _onAuthLogout() {
        if (this._checkId !== null) {
            clearInterval(this._checkId);
            this._checkId = null;
        }

        this._onChanged();
    }

    async _ensureInstance() {
        if (this._keycloak !== null)
            return;

        const Keycloak = await importKeycloak(this._baseURL);

        this._keycloak = Keycloak({
            url: this._baseURL,
            realm: this._realm,
            clientId: this._clientId,
        });

        this._keycloak.onTokenExpired = this._onTokenExpired.bind(this);
        this._keycloak.onAuthRefreshSuccess = this._onChanged.bind(this);
        this._keycloak.onAuthRefreshError = this._onChanged.bind(this);
        this._keycloak.onAuthLogout = this._onAuthLogout.bind(this);
        this._keycloak.onAuthSuccess = this._onAuthSuccess.bind(this);
        this._keycloak.onAuthError = this._onChanged.bind(this);
        this._keycloak.onReady = this._onReady.bind(this);
    }

    async _keycloakInit(options) {
        // https://gitlab.tugraz.at/dbp/apps/library/issues/41
        // retry the keycloak init in case it fails, maybe it helps :/
        try {
            return await this._keycloak.init(options);
        } catch (e) {
            return await this._keycloak.init(options);
        }
    }

    async _ensureInit() {
        await this._ensureInstance();
        if (this._initDone)
            return;
        this._initDone = true;

        const options = {
            promiseType: 'native',
            pkceMethod: 'S256',
        };

        if (this.DEBUG) {
            options['enableLogging'] = true;
        }

        if (this._silentCheckSsoUri) {

            options['onLoad'] = 'check-sso';
            options['silentCheckSsoRedirectUri'] = ensureURL(this._silentCheckSsoUri);

            // When silent-sso-check is active but the iframe doesn't load/work we will
            // never return here, so add a timeout and emit a signal so the app can continue
            await promiseTimeout(5000, this._keycloakInit(options)).catch(() => {
                console.log('Login timed out');
                this._onChanged();
            });
        } else {
            await this._keycloakInit(options);
        }

    }

    /**
     * If this returns true you need to call login() at one point to finish the login process.
     */
    isLoggingIn() {
        const href = window.location.href;
        return (href.search('[&#]state=') >= 0 && href.search('[&#]session_state=') >= 0);
    }

    /**
     * Logs the user in. Might lead to a site refresh or the user needing to authenticate.
     *
     * @param {object} options
     * @param {string} [options.lang] - The locale to use on the keycloak login page
     */
    async login(options) {
        await this._ensureInit();

        options = options || {};
        const language = options['lang'] || 'en';
        const scope = options['scope'] || '';

        if (!this._keycloak.authenticated) {
            await this._keycloak.login({
                kcLocale: language,  // Keycloak < 9.0
                locale: language,
                scope: scope,
                idpHint: this._idpHint,
            });
        }
    }

    /**
     * Logs the user in if it is possible without leaving the page or the user needing to authenticate again.
     */
    async tryLogin() {
        await this._ensureInit();
    }

    /**
     * Logs the user out locally, but not with keycloak. Login will instantly log the user back in without
     * requiring a re-auth.
     */
    async localLogout() {
        this._keycloak.clearToken();
    }

    /**
     * Log the user out from keycloak.
     */
    async logout() {
        await this._ensureInit();
        this._keycloak.logout();
    }
}

const LoginStatus = Object.freeze({
    UNKNOWN: 'unknown',
    LOGGING_IN: 'logging-in',
    LOGGED_IN: 'logged-in',
    LOGGING_OUT: 'logging-out',
    LOGGED_OUT: 'logged-out',
});

class AdapterLitElement extends LitElement {
    constructor() {
        super();
        this.connected = false;
        this.deferSubscribe = false;
        this.deferUnSubscribe = false;
        // attributes (if they exist) will be updated if a property is changed by "subscribe"
        this.reflectAttribute = true;

        // default values
        this.subscribe = '';
        this.unsubscribe = '';

        this.callbackStore = [];

        // Previously we used direct properties like this["lang"] (instead of this.propertyStore["lang"]) for storing the
        // properties, but the "lang" property seems to be updated before the event from the MutationObserver, so we
        // cannot observe a value change directly (as workaround we use another property (e.g. "langValue") instead of "lang")
        this.propertyStore = {};

        // We need to store our own "last values" because we cannot be sure what the MutationObserver detects
        this.lastProperties = {};

        console.log('AdapterLitElement(' + this.tagName + ') constructor()');
    }

    getProperty(name) {
        return this.propertyStore[name];
    }

    getPropertyByAttributeName(name) {
        return this[this.findPropertyName(name)];
    }

    setPropertyByAttributeName(name, value) {
        this[this.findPropertyName(name)] = value;
    }

    setProperty(name, value) {
        // TODO: check if lit attribute really present?
        if (typeof value === 'object' && value !== null) {
            // console.log("value is object", value);
            this.setPropertyByAttributeName(name, value);
        } else {
            this.attributeChangedCallback(name, this.getPropertyByAttributeName(name), value);
        }

        this.lastProperties[name] = value;
        this.propertyStore[name] = value;
    }

    hasPropertyChanged(name, value) {
        return this.lastProperties[name] !== value;
    }

    hasProperty(name) {
        // return this.hasAttribute("name")
        return Object.hasOwnProperty.call(this.propertyStore, name);
    }

    connectedCallback() {
        super.connectedCallback();

        if (this.deferUnSubscribe) {
            const attrs = this.unsubscribe.split(',');
            attrs.forEach(element => this.unSubscribeProviderFor(element));
            this.deferSubscribe = false;
            this.unsubscribe = '';
        }

        if (this.deferSubscribe) {
            const attrs = this.subscribe.split(',');
            attrs.forEach(element => this.subscribeProviderFor(element));
            this.deferSubscribe = false;
        }

        this.connected = true;

        const that = this;

        this.addEventListener('dbp-subscribe', function (e) {
            const name = e.detail.name;
            if (that.hasProperty(name) || that.root) {
                console.log('AdapterLitElementProvider(' + that.tagName + ') eventListener("dbp-subscribe",..) name "' + name + '" found.');
                that.callbackStore.push({name: name, callback: e.detail.callback, sender: e.detail.sender});

                e.detail.callback(that.getProperty(name));
                e.stopPropagation();
            }
        }, false);

        this.addEventListener('dbp-unsubscribe', function (e) {
            const name = e.detail.name;
            const sender = e.detail.sender;
            if (that.hasProperty(name) || that.root) {
                console.log('AdapterLitElementProvider(' + that.tagName + ') eventListener("dbp-unsubscribe",..) name "' + name + '" found.');
                that.callbackStore.forEach(item => {
                    if (item.sender === sender && item.name === name) {
                        const index = that.callbackStore.indexOf(item);
                        that.callbackStore.splice(index, 1);
                        console.log('AdapterLitElementProvider(' + that.tagName + ') eventListener for name "' + name + '" removed.');
                    }
                });

                e.stopPropagation();
            }
        }, false);

        // listen to property changes
        this.addEventListener('dbp-set-property', function (e) {
            const name = e.detail.name;
            const value = e.detail.value;

            if (that.hasProperty(name) || that.root) {
                console.log('AdapterLitElementProvider(' + that.tagName + ') eventListener("dbp-set-property",..) name "' + name + '" found.');
                that.setProperty(name, value);

                that.callbackStore.forEach(item => {
                    if (item.name === name) {
                        item.callback(value);
                    }
                });

                e.stopPropagation();
            }
        }, false);

        // Options for the observer (which mutations to observe)
        const config = { attributes: true, childList: false, subtree: false };

        // Callback function to execute when mutations are observed
        const callback = function(mutationsList, observer) {
            // Use traditional 'for loops' for IE 11
            for(const mutation of mutationsList) {
                if (mutation.type === 'attributes') {
                    const name = mutation.attributeName;
                    const value = that.getAttribute(name);

                    if (that.hasPropertyChanged(name, value)) {
                        console.log('AdapterLitElementProvider (' + that.tagName + ') observed attribute "' + name + '" changed');
                        that.setProperty(name, value);

                        that.callbackStore.forEach(item => {
                            if (item.name === name) {
                                item.callback(value);
                            }
                        });
                    }
                }
            }
        };

        // Create an observer instance linked to the callback function
        const observer = new MutationObserver(callback);

        // Start observing the target node for configured mutations
        observer.observe(this, config);

        // get all *not observed* attributes
        if (this.hasAttributes()) {
            const attrs = this.attributes;
            for(let i = attrs.length - 1; i >= 0; i--) {
                if (['id', 'class', 'style', 'data-tag-name'].includes(attrs[i].name)) {
                    continue;
                }

                this.setProperty(attrs[i].name, attrs[i].value);
                console.log('AdapterLitElementProvider (' + that.tagName + ') found attribute "' + attrs[i].name + '" = "' + attrs[i].value + '"');
            }
        }
    }

    disconnectedCallback() {
        const attrs = this.subscribe.split(',');
        attrs.forEach(element => this.unSubscribeProviderFor(element));

        super.disconnectedCallback();
    }

    subscribeProviderFor(element) {
        console.log('AdapterLitElement(' + this.tagName + ') subscribeProviderFor( ' + element + ' )');
        const pair = element.trim().split(':');
        const local = pair[0];
        const global = pair[1] || local;
        const that = this;
        const event = new CustomEvent('dbp-subscribe',
            {
                bubbles: true,
                composed: true,
                detail: {
                    name: global,
                    callback: (value) => {
                        console.log('AdapterLitElement(' + that.tagName + ') sub/Callback ' + global + ' -> ' + local + ' = ' + value);

                        // If value is an object set it directly as property
                        if (typeof value === 'object' && value !== null) {
                            // console.log("value is object", value);
                            that.setPropertyByAttributeName(local, value);
                        } else {
                            // console.log("local, that.getPropertyByAttributeName(local), value", local, that.getPropertyByAttributeName(local), value);
                            that.attributeChangedCallback(local, that.getPropertyByAttributeName(local), value);

                            // check if an attribute also exists in the tag
                            if (that.getAttribute(local) !== null) {
                                // we don't support attributes and provider values at the same time
                                console.warn('Provider callback: "' + local + '" is also an attribute in tag "' + that.tagName + '", this is not supported!');

                                // update attribute if reflectAttribute is enabled
                                if (that.reflectAttribute) {
                                    that.setAttribute(local, value);
                                }
                            }
                        }
                    },
                    sender: this,
                }
            });
        this.dispatchEvent(event);
    }

    unSubscribeProviderFor(element) {
        console.log('AdapterLitElement(' + this.tagName + ') unSubscribeProviderFor( ' + element + ' )');
        const pair = element.trim().split(':');
        const global = pair[1] || pair[0];
        const event = new CustomEvent('dbp-unsubscribe',
            {
                bubbles: true,
                composed: true,
                detail: {
                    name: global,
                    sender: this,
                }
            });
        this.dispatchEvent(event);
    }

    static get properties() {
        return {
            subscribe: { type: String },
            unsubscribe: { type: String },
        };
    }

    findPropertyName(attributeName) {
        let resultName = attributeName;
        const properties = this.constructor.properties;
        // console.log("properties", properties);

        for (const propertyName in properties) {
            // console.log("findPropertyName", `${propertyName}: ${properties[propertyName]}`);
            const attribute = properties[propertyName].attribute;
            if (attribute === attributeName) {
                resultName = propertyName;
                break;
            }
        }

        return resultName;
    }

    attributeChangedCallback(name, oldValue, newValue) {
        switch(name) {
            case 'subscribe':
                console.log('AdapterLitElement() attributeChangesCallback( ' + name + ', ' + oldValue + ', ' + newValue + ')');

                if (this.subscribe && this.subscribe.length > 0) {
                    if (this.connected) {
                        const attrs = this.subscribe.split(',');
                        attrs.forEach(element => this.unSubscribeProviderFor(element));
                    } else {
                        this.deferUnSubscribe = this.subscribe.length > 0;
                        this.unsubscribe = this.subscribe;
                    }
                }

                if (newValue !== null) {
                    this.subscribe = newValue;
                    if (this.connected) {
                        const attrs = newValue.split(',');
                        attrs.forEach(element => this.subscribeProviderFor(element));
                    } else {
                        this.deferSubscribe = newValue && newValue.length > 0;
                    }
                }
                break;
            default:
                // The function should not be called if newValue is empty but name and oldValue are set
                // This should prevent 'Uncaught SyntaxError: JSON.parse unexpected end of data at line 1 column 1 of the JSON data'
                if (newValue || !oldValue || !name) {
                    super.attributeChangedCallback(name, oldValue, newValue);
                // } else {
                    //     console.log("attributeChangedCallback ignored", name, oldValue, newValue);
                }
        }

        // console.log("this.lang", this.tagName, name, this.lang);
        // console.log("this.entryPointUrl", this.tagName, name, this.entryPointUrl);
        // console.trace();
    }

    /**
     * Send a dbp-set-property event to the provider components
     *
     * @param name
     * @param value
     * @returns {boolean}
     */
    sendSetPropertyEvent(name, value) {
        // console.log("dbp-set-property", name, value);

        const event = new CustomEvent('dbp-set-property', {
            bubbles: true,
            composed: true,
            detail: {'name': name, 'value': value}
        });

        return this.dispatchEvent(event);
    }

    // update(changedProperties) {
    //     changedProperties.forEach((oldValue, propName) => {
    //         switch(propName) {
    //             case 'subscribe':
    //                 if (this.subscribe && this.subscribe.length > 0) {
    //                     if (this.connected) {
    //                         const attrs = this.subscribe.split(',');
    //                         attrs.forEach(element => this.unSubscribeProviderFor(element));
    //                     } else {
    //                         this.deferUnSubscribe = this.subscribe.length > 0;
    //                         this.unsubscribe = this.subscribe;
    //                     }
    //                 }
    //                 if (this.subscribe !== null) {
    //                     if (this.connected) {
    //                         const attrs = this.subscribe.split(',');
    //                         attrs.forEach(element => this.subscribeProviderFor(element));
    //                     } else {
    //                         this.deferSubscribe = this.subscribe && this.subscribe.length > 0;
    //                     }
    //                 }
    //                 break;
    //         }
    //     });
    //
    //     super.update(changedProperties);
    // }
}

/**
 * Keycloak auth web component
 * https://www.keycloak.org/docs/latest/securing_apps/index.html#_javascript_adapter
 *
 * Emits a dbp-set-property event for the attribute "auth":
 *   auth.subject: Keycloak username
 *   auth.login-status: Login status (see object LoginStatus)
 *   auth.token: Keycloak token to send with your requests
 *   auth.user-full-name: Full name of the user
 *   auth.person-id: Person identifier of the user
 *   auth.person: Person json object of the user (optional, enable by setting the `load-person` attribute)
 */
class AuthKeycloak extends AdapterLitElement {
    constructor() {
        super();
        this.lang = 'de';
        this.forceLogin = false;
        this.loadPerson = false;
        this.token = "";
        this.subject = "";
        this.name = "";
        this.personId = "";
        this.tryLogin = false;
        this.person = null;
        this.entryPointUrl = '';
        this._loginStatus = LoginStatus.UNKNOWN;
        this.requestedLoginStatus = LoginStatus.UNKNOWN;

        // Keycloak config
        this.keycloakUrl = null;
        this.realm = null;
        this.clientId = null;
        this.silentCheckSsoRedirectUri = null;
        this.scope = null;
        this.idpHint = '';

        this._onKCChanged = this._onKCChanged.bind(this);
    }

    update(changedProperties) {
        // console.log("changedProperties", changedProperties);
        changedProperties.forEach((oldValue, propName) => {
            switch (propName) {
                case 'lang':
                    i18n.changeLanguage(this.lang);
                break;
                case 'requestedLoginStatus':
                    console.log("requested-login-status changed", this.requestedLoginStatus);
                    switch(this.requestedLoginStatus) {
                        case LoginStatus.LOGGED_IN:
                            this._kcwrapper.login({lang: this.lang, scope: this.scope || ''});
                        break;
                        case LoginStatus.LOGGED_OUT:
                            // Keycloak will redirect right away without emitting events, so we have
                            // to do this manually here
                            if (this._loginStatus === LoginStatus.LOGGED_IN) {
                                this._setLoginStatus(LoginStatus.LOGGING_OUT);
                            }
                            this._kcwrapper.logout();
                            // In case logout was aborted, for example with beforeunload,
                            // revert back to being logged in
                            if (this._loginStatus === LoginStatus.LOGGING_OUT) {
                                this._setLoginStatus(LoginStatus.LOGGED_IN);
                            }
                        break;
                    }
                break;
            }
        });

        super.update(changedProperties);
    }

    _onKCChanged(event) {
        const kc = event.detail;
        let newPerson = false;

        if (kc.authenticated) {
            let tokenChanged = (this.token !== kc.token);
            this.name = kc.idTokenParsed.name;
            this.token = kc.token;

            this.subject = kc.subject;
            const personId = kc.idTokenParsed.preferred_username;
            if (personId !== this.personId) {
                this.person = null;
                newPerson = true;
            }
            this.personId = personId;

            this.sendSetPropertyEvents();
            this._setLoginStatus(LoginStatus.LOGGED_IN, tokenChanged || newPerson);
        } else {
            if (this._loginStatus === LoginStatus.LOGGED_IN) {
                this._setLoginStatus(LoginStatus.LOGGING_OUT);
            }
            this.name = "";
            this.token = "";
            this.subject = "";
            this.personId = "";
            this.person = null;

            this.sendSetPropertyEvents();
            this._setLoginStatus(LoginStatus.LOGGED_OUT);
        }

        const that = this;

        if (newPerson && this.loadPerson) {
            JSONLD.initialize(this.entryPointUrl, (jsonld) => {
                // find the correct api url for the current person
                // we are fetching the logged-in person directly to respect the REST philosophy
                // see: https://github.com/api-platform/api-platform/issues/337
                const apiUrl = jsonld.getApiUrlForEntityName("Person") + '/' + that.personId;

                fetch(apiUrl, {
                    headers: {
                        'Content-Type': 'application/ld+json',
                        'Authorization': 'Bearer ' + that.token,
                    },
                })
                .then(response => response.json())
                .then((person) => {
                    that.person = person;
                    this.sendSetPropertyEvents();
                    this._setLoginStatus(this._loginStatus, true);
                });
            }, {}, that.lang);
        }
    }

    sendSetPropertyEvents() {
        const auth = {
            'login-status': this._loginStatus,
            'subject': this.subject,
            'token': this.token,
            'user-full-name': this.name,
            'person-id': this.personId,
            'person': this.person,
        };

        // inject a window.DBPAuth variable for cypress
        if (window.Cypress) {
            window.DBPAuth = auth;
        }

        this.sendSetPropertyEvent('auth', auth);
        JSONLD.doInitializationOnce(this.entryPointUrl, this.token);

        // this.sendSetPropertyEvent('auth-subject', this.subject);
        // this.sendSetPropertyEvent('auth-token', this.token);
        // this.sendSetPropertyEvent('user-full-name', this.name);
        // this.sendSetPropertyEvent('person-id', this.personId);
        // this.sendSetPropertyEvent('person', this.person);
    }

    _setLoginStatus(status, force) {
        if (this._loginStatus === status && !force)
            return;

        this._loginStatus = status;
        this.sendSetPropertyEvents();
    }

    static get properties() {
        return {
            ...super.properties,
            lang: { type: String },
            forceLogin: { type: Boolean, attribute: 'force-login' },
            tryLogin: { type: Boolean, attribute: 'try-login' },
            loadPerson: { type: Boolean, attribute: 'load-person' },
            entryPointUrl: { type: String, attribute: 'entry-point-url' },
            name: { type: String, attribute: false },
            token: { type: String, attribute: false },
            subject: { type: String, attribute: false },
            personId: { type: String, attribute: false },
            person: { type: Object, attribute: false },
            _loginStatus: { type: String, attribute: false },
            keycloakUrl: { type: String, attribute: 'url' },
            realm: { type: String },
            clientId: { type: String, attribute: 'client-id' },
            silentCheckSsoRedirectUri: { type: String, attribute: 'silent-check-sso-redirect-uri' },
            scope: { type: String },
            idpHint: { type: String, attribute: 'idp-hint' },
            requestedLoginStatus: { type: String, attribute: 'requested-login-status' },
        };
    }

    connectedCallback() {
        super.connectedCallback();

        if (!this.keycloakUrl)
            throw Error("url not set");
        if (!this.realm)
            throw Error("realm not set");
        if (!this.clientId)
            throw Error("client-id not set");

        this._kcwrapper = new KeycloakWrapper(this.keycloakUrl, this.realm, this.clientId, this.silentCheckSsoRedirectUri, this.idpHint);
        this._kcwrapper.addEventListener('changed', this._onKCChanged);

        const handleLogin = async () => {
            if (this.forceLogin || this._kcwrapper.isLoggingIn()) {
                this._setLoginStatus(LoginStatus.LOGGING_IN);
                await this._kcwrapper.login({lang: this.lang, scope: this.scope || ''});
            } else if (this.tryLogin) {
                this._setLoginStatus(LoginStatus.LOGGING_IN);
                await this._kcwrapper.tryLogin();
                if (this._loginStatus === LoginStatus.LOGGING_IN)
                    this._setLoginStatus(LoginStatus.LOGGED_OUT);
            } else {
                this._setLoginStatus(LoginStatus.LOGGED_OUT);
            }
        };

        handleLogin();
    }

    disconnectedCallback() {
        this._kcwrapper.close();
        this._kcwrapper.removeEventListener('changed', this._onKCChanged);

        super.disconnectedCallback();
    }
}

/**
 * @license
 * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt
 * The complete set of authors may be found at
 * http://polymer.github.io/AUTHORS.txt
 * The complete set of contributors may be found at
 * http://polymer.github.io/CONTRIBUTORS.txt
 * Code distributed by Google as part of the polymer project is also
 * subject to an additional IP rights grant found at
 * http://polymer.github.io/PATENTS.txt
 */
// For each part, remember the value that was last rendered to the part by the
// unsafeHTML directive, and the DocumentFragment that was last set as a value.
// The DocumentFragment is used as a unique key to check if the last value
// rendered to the part was with unsafeHTML. If not, we'll always re-render the
// value passed to unsafeHTML.
const previousValues = new WeakMap();
/**
 * Renders the result as HTML, rather than text.
 *
 * Note, this is unsafe to use with any user-provided input that hasn't been
 * sanitized or escaped, as it may lead to cross-site-scripting
 * vulnerabilities.
 */
const unsafeHTML = directive((value) => (part) => {
    if (!(part instanceof NodePart)) {
        throw new Error('unsafeHTML can only be used in text bindings');
    }
    const previousValue = previousValues.get(part);
    if (previousValue !== undefined && isPrimitive(value) &&
        value === previousValue.value && part.value === previousValue.fragment) {
        return;
    }
    const template = document.createElement('template');
    template.innerHTML = value; // innerHTML casts to string internally
    const fragment = document.importNode(template.content, true);
    part.setValue(fragment);
    previousValues.set(part, { value, fragment });
});

/**
 * We want to have "neutral" colors here
 *
 * @returns {CSSResult}
 */
function getThemeCSS() {
    // language=css
    return css`
        :host {
            --dbp-primary-bg-color: var(--dbp-override-primary-bg-color, #007bff);
            --dbp-primary-text-color: var(--dbp-override-primary-text-color, #fff);
            --dbp-primary-button-border: var(--dbp-override-primary-button-border, #007bff);
            --dbp-secondary-bg-color: var(--dbp-override-secondary-bg-color, #6c757d);
            --dbp-secondary-text-color: var(--dbp-override-secondary-text-color, #fff);
            --dbp-info-bg-color: var(--dbp-override-info-bg-color, #17a2b8);
            --dbp-info-text-color: var(--dbp-override-info-text-color, #fff);
            --dbp-success-bg-color: var(--dbp-override-success-bg-color, #28a745);
            --dbp-success-text-color: var(--dbp-override-success-text-color, #fff);
            --dbp-warning-bg-color: var(--dbp-override-warning-bg-color, #ffc107);
            --dbp-warning-text-color: var(--dbp-override-warning-text-color, #343a40);
            --dbp-danger-bg-color: var(--dbp-override-danger-bg-color, #dc3545);
            --dbp-danger-text-color: var(--dbp-override-danger-text-color, #fff);
            --dbp-light: var(--dbp-override-light, #f8f9fa);
            --dbp-dark: var(--dbp-override-dark, #343a40);
            --dbp-muted-text: var(--dbp-override-muted-text, #6c757d);
            --dbp-border-radius: var(--dbp-override-border-radius, 0px);
            --dbp-border-width: var(--dbp-override-border-width, 1px);
            --dbp-border-color: var(--dbp-override-border-color, #000);
            --dbp-placeholder-color: #777; 
        }
    `;
}

let logoutSVG = `
<svg
   viewBox="0 0 100 100"
   y="0px"
   x="0px"
   id="icon"
   role="img"
   version="1.1">
<g
   id="g6">
  <path
     d="m 20.749313,38.894934 -5.885859,6.967885 h 43.408213 c 1.839331,0 3.433418,1.741972 3.433418,4.064599 0,2.322628 -1.471465,4.064599 -3.433418,4.064599 H 14.863454 l 5.885859,6.967883 c 1.348843,1.596808 1.348843,4.064599 0,5.661406 -1.348843,1.596808 -3.433418,1.596808 -4.782261,0 L 1.9881356,49.927418 15.967052,33.378693 c 1.348843,-1.596806 3.433418,-1.596806 4.782261,0 1.348843,1.596807 1.348843,4.064599 0,5.516241 z"
     id="path2"
     style="stroke-width:1.33417916"
     inkscape:connector-curvature="0" />
  <path
     style="stroke-width:0.67017764"
     d="M 61.663665,16.308792 C 48.158763,16.560171 35.913199,25.828579 30.896087,38.197464 34.816744,37.806184 40.033349,39.91491 41.470306,35.017776 50.594944,21.215302 72.517616,20.362655 82.800384,33.07637 93.497261,44.618596 90.228387,65.093356 76.499603,72.791214 64.104901,80.786232 45.895432,75.593227 39.470306,62.310745 35.613955,62.60637 27.974792,60.593775 32.925384,66.267776 41.232037,82.878292 64.023613,89.46919 79.876556,79.765823 96.140149,70.989504 102.10102,48.145494 91.970306,32.629104 85.705979,22.257901 73.793809,15.772382 61.663665,16.308792 Z"
     id="path4"
     inkscape:connector-curvature="0" />
</g>
</svg>
`;

let loginSVG = `
<svg
   viewBox="0 0 100 100"
   y="0px"
   x="0px"
   id="icon"
   role="img"
   version="1.1">
<g
   id="g6">
    <path
   style="stroke-width:1.33417916"
   id="path2"
   d="m 42.943908,38.894934 5.885859,6.967885 H 5.4215537 c -1.8393311,0 -3.4334181,1.741972 -3.4334181,4.064599 0,2.322628 1.4714649,4.064599 3.4334181,4.064599 H 48.829767 L 42.943908,60.9599 c -1.348843,1.596808 -1.348843,4.064599 0,5.661406 1.348843,1.596808 3.433418,1.596808 4.782261,0 L 61.705085,49.927418 47.726169,33.378693 c -1.348843,-1.596806 -3.433418,-1.596806 -4.782261,0 -1.348843,1.596807 -1.348843,4.064599 0,5.516241 z" />
    <path
   id="path4"
   d="m 50,2.3007812 c -18.777325,0 -35.049449,10.9124408 -42.8261719,26.7246098 H 13.390625 C 20.672112,16.348362 34.336876,7.8007812 50,7.8007812 73.3,7.8007812 92.300781,26.7 92.300781,50 92.300781,73.3 73.3,92.300781 50,92.300781 c -15.673389,0 -29.345175,-8.60579 -36.623047,-21.326172 H 7.1640625 C 14.942553,86.8272 31.242598,97.800781 50.099609,97.800781 76.399609,97.800781 97.900391,76.4 97.900391,50 97.800391,23.7 76.3,2.3007812 50,2.3007812 Z" />
</g>
</svg>
`;

let loggingInSVG = `
<svg
   viewBox="0 0 100 100"
   y="0px"
   x="0px"
   id="icon"
   role="img"
   version="1.1">
</svg>
`;

class LoginButton extends ScopedElementsMixin(AdapterLitElement) {

    constructor() {
        super();
        this.lang = 'de';
        this.auth = {};
    }

    static get scopedElements() {
        return {
        };
    }

    static get properties() {
        return {
            lang: { type: String },
            auth: { type: Object },
        };
    }

    connectedCallback() {
        super.connectedCallback();
    }

    disconnectedCallback() {
        super.disconnectedCallback();
    }

    _onLoginClicked(e) {
        this.sendSetPropertyEvent('requested-login-status', LoginStatus.LOGGED_IN);
        e.preventDefault();
    }

    _onLogoutClicked(e) {
        this.sendSetPropertyEvent('requested-login-status', LoginStatus.LOGGED_OUT);
        e.preventDefault();
    }

    update(changedProperties) {
        changedProperties.forEach((oldValue, propName) => {
            if (propName === "lang") {
                i18n.changeLanguage(this.lang);
            }
        });

        super.update(changedProperties);
    }

    static get styles() {
        // language=css
        return [
            getThemeCSS(),
            css`
            :host {
                display: inline-block;
            }

            a {
                color: currentColor;
                cursor: pointer;
                text-decoration: none;
            }

            .login-box {
                display: flex;
                align-items: center;
                padding: 0.3em 0.4em;
                transition: background-color 0.15s, color 0.15s;
            }

            .login-box svg {
                width: 1.1em;
                height: 1.1em;
                display: flex;
            }

            .login-box:hover svg path {
                fill: var(--dbp-light);
            }

            .login-box:hover {
                background-color: var(--dbp-dark);
                color: var(--dbp-light);
                cursor: pointer;
                transition: none;
            }

            .login-box .label {
                padding-left: 0.2em;
            }
            `
        ];
    }

    render() {
        if (this.auth['login-status'] === LoginStatus.LOGGING_IN) {
            // try to keep the layout the same to avoid layout shifts
            return html`
                <a href="#">
                    <div class="login-box login-button">
                        <div class="icon">${unsafeHTML(loggingInSVG)}</div>
                        <div class="label">&#8203;</div>
                    </div>
                </a>
            `;
        } else if (this.auth['login-status'] === LoginStatus.LOGGED_IN) {
            return html`
                <a href="#" @click="${this._onLogoutClicked}">
                    <div class="login-box login-button">
                        <div class="icon">${unsafeHTML(logoutSVG)}</div>
                        <div class="label">${i18n.t('logout')}</div>
                    </div>
                </a>
            `;
        } else {
            return html`
                <a href="#" @click="${this._onLoginClicked}">
                    <div class="login-box login-button">
                        <div class="icon">${unsafeHTML(loginSVG)}</div>
                        <div class="label">${i18n.t('login')}</div>
                    </div>
                </a>
            `;
        }
    }
}

class ApiPlatformAuth extends ScopedElementsMixin(AdapterLitElement) {
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
                               load-person try-login></dbp-auth-keycloak>
            <dbp-login-button subscribe="auth" lang="${this.lang}" show-image></dbp-login-button>
        `;
    }
}

defineCustomElement('api-platform-auth', ApiPlatformAuth);

export { ApiPlatformAuth };
//# sourceMappingURL=api-platform-auth.js.map
