/**
 * IndexNowAPIHelper tests.
 *
 * Tests the fetch wrappers and timeout logic. Uses global fetch mock
 * so we don't hit real endpoints.
 */

import { useFetch, useSubmit, withTimeout } from '../components/IndexNowAPIHelper';
import { ErrorConstants } from '../components/ErrorConstants';

// ---- helpers ----

function mockFetchSuccess<T>(body: T, status = 200) {
  (global.fetch as jest.Mock).mockResolvedValueOnce({
    ok: status >= 200 && status < 300,
    status,
    json: () => Promise.resolve(body),
    headers: { get: () => null, has: () => false },
  });
}

function mockFetchFailure(errorMessage: string) {
  (global.fetch as jest.Mock).mockRejectedValueOnce(new Error(errorMessage));
}

// ---- setup ----

beforeEach(() => {
  global.fetch = jest.fn();
});

afterEach(() => {
  jest.restoreAllMocks();
});

// ---- useFetch ----

describe('useFetch', () => {
  it('resolves with data on 200', async () => {
    const payload = { hasAPIKey: true, APIKey: 'abc123' };
    mockFetchSuccess(payload);

    const res = await useFetch<typeof payload>('apiKey');

    expect(res.data).toEqual(payload);
    expect(res.error).toBeUndefined();
    expect(global.fetch).toHaveBeenCalledTimes(1);
  });

  it('populates error on non-ok response', async () => {
    const errBody = { code: 'Forbidden', message: 'No access' };
    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: false,
      status: 403,
      json: () => Promise.resolve(errBody),
      headers: { get: () => null, has: () => false },
    });

    const res = await useFetch('apiKey');

    expect(res.error).toEqual(errBody);
    expect(res.data).toBeUndefined();
  });

  it('rejects on network failure', async () => {
    mockFetchFailure('Network error');

    await expect(useFetch('apiKey')).rejects.toThrow('Network error');
  });

  it('includes the nonce header', async () => {
    mockFetchSuccess({});

    await useFetch('test');

    const [, init] = (global.fetch as jest.Mock).mock.calls[0];
    expect(init.headers['X-WP-Nonce']).toBe('test-nonce-12345');
  });
});

// ---- useSubmit ----

describe('useSubmit', () => {
  it('sends POST with JSON body', async () => {
    const payload = { error_type: '' };
    mockFetchSuccess(payload);

    const body = { APIKey: 'mykey' };
    const res = await useSubmit<typeof payload>('apiKey', body);

    expect(res.data).toEqual(payload);

    const [, init] = (global.fetch as jest.Mock).mock.calls[0];
    expect(init.method).toBe('POST');
    expect(JSON.parse(init.body)).toEqual(body);
    expect(init.headers['Content-Type']).toContain('application/json');
  });
});

// ---- withTimeout ----

describe('withTimeout', () => {
  it('returns the response if it resolves before timeout', async () => {
    const fakeResponse = new Response(null, { status: 200 });
    (fakeResponse as any).data = { ok: true };

    const result = await withTimeout(Promise.resolve(fakeResponse as any), 5000);
    expect(result.status).toBe(200);
  });

  it('returns a timeout error if the promise takes too long', async () => {
    jest.useFakeTimers();

    const neverResolve = new Promise<any>(() => {});
    const racePromise = withTimeout(neverResolve, 100);

    jest.advanceTimersByTime(150);

    const result = await racePromise;
    expect(result.status).toBe(ErrorConstants.RequestTimedOut.HttpStatusCode);
    expect(result.error?.code).toBe(ErrorConstants.RequestTimedOut.Code);

    jest.useRealTimers();
  });
});
