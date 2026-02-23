/**
 * StartPage component tests.
 *
 * Mocks the API layer so we only test UI logic:
 * - Feature list renders
 * - Submit button disabled until valid key
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from './testUtils';
import { StartPage } from '../components/StartPage';

// Mock the API calls used by StartPage
jest.mock('../components/withDashboardData', () => ({
  GetApiKey: jest.fn().mockResolvedValue({ data: { hasAPIKey: false, APIKey: '' } }),
  SetApiKey: jest.fn().mockResolvedValue({ data: { error_type: '' } }),
}));

describe('StartPage', () => {
  const defaultProps = {
    addBanner: jest.fn(),
    setAPIKeyAdded: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders the features section', () => {
    render(<StartPage {...defaultProps} />);
    expect(screen.getByText(/What you can do with this plugin/)).toBeInTheDocument();
  });

  it('lists all five features', () => {
    render(<StartPage {...defaultProps} />);
    expect(screen.getByText('Automate URL submissions')).toBeInTheDocument();
    expect(screen.getByText('Manual URL submissions')).toBeInTheDocument();
    expect(screen.getByText('View stats of submitted URLs')).toBeInTheDocument();
    expect(screen.getByText('View recent submissions')).toBeInTheDocument();
    expect(screen.getByText('Re-submit recent submissions')).toBeInTheDocument();
  });

  it('has a disabled submit button by default (no key)', () => {
    render(<StartPage {...defaultProps} />);
    const button = screen.getByRole('button', { name: /get started/i });
    expect(button).toBeDisabled();
  });
});
