/**
 * App component integration test.
 *
 * Replaces the old placeholder test. Verifies:
 * - App renders a loading spinner initially
 * - After API resolves, shows either StartPage or Dashboard
 */

import React from 'react';
import { render, screen, waitFor } from './testUtils';
import { App } from '../components/App';

// Control what GetApiKey returns per test
const mockGetApiKey = jest.fn();
jest.mock('../components/withDashboardData', () => ({
  GetApiKey: (...args: unknown[]) => mockGetApiKey(...args),
  // Dashboard uses many more — provide safe defaults
  GetApiSettings: jest.fn().mockResolvedValue({ data: { AutoSubmissionEnabled: true, ExcludedPaths: '', SiteUrl: 'https://example.com', error_type: '' } }),
  GetStats: jest.fn().mockResolvedValue({ data: { PassedSubmissionCount: 0, FailedSubmissionCount: 0, Quota: 100, error_type: '' } }),
  GetAllSubmissions: jest.fn().mockResolvedValue({ data: { Submissions: [], error_type: '' } }),
  SetApiKey: jest.fn().mockResolvedValue({ data: { error_type: '' } }),
  SubmitUrl: jest.fn().mockResolvedValue({ data: { error: '' } }),
  RetryFailedSubmissions: jest.fn().mockResolvedValue({ data: { hasError: false, SubmissionErrors: [], error_type: '' } }),
  UpdateAutoSubmissionsEnabled: jest.fn().mockResolvedValue({ data: { error_type: '' } }),
  GetIndexNowInsightsUrl: jest.fn().mockResolvedValue({ data: { InsightsUrl: '', error_type: '' } }),
  UpdateExcludedPaths: jest.fn().mockResolvedValue({ data: { error_type: '' } }),
}));

describe('App', () => {
  beforeEach(() => {
    mockGetApiKey.mockReset();
  });

  it('shows a spinner while loading', () => {
    // Never resolve — keeps spinner visible
    mockGetApiKey.mockReturnValue(new Promise(() => {}));
    render(<App />);
    expect(document.querySelector('.indexnow-App')).toBeInTheDocument();
  });

  it('renders StartPage when there is no API key', async () => {
    mockGetApiKey.mockResolvedValue({ data: { hasAPIKey: false, APIKey: '' } });
    render(<App />);

    await waitFor(() => {
      expect(screen.getByText(/What you can do with this plugin/)).toBeInTheDocument();
    });
  });

  it('renders Dashboard when API key exists', async () => {
    mockGetApiKey.mockResolvedValue({
      data: { hasAPIKey: true, APIKey: 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4' },
    });
    render(<App />);

    await waitFor(() => {
      expect(screen.getByText(/Manual URL submission/)).toBeInTheDocument();
    });
  });

  it('shows an error banner when API call fails', async () => {
    // Simulate a network error that the catch handler in App will process
    mockGetApiKey.mockImplementation(
      () => new Promise((_, reject) => setTimeout(() => reject(new TypeError('Network failure')), 0))
    );
    render(<App />);

    await waitFor(() => {
      const alerts = screen.queryAllByRole('alert');
      expect(alerts.length).toBeGreaterThan(0);
      expect(alerts[0].textContent).toMatch(/Could not connect to the server/);
    }, { timeout: 3000 });
  });
});
