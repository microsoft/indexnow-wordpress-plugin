/**
 * ErrorConstants validation tests.
 */

import { ErrorConstants } from '../components/ErrorConstants';

describe('ErrorConstants', () => {
  it('defines expected error categories', () => {
    expect(ErrorConstants).toHaveProperty('NoDataFound');
    expect(ErrorConstants).toHaveProperty('RequestTimedOut');
    expect(ErrorConstants).toHaveProperty('UrlNotAllowed');
  });

  it('RequestTimedOut has a valid HTTP status code', () => {
    expect(ErrorConstants.RequestTimedOut.HttpStatusCode).toBe(408);
  });

  it('every error constant has Code and Message', () => {
    for (const [, value] of Object.entries(ErrorConstants)) {
      expect(typeof (value as any).Code).toBe('string');
      expect(typeof (value as any).Message).toBe('string');
      expect((value as any).Code.length).toBeGreaterThan(0);
      expect((value as any).Message.length).toBeGreaterThan(0);
    }
  });
});
