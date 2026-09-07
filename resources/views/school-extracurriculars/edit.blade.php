@extends('layouts.app')

@section('title', 'Edit Ekstrakurikuler')
@section('mobile_title', 'Edit Ekstrakurikuler')

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
  title="Edit Ekstrakurikuler"
  :subtitle="$extracurricular->name.' • '.$institution->name"
  icon="bi-pencil-square"
>
  <x-slot:actions>
    <x-ui.button :href="route('school-extracurriculars.index', $institution)" variant="secondary">
      Kembali
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

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

<form method="POST" action="{{ route('school-extracurriculars.update', [$institution, $extracurricular]) }}">
  @csrf
  @method('PUT')

  <div class="grid gap-6 lg:grid-cols-12">
    <div class="lg:col-span-8">
      <x-ui.card>
        <div class="mb-5">
          <div class="text-lg font-black text-slate-900">Informasi Kegiatan</div>
          <div class="text-sm font-medium text-slate-500">Kode tetap: {{ $extracurricular->code }}</div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label for="name" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Nama Kegiatan</label>
            <input id="name" name="name" value="{{ old('name', $extracurricular->name) }}" required maxlength="120" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          </div>

          <div>
            <label for="level" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Peserta</label>
            <select id="level" name="level" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
              <option value="mts" @selected(old('level', $extracurricular->level) === 'mts')>MTs</option>
              <option value="ma" @selected(old('level', $extracurricular->level) === 'ma')>MA</option>
              <option value="mts_ma" @selected(old('level', $extracurricular->level) === 'mts_ma')>MTs & MA</option>
            </select>
          </div>

          <div>
            <label for="schedule_day" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Hari</label>
            <select id="schedule_day" name="schedule_day" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
              @foreach($dayOptions as $number => $label)
                <option value="{{ $number }}" @selected((int) old('schedule_day', $extracurricular->schedule_day) === $number)>{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label for="start_time" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Jam Mulai</label>
            <input id="start_time" name="start_time" type="time" value="{{ old('start_time', $extracurricular->startTimeLabel()) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          </div>

          <div>
            <label for="end_time" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Jam Selesai</label>
            <input id="end_time" name="end_time" type="time" value="{{ old('end_time', $extracurricular->endTimeLabel()) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          </div>

          <div>
            <label for="late_minutes" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Toleransi Telat</label>
            <input id="late_minutes" name="late_minutes" type="number" min="0" max="180" value="{{ old('late_minutes', $extracurricular->late_minutes) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          </div>

          <div>
            <label for="coach_name" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Pembina</label>
            <input id="coach_name" name="coach_name" value="{{ old('coach_name', $extracurricular->coach_name) }}" maxlength="120" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          </div>

          <div class="sm:col-span-2">
            <label for="description" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Keterangan</label>
            <textarea id="description" name="description" rows="4" maxlength="1000" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">{{ old('description', $extracurricular->description) }}</textarea>
          </div>
        </div>
      </x-ui.card>
    </div>

    <div class="lg:col-span-4">
      <div class="space-y-6">
        <x-ui.card>
          <label class="flex items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3">
            <div>
              <div class="text-sm font-black text-emerald-900">Status Aktif</div>
              <div class="text-xs font-semibold text-emerald-600">Nonaktifkan tanpa menghapus riwayat</div>
            </div>
            <input type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded border-emerald-300 text-emerald-600" @checked(old('is_active', $extracurricular->is_active))>
          </label>
        </x-ui.card>

        <x-ui.card>
          <div class="space-y-3">
            <x-ui.button type="submit" class="w-full justify-center">
              <i class="bi bi-check-circle"></i>
              Simpan Perubahan
            </x-ui.button>
            <x-ui.button :href="route('school-extracurriculars.index', $institution)" variant="secondary" class="w-full justify-center">
              Batal
            </x-ui.button>
          </div>
        </x-ui.card>
      </div>
    </div>
  </div>
</form>
@endsection
