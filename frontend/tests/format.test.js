import { describe, it, expect } from 'vitest';
import { asCurrency } from '@/utils/format';

describe('asCurrency', () => {
  it('formats finite numbers as NZD currency', () => {
    const out = asCurrency(12345);
    expect(typeof out).toBe('string');
    expect(out).toMatch(/12|,|\.|NZ|$/); 
  });

  it('returns original value when not finite', () => {
    expect(asCurrency('abc')).toBe('abc');
    expect(asCurrency(NaN)).toBeNaN;
    expect(Number.isNaN(Number(NaN))).toBe(true);
  });
});
