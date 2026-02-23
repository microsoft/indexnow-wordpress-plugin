/**
 * withDashboardData (API wrapper functions) tests.
 *
 * Verifies that each wrapper calls the correct endpoint and forwards
 * data / errors properly. We mock the underlying fetch helpers so
 * these tests are fast and network-free.
 */

import * as APIHelper from '../components/IndexNowAPIHelper';

// Mock the underlying fetch helpers — we already test them separately
jest.mock('../components/IndexNowAPIHelper', () => ({
  ...jest.requireActual('../components/IndexNowAPIHelper'),
  useFetch: jest.fn(),
  useSubmit: jest.fn(),
}));

import {
  GetApiKey,
  SetApiKey,
  GetApiSettings,
  GetStats,
  GetAllSubmissions,
  SubmitUrl,
  UpdateAutoSubmissionsEnabled,
  RetryFailedSubmissions,
  GetIndexNowInsightsUrl,
  GetExcludedPaths,
  UpdateExcludedPaths,
} from '../components/withDashboardData';

const mockFetch = APIHelper.useFetch as jest.Mock;
const mockSubmit = APIHelper.useSubmit as jest.Mock;

beforeEach(() => {
  mockFetch.mockReset();
  mockSubmit.mockReset();
});

describe('GetApiKey', () => {
  it('calls useFetch with "apiKey" endpoint', async () => {
    mockFetch.mockResolvedValue({ data: { hasAPIKey: true, APIKey: 'k' } });
    const res = await GetApiKey();
    expect(mockFetch).toHaveBeenCalledWith('apiKey');
    expect(res.data?.hasAPIKey).toBe(true);
  });

  it('returns the error when fetch rejects', async () => {
    mockFetch.mockRejectedValue(new Error('fail'));
    const res = await GetApiKey();
    expect(res).toBeInstanceOf(Error);
  });
});

describe('SetApiKey', () => {
  it('calls useSubmit with the APIKey payload', async () => {
    mockSubmit.mockResolvedValue({ data: { error_type: '' } });
    await SetApiKey('abcd1234abcd1234abcd1234abcd1234');
    expect(mockSubmit).toHaveBeenCalledWith('apiKey', {
      APIKey: 'abcd1234abcd1234abcd1234abcd1234',
    });
  });
});

describe('GetApiSettings', () => {
  it('calls useFetch with "apiSettings"', async () => {
    mockFetch.mockResolvedValue({ data: { AutoSubmissionEnabled: true, error_type: '' } });
    const res = await GetApiSettings();
    expect(mockFetch).toHaveBeenCalledWith('apiSettings');
    expect(res.data?.AutoSubmissionEnabled).toBe(true);
  });
});

describe('GetStats', () => {
  it('calls useFetch with "getStats"', async () => {
    mockFetch.mockResolvedValue({ data: { PassedSubmissionCount: 5, FailedSubmissionCount: 1, error_type: '' } });
    const res = await GetStats();
    expect(mockFetch).toHaveBeenCalledWith('getStats');
    expect(res.data?.PassedSubmissionCount).toBe(5);
  });
});

describe('GetAllSubmissions', () => {
  it('calls useFetch with "allSubmissions"', async () => {
    mockFetch.mockResolvedValue({ data: { Submissions: [], error_type: '' } });
    await GetAllSubmissions();
    expect(mockFetch).toHaveBeenCalledWith('allSubmissions');
  });
});

describe('SubmitUrl', () => {
  it('calls useSubmit with the url payload', async () => {
    mockSubmit.mockResolvedValue({ data: { error: '' } });
    await SubmitUrl('https://example.com/page');
    expect(mockSubmit).toHaveBeenCalledWith('submitUrl', { url: 'https://example.com/page' });
  });
});

describe('UpdateAutoSubmissionsEnabled', () => {
  it('sends correct boolean payload', async () => {
    mockSubmit.mockResolvedValue({ data: { error_type: '' } });
    await UpdateAutoSubmissionsEnabled(true);
    expect(mockSubmit).toHaveBeenCalledWith('automaticSubmission', { AutoSubmissionEnabled: true });
  });
});

describe('RetryFailedSubmissions', () => {
  it('sends submissions array', async () => {
    const subs = [{ url: 'https://x.com', submission_type: 0, submission_date: 0, error: 'err', type: 0 as any }];
    mockSubmit.mockResolvedValue({ data: { hasError: false, SubmissionErrors: [], error_type: '' } });
    await RetryFailedSubmissions(subs);
    expect(mockSubmit).toHaveBeenCalledWith('allSubmissions', { Submissions: subs });
  });
});

describe('GetIndexNowInsightsUrl', () => {
  it('calls useFetch with the insights endpoint', async () => {
    mockFetch.mockResolvedValue({ data: { InsightsUrl: 'https://bing.com', error_type: '' } });
    await GetIndexNowInsightsUrl();
    expect(mockFetch).toHaveBeenCalledWith('getIndexNowInsightsUrl');
  });
});

describe('GetExcludedPaths', () => {
  it('calls useFetch with "excludedPaths"', async () => {
    mockFetch.mockResolvedValue({ data: { ExcludedPaths: '/private/*', error_type: '' } });
    await GetExcludedPaths();
    expect(mockFetch).toHaveBeenCalledWith('excludedPaths');
  });
});

describe('UpdateExcludedPaths', () => {
  it('sends the paths string', async () => {
    mockSubmit.mockResolvedValue({ data: { error_type: '' } });
    await UpdateExcludedPaths('/private/*\n/draft-*');
    expect(mockSubmit).toHaveBeenCalledWith('excludedPaths', { ExcludedPaths: '/private/*\n/draft-*' });
  });
});
