@extends('layouts.app')

@section('title', 'Dashboard '.$institution->short_name)
@section('mobile_title', $institution->short_name)

@section('content')
<x-ui.page-header
  :title="'Dashboard '.$institution->short_name"
  :subtitle="$institution->name.' • Tahun ajaran '.$academicYear"
  :icon="$institution->icon"
>
  <x-slot:actions>
    <x-ui.button :href="route('dashboard')" variant="secondary">
      <i class="bi bi-arrow-left"></i>
      Dashboard Utama
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
  <x-ui.stat-card label="Siswa Aktif" :value="$totalStudents" icon="bi-people" tone="emerald" />
  <x-ui.stat-card :label="$institution->code === 'madad' ? 'Jenjang/Kelas' : 'Jumlah Kelas'" :value="$totalClasses" icon="bi-door-open" tone="blue" />
  <x-ui.stat-card label="Pengguna" :value="$totalUsers" icon="bi-person-badge" tone="amber" />
</div>

@if($institution->code === 'madad' && $unassignedClassCount > 0)
  <div class="mb-6 flex flex-col gap-3 rounded-[1.5rem] border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <div class="font-black"><i class="bi bi-exclamation-triangle"></i> {{ $unassignedClassCount }} siswa MADAD belum memiliki jenjang/kelas lengkap</div>
      <div class="mt-1 font-semibold">Buka Data Santri, edit lembaga siswa, lalu pilih Ula/Wustha/Ulya dan isi Kelas MADAD.</div>
    </div>
    @can('manage_students')
      <x-ui.button :href="route('students.index', ['institution_id' => $institution->id])" variant="secondary">
        Perbaiki Data Siswa
      </x-ui.button>
    @endcan
  </div>
@endif

<div class="grid gap-6 lg:grid-cols-12">
  <x-ui.card class="lg:col-span-7">
    <div class="mb-4">
      <div class="text-lg font-black text-slate-900">Menu {{ $institution->short_name }}</div>
      <div class="text-sm font-medium text-slate-500">Modul khusus untuk {{ $institution->name }}</div>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
      @foreach($modules as $module)
        @if(!empty($module['permission']) && !auth()->user()?->can($module['permission']))
          @continue
        @endif
        @if(!empty($module['settings_access']) && !auth()->user()?->canManageInstitutionSettings($institution))
          @continue
        @endif
        @if($module['ready'] && $module['route'])
          <a href="{{ route($module['route'], $module['params'] ?? []) }}" class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 transition hover:-translate-y-1 hover:shadow-lg">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 text-xl text-white">
              <i class="bi {{ $module['icon'] }}"></i>
            </div>
            <div class="mt-3 text-sm font-black text-slate-800">{{ $module['label'] }}</div>
            <div class="mt-1 text-xs font-bold text-emerald-600">Buka modul</div>
          </a>
        @else
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-200 text-xl text-slate-500">
              <i class="bi {{ $module['icon'] }}"></i>
            </div>
            <div class="mt-3 text-sm font-black text-slate-700">{{ $module['label'] }}</div>
            <div class="mt-1 text-xs font-bold text-amber-600">Tahap berikutnya</div>
          </div>
        @endif
      @endforeach
    </div>
  </x-ui.card>

  <x-ui.card class="lg:col-span-5">
    <div class="mb-4">
      <div class="text-lg font-black text-slate-900">{{ $institution->code === 'madad' ? 'Data per Jenjang dan Kelas' : 'Data per Kelas' }}</div>
      <div class="text-sm font-medium text-slate-500">Keanggotaan aktif {{ $academicYear }}</div>
    </div>

    @if($studentsByClass->isEmpty())
      <div class="rounded-2xl bg-amber-50 p-4 text-sm font-bold text-amber-700">
        Belum ada siswa yang dimasukkan ke lembaga ini.
      </div>
    @else
      <div class="space-y-2">
        @foreach($studentsByClass as $class)
          <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
            <span class="font-bold text-slate-700">
              {{ $institution->code === 'madad' ? $class->level_label.' / Kelas '.$class->class_label : $class->class_label }}
            </span>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">
              {{ $class->total }} siswa
            </span>
          </div>
        @endforeach
      </div>
    @endif
  </x-ui.card>
</div>

@if($institution->code !== 'ponpes')
  <div class="mt-6 rounded-[1.75rem] border {{ $institution->code === 'madad' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-blue-200 bg-blue-50 text-blue-700' }} p-4 text-sm font-semibold">
    @if($institution->code === 'madad')
      Dashboard MADAD sudah aktif. Scan, jadwal, data siswa, dan seluruh rekap hanya memakai kegiatan Diniyah serta keanggotaan MADAD.
    @else
      Dashboard lembaga sudah aktif. Data dan rekap ditampilkan terpisah sesuai lembaga yang dipilih.
    @endif
  </div>
@endif
@endsection
