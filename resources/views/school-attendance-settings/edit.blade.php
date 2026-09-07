@extends('layouts.app')

@section('title', 'Pengaturan Waktu '.$institution->short_name)
@section('mobile_title', 'Pengaturan Waktu')

@section('content')
<x-ui.page-header
  :title="'Pengaturan Waktu '.$institution->short_name"
  subtitle="Atur batas scan masuk dan pulang siswa serta guru"
  icon="bi-clock-history"
>
  <x-slot:actions>
    <x-ui.button :href="route('dashboard.institution', $institution)" variant="secondary">
      <i class="bi bi-arrow-left"></i>
      Dashboard {{ $institution->short_name }}
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

@if(session('success'))
  <div class="mb-6 rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-black text-emerald-700">
    <i class="bi bi-check-circle"></i>
    {{ session('success') }}
  </div>
@endif

@if($errors->any())
  <div class="mb-6 rounded-[1.5rem] border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700">
    <div class="font-black">Pengaturan belum disimpan:</div>
    <ul class="mt-2 list-disc space-y-1 pl-5">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ route('school-attendance-settings.update', $institution) }}">
  @csrf
  @method('PUT')

  <div class="grid gap-6 lg:grid-cols-12">
    <div class="space-y-6 lg:col-span-8">
      <x-ui.card>
        <div class="mb-6">
          <div class="text-lg font-black text-slate-900">Waktu Masuk</div>
          <div class="mt-1 text-sm font-medium text-slate-500">
            Scan sebelum jam resmi dicatat tepat waktu; setelahnya dicatat terlambat sampai batas akhir.
          </div>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
          <x-ui.form-group label="Scan Dibuka" required>
            <x-ui.input type="time" name="check_in_open"
              value="{{ old('check_in_open', substr($setting->check_in_open, 0, 5)) }}" />
          </x-ui.form-group>
          <x-ui.form-group label="Jam Masuk Resmi" required>
            <x-ui.input type="time" name="check_in_time"
              value="{{ old('check_in_time', substr($setting->check_in_time, 0, 5)) }}" />
          </x-ui.form-group>
          <x-ui.form-group label="Batas Akhir Scan" required>
            <x-ui.input type="time" name="check_in_deadline"
              value="{{ old('check_in_deadline', substr($setting->check_in_deadline, 0, 5)) }}" />
          </x-ui.form-group>
        </div>
      </x-ui.card>

      <x-ui.card>
        <div class="mb-6">
          <div class="text-lg font-black text-slate-900">Waktu Pulang</div>
          <div class="mt-1 text-sm font-medium text-slate-500">
            Atur kapan scan pulang dibuka, jam pulang resmi, dan batas akhirnya.
          </div>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
          <x-ui.form-group label="Scan Dibuka" required>
            <x-ui.input type="time" name="check_out_open"
              value="{{ old('check_out_open', substr($setting->check_out_open, 0, 5)) }}" />
          </x-ui.form-group>
          <x-ui.form-group label="Jam Pulang Resmi" required>
            <x-ui.input type="time" name="check_out_time"
              value="{{ old('check_out_time', substr($setting->check_out_time, 0, 5)) }}" />
          </x-ui.form-group>
          <x-ui.form-group label="Batas Akhir Scan" required>
            <x-ui.input type="time" name="check_out_deadline"
              value="{{ old('check_out_deadline', substr($setting->check_out_deadline, 0, 5)) }}" />
          </x-ui.form-group>
        </div>
      </x-ui.card>
    </div>

    <div class="space-y-6 lg:col-span-4">
      <x-ui.card>
        <div class="text-lg font-black text-slate-900">Status Pengaturan</div>
        <div class="mt-4 flex items-center justify-between rounded-2xl bg-emerald-50 p-4">
          <div>
            <div class="text-sm font-black text-emerald-900">Aktif</div>
            <div class="mt-1 text-xs font-semibold text-emerald-600">Waktu ini dipakai saat scan</div>
          </div>
          <i class="bi bi-check-circle-fill text-xl text-emerald-600"></i>
        </div>

        <x-ui.button type="submit" class="mt-5 w-full justify-center">
          <i class="bi bi-check-circle"></i>
          Simpan Pengaturan
        </x-ui.button>
      </x-ui.card>

      <x-ui.card>
        <div class="text-base font-black text-slate-900">Pengaturan Jadwal Lain</div>
        <div class="mt-1 text-sm font-medium text-slate-500">Admin juga dapat mengubah jadwal berikut.</div>
        <div class="mt-4 space-y-3">
          <x-ui.button :href="route('prayers.index')" variant="secondary" class="w-full justify-center">
            Jadwal Salat
          </x-ui.button>
          <x-ui.button :href="route('activities.index', ['category' => 'umum'])" variant="secondary" class="w-full justify-center">
            Kegiatan Pondok
          </x-ui.button>
          <x-ui.button :href="route('activities.index', ['category' => 'diniyah'])" variant="secondary" class="w-full justify-center">
            Kegiatan MADAD
          </x-ui.button>
        </div>
      </x-ui.card>
    </div>
  </div>
</form>
@endsection
