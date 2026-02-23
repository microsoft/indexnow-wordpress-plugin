/**
 * Card component tests.
 *
 * Card is a simple presentational wrapper — we only verify that it
 * renders its title, tooltip, and children correctly.
 */

import React from 'react';
import { render, screen } from './testUtils';
import { Card } from '../components/Card';
import { Send24Regular } from '@fluentui/react-icons';

describe('Card', () => {
  const defaultProps = {
    title: 'Test Card',
    tooltip: 'Helpful tooltip text',
    leadingIcon: Send24Regular,
  };

  it('renders without crashing', () => {
    const { container } = render(<Card {...defaultProps}>Content</Card>);
    expect(container.querySelector('.indexnow-Card')).toBeInTheDocument();
  });

  it('displays the title', () => {
    render(<Card {...defaultProps}>Content</Card>);
    expect(screen.getByText('Test Card')).toBeInTheDocument();
  });

  it('renders children', () => {
    render(<Card {...defaultProps}><p>Child content</p></Card>);
    expect(screen.getByText('Child content')).toBeInTheDocument();
  });

  it('applies extra className when provided', () => {
    const { container } = render(
      <Card {...defaultProps} className="extra-class">Content</Card>
    );
    expect(container.querySelector('.indexnow-Card.extra-class')).toBeInTheDocument();
  });
});
