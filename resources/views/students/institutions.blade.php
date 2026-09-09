@extends('layouts.app')

@section('title', 'Lembaga dan Kelas Siswa')
@section('mobile_title', 'Lembaga Siswa')

@section('content')
<x-ui.page-header
  title="Lembaga dan Kelas"
  subtitle="{{ $student->name }} • {{ $student->nis }} • {{ $academicYear }}"
  icon="bi-building"
>
  <x-slot:actions>
    <x-ui.button :href="route('students.show', $student)" variant="secondary">
      <i class="bi bi-arrow-left"></i>
      Kembali
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<form method="POST" action="{{ route('students.institutions.update', $student) }}">
  @csrf
  @method('PUT')

  @if($canManageResidency)
    <x-ui.card class="mb-6">
      <div class="grid gap-5 md:grid-cols-2 md:items-end">
        <div>
          <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
            Status Tempat Tinggal
          </label>
          <x-ui.select name="residency_status">
            <option value="mukim" @selected(old('residency_status', $student->residency_status) === 'mukim')>
              Mukim di Pondok
            </option>
            <option value="non_mukim" @selected(old('residency_status', $student->residency_status) === 'non_mukim')>
              Tidak Mukim
            </option>
          </x-ui.select>
        </div>

        <div class="rounded-2xl bg-blue-50 p-4 text-sm font-semibold text-blue-700">
          Siswa tidak mukim nantinya tidak dihitung sebagai wajib hadir pada salat dan kegiatan Pondok.
        </div>
      </div>
    </x-ui.card>
  @endif

  <div class="grid gap-4 lg:grid-cols-2">
    @foreach($institutions as $institution)
      @php
        $enrollment = $enrollments->get($institution->id);
        $isChecked = old(
          "institutions.{$institution->id}.active",
          $enrollment?->is_active ?? false
        );
      @endphp

      <x-ui.card>
        <input type="hidden" name="institutions[{{ $institution->id }}][active]" value="0">

        <div class="flex items-start gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-600">
            <i class="bi {{ $institution->icon }}"></i>
          </div>

          <div class="min-w-0 flex-1">
            <label class="flex cursor-pointer items-center justify-between gap-3">
              <span>
                <span class="block font-black text-slate-900">{{ $institution->short_name }}</span>
                <span class="block text-xs font-semibold text-slate-500">{{ $institution->name }}</span>
              </span>

              <input
                type="checkbox"
                name="institutions[{{ $institution->id }}][active]"
                value="1"
                @checked((bool) $isChecked)
                class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            </label>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
                  {{ $institution->code === 'madad' ? 'Kelas MADAD' : 'Kelas' }}
                </label>
                <x-ui.input
                  name="institutions[{{ $institution->id }}][class_name]"
                  :value="old('institutions.'.$institution->id.'.class_name', $enrollment?->class_name)"
                  placeholder="{{ $institution->code === 'madad' ? 'Contoh: 1 / 2 / 3' : 'Contoh: 7A / 10 IPA' }}" />
              </div>

              <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
                  {{ $institution->code === 'madad' ? 'Jenjang MADAD' : 'Jenjang' }}
                </label>
                @if($institution->code === 'madad')
                  @php
                    $selectedLevel = old('institutions.'.$institution->id.'.level', $enrollment?->level);
                  @endphp
                  <x-ui.select name="institutions[{{ $institution->id }}][level]">
                    <option value="">Pilih jenjang</option>
                    @foreach(\App\Models\StudentEnrollment::madadLevels() as $madadLevel)
                      <option value="{{ $madadLevel }}" @selected($selectedLevel === $madadLevel)>
                        {{ $madadLevel }}
                      </option>
                    @endforeach
                  </x-ui.select>
                  @error('institutions.'.$institution->id.'.level')
                    <div class="mt-1 text-xs font-bold text-red-600">{{ $message }}</div>
                  @enderror
                @elseif(in_array($institution->code, ['mi', 'mts', 'ma'], true))
                  <input
                    type="hidden"
                    name="institutions[{{ $institution->id }}][level]"
                    value="{{ $institution->code }}">
                  <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">
                    {{ $institution->short_name }}
                  </div>
                @else
                  <x-ui.input
                    name="institutions[{{ $institution->id }}][level]"
                    :value="old('institutions.'.$institution->id.'.level', $enrollment?->level)"
                    placeholder="MI / MTs / MA" />
                @endif
              </div>
            </div>
          </div>
        </div>
      </x-ui.card>
    @endforeach
  </div>

  <div class="mt-6 flex flex-wrap justify-end gap-3">
    <x-ui.button :href="route('students.show', $student)" variant="secondary">
      Batal
    </x-ui.button>
    <x-ui.button type="submit">
      <i class="bi bi-check-lg"></i>
      Simpan Keanggotaan
    </x-ui.button>
  </div>
</form>
@endsection
