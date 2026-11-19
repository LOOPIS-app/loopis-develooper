'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

async function testProcess(tool, fail) {
    const source = fs.readFileSync(path.join(__dirname, '../../../loopis-webmaster/includes/image-' + tool + '/page.php'), 'utf8');
    const expression = new RegExp('\\(function\\(\\) \\{\\s*const form = document\\.getElementById\\(\'loopis-image-' + tool + '-process-form\'\\);[\\s\\S]*?\\}\\)\\(\\);');
    const match = source.match(expression);
    assert.ok(match, 'The process script must exist.');
    const classes = new Set();
    const progress = { textContent: '', classList: {
        add(name) { classes.add(name); },
        remove(name) { classes.delete(name); },
    } };
    const stop = { hidden: true, disabled: false, classList: { toggle() {}, add() {} }, addEventListener() {} };
    const start = { disabled: false };
    const one = { name: 'process_one', disabled: false };
    let submit;
    const form = {
        reportValidity() { return true; },
        querySelector(selector) { return selector === 'input[type="submit"]' ? start : one; },
        querySelectorAll() { return [start, one]; },
        addEventListener(name, callback) { submit = callback; },
    };
    let resolveFetch;
    const response = new Promise(resolve => { resolveFetch = resolve; });
    const context = {
        document: { getElementById(id) {
            if (id.endsWith('-form')) return form;
            if (id.endsWith('-stop')) return stop;
            return progress;
        } },
        window: { location: { reload() {} } },
        FormData: class { set() {} },
        ajaxurl: '/wp-admin/admin-ajax.php',
        fetch: () => response,
    };
    vm.runInNewContext(match[0], context);
    const completion = submit({ preventDefault() {}, submitter: start });
    assert.ok(classes.has('loopis-image-processing'), tool + ' must show red status while the request is executing.');
    resolveFetch({ ok: !fail, json: async () => fail
        ? { success: false, data: { message: 'Test failure' } }
        : { success: true, data: { pending: 0, message: 'Complete' } } });
    await completion;
    assert.equal(classes.has('loopis-image-processing'), false, tool + ' must clear the executing state after completion or error.');
    if (fail) assert.ok(progress.textContent.includes('Test failure'), 'Errors must remain visible.');
}

(async () => {
    for (const tool of ['cleanup', 'conversion']) {
        await testProcess(tool, false);
        await testProcess(tool, true);
    }
    console.log('Image processing status tests passed.');
})().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
