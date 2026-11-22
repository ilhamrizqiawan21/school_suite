import assert from 'node:assert/strict';
import test from 'node:test';
import { contrastAgainstWhite, whiteTextContrastLabel } from '../../resources/js/lib/colorContrast.ts';

test('gold preset is not labelled as passing normal-text AA', () => {
    assert.ok(Math.abs(contrastAgainstWhite('#b87818') - 3.66) < 0.01);
    assert.match(whiteTextContrastLabel('#b87818'), /Di bawah AA/);
});

test('contrast handles passing presets, extremes and invalid input', () => {
    assert.match(whiteTextContrastLabel('#3157a4'), /\(AA\)/);
    assert.equal(contrastAgainstWhite('#000000'), 21);
    assert.equal(contrastAgainstWhite('#FFFFFF'), 1);
    assert.equal(contrastAgainstWhite('invalid'), 1);
});
