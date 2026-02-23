/**
 * Dashboard component tests.
 *
 * All API functions are mocked to return sensible defaults.
 * Tests focus on rendered output and high-level interactions,
 * NOT internal FluentUI behavior.
 */

import React from 'react';
import { render, screen, waitFor, fireEvent } from './testUtils';
import { Dashboard } from '../components/Dashboard';

const mockGetApiKey = jest.fn();
const mockGetApiSettings = jest.fn();
const mockGetStats = jest.fn();
const mockGetAllSubmissions = jest.fn();
const mockSubmitUrl = jest.fn();
const mockUpdateAutoSubmissions = jest.fn();
const mockRetryFailed = jest.fn();
const mockGetInsightsUrl = jest.fn();
const mockUpdateExcludedPaths = jest.fn();

jest.mock('../components/withDashboardData', () => ({
  GetApiKey: (...a: unknown[]) => mockGetApiKey(...a),
  GetApiSettings: (...a: unknown[]) => mockGetApiSettings(...a),
  GetStats: (...a: unknown[]) => mockGetStats(...a),
  GetAllSubmissions: (...a: unknown[]) => mockGetAllSubmissions(...a),
  SubmitUrl: (...a: unknown[]) => mockSubmitUrl(...a),
  UpdateAutoSubmissionsEnabled: (...a: unknown[]) => mockUpdateAutoSubmissions(...a),
  RetryFailedSubmissions: (...a: unknown[]) => mockRetryFailed(...a),
  GetIndexNowInsightsUrl: (...a: unknown[]) => mockGetInsightsUrl(...a),
  UpdateExcludedPaths: (...a: unknown[]) => mockUpdateExcludedPaths(...a),
}));

describe('Dashboard', () => {
  const addBanner = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    // Re-apply mock implementations after clearAllMocks
    mockGetApiKey.mockResolvedValue({ data: { hasAPIKey: true, APIKey: 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4' } });
    mockGetApiSettings.mockResolvedValue({ data: { AutoSubmissionEnabled: true, ExcludedPaths: '', SiteUrl: 'https://example.com', error_type: '' } });
    mockGetStats.mockResolvedValue({ data: { PassedSubmissionCount: 10, FailedSubmissionCount: 2, Quota: 100, error_type: '' } });
    mockGetAllSubmissions.mockResolvedValue({
      data: {
        Submissions: [
          { url: 'https://example.com/page1', submission_type: 1, submission_date: Math.floor(Date.now() / 1000), error: 'Success', type: 'add' },
          { url: 'https://example.com/page2', submission_type: 0, submission_date: Math.floor(Date.now() / 1000) - 3600, error: 'TooManyRequests', type: 'update' },
        ],
        error_type: '',
      },
    });
    mockSubmitUrl.mockResolvedValue({ data: { error: '' } });
    mockUpdateAutoSubmissions.mockResolvedValue({ data: { error_type: '' } });
    mockRetryFailed.mockResolvedValue({ data: { hasError: false, SubmissionErrors: [], error_type: '' } });
    mockGetInsightsUrl.mockResolvedValue({ data: { InsightsUrl: 'https://bing.com/webmasters', error_type: '' } });
    mockUpdateExcludedPaths.mockResolvedValue({ data: { error_type: '' } });
  });

  it('renders main sections', async () => {
    render(<Dashboard addBanner={addBanner} />);

    await waitFor(() => {
      // Use getAllByText for labels that also appear in tooltips
      expect(screen.getAllByText('Manual URL submission').length).toBeGreaterThan(0);
      expect(screen.getAllByText('Automate URL submission').length).toBeGreaterThan(0);
      expect(screen.getAllByText(/Successful submissions/).length).toBeGreaterThan(0);
      expect(screen.getAllByText(/Failed submissions/).length).toBeGreaterThan(0);
      expect(screen.getAllByText('IndexNow Insights').length).toBeGreaterThan(0);
      expect(screen.getAllByText('Excluded Paths').length).toBeGreaterThan(0);
    });
  });

  it('displays submission stats', async () => {
    render(<Dashboard addBanner={addBanner} />);

    await waitFor(() => {
      // Stats appear in h2 tags inside cards
      const headings = screen.getAllByRole('heading');
      const headingTexts = headings.map(h => h.textContent?.trim());
      expect(headingTexts).toContain('10');
      expect(headingTexts).toContain('2');
    });
  });

  it('displays auto-submission status', async () => {
    render(<Dashboard addBanner={addBanner} />);

    await waitFor(() => {
      expect(screen.getAllByText('Enabled').length).toBeGreaterThan(0);
    });
  });

  it('renders submission table with URL data', async () => {
    render(<Dashboard addBanner={addBanner} />);

    await waitFor(() => {
      // URLs are rendered as <a> links with href; query by role
      const links = screen.getAllByRole('link');
      const hrefs = links.map(l => l.getAttribute('href'));
      expect(hrefs).toContain('https://example.com/page1');
      expect(hrefs).toContain('https://example.com/page2');
    });
  });

  it('shows success and failed status correctly in table', async () => {
    render(<Dashboard addBanner={addBanner} />);

    await waitFor(() => {
      // Look for table cell content, not exact standalone text
      const cells = screen.getAllByRole('cell');
      const texts = cells.map(c => c.textContent);
      expect(texts.some(t => t === 'Success')).toBe(true);
      expect(texts.some(t => t?.includes('Failed'))).toBe(true);
    });
  });

  it('has a Submit URL button', async () => {
    render(<Dashboard addBanner={addBanner} />);

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Submit URL/i })).toBeInTheDocument();
    });
  });

  it('has a View Insights button', async () => {
    render(<Dashboard addBanner={addBanner} />);
    
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /View Insights/i })).toBeInTheDocument();
    });
  });

  it('has a Manage Paths button', async () => {
    render(<Dashboard addBanner={addBanner} />);

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Manage Paths/i })).toBeInTheDocument();
    });
  });

  it('has a Download button', async () => {
    render(<Dashboard addBanner={addBanner} />);

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Download/i })).toBeInTheDocument();
    });
  });
});
