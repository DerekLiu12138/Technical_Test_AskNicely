import { describe, it, expect } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/vue';
import EmployeesTable from '@/components/EmployeesTable.vue';

const rows = [
  { id:1, company:'ACME',  name:'John Doe',  email:'john@acme.com',  salary:50000, _saving:false, _error:'' },
  { id:2, company:'Wayne', name:'Bruce W',   email:'bruce@wayne.com',salary:90000, _saving:false, _error:'' },
  { id:3, company:'ACME',  name:'Jane Roe',  email:'jane@acme.com',  salary:60000, _saving:false, _error:'' },
];

function mount(props = {}) {
  return render(EmployeesTable, { props: { rows, loading: false, ...props } });
}

describe('EmployeesTable.vue', () => {
  it('renders rows and headers', () => {
    mount();
    expect(screen.getByText('Company')).toBeTruthy();
    expect(screen.getByText('Name')).toBeTruthy();
    expect(screen.getByText('Salary')).toBeTruthy();
    expect(screen.getByText('John Doe')).toBeTruthy();
    expect(screen.getByText('Bruce W')).toBeTruthy();
  });

  it('filters by search query (name/email)', async () => {
    mount();
    const search = screen.getByPlaceholderText('Search name/email…');
    await fireEvent.update(search, 'jane');
    expect(screen.queryByText('John Doe')).toBeNull();
    expect(screen.getByText('Jane Roe')).toBeTruthy();
  });

  it('filters by company', async () => {
    mount();
    const select = screen.getByLabelText('Company filter');
    await fireEvent.update(select, 'Wayne');
    expect(screen.getByText('Bruce W')).toBeTruthy();
    expect(screen.queryByText('John Doe')).toBeNull();
    expect(screen.queryByText('Jane Roe')).toBeNull();
  });

  it('sorts by salary when header clicked', async () => {
    mount();
    const th = screen.getByText('Salary');
    await fireEvent.click(th);
    await fireEvent.click(th);
    const allRows = screen.getAllByRole('row');
    const firstDataRowText = allRows[1].textContent;
    expect(firstDataRowText).toMatch(/Bruce W/); 
  });

  it('validates email format and blocks save', async () => {
    const { emitted } = mount();
    const emailInputs = screen.getAllByDisplayValue(/@/);
    await fireEvent.update(emailInputs[0], 'bad-email');
    await fireEvent.blur(emailInputs[0]);
    expect(screen.getByText('Invalid email format')).toBeTruthy();

    const firstSave = screen.getAllByText('Save')[0];
    await fireEvent.click(firstSave);
    expect((emitted().save || []).length).toBe(0);
  });

  it('emits save(eventRow) when clicking Save with valid email', async () => {
    const received = [];
    const rows = [
        { id:1, company:'ACME',  name:'John Doe',  email:'john@acme.com',  salary:50000, _saving:false, _error:'' },
        { id:2, company:'Wayne', name:'Bruce W',   email:'bruce@wayne.com',salary:90000, _saving:false, _error:'' },
    ];

    const Wrapper = {
        components: { EmployeesTable: (await import('@/components/EmployeesTable.vue')).default },
        template: `<EmployeesTable :rows="rows" :loading="false" @save="onSave" />`,
        data: () => ({ rows }),
        methods: { onSave(row) { received.push(row); } }
    };

    await render(Wrapper);
    const saveBtn = screen.getAllByText('Save')[0];
    await fireEvent.click(saveBtn);

    expect(received.length).toBe(1);
    expect(received[0]).toMatchObject({ id:1, email:'john@acme.com' });
 });


  it('shows loading skeleton when loading=true', () => {
    render(EmployeesTable, { props: { rows: [], loading: true } });
    expect(document.querySelector('.skeleton-line')).toBeTruthy();
  });

  it('shows empty state when no rows and not loading', () => {
    render(EmployeesTable, { props: { rows: [], loading: false } });
    expect(screen.getByText('No employees match current filter.')).toBeTruthy();
  });
});
