'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../../loopis-webmaster/includes/image-conversion/batched-scan.php'), 'utf8');
const script = source.match(/<script>\s*([\s\S]*?)\s*<\/script>/);
assert.ok(script, 'The shared scan form script must exist.');

async function testForm(tool, resume, shared) {
    const controls = [
        { name: 'action', value: 'loopis_image_tools_scan', disabled: false },
        { name: 'tool', value: tool, disabled: false },
        { name: '_wpnonce', value: 'nonce', disabled: false },
        { name: 'year', value: '2026', disabled: false },
        { name: 'month', value: '02', disabled: false },
    ];
    const button = { disabled: false };
    const year = controls[3];
    const month = controls[4];
    const handlers = {};
    const resumeButton = resume ? {
        disabled: false,
        dataset: { scanId: 'existing-scan' },
        addEventListener(name, callback) { handlers.resume = callback; },
    } : null;
    const form = {
        elements: { year, month },
        querySelectorAll() { return resumeButton ? [button, year, month, resumeButton] : [button, year, month]; },
        addEventListener(name, callback) { handlers[name] = callback; },
    };
    const classes = new Set(['loopis-image-scan-complete']);
    const progress = { textContent: '', classList: {
        add(name) { classes.add(name); },
        remove(name) { classes.delete(name); },
        toggle(name, enabled) { if (enabled) classes.add(name); else classes.delete(name); },
    } };
    const requests = [];
    let resolveReload;
    const reloaded = new Promise(resolve => { resolveReload = resolve; });
    class FormDataStub {
        constructor() {
            this.values = new Map(controls.filter(control => !control.disabled).map(control => [control.name, control.value]));
        }
        set(name, value) { this.values.set(name, value); }
        get(name) { return this.values.get(name); }
    }
    const context = {
        document: { getElementById(id) {
            return { 'loopis-image-scan-form': form, 'loopis-image-scan-progress': progress, 'loopis-image-scan-resume': resumeButton }[id];
        } },
        window: { confirm: () => true, location: { reload: resolveReload } },
        FormData: FormDataStub,
        ajaxurl: '/wp-admin/admin-ajax.php',
        fetch: async (url, options) => {
            if (!requests.length) assert.equal(classes.has('loopis-image-scan-complete'), false, 'An active scan must not retain the completed color.');
            assert.ok(classes.has('loopis-image-processing'), 'A running scan must use the active status color.');
            requests.push(new Map(options.body.values));
            return { ok: true, status: 200, text: async () => JSON.stringify({ success: true, data: options.body.get('operation') === 'start'
                ? { id: 'new-scan', status: 'active' }
                : { id: options.body.get('scan_id'), status: 'complete', year: '2026', month: 13, files: 9, candidates: 3, pending_attachments: 2, pending_source_files: 5, skipped_attachments: 1, shared } }) };
        },
    };
    vm.runInNewContext(script[1], context);
    if (resume) handlers.resume();
    else handlers.submit({ preventDefault() {} });
    await reloaded;
    assert.ok(classes.has('loopis-image-scan-complete'), 'A completed scan must use the green status class.');
    assert.equal(classes.has('loopis-image-processing'), false, 'A completed scan must no longer use the active status color.');
    assert.equal(progress.textContent.includes('multiple attachment registrations'), shared > 0, 'Shared-file details must be shown only when files are affected.');
    if (tool === 'conversion') assert.match(progress.textContent, /2 pending attachments \(5 existing source and associated files\); 1 skipped attachments/);
    else assert.doesNotMatch(progress.textContent, /pending attachments/);
    assert.equal(requests.length, resume ? 1 : 2);
    assert.equal(requests[0].get('action'), 'loopis_image_tools_scan');
    assert.equal(requests[0].get('tool'), tool);
    assert.equal(requests[0].get('_wpnonce'), 'nonce');
    if (resume) {
        assert.equal(requests[0].get('operation'), 'batch');
        assert.equal(requests[0].get('scan_id'), 'existing-scan');
    } else {
        assert.equal(requests[0].get('operation'), 'start');
        assert.equal(requests[0].get('year'), '2026', 'The selected year must be submitted even while the selector is disabled.');
        assert.equal(requests[0].get('month'), '02', 'The selected month must be submitted even while the selector is disabled.');
        assert.equal(requests[1].get('scan_id'), 'new-scan');
    }
}

async function testInvalidResponse() {
    let submit;
    let finish;
    const finished = new Promise(resolve => { finish = resolve; });
    const classes = new Set();
    const progress = { textContent: '', classList: {
        add(name) { classes.add(name); },
        remove(name) { classes.delete(name); },
        toggle(name, enabled) { if (enabled) classes.add(name); else classes.delete(name); },
    } };
    const form = {
        querySelectorAll() { finish(); return []; },
        addEventListener(name, callback) { submit = callback; },
    };
    let calls = 0;
    form.querySelectorAll = () => { if (++calls === 2) finish(); return []; };
    vm.runInNewContext(script[1], {
        document: { getElementById(id) { return id === 'loopis-image-scan-form' ? form : id === 'loopis-image-scan-progress' ? progress : null; } },
        window: { confirm: () => true, location: { reload() { throw new Error('Must not reload after a failed request.'); } } },
        FormData: class { set() {} },
        ajaxurl: '/wp-admin/admin-ajax.php',
        fetch: async () => ({ ok: true, status: 200, text: async () => '<p>There has been a critical error</p>' }),
    });
    submit({ preventDefault() {} });
    await finished;
    assert.match(progress.textContent, /non-JSON response \(HTTP 200\)/);
    assert.match(progress.textContent, /PHP error log/);
    assert.equal(classes.has('loopis-image-scan-complete'), false, 'A server error must not appear successful.');
}

(async () => {
    for (const tool of ['cleanup', 'conversion']) {
        for (const shared of [0, 1]) {
            await testForm(tool, false, shared);
            await testForm(tool, true, shared);
        }
        await testInvalidResponse();
    }
    console.log('Image scan form tests passed.');
})().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
