<div class="mt-2">
    <label for="{{ $id }}" class="mb-1 block text-xs font-semibold text-muted">
        Alasan Override (wajib bila berbeda dari saran mesin)
    </label>
    <textarea id="{{ $id }}" name="alasan_override" rows="2" minlength="5" maxlength="2000"
        class="block w-full rounded-xl border border-line-strong bg-surface px-3 py-2 text-xs text-ink outline-none transition placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30"
        placeholder="Jelaskan alasan memilih tindakan ini..."></textarea>

    @error('alasan_override')
        <p class="mt-1 text-xs font-semibold text-[#b91c1c]">{{ $message }}</p>
    @enderror
</div>