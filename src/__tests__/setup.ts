/**
 * Global test setup — runs before every test suite via setupFiles.
 *
 * Sets up the WordPress / plugin globals that are normally injected by
 * wp_localize_script() so that module-level code in IndexNowAPIHelper.tsx
 * (which reads window.indexnow_wpr_object at import time) doesn't crash.
 */

// Provide the WordPress-injected global used by IndexNowAPIHelper
(window as any).indexnow_wpr_object = {
  api_nonce: 'test-nonce-12345',
  indexnow_api_url: 'https://example.com/wp-json/indexnow-url-submission/v_1.0.4/',
};

// Polyfill Fetch globals for jsdom / Node test environment
if (typeof globalThis.Headers === 'undefined') {
  (globalThis as any).Headers = class Headers {
    private map: Map<string, string>;
    constructor(init?: Record<string, string> | [string, string][]) {
      const entries = Array.isArray(init) ? init : Object.entries(init ?? {});
      this.map = new Map(entries.map(([k, v]) => [k.toLowerCase(), v]));
    }
    get(name: string) { return this.map.get(name.toLowerCase()) ?? null; }
    set(name: string, value: string) { this.map.set(name.toLowerCase(), value); }
    has(name: string) { return this.map.has(name.toLowerCase()); }
    delete(name: string) { return this.map.delete(name.toLowerCase()); }
    forEach(cb: (value: string, key: string) => void) { this.map.forEach(cb); }
  };
}

if (typeof globalThis.Response === 'undefined') {
  (globalThis as any).Response = class Response {
    ok: boolean;
    status: number;
    headers: Map<string, string>;
    body: any;
    [key: string]: any;
    constructor(body: any, init?: { status?: number; headers?: Record<string, string> }) {
      this.body = body;
      this.status = init?.status ?? 200;
      this.ok = this.status >= 200 && this.status < 300;
      this.headers = new Map(Object.entries(init?.headers ?? {}));
    }
    json() { return Promise.resolve(JSON.parse(this.body)); }
    text() { return Promise.resolve(String(this.body ?? '')); }
  };
}

