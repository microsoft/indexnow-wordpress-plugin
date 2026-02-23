/**
 * Shared test utilities.
 *
 * Re-exports everything from @testing-library/react but replaces `render`
 * with a version that wraps components in the FluentProvider (same provider
 * used in the real app entry-point).
 *
 * Usage in test files:
 *   import { render, screen, fireEvent } from '../testUtils';
 */

import React from 'react';
import { render, RenderOptions } from '@testing-library/react';
import { FluentProvider, webLightTheme } from '@fluentui/react-components';

/** Wrapper that mirrors <FluentProvider> in index.js */
const AllProviders: React.FC<{ children: React.ReactNode }> = ({ children }) => (
  <FluentProvider theme={webLightTheme}>{children}</FluentProvider>
);

/**
 * Custom render that wraps the component under test in all required providers.
 * Extend `AllProviders` above when the app adds more global providers.
 */
const customRender = (
  ui: React.ReactElement,
  options?: Omit<RenderOptions, 'wrapper'>,
) => render(ui, { wrapper: AllProviders, ...options });

// Re-export everything
export * from '@testing-library/react';
// Override render
export { customRender as render };
