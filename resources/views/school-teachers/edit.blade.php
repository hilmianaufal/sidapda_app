@extends('layouts.app')

@section('title', 'Edit Guru '.$institution->short_name)
@section('mobile_title', 'Edit Guru')

@section('content')
<x-ui.page-header
  :title="'Edit Guru '.$institution->short_name"
  :subtitle="$teacher->name.' • '.$teacher->teacher_code"
  icon="bi-pencil-square"
>
  <x-slot:actions>
    <x-ui.button :href="route('school-teachers.index', $institution)" variant="secondary">Kembali</x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

@if($errors->any())
  <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">
    <ul class="list-disc space-y-1 pl-5">
      @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
  </div>
@endif

<div class="grid gap-6 lg:grid-cols-12">
  <div class="lg:col-span-8">
    <x-ui.card>
      <form method="POST" action="{{ route('school-teachers.update', [$institution, $teacher]) }}" class="grid gap-5 sm:grid-cols-2">
        @csrf
        @method('PUT')

        <div>
          <label for="teacher_code" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Kode Guru/NIP</label>
          <input id="teacher_code" name="teacher_code" value="{{ old('teacher_code', $teacher->teacher_code) }}" required maxlength="50" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
        </div>

        <div>
          <label for="name" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Nama Guru</label>
          <input id="name" name="name" value="{{ old('name', $teacher->name) }}" required maxlength="120" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
        </div>

        <div>
          <label for="gender" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Jenis Kelamin</label>
          <select id="gender" name="gender" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
            <option value="">Pilih</option>
            <option value="putra" @selected(old('gender', $teacher->gender) === 'putra')>Laki-laki</option>
            <option value="putri" @selected(old('gender', $teacher->gender) === 'putri')>Perempuan</option>
          </select>
        </div>

        <div>
          <label for="level" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Jenjang Mengajar</label>
          <select id="level" name="level" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
            @foreach($levelOptions as $levelValue => $levelLabel)
              <option value="{{ $levelValue }}" @selected(old('level', $teacher->level) === $levelValue)>{{ $levelLabel }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label for="phone" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">No. HP</label>
          <input id="phone" name="phone" value="{{ old('phone', $teacher->phone) }}" maxlength="30" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
        </div>

        <label class="flex items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3">
          <div>
            <div class="text-sm font-black text-emerald-900">Status Aktif</div>
            <div class="text-xs font-semibold text-emerald-600">Nonaktifkan tanpa menghapus data</div>
          </div>
          <input type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded border-emerald-300 text-emerald-600" @checked(old('is_active', $teacher->is_active))>
        </label>

        <button type="submit" class="rounded-2xl bg-gradient-to-r from-emerald-600 to-lime-500 px-5 py-3 text-sm font-black text-white sm:col-span-2">
          <i class="bi bi-check-circle"></i>
          Simpan Perubahan
        </button>
      </form>
    </x-ui.card>
  </div>

  <div class="lg:col-span-4">
    <x-ui.card>
      <div class="text-center">
        <img src="{{ route('school-teachers.qr', [$institution, $teacher]) }}" alt="QR {{ $teacher->name }}" class="mx-auto h-56 w-56 rounded-2xl border border-slate-100 bg-white p-2">
        <div class="mt-4 text-sm font-black text-slate-900">QR Tetap Guru</div>
        <div class="mt-1 text-xs font-semibold text-slate-500">Mengubah nama, kode, atau jenjang tidak mengganti QR.</div>
        <x-ui.button :href="route('school-teachers.qr.download', [$institution, $teacher])" class="mt-4 w-full justify-center">
          <i class="bi bi-download"></i>
          Download QR PNG
        </x-ui.button>
      </div>
    </x-ui.card>
  </div>
</div>
@endsection
