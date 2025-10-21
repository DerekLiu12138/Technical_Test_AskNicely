import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/vue';
import UploadCsv from '@/components/UploadCsv.vue';

function fileOf(sizeBytes, name = 'employees.csv', type = 'text/csv') {
  const blob = new Blob([new Uint8Array(sizeBytes)], { type });
  return new File([blob], name, { type });
}

describe('UploadCsv.vue', () => {
  it('enables submit button after choosing a file', async () => {
    render(UploadCsv, { props: { uploading: false, result: null } });
    const input = screen.getByLabelText('Select CSV file');
    const btn = screen.getByText('Upload & Import');

    expect(btn.disabled).toBe(true);

    await fireEvent.change(input, { target: { files: [fileOf(100)] } });
    expect(btn.disabled).toBe(false);
  });

  it('emits upload on submit with valid file', async () => {
    const uploads = [];
    const Wrapper = {
      components: { UploadCsv },
      template: `<UploadCsv @upload="onUp" />`,
      methods: { onUp(f) { uploads.push(f); } }
    };

    render(Wrapper);

    const input = screen.getByLabelText('Select CSV file');
    const btn = screen.getByText('Upload & Import');

    await fireEvent.change(input, { target: { files: [fileOf(100)] } });
    await fireEvent.click(btn);

    expect(uploads.length).toBe(1);
    expect(uploads[0]).toBeInstanceOf(File);
  });

  it('accepts drag-and-drop file (emits upload)', async () => {
    const uploads = [];
    const Wrapper = {
      components: { UploadCsv },
      template: `<UploadCsv @upload="onUp" />`,
      methods: { onUp(f) { uploads.push(f); } }
    };

    render(Wrapper);

    const submitBtn = screen.getByText('Upload & Import');
    const form = submitBtn.closest('form');

    await fireEvent.drop(form, { dataTransfer: { files: [fileOf(100)] } });
    await fireEvent.submit(form);

    expect(uploads.length).toBe(1);
    expect(uploads[0]).toBeInstanceOf(File);
  });

  it('downloads example CSV (smoke)', async () => {
    const clickSpy = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
    const urlSpy = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob://test');

    render(UploadCsv);
    await fireEvent.click(screen.getByText('Example CSV'));

    expect(urlSpy).toHaveBeenCalledTimes(1);
    expect(clickSpy).toHaveBeenCalledTimes(1);

    clickSpy.mockRestore();
    urlSpy.mockRestore();
  });
});
