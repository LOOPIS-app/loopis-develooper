'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../../loopis-webmaster/includes/image-cleanup/page.php'), 'utf8');
const match = source.match(/<script>\s*(\(function\(\) \{[\s\S]*?\}\)\(\);)/);
assert.ok(match, 'The cleanup preview script must exist.');

const handlers = {};
const imageHandlers = {};
const image = {
    hidden: true,
    addEventListener(name, callback) { imageHandlers[name] = callback; },
};
const status = { hidden: false, textContent: 'Loading preview…' };
const popup = { querySelector(selector) { return selector === 'img' ? image : status; } };
const trigger = {
    dataset: { previewUrl: 'https://example.test/uploads/sites/2/2026/11/image.jpg' },
    parentNode: { querySelector() { return popup; } },
};
const event = { target: { closest() { return trigger; } } };
vm.runInNewContext(match[1], {
    document: { addEventListener(name, callback) { handlers[name] = callback; } },
});
assert.equal(image.src, undefined, 'Images must not load before interaction.');
handlers.pointerover(event);
assert.equal(image.src, trigger.dataset.previewUrl);
imageHandlers.load();
assert.equal(image.hidden, false);
assert.equal(status.hidden, true);

delete trigger.dataset.loaded;
image.hidden = true;
status.hidden = false;
handlers.focusin(event);
imageHandlers.error();
assert.equal(status.textContent, 'Preview unavailable for this image.');
assert.equal(image.hidden, true);
console.log('Cleanup preview interaction tests passed.');
