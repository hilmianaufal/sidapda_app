@extends('layouts.app')

@section('title', 'Ekstrakurikuler Sekolah Pagi')
@section('mobile_title', 'Ekstrakurikuler')

@section('content')
@php
  $dayOptions = [
    0 => 'Ahad',
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
  ];
@endphp

<x-ui.page-header
  title="Ekstrakurikuler Sekolah Pagi"
  :subtitle="$institution->name.' • Jadwal MTs & MA terpisah dari kegiatan pondok'"
  icon="bi-trophy"
>
  <x-slot:actions>
    <x-ui.button :href="route('school-extracurricular-attendance.index', $institution)">
      <i class="bi bi-qr-code-scan"></i>
      Scan Absensi
    </x-ui.button>
    <x-ui.button :href="route('school-extracurricular-attendance.reports.index', $institution)" variant="secondary">
      <i class="bi bi-clipboard2-check"></i>
      Rekap
    </x-ui.button>
    <x-ui.button :href="route('school-extracurricular-attendance.excuses.index', $institution)" variant="secondary">
      <i class="bi bi-file-earmark-medical"></i>
      Izin & Sakit
    </x-ui.button>
    <x-ui.button :href="route('dashboard.institution', $institution)" variant="secondary">
      Dashboard MTs & MA
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

@if(session('success'))
  <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-black text-emerald-700">
    <i class="bi bi-check-circle"></i>
    {{ session('success') }}
  </div>
@endif

@if(session('error'))
  <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-black text-red-700">
    <i class="bi bi-exclamation-triangle"></i>
    {{ session('error') }}
  </div>
@endif

@if($errors->any())
  <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">
    <div class="mb-2 font-black">Data belum dapat disimpan:</div>
    <ul class="list-disc space-y-1 pl-5">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
  <x-ui.stat-card label="Total Kegiatan" :value="$summary['total']" icon="bi-trophy" tone="slate" />
  <x-ui.stat-card label="Aktif" :value="$summary['active']" icon="bi-check-circle" tone="emerald" />
  <x-ui.stat-card label="Nonaktif" :value="$summary['inactive']" icon="bi-pause-circle" tone="red" />
</div>

<div class="grid gap-6 xl:grid-cols-12">
  <div class="xl:col-span-5">
    <x-ui.card>
      <div class="mb-5">
        <div class="text-lg font-black text-slate-900">Tambah Ekstrakurikuler</div>
        <div class="text-sm font-medium text-slate-500">Contoh: Pramuka pada Ahad atau kegiatan lain pada Jumat.</div>
      </div>

      <form method="POST" action="{{ route('school-extracurriculars.store', $institution) }}" class="grid gap-4 sm:grid-cols-2">
        @csrf

        <div class="sm:col-span-2">
          <label for="name" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Nama Kegiatan</label>
          <input id="name" name="name" value="{{ old('name') }}" required maxlength="120" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100" placeholder="Contoh: Pramuka">
        </div>

        <div>
          <label for="level" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Peserta</label>
          <select id="level" name="level" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
            <option value="">Pilih jenjang</option>
            <option value="mts" @selected(old('level') === 'mts')>MTs</option>
            <option value="ma" @selected(old('level') === 'ma')>MA</option>
            <option value="mts_ma" @selected(old('level') === 'mts_ma')>MTs & MA</option>
          </select>
        </div>

        <div>
          <label for="schedule_day" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Hari</label>
          <select id="schedule_day" name="schedule_day" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
            <option value="">Pilih hari</option>
            @foreach($dayOptions as $number => $label)
              <option value="{{ $number }}" @selected((string) old('schedule_day', '') === (string) $number)>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label for="start_time" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Jam Mulai</label>
          <input id="start_time" name="start_time" type="time" value="{{ old('start_time') }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
        </div>

        <div>
          <label for="end_time" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Jam Selesai</label>
          <input id="end_time" name="end_time" type="time" value="{{ old('end_time') }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
        </div>

        <div>
          <label for="late_minutes" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Toleransi Telat</label>
          <input id="late_minutes" name="late_minutes" type="number" min="0" max="180" value="{{ old('late_minutes', 0) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100" placeholder="Menit">
        </div>

        <div>
          <label for="coach_name" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Pembina</label>
          <input id="coach_name" name="coach_name" value="{{ old('coach_name') }}" maxlength="120" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100" placeholder="Nama pembina">
        </div>

        <div class="sm:col-span-2">
          <label for="description" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Keterangan</label>
          <textarea id="description" name="description" rows="3" maxlength="1000" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100" placeholder="Keterangan tambahan (opsional)">{{ old('description') }}</textarea>
        </div>

        <label class="flex items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3 sm:col-span-2">
          <div>
            <div class="text-sm font-black text-emerald-900">Status Aktif</div>
            <div class="text-xs font-semibold text-emerald-600">Kegiatan dapat digunakan pada tahap absensi</div>
          </div>
          <input type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded border-emerald-300 text-emerald-600" @checked(old('is_active', true))>
        </label>

        <button type="submit" class="rounded-2xl bg-gradient-to-r from-emerald-600 to-lime-500 px-5 py-3 text-sm font-black text-white sm:col-span-2">
          <i class="bi bi-plus-circle"></i>
          Simpan Ekstrakurikuler
        </button>
      </form>
    </x-ui.card>
  </div>

  <div class="xl:col-span-7">
    <x-ui.card class="mb-6">
      <form method="GET" action="{{ route('school-extracurriculars.index', $institution) }}" class="grid gap-3 sm:grid-cols-2">
        <input name="q" value="{{ $q }}" placeholder="Cari kegiatan atau pembina" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">

        <select name="level" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          <option value="">Semua jenjang</option>
          <option value="mts" @selected($level === 'mts')>MTs</option>
          <option value="ma" @selected($level === 'ma')>MA</option>
          <option value="mts_ma" @selected($level === 'mts_ma')>MTs & MA</option>
        </select>

        <select name="day" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          <option value="">Semua hari</option>
          @foreach($dayOptions as $number => $label)
            <option value="{{ $number }}" @selected($day !== null && $day === $number)>{{ $label }}</option>
          @endforeach
        </select>

        <select name="status" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          <option value="">Semua status</option>
          <option value="active" @selected($status === 'active')>Aktif</option>
          <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
        </select>

        <div class="flex gap-2 sm:col-span-2">
          <button type="submit" class="flex-1 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Filter</button>
          <a href="{{ route('school-extracurriculars.index', $institution) }}" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-700">Reset</a>
        </div>
      </form>
    </x-ui.card>

    <x-ui.card padding="p-0">
      <div class="border-b border-slate-100 px-5 py-4">
        <div class="text-lg font-black text-slate-900">Daftar Ekstrakurikuler</div>
        <div class="text-sm font-medium text-slate-500">Jadwal ini khusus Sekolah Pagi dan tidak tercampur kegiatan pondok.</div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50">
            <tr class="text-xs font-black uppercase tracking-wide text-slate-400">
              <th class="px-5 py-4">Kegiatan</th>
              <th class="px-5 py-4">Jadwal</th>
              <th class="px-5 py-4">Peserta</th>
              <th class="px-5 py-4">Status</th>
              <th class="px-5 py-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($extracurriculars as $extracurricular)
              <tr class="align-top hover:bg-emerald-50/40">
                <td class="px-5 py-4">
                  <div class="font-black text-slate-900">{{ $extracurricular->name }}</div>
                  <div class="text-xs font-bold text-slate-400">{{ $extracurricular->code }}</div>
                  <div class="mt-1 text-xs font-semibold text-slate-500">Pembina: {{ $extracurricular->coach_name ?: '-' }}</div>
                </td>
                <td class="px-5 py-4">
                  <div class="font-black text-slate-700">{{ $extracurricular->dayLabel() }}</div>
                  <div class="text-xs font-bold text-slate-500">{{ $extracurricular->startTimeLabel() }}–{{ $extracurricular->endTimeLabel() }}</div>
                  <div class="text-xs font-semibold text-amber-600">Telat setelah {{ $extracurricular->late_minutes }} menit</div>
                </td>
                <td class="px-5 py-4">
                  <x-ui.badge tone="blue">{{ $extracurricular->levelLabel() }}</x-ui.badge>
                </td>
                <td class="px-5 py-4">
                  <x-ui.badge tone="{{ $extracurricular->is_active ? 'emerald' : 'red' }}">
                    {{ $extracurricular->is_active ? 'Aktif' : 'Nonaktif' }}
                  </x-ui.badge>
                </td>
                <td class="px-5 py-4">
                  <div class="flex justify-end gap-2">
                    <x-ui.button :href="route('school-extracurriculars.edit', [$institution, $extracurricular])" variant="secondary">
                      <i class="bi bi-pencil"></i>
                      Edit
                    </x-ui.button>
                    @if(auth()->user()?->hasRole('admin'))
                      <form method="POST" action="{{ route('school-extracurriculars.destroy', [$institution, $extracurricular]) }}" onsubmit="return confirm('Hapus ekstrakurikuler yang belum terpakai ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-red-50 px-4 py-2 text-sm font-black text-red-600 ring-1 ring-red-100 hover:bg-red-100">
                          <i class="bi bi-trash"></i>
                          Hapus
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-5 py-12">
                  <x-ui.empty-state title="Belum ada ekstrakurikuler" subtitle="Tambahkan Pramuka atau kegiatan Jumat dari formulir." icon="bi-trophy" />
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($extracurriculars->hasPages())
        <div class="border-t border-slate-100 p-5">{{ $extracurriculars->links() }}</div>
      @endif
    </x-ui.card>
  </div>
</div>
@endsection
