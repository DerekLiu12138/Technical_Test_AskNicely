import { describe, it, expect, vi, beforeAll, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/vue';
import App from '@/App.vue';

vi.mock('@/utils/api', () => {
  return {
    api: {
      uploadCsv: vi.fn(async () => ({ ok: true, data: { imported: 2, skipped: 0, errors: [] } })),
      employees: vi.fn(async () => ([
        { id:1, company:'ACME', name:'John Doe', email:'john@acme.com', salary:50000 },
      ])),
      updateEmail: vi.fn(async () => ({ ok: true })),
      averages: vi.fn(async () => ([
        { company:'ACME', avg_salary: 50000 }
      ])),
    }
  };
});

let api;
beforeAll(async () => {
  ({ api } = await import('@/utils/api'));
});

describe('App.vue (integration)', () => {
  beforeEach(() => vi.clearAllMocks());

  it('loads employees and averages on mount and renders them', async () => {
    render(App);

    await screen.findByText('John Doe');

    const avgHeading = screen.getByText('Average Salary by Company');
    const avgSection = avgHeading.closest('section');
    const avgTable = avgSection.querySelector('table');
    expect(within(avgTable).getByText('ACME')).toBeTruthy();

    expect(api.employees).toHaveBeenCalledTimes(1);
    expect(api.averages).toHaveBeenCalledTimes(1);
  });

  it('handles CSV upload and refreshes data', async () => {
    render(App);
    await screen.findByText('John Doe');

    const input = screen.getByLabelText('Select CSV file');
    const f = new File(['a,b,c'], 'employees.csv', { type: 'text/csv' });
    await fireEvent.change(input, { target: { files: [f] } });

    const form = input.closest('form');
    await fireEvent.submit(form);

    await waitFor(() => {
        expect(api.uploadCsv).toHaveBeenCalledTimes(1);
    });

    expect(api.employees).toHaveBeenCalledTimes(2);
    expect(api.averages).toHaveBeenCalledTimes(2);
  });

  it('saves email and refreshes averages', async () => {
    render(App);
    await screen.findByText('John Doe');

    const save = screen.getByText('Save');
    await fireEvent.click(save);

    expect(api.updateEmail).toHaveBeenCalledTimes(1);
    await waitFor(() => {
      expect(api.averages).toHaveBeenCalledTimes(2);
    });
  });
});
