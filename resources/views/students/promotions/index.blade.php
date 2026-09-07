@extends('layouts.app')

@section('title', 'Kenaikan Kelas Otomatis')
@section('mobile_title', 'Kenaikan Kelas')

@section('content')

<x-ui.page-header
  title="Kenaikan Kelas Otomatis"
  subtitle="Naikkan kelas per lembaga dan tahun ajaran tanpa mengganti QR siswa"
  icon="bi-arrow-up-circle"
>
  <x-slot:actions>
    <x-ui.button :href="route('students.index')" variant="secondary">
      <i class="bi bi-people"></i>
      Data Santri
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

@if($errors->any())
  <div class="mb-6 rounded-[1.5rem] border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700">
    <div class="font-black"><i class="bi bi-exclamation-triangle"></i> Data belum diproses.</div>
    <ul class="mt-2 space-y-1 pl-5">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="mb-6 rounded-[1.5rem] border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-700">
  <div class="font-black"><i class="bi bi-shield-check"></i> Aman untuk data multi-lembaga</div>
  <p class="mt-1 font-semibold">
    Sistem membuat keanggotaan baru untuk tahun berikutnya. Riwayat tahun lama tetap tersimpan,
    siswa lulus hanya keluar dari lembaga yang dipilih, dan token QR tidak diubah.
  </p>
</div>

<x-ui.card class="mb-6">
  <form method="GET" class="grid items-end gap-4 lg:grid-cols-3">
    <div>
      <label for="institution_id" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
        Lembaga
      </label>
      <x-ui.select id="institution_id" name="institution_id" required>
        @forelse($institutions as $institutionOption)
          <option value="{{ $institutionOption->id }}" @selected($institution?->id === $institutionOption->id)>
            {{ $institutionOption->name }}
          </option>
        @empty
          <option value="">Tidak ada lembaga yang dapat diakses</option>
        @endforelse
      </x-ui.select>
    </div>

    <div>
      <label for="from_academic_year" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
        Tahun Ajaran Asal
      </label>
      <x-ui.select id="from_academic_year" name="from_academic_year" required>
        @foreach($academicYears as $year)
          <option value="{{ $year }}" @selected($fromAcademicYear === $year)>{{ $year }}</option>
        @endforeach
      </x-ui.select>
    </div>

    <x-ui.button type="submit" class="h-12">
      <i class="bi bi-search"></i>
      Tampilkan Rencana
    </x-ui.button>
  </form>
</x-ui.card>

@if($institution)
  <div class="mb-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
    <x-ui.stat-card label="Siswa Tahun Asal" :value="$summary['students']" icon="bi-people" tone="blue" />
    <x-ui.stat-card label="Belum Diproses" :value="$summary['remaining']" icon="bi-hourglass-split" tone="amber" />
    <x-ui.stat-card label="Kelompok Kelas" :value="$summary['groups']" icon="bi-collection" tone="emerald" />
    <x-ui.stat-card label="Siswa Perlu Manual" :value="$summary['manual']" icon="bi-pencil-square" tone="red" />
  </div>

  <x-ui.card class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="text-xs font-black uppercase tracking-wide text-slate-400">Perpindahan Tahun Ajaran</div>
        <div class="mt-1 text-xl font-black text-slate-900">
          {{ $fromAcademicYear }}
          <i class="bi bi-arrow-right px-3 text-emerald-500"></i>
          {{ $toAcademicYear }}
        </div>
      </div>
      <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
        <i class="bi bi-calendar-check"></i>
        Proses otomatis dijadwalkan setiap 1 Juli pukul 00.10 WIB.
      </div>
    </div>
  </x-ui.card>

  @if($groups->isEmpty())
    <x-ui.empty-state
      title="Belum ada kelas pada tahun {{ $fromAcademicYear }}"
      subtitle="Pilih tahun ajaran lain atau lengkapi keanggotaan siswa pada lembaga ini."
      icon="bi-mortarboard" />
  @else
    @unless($canProcess)
      <div class="mb-5 rounded-[1.5rem] border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-700">
        <div class="font-black"><i class="bi bi-clock-history"></i> Ini masih rencana tahun depan</div>
        <p class="mt-1 font-semibold">
          Tahun ajaran {{ $toAcademicYear }} belum dimulai. Rencana dapat diperiksa sekarang,
          tetapi baru dapat diproses mulai 1 Juli {{ substr($toAcademicYear, 0, 4) }} agar kelas aktif tidak berubah terlalu cepat.
        </p>
      </div>
    @endunless

    <form
      method="POST"
      action="{{ route('students.promotions.store') }}"
      id="promotionForm"
      onsubmit="return confirm('Proses kenaikan kelas yang dipilih ke tahun {{ $toAcademicYear }}?')">
      @csrf
      <input type="hidden" name="institution_id" value="{{ $institution->id }}">
      <input type="hidden" name="from_academic_year" value="{{ $fromAcademicYear }}">

      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm font-bold text-slate-600">
          Periksa kelas tujuan. Baris yang tidak dicentang tidak akan diubah.
        </div>
        <button type="button" id="toggleAllGroups" class="rounded-2xl bg-white px-4 py-2 text-sm font-black text-slate-700 ring-1 ring-slate-200">
          Pilih / Lepas Semua
        </button>
      </div>

      <div class="space-y-4">
        @foreach($groups as $index => $group)
          @php
            $oldGroup = old('groups.'.$index, []);
            $action = $oldGroup['action'] ?? $group['action'];
            $isSelected = array_key_exists('selected', $oldGroup)
              ? (bool) $oldGroup['selected']
              : ($group['remaining'] > 0 && $group['action'] !== 'skip');
          @endphp

          <x-ui.card data-group-card>
            <input type="hidden" name="groups[{{ $index }}][selected]" value="0">
            <input type="hidden" name="groups[{{ $index }}][source_class]" value="{{ $group['source_class'] }}">
            <input type="hidden" name="groups[{{ $index }}][source_level]" value="{{ $group['source_level'] }}">

            <div class="grid items-end gap-5 md:grid-cols-2 lg:grid-cols-5">
              <div class="flex items-center gap-3 pb-2">
                <input
                  type="checkbox"
                  name="groups[{{ $index }}][selected]"
                  value="1"
                  data-group-checkbox
                  @checked($isSelected)
                  @disabled($group['remaining'] === 0 || ! $canProcess)
                  class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <div>
                  <div class="text-xs font-black uppercase tracking-wide text-slate-400">Kelas Asal</div>
                  <div class="mt-1 text-lg font-black text-slate-900">
                    {{ $group['source_class'] ?: 'Tanpa kelas' }}
                  </div>
                  <div class="text-xs font-bold text-slate-500">{{ $group['source_level'] ?: 'Tanpa jenjang' }}</div>
                </div>
              </div>

              <div>
                <div class="mb-2 flex flex-wrap gap-2">
                  <x-ui.badge tone="blue">{{ $group['total'] }} siswa</x-ui.badge>
                  @if($group['processed'] > 0)
                    <x-ui.badge tone="emerald">{{ $group['processed'] }} sudah diproses</x-ui.badge>
                  @endif
                  @if($group['target_exists'] > 0)
                    <x-ui.badge tone="slate">{{ $group['target_exists'] }} sudah ada di tahun tujuan</x-ui.badge>
                  @endif
                </div>
                <div class="text-sm font-bold {{ $group['action'] === 'skip' ? 'text-amber-700' : 'text-slate-600' }}">
                  {{ $group['reason'] }}
                </div>
              </div>

              <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Aksi</label>
                <x-ui.select name="groups[{{ $index }}][action]" data-action-select>
                  <option value="promote" @selected($action === 'promote')>Naik Kelas</option>
                  <option value="graduate" @selected($action === 'graduate')>Lulus dari Lembaga</option>
                  <option value="skip" @selected($action === 'skip')>Lewati</option>
                </x-ui.select>
              </div>

              <div data-target-field>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Kelas Tujuan</label>
                <x-ui.input
                  name="groups[{{ $index }}][target_class]"
                  :value="$oldGroup['target_class'] ?? $group['target_class']"
                  maxlength="50"
                  placeholder="Contoh: 11A" />
              </div>

              <div data-target-field>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Jenjang Tujuan</label>
                <x-ui.input
                  name="groups[{{ $index }}][target_level]"
                  :value="$oldGroup['target_level'] ?? $group['target_level']"
                  maxlength="20"
                  placeholder="Contoh: MA" />
              </div>
            </div>
          </x-ui.card>
        @endforeach
      </div>

      <div class="sticky bottom-20 z-40 mt-6 rounded-[1.75rem] border border-emerald-200 bg-white p-4 shadow-xl shadow-slate-200/70">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="text-sm font-bold text-slate-600">
            <i class="bi bi-info-circle text-emerald-600"></i>
            Data yang sudah diproses tidak akan dibuat ganda ketika tombol ditekan lagi.
          </div>
          @if($canProcess)
            <x-ui.button type="submit" class="justify-center">
              <i class="bi bi-arrow-up-circle"></i>
              Proses Kenaikan Kelas
            </x-ui.button>
          @else
            <span class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-500">
              <i class="bi bi-lock"></i>
              Menunggu 1 Juli {{ substr($toAcademicYear, 0, 4) }}
            </span>
          @endif
        </div>
      </div>
    </form>
  @endif

  @if($recentPromotions->isNotEmpty())
    <x-ui.card padding="p-0" class="mt-8">
      <div class="border-b border-slate-100 px-5 py-4">
        <h2 class="font-black text-slate-900">Riwayat Proses Terbaru</h2>
        <p class="mt-1 text-sm font-semibold text-slate-500">Audit kenaikan kelas untuk {{ $institution->short_name }}.</p>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[760px]">
          <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-400">
            <tr>
              <th class="px-5 py-3">Waktu</th>
              <th class="px-5 py-3">Siswa</th>
              <th class="px-5 py-3">Perubahan</th>
              <th class="px-5 py-3">Status</th>
              <th class="px-5 py-3">Diproses Oleh</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($recentPromotions as $promotion)
              <tr>
                <td class="px-5 py-4 text-sm font-semibold text-slate-500">
                  {{ $promotion->processed_at?->format('d/m/Y H:i') }}
                </td>
                <td class="px-5 py-4">
                  <div class="font-black text-slate-900">{{ $promotion->student?->name ?: 'Siswa terhapus' }}</div>
                  <div class="text-xs font-bold text-slate-500">{{ $promotion->student?->nis }}</div>
                </td>
                <td class="px-5 py-4 text-sm font-bold text-slate-600">
                  {{ $promotion->from_class ?: '-' }} / {{ $promotion->from_level ?: '-' }}
                  <i class="bi bi-arrow-right px-3 text-emerald-500"></i>
                  {{ $promotion->to_class ?: 'Lulus' }} / {{ $promotion->to_level ?: '-' }}
                </td>
                <td class="px-5 py-4">
                  @if($promotion->action === 'graduated')
                    <x-ui.badge tone="amber">Lulus</x-ui.badge>
                  @elseif($promotion->action === 'already_enrolled')
                    <x-ui.badge tone="slate">Sudah Terdaftar</x-ui.badge>
                  @else
                    <x-ui.badge tone="emerald">Naik Kelas</x-ui.badge>
                  @endif
                </td>
                <td class="px-5 py-4 text-sm font-bold text-slate-600">
                  {{ $promotion->processor?->name ?: 'Sistem Otomatis' }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </x-ui.card>
  @endif
@endif

@push('scripts')
<script>
  document.querySelectorAll('[data-group-card]').forEach((card) => {
    const action = card.querySelector('[data-action-select]');
    const targetFields = card.querySelectorAll('[data-target-field]');

    const refreshTargetFields = () => {
      const enabled = action?.value === 'promote';
      targetFields.forEach((field) => {
        field.classList.toggle('opacity-50', !enabled);
        field.querySelectorAll('input').forEach((input) => {
          input.readOnly = !enabled;
        });
      });
    };

    action?.addEventListener('change', refreshTargetFields);
    refreshTargetFields();
  });

  document.getElementById('toggleAllGroups')?.addEventListener('click', () => {
    const checkboxes = [...document.querySelectorAll('[data-group-checkbox]:not(:disabled)')];
    const shouldCheck = checkboxes.some((checkbox) => !checkbox.checked);
    checkboxes.forEach((checkbox) => checkbox.checked = shouldCheck);
  });
</script>
@endpush

@endsection
