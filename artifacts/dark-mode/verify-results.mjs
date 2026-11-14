import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const read = async name => JSON.parse(await readFile(new URL(name, import.meta.url), 'utf8'));
const base = await read('after.json');
const extended = await read('extended-after.json');
const transitions = await read('transitions.json');
assert.equal(base.length, 76);
assert.equal(extended.length, 108);
assert.equal(transitions.length, 11);
for (const result of [...base, ...extended]) {
    assert.deepEqual(result.errors, [], `${result.key}: JavaScript errors`);
    for (const observation of [result, result.opened].filter(Boolean)) {
        if (observation.theme === 'dark') {
            assert.deepEqual(observation.issues, [], `${result.key}/${result.width}: dark text contrast`);
        } else {
            const actionable = observation.issues.filter(issue => !(issue.cls.includes('text-slate-300') && /Previous|Next/.test(issue.text)));
            assert.deepEqual(actionable, [], `${result.key}/${result.width}: light text contrast`);
        }
    }
}
for (const result of transitions) {
    assert.deepEqual(result.errors, []);
    assert.equal(result.toggle, true);
    if (result.mode) {
        for (const key of ['initialPaint', 'reload', 'rapidToggle', 'crossTab', 'publicRoundtrip']) assert.equal(result[key], true);
    }
}
console.log('Passed: 184 render observations and 11 theme scenarios; no sampled dark text contrast failures or JavaScript errors.');
