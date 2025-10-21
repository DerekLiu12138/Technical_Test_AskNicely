import { render, screen } from '@testing-library/vue';
import { describe, it, expect } from 'vitest';
import AveragesTable from '@/components/AveragesTable.vue';

describe('AveragesTable.vue', () => {
  it('shows loading skeleton when loading', () => {
    render(AveragesTable, { props: { rows: [], loading: true } });
    expect(document.querySelector('.skeleton-line')).toBeTruthy();
  });

  it('shows empty state when no data and not loading', () => {
    render(AveragesTable, { props: { rows: [], loading: false } });
    expect(screen.getByText('No data yet. Upload a CSV first.')).toBeTruthy();
  });

  it('renders data rows', () => {
    const rows = [{ company: 'ACME', avg_salary: 57500 }];
    render(AveragesTable, { props: { rows, loading: false } });
    expect(screen.getByText('ACME')).toBeTruthy();
  });
});
