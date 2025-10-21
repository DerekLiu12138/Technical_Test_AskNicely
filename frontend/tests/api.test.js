import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { api } from '@/utils/api';

describe('api', () => {
  const originalFetch = global.fetch;

  beforeEach(() => {
    global.fetch = vi.fn();
  });

  afterEach(() => {
    global.fetch = originalFetch;
    vi.restoreAllMocks();
  });

  it('employees(): returns json when response ok', async () => {
    const payload = [{ id: 1, name: 'John' }];
    global.fetch.mockResolvedValueOnce(new Response(JSON.stringify(payload), { status: 200 }));
    const data = await api.employees();
    expect(data).toEqual(payload);
  });

  it('employees(): throws enriched Error when response not ok', async () => {
    const payload = { error: 'boom' };
    global.fetch.mockResolvedValueOnce(new Response(JSON.stringify(payload), { status: 500 }));
    await expect(api.employees()).rejects.toThrow('boom');
  });

  it('updateEmail(): sends PATCH with JSON body', async () => {
    global.fetch.mockResolvedValueOnce(new Response(JSON.stringify({ ok: true }), { status: 200 }));
    await api.updateEmail(7, 'x@y.com');
    const [url, init] = global.fetch.mock.calls[0];
    expect(String(url)).toContain('/employees/7/email');
    expect(init.method).toBe('PATCH');
    expect(JSON.parse(init.body)).toEqual({ email: 'x@y.com' });
  });

  it('averages(): returns json', async () => {
    const payload = [{ company: 'ACME', avg_salary: 50000 }];
    global.fetch.mockResolvedValueOnce(new Response(JSON.stringify(payload), { status: 200 }));
    const data = await api.averages();
    expect(data).toEqual(payload);
  });
});
