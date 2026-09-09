@extends('layouts.app')

@section('title', 'Rekap Absensi Ekstrakurikuler')
@section('mobile_title', 'Rekap Ekstra')

@section('content')
<x-ui.page-header
  :title="'Rekap Absensi Ekstrakurikuler '.$institution->short_name"
  :subtitle="$institution->name.' • Tahun ajaran '.$academicYear"
  icon="bi-clipboard-data"
>
  <x-slot:actions>
    <x-ui.button :href="route('school-extracurricular-attendance.index', ['institution' => $institution, 'extracurricular_id' => $selectedExtracurricular?->id])" variant="secondary">
      <i class="bi bi-qr-code-scan"></i>
      Scan Absensi
    </x-ui.button>
    <x-ui.button :href="route('school-extracurricular-attendance.excuses.index', ['institution' => $institution, 'extracurricular_id' => $selectedExtracurricular?->id, 'date' => $date])" variant="secondary">
      <i class="bi bi-file-earmark-medical"></i>
      Izin & Sakit
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

@if(!$selectedExtracurricular)
  <div class="rounded-[2rem] border border-amber-200 bg-amber-50 p-8 text-center">
    <i class="bi bi-trophy text-5xl text-amber-500"></i>
    <div class="mt-4 text-xl font-black text-amber-950">Belum ada data ekstrakurikuler</div>
    <div class="mt-2 text-sm font-semibold text-amber-700">Tambahkan jadwal ekstrakurikuler sebelum membuka rekap.</div>
    <a href="{{ route('school-extracurriculars.index', $institution) }}" class="mt-5 inline-flex rounded-2xl bg-amber-500 px-5 py-3 text-sm font-black text-white">
      Buka Master Data
    </a>
  </div>
@else
  <x-ui.card class="mb-6">
    <div class="mb-4">
      <div class="text-lg font-black text-slate-900">Filter Rekap</div>
      <div class="text-sm font-medium text-slate-500">Pilih kegiatan, tanggal, dan kelas peserta.</div>
    </div>

    <form method="GET" action="{{ route('school-extracurricular-attendance.reports.index', $institution) }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
      <div class="xl:col-span-2">
        <label for="extracurricular_id" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Ekstrakurikuler</label>
        <select id="extracurricular_id" name="extracurricular_id" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          @foreach($extracurriculars as $extracurricular)
            <option value="{{ $extracurricular->id }}" @selected($selectedExtracurricular->id === $extracurricular->id)>
              {{ $extracurricular->name }} • {{ $extracurricular->dayLabel() }} • {{ $extracurricular->levelLabel() }}{{ $extracurricular->is_active ? '' : ' • Nonaktif' }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label for="date" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Tanggal</label>
        <input id="date" name="date" type="date" value="{{ $date }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
      </div>

      <div>
        <label for="class_name" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Kelas</label>
        <select id="class_name" name="class_name" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          <option value="">Semua kelas</option>
          @foreach($classOptions as $class)
            <option value="{{ $class }}" @selected($className === $class)>{{ $class }}</option>
          @endforeach
        </select>
      </div>

      <div class="flex items-end gap-2">
        <button type="submit" class="flex-1 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">
          <i class="bi bi-funnel"></i>
          Tampilkan
        </button>
        <a href="{{ route('school-extracurricular-attendance.reports.index', $institution) }}" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-700">Reset</a>
      </div>

      <div class="md:col-span-2 xl:col-span-5">
        <a
          href="{{ route('school-extracurricular-attendance.reports.export', array_filter([
            'institution' => $institution,
            'extracurricular_id' => $selectedExtracurricular->id,
            'date' => $date,
            'class_name' => $className,
          ], fn ($value) => $value !== null && $value !== '')) }}"
          class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-500 px-4 py-3 text-sm font-black text-white shadow-lg shadow-blue-200 sm:w-auto">
          <i class="bi bi-file-earmark-excel"></i>
          Export Excel
        </a>
      </div>
    </form>
  </x-ui.card>

  <div class="mb-6 rounded-[1.75rem] border {{ $scheduledDate ? 'border-indigo-200 bg-indigo-50' : 'border-amber-200 bg-amber-50' }} p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="text-xs font-black uppercase tracking-wide {{ $scheduledDate ? 'text-indigo-400' : 'text-amber-500' }}">Rekap Kegiatan</div>
        <div class="mt-1 text-xl font-black {{ $scheduledDate ? 'text-indigo-950' : 'text-amber-950' }}">{{ $selectedExtracurricular->name }}</div>
        <div class="mt-1 text-sm font-semibold {{ $scheduledDate ? 'text-indigo-700' : 'text-amber-700' }}">
          {{ $selectedExtracurricular->dayLabel() }} • {{ $selectedExtracurricular->startTimeLabel() }}–{{ $selectedExtracurricular->endTimeLabel() }} • {{ $selectedExtracurricular->levelLabel() }}
        </div>
      </div>
      <div class="rounded-2xl bg-white/80 px-5 py-3 text-sm font-bold text-slate-700">
        {{ \Illuminate\Support\Carbon::parse($date)->format('d-m-Y') }}
      </div>
    </div>

    @if(!$scheduledDate)
      <div class="mt-4 rounded-2xl border border-amber-200 bg-white/70 px-4 py-3 text-sm font-bold text-amber-700">
        <i class="bi bi-exclamation-triangle"></i>
        Tanggal yang dipilih bukan jadwal hari {{ $selectedExtracurricular->dayLabel() }}. Pastikan tanggal rekap sudah benar.
      </div>
    @endif
  </div>

  <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
    <x-ui.stat-card label="Total Peserta" :value="$summary['total']" icon="bi-people" tone="slate" />
    <x-ui.stat-card label="Hadir" :value="$summary['hadir']" icon="bi-person-check" tone="emerald" />
    <x-ui.stat-card label="Terlambat" :value="$summary['terlambat']" icon="bi-clock-history" tone="amber" />
    <x-ui.stat-card label="Izin" :value="$summary['izin']" icon="bi-file-earmark-text" tone="blue" />
    <x-ui.stat-card label="Sakit" :value="$summary['sakit']" icon="bi-bandaid" tone="red" />
    <x-ui.stat-card label="Lainnya" :value="$summary['lainnya']" icon="bi-three-dots" tone="slate" />
    <x-ui.stat-card label="Alpa" :value="$summary['alpa']" icon="bi-person-x" tone="red" />
  </div>

  <x-ui.card>
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="text-lg font-black text-slate-900">Data per Siswa</div>
        <div class="text-sm font-medium text-slate-500">
          {{ $selectedExtracurricular->name }} • {{ \Illuminate\Support\Carbon::parse($date)->format('d-m-Y') }}
          @if($className)
            • Kelas {{ $className }}
          @endif
        </div>
      </div>
      <div class="text-xs font-bold text-slate-400">25 siswa per halaman</div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-left text-sm">
        <thead>
          <tr class="border-b border-slate-200 text-xs font-black uppercase tracking-wide text-slate-400">
            <th class="px-3 py-3">No</th>
            <th class="px-3 py-3">Siswa</th>
            <th class="px-3 py-3">Jenjang/Kelas</th>
            <th class="px-3 py-3">Status</th>
            <th class="px-3 py-3">Jam Scan</th>
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
                <div class="text-xs font-semibold text-slate-400">NIS {{ $row['nis'] }} • {{ $row['gender'] }}</div>
              </td>
              <td class="px-3 py-3 font-bold text-slate-600">{{ $row['level_label'] }} / {{ $row['class_name'] }}</td>
              <td class="px-3 py-3">
                <span class="rounded-full px-3 py-1 text-xs font-black {{ $dayStatusClass }}">{{ $row['day_status_label'] }}</span>
              </td>
              <td class="px-3 py-3 font-black text-slate-700">{{ $row['scan_time'] ?: '-' }}</td>
              <td class="max-w-xs px-3 py-3 font-semibold text-slate-600">
                {{ $row['notes'] }}
                @if($row['has_attachment'])
                  <a href="{{ route('school-extracurricular-attendance.reports.excuses.download', [$institution, $row['excuse_id']]) }}" class="mt-2 inline-flex items-center gap-1 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700">
                    <i class="bi bi-download"></i>
                    Unduh Surat
                  </a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-4 py-10 text-center font-bold text-slate-400">Belum ada siswa aktif pada filter ini.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($rows->hasPages())
      <div class="mt-5">{{ $rows->links() }}</div>
    @endif
  </x-ui.card>
@endif
@endsection
