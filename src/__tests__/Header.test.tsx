/**
 * Header component smoke test.
 *
 * Verifies the header renders its core elements.
 * Does NOT test FluentUI icon internals — that's their concern.
 */

import React from 'react';
import { render, screen } from './testUtils';
import { Header } from '../components/Header';

describe('Header', () => {
  it('renders without crashing', () => {
    const { container } = render(<Header />);
    expect(container.querySelector('.indexnow-Header')).toBeInTheDocument();
  });

  it('displays the plugin title', () => {
    render(<Header />);
    expect(screen.getByText('IndexNow Plugin')).toBeInTheDocument();
  });

  it('has an "About this plugin" link', () => {
    render(<Header />);
    expect(screen.getByText('About this plugin')).toBeInTheDocument();
  });
});
