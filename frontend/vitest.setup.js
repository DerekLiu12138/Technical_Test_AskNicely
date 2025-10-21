// DOM environment & fetch polyfill so code using fetch works in tests.
import 'whatwg-fetch';
import { afterEach, vi } from 'vitest';
import { cleanup } from '@testing-library/vue';

// Auto-cleanup DOM between tests.
afterEach(() => cleanup());

// Stub URL.createObjectURL / revokeObjectURL used in downloads.
if (!global.URL.createObjectURL) {
  global.URL.createObjectURL = vi.fn(() => 'blob://test');
}
if (!global.URL.revokeObjectURL) {
  global.URL.revokeObjectURL = vi.fn();
}

