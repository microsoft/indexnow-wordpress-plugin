# Test Setup

Jest 29 + React Testing Library + jsdom. Tests live in `src/__tests__/`. ~65 tests, ~12s execution, zero added dependencies.

## Running Tests

```bash
npm test                                    # watch mode
npm test -- --coverage --watchAll=false      # single run with coverage
```

Tests also run automatically before each build via the `prebuild` script.

## Key Files

| File | Purpose |
|------|---------|
| `setup.ts` | Mocks `window.indexnow_wpr_object` (WordPress globals) and polyfills `Response` for jsdom |
| `testUtils.tsx` | Custom `render()` that wraps components in `<FluentProvider>` — use this instead of RTL's `render` |
| `Constants.test.ts` | Regex and string constant validation |
| `ErrorConstants.test.ts` | Error constant structure validation |
| `IndexNowAPIHelper.test.ts` | Fetch wrapper tests (mocks `global.fetch`) |
| `withDashboardData.test.ts` | API endpoint contract tests (mocks `useFetch`/`useSubmit`) |
| `Header.test.tsx`, `Card.test.tsx`, `StartPage.test.tsx` | Component smoke tests |
| `App.test.tsx` | Integration: loading states, routing between StartPage/Dashboard |
| `Dashboard.test.tsx` | Integration: stats, tables, modals, buttons |

## Mocking

API mocks use module-level `jest.mock()` (hoisted above imports) with implementations set in `beforeEach()`:

```tsx
jest.mock('../components/withDashboardData', () => ({
  GetApiKey: (...a) => mockGetApiKey(...a),
}));

beforeEach(() => {
  jest.clearAllMocks();
  mockGetApiKey.mockResolvedValue({ data: { hasAPIKey: true, APIKey: 'abc123' } });
});
```

`IndexNowAPIHelper.test.ts` mocks `global.fetch` directly since it tests the fetch wrapper itself.

## Adding Tests

- **New constants/logic** — add to existing `*.test.ts` or create a new one; use `it.each()` for parameterized cases
- **New API endpoint** — add a describe block in `withDashboardData.test.ts` verifying the correct endpoint/payload
- **New component** — create `__tests__/MyComponent.test.tsx`, use `render()` from `testUtils.tsx`, test by text/roles/labels
- **New feature on existing component** — add an `it()` block in the existing test file; use `waitFor()` for async updates

## Jest Config Notes

Configured in `package.json`. SCSS imports are mapped to `identity-obj-proxy`. The `cssTransform.js` and `fileTransform.js` transformers return `{ code: string }` objects (Jest 29 requirement).
