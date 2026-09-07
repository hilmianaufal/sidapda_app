@extends('layouts.app')

@section('title', 'Rekap Absensi Guru '.$institution->short_name)
@section('mobile_title', 'Rekap Guru')

@section('content')
<x-ui.page-header
  :title="'Rekap Absensi Guru '.$institution->short_name"
  :subtitle="$institution->name.' • Tahun ajaran '.$academicYear"
  icon="bi-clipboard-data"
>
  <x-slot:actions>
    <x-ui.button :href="route('school-teacher-attendance.index', $institution)" variant="secondary">
      <i class="bi bi-qr-code-scan"></i>
      Scan Absensi
    </x-ui.button>
    <x-ui.button :href="route('school-teacher-attendance.excuses.index', ['institution' => $institution, 'date' => $date])" variant="secondary">
      <i class="bi bi-file-earmark-medical"></i>
      Izin & Sakit
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<x-ui.card class="mb-6">
  <div class="mb-4">
    <div class="text-lg font-black text-slate-900">Filter Rekap</div>
    <div class="text-sm font-medium text-slate-500">Pilih tanggal dan jenjang guru.</div>
  </div>

  <form method="GET" action="{{ route('school-teacher-attendance.reports.index', $institution) }}" class="grid gap-3 md:grid-cols-4">
    <div>
      <label for="date" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Tanggal</label>
      <input
        id="date"
        name="date"
        type="date"
        value="{{ $date }}"
        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
    </div>

    <div>
      <label for="level" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Jenjang</label>
      <select id="level" name="level" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
        <option value="">Semua jenjang</option>
        @foreach($levelOptions as $levelValue => $levelLabel)
          <option value="{{ $levelValue }}" @selected($level === $levelValue)>{{ $levelLabel }}</option>
        @endforeach
      </select>
    </div>

    <div class="flex items-end gap-2">
      <button type="submit" class="flex-1 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">
        <i class="bi bi-funnel"></i>
        Tampilkan
      </button>
      <a href="{{ route('school-teacher-attendance.reports.index', $institution) }}" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-700">
        Reset
      </a>
    </div>

    <div class="flex items-end">
      <a
        href="{{ route('school-teacher-attendance.reports.export', array_filter([
          'institution' => $institution,
          'date' => $date,
          'level' => $level,
        ], fn ($value) => $value !== null && $value !== '')) }}"
        class="w-full rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-500 px-4 py-3 text-center text-sm font-black text-white shadow-lg shadow-blue-200">
        <i class="bi bi-file-earmark-excel"></i>
        Export Excel
      </a>
    </div>
  </form>
</x-ui.card>

<div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
  <x-ui.stat-card label="Total Guru" :value="$summary['total']" icon="bi-people" tone="slate" />
  <x-ui.stat-card label="Hadir" :value="$summary['hadir']" icon="bi-person-check" tone="emerald" />
  <x-ui.stat-card label="Terlambat" :value="$summary['terlambat']" icon="bi-clock-history" tone="amber" />
  <x-ui.stat-card label="Izin" :value="$summary['izin']" icon="bi-file-earmark-text" tone="blue" />
  <x-ui.stat-card label="Sakit" :value="$summary['sakit']" icon="bi-bandaid" tone="red" />
  <x-ui.stat-card label="Lainnya" :value="$summary['lainnya']" icon="bi-three-dots" tone="slate" />
  <x-ui.stat-card label="Alpa" :value="$summary['alpa']" icon="bi-person-x" tone="red" />
  <x-ui.stat-card label="Sudah Pulang" :value="$summary['sudah_pulang']" icon="bi-box-arrow-right" tone="blue" />
  <x-ui.stat-card label="Pulang Cepat" :value="$summary['pulang_cepat']" icon="bi-fast-forward" tone="amber" />
</div>

<x-ui.card>
  <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <div class="text-lg font-black text-slate-900">Data per Guru</div>
      <div class="text-sm font-medium text-slate-500">
        Tanggal {{ \Illuminate\Support\Carbon::parse($date)->format('d-m-Y') }}
        @if($level)
          • {{ $levelOptions[$level] ?? '-' }}
        @endif
      </div>
    </div>
    <div class="text-xs font-bold text-slate-400">25 guru per halaman</div>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead>
        <tr class="border-b border-slate-200 text-xs font-black uppercase tracking-wide text-slate-400">
          <th class="px-3 py-3">No</th>
          <th class="px-3 py-3">Guru</th>
          <th class="px-3 py-3">Jenjang</th>
          <th class="px-3 py-3">Status Harian</th>
          <th class="px-3 py-3">Masuk</th>
          <th class="px-3 py-3">Pulang</th>
          <th class="px-3 py-3">Keterangan</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $row)
          @php
            $dayStatusClass = match($row['day_status']) {
              'hadir' => 'bg-emerald-100 text-emerald-700',
              'terlambat' => 'bg-amber-100 text-amber-700',
              'izin' => 'bg-blue-100 text-blue-700',
              'sakit' => 'bg-red-100 text-red-700',
              'lainnya' => 'bg-slate-100 text-slate-600',
              default => 'bg-rose-100 text-rose-700',
            };
          @endphp
          <tr class="border-b border-slate-100 align-top">
            <td class="px-3 py-3 font-bold text-slate-400">{{ $rows->firstItem() + $loop->index }}</td>
            <td class="px-3 py-3">
              <div class="font-black text-slate-800">{{ $row['name'] }}</div>
              <div class="text-xs font-semibold text-slate-400">Kode Guru {{ $row['teacher_code'] }} • {{ $row['gender'] }}</div>
            </td>
            <td class="px-3 py-3 font-bold text-slate-600">{{ $row['level_label'] }}</td>
            <td class="px-3 py-3">
              <span class="rounded-full px-3 py-1 text-xs font-black {{ $dayStatusClass }}">
                {{ $row['day_status_label'] }}
              </span>
            </td>
            <td class="px-3 py-3">
              <div class="font-black text-slate-700">{{ $row['check_in_time'] ?: '-' }}</div>
              <div class="text-xs font-bold {{ $row['check_in_status'] === 'terlambat' ? 'text-amber-600' : 'text-slate-400' }}">
                {{ $row['check_in_status_label'] }}
              </div>
            </td>
            <td class="px-3 py-3">
              <div class="font-black text-slate-700">{{ $row['check_out_time'] ?: '-' }}</div>
              <div class="text-xs font-bold {{ $row['check_out_status'] === 'pulang_cepat' ? 'text-amber-600' : 'text-slate-400' }}">
                {{ $row['check_out_status_label'] }}
              </div>
            </td>
            <td class="max-w-xs px-3 py-3 font-semibold text-slate-600">
              {{ $row['notes'] }}
              @if($row['has_attachment'])
                <a
                  href="{{ route('school-teacher-attendance.reports.excuses.download', [$institution, $row['excuse_id']]) }}"
                  class="mt-2 inline-flex items-center gap-1 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700">
                  <i class="bi bi-download"></i>
                  Unduh Surat
                </a>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-4 py-10 text-center font-bold text-slate-400">
              Belum ada guru aktif pada filter ini.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($rows->hasPages())
    <div class="mt-5">{{ $rows->links() }}</div>
  @endif
</x-ui.card>
@endsection
