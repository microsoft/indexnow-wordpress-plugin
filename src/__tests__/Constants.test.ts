/**
 * Constants & Regex validation tests.
 *
 * These are pure-logic tests with zero UI coupling — they should never
 * need updating unless the actual validation rules change.
 */

import { StringConstants, ApiKeyRegex, SubmitUrlRegex } from '../Constants';

describe('StringConstants', () => {
  it('has expected static links', () => {
    expect(StringConstants.IndexNowLink).toMatch(/^https:\/\//);
    expect(StringConstants.PluginInfoLink).toMatch(/wordpress\.org/);
  });

  it('has non-empty error messages', () => {
    expect(StringConstants.ApiKeyValidationError.length).toBeGreaterThan(0);
    expect(StringConstants.UrlSubmitErrorMessage.length).toBeGreaterThan(0);
  });
});

describe('ApiKeyRegex', () => {
  const valid = [
    'abcdefghijklmnopqrstuvwxyz012345', // 32 lower + digits
    'ABCDEFGHIJKLMNOPQRSTUVWXYZ012345', // 32 upper + digits
    'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4', // mixed
    '',                                   // empty is valid per regex (0-32)
    'abc',                                // partial (< 32) still matches
  ];

  const invalid = [
    'abc-def',                            // hyphen
    'abc!@#$',                            // special chars
    'abcdefghijklmnopqrstuvwxyz0123456',  // 33 chars — too long
  ];

  it.each(valid)('accepts "%s"', (key) => {
    expect(ApiKeyRegex.test(key)).toBe(true);
  });

  it.each(invalid)('rejects "%s"', (key) => {
    expect(ApiKeyRegex.test(key)).toBe(false);
  });
});

describe('SubmitUrlRegex', () => {
  const valid = [
    'https://example.com',
    'https://example.com/path/to/page',
    'http://www.example.com/page?q=1&r=2',
    'https://sub.domain.example.co.uk/path',
    'https://example.com/path#anchor',
  ];

  const invalid = [
    '',
    'not-a-url',
    'ftp://example.com',           // wrong scheme
    'https://',                     // missing host
    '://example.com',              // missing scheme
  ];

  it.each(valid)('accepts "%s"', (url) => {
    expect(SubmitUrlRegex.test(url)).toBe(true);
  });

  it.each(invalid)('rejects "%s"', (url) => {
    expect(SubmitUrlRegex.test(url)).toBe(false);
  });
});
