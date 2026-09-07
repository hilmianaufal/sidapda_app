@php
  $assignedInstitutionIds = collect(old('institution_ids', $selectedInstitutionIds ?? []))
    ->map(fn ($id) => (int) $id)
    ->all();
@endphp

<x-ui.card>
  <div class="mb-5">
    <div class="text-lg font-black text-slate-900">Akses Lembaga</div>
    <div class="mt-1 text-sm font-medium text-slate-500">
      Pilih satu atau beberapa lembaga yang boleh dibuka oleh akun ini.
    </div>
  </div>

  <div id="adminAccessNotice" class="mb-4 hidden rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-bold text-blue-700">
    Role admin otomatis memiliki akses penuh ke seluruh lembaga.
  </div>

  <div id="institutionAccessOptions" class="grid gap-3 sm:grid-cols-2">
    @foreach($institutions as $institution)
      <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-emerald-300 hover:bg-emerald-50/50">
        <input
          type="checkbox"
          name="institution_ids[]"
          value="{{ $institution->id }}"
          @checked(in_array((int) $institution->id, $assignedInstitutionIds, true))
          class="mt-1 h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">

        <span class="min-w-0">
          <span class="block font-black text-slate-900">{{ $institution->short_name }}</span>
          <span class="mt-1 block text-xs font-semibold text-slate-500">{{ $institution->name }}</span>
        </span>
      </label>
    @endforeach
  </div>

  @error('institution_ids')
    <div class="mt-3 text-sm font-bold text-red-600">{{ $message }}</div>
  @enderror
  @error('institution_ids.*')
    <div class="mt-3 text-sm font-bold text-red-600">{{ $message }}</div>
  @enderror
</x-ui.card>

