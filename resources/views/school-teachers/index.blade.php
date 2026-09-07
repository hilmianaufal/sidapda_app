@extends('layouts.app')

@section('title', 'Data Guru '.$institution->short_name)
@section('mobile_title', 'Data Guru')

@section('content')
<x-ui.page-header
  :title="'Data Guru '.$institution->short_name"
  :subtitle="$institution->name.' • QR guru dibuat otomatis dan tetap'"
  icon="bi-person-badge"
>
  <x-slot:actions>
    <x-ui.button :href="route('school-teachers.template', $institution)" variant="secondary">
      <i class="bi bi-file-earmark-arrow-down"></i>
      Template
    </x-ui.button>
    <x-ui.button :href="route('school-teachers.export', [
      'institution' => $institution,
      'q' => $q,
      'level' => $level,
      'status' => $status,
    ])" variant="secondary">
      <i class="bi bi-file-earmark-excel"></i>
      Export
    </x-ui.button>
    <x-ui.button :href="route('dashboard.institution', $institution)" variant="secondary">
      Dashboard {{ $institution->short_name }}
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
  <x-ui.stat-card label="Total Guru" :value="$summary['total']" icon="bi-people" tone="slate" />
  <x-ui.stat-card label="Guru Aktif" :value="$summary['active']" icon="bi-person-check" tone="emerald" />
  <x-ui.stat-card label="Nonaktif" :value="$summary['inactive']" icon="bi-person-x" tone="red" />
</div>

<div class="grid gap-6 lg:grid-cols-12">
  <div class="lg:col-span-7">
    <x-ui.card>
      <div class="mb-5">
        <div class="text-lg font-black text-slate-900">Tambah Guru</div>
        <div class="text-sm font-medium text-slate-500">Kode guru dapat berupa NIP, NIK, atau kode internal yang unik.</div>
      </div>

      <form method="POST" action="{{ route('school-teachers.store', $institution) }}" class="grid gap-4 sm:grid-cols-2">
        @csrf

        <div>
          <label for="teacher_code" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Kode Guru/NIP</label>
          <input id="teacher_code" name="teacher_code" value="{{ old('teacher_code') }}" required maxlength="50" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100" placeholder="Contoh: G001">
        </div>

        <div>
          <label for="name" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Nama Guru</label>
          <input id="name" name="name" value="{{ old('name') }}" required maxlength="120" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100" placeholder="Nama lengkap guru">
        </div>

        <div>
          <label for="gender" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Jenis Kelamin</label>
          <select id="gender" name="gender" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
            <option value="">Pilih</option>
            <option value="putra" @selected(old('gender') === 'putra')>Laki-laki</option>
            <option value="putri" @selected(old('gender') === 'putri')>Perempuan</option>
          </select>
        </div>

        <div>
          <label for="level" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Jenjang Mengajar</label>
          <select id="level" name="level" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
            <option value="">Pilih jenjang</option>
            @foreach($levelOptions as $levelValue => $levelLabel)
              <option value="{{ $levelValue }}" @selected(old('level') === $levelValue)>{{ $levelLabel }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label for="phone" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">No. HP</label>
          <input id="phone" name="phone" value="{{ old('phone') }}" maxlength="30" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100" placeholder="08xxxxxxxxxx">
        </div>

        <label class="flex items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3">
          <div>
            <div class="text-sm font-black text-emerald-900">Status Aktif</div>
            <div class="text-xs font-semibold text-emerald-600">Guru dapat digunakan untuk absensi</div>
          </div>
          <input type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded border-emerald-300 text-emerald-600" @checked(old('is_active', true))>
        </label>

        <button type="submit" class="rounded-2xl bg-gradient-to-r from-emerald-600 to-lime-500 px-5 py-3 text-sm font-black text-white sm:col-span-2">
          <i class="bi bi-plus-circle"></i>
          Simpan Guru
        </button>
      </form>
    </x-ui.card>
  </div>

  <div class="lg:col-span-5">
    <x-ui.card>
      <div class="mb-5">
        <div class="text-lg font-black text-slate-900">Import Excel</div>
        <div class="text-sm font-medium text-slate-500">Import ulang kode yang sama akan memperbarui data tanpa mengganti QR.</div>
      </div>

      <form method="POST" action="{{ route('school-teachers.import', $institution) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <label class="block cursor-pointer rounded-2xl border-2 border-dashed border-emerald-200 bg-emerald-50/60 p-6 text-center">
          <i class="bi bi-cloud-arrow-up text-3xl text-emerald-600"></i>
          <div class="mt-2 text-sm font-black text-emerald-900">Pilih Excel Guru</div>
          <div class="text-xs font-semibold text-slate-500">XLSX, XLS, atau CSV maksimal 5 MB</div>
          <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="mt-4 block w-full text-sm font-semibold text-slate-500">
        </label>
        <button type="submit" class="w-full rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white">
          <i class="bi bi-upload"></i>
          Import Sekarang
        </button>
      </form>

      <div class="mt-4 rounded-2xl bg-amber-50 p-4 text-xs font-bold text-amber-700">
        Header: kode_guru, nama, jenis_kelamin, jenjang, no_hp, status.
      </div>
    </x-ui.card>
  </div>
</div>

<x-ui.card class="mt-6 mb-6">
  <form method="GET" action="{{ route('school-teachers.index', $institution) }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <input name="q" value="{{ $q }}" placeholder="Cari nama atau kode guru" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
    <select name="level" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
      <option value="">Semua jenjang</option>
      @foreach($levelOptions as $levelValue => $levelLabel)
        <option value="{{ $levelValue }}" @selected($level === $levelValue)>{{ $levelLabel }}</option>
      @endforeach
    </select>
    <select name="status" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
      <option value="">Semua status</option>
      <option value="active" @selected($status === 'active')>Aktif</option>
      <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
    </select>
    <div class="flex gap-2">
      <button type="submit" class="flex-1 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Filter</button>
      <a href="{{ route('school-teachers.index', $institution) }}" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-700">Reset</a>
    </div>
  </form>
</x-ui.card>

<x-ui.card padding="p-0">
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50">
        <tr class="text-xs font-black uppercase tracking-wide text-slate-400">
          <th class="px-5 py-4">QR</th>
          <th class="px-5 py-4">Guru</th>
          <th class="px-5 py-4">Jenjang</th>
          <th class="px-5 py-4">Kontak</th>
          <th class="px-5 py-4">Status</th>
          <th class="px-5 py-4 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($teachers as $teacher)
          <tr class="align-middle hover:bg-emerald-50/40">
            <td class="px-5 py-4">
              <img src="{{ route('school-teachers.qr', [$institution, $teacher]) }}" alt="QR {{ $teacher->name }}" class="h-16 w-16 rounded-xl border border-slate-100 bg-white p-1">
            </td>
            <td class="px-5 py-4">
              <div class="font-black text-slate-900">{{ $teacher->name }}</div>
              <div class="text-xs font-bold text-slate-400">{{ $teacher->teacher_code }} • {{ $teacher->genderLabel() }}</div>
            </td>
            <td class="px-5 py-4"><x-ui.badge tone="blue">{{ $teacher->levelLabel() }}</x-ui.badge></td>
            <td class="px-5 py-4 font-bold text-slate-600">{{ $teacher->phone ?: '-' }}</td>
            <td class="px-5 py-4">
              <x-ui.badge tone="{{ $teacher->is_active ? 'emerald' : 'red' }}">{{ $teacher->is_active ? 'Aktif' : 'Nonaktif' }}</x-ui.badge>
            </td>
            <td class="px-5 py-4">
              <div class="flex justify-end gap-2">
                <x-ui.button :href="route('school-teachers.qr.download', [$institution, $teacher])" variant="secondary">
                  <i class="bi bi-download"></i>
                  QR
                </x-ui.button>
                <x-ui.button :href="route('school-teachers.edit', [$institution, $teacher])" variant="secondary">
                  <i class="bi bi-pencil"></i>
                  Edit
                </x-ui.button>
                @if(auth()->user()?->hasRole('admin'))
                  <form method="POST" action="{{ route('school-teachers.destroy', [$institution, $teacher]) }}" onsubmit="return confirm('Hapus guru yang belum terpakai ini?')">
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
            <td colspan="6" class="p-10 text-center font-bold text-slate-400">Belum ada data guru pada filter ini.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($teachers->hasPages())
    <div class="border-t border-slate-100 p-5">{{ $teachers->links() }}</div>
  @endif
</x-ui.card>
@endsection
