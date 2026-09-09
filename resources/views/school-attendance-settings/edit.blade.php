@extends('layouts.app')

@section('title', 'Pengaturan Waktu & Zona '.$institution->short_name)
@section('mobile_title', 'Waktu & Zona')

@section('content')
<x-ui.page-header
  :title="'Pengaturan Waktu & Zona '.$institution->short_name"
  subtitle="Atur batas waktu serta area yang diizinkan untuk absensi guru"
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

      <x-ui.card>
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div class="text-lg font-black text-slate-900">Zona Absensi Guru</div>
            <div class="mt-1 text-sm font-medium text-slate-500">
              Guru hanya dapat scan masuk dan pulang dari dalam radius sekolah.
            </div>
          </div>

          <label class="inline-flex cursor-pointer items-center gap-3 rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-700">
            <input type="hidden" name="teacher_geofence_enabled" value="0">
            <input
              type="checkbox"
              name="teacher_geofence_enabled"
              value="1"
              @checked((bool) old('teacher_geofence_enabled', $setting->teacher_geofence_enabled))
              class="h-5 w-5 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
            Aktifkan Zona Guru
          </label>
        </div>

        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-semibold text-blue-700">
          <i class="bi bi-info-circle"></i>
          Buka halaman ini saat berada di sekolah, lalu tekan <strong>Ambil Lokasi Perangkat</strong> agar titik pusat terisi otomatis.
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
          <x-ui.form-group label="Latitude Titik Sekolah" required>
            <x-ui.input
              id="teacherGeofenceLatitude"
              type="number"
              step="0.0000001"
              name="teacher_geofence_latitude"
              value="{{ old('teacher_geofence_latitude', $setting->teacher_geofence_latitude) }}"
              placeholder="Contoh: -6.7061234" />
          </x-ui.form-group>

          <x-ui.form-group label="Longitude Titik Sekolah" required>
            <x-ui.input
              id="teacherGeofenceLongitude"
              type="number"
              step="0.0000001"
              name="teacher_geofence_longitude"
              value="{{ old('teacher_geofence_longitude', $setting->teacher_geofence_longitude) }}"
              placeholder="Contoh: 108.5571234" />
          </x-ui.form-group>

          <x-ui.form-group label="Radius yang Diizinkan (meter)" required>
            <x-ui.input
              type="number"
              min="20"
              max="5000"
              name="teacher_geofence_radius_meters"
              value="{{ old('teacher_geofence_radius_meters', $setting->teacher_geofence_radius_meters ?? 200) }}" />
          </x-ui.form-group>

          <x-ui.form-group label="Batas Akurasi GPS (meter)" required>
            <x-ui.input
              type="number"
              min="10"
              max="2000"
              name="teacher_geofence_max_accuracy_meters"
              value="{{ old('teacher_geofence_max_accuracy_meters', $setting->teacher_geofence_max_accuracy_meters ?? 100) }}" />
          </x-ui.form-group>
        </div>

        <button
          id="btnUseCurrentLocation"
          type="button"
          class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-200">
          <i class="bi bi-geo-alt-fill"></i>
          Ambil Lokasi Perangkat
        </button>

        <div id="geofenceLocationStatus" class="mt-3 hidden rounded-2xl px-4 py-3 text-sm font-bold"></div>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const button = document.getElementById('btnUseCurrentLocation');
  const latitude = document.getElementById('teacherGeofenceLatitude');
  const longitude = document.getElementById('teacherGeofenceLongitude');
  const status = document.getElementById('geofenceLocationStatus');

  function showStatus(type, message) {
    status.className = `mt-3 rounded-2xl px-4 py-3 text-sm font-bold ${type === 'success'
      ? 'border border-emerald-200 bg-emerald-50 text-emerald-700'
      : 'border border-red-200 bg-red-50 text-red-700'}`;
    status.textContent = message;
  }

  button?.addEventListener('click', () => {
    if (!window.isSecureContext || !navigator.geolocation) {
      showStatus('danger', 'Lokasi tidak tersedia. Pastikan halaman memakai HTTPS dan izin lokasi browser aktif.');
      return;
    }

    button.disabled = true;
    button.innerHTML = '<i class="bi bi-arrow-repeat"></i> Mengambil Lokasi...';

    navigator.geolocation.getCurrentPosition(
      position => {
        latitude.value = position.coords.latitude.toFixed(7);
        longitude.value = position.coords.longitude.toFixed(7);
        showStatus('success', `Titik sekolah berhasil diambil. Akurasi perangkat sekitar ${Math.round(position.coords.accuracy)} meter.`);
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Ambil Ulang Lokasi';
      },
      error => {
        const messages = {
          1: 'Izin lokasi ditolak. Aktifkan izin lokasi untuk situs ini pada pengaturan browser.',
          2: 'Lokasi perangkat tidak dapat ditemukan. Aktifkan GPS lalu coba lagi.',
          3: 'Pengambilan lokasi terlalu lama. Coba berada di area yang lebih terbuka.',
        };
        showStatus('danger', messages[error.code] ?? 'Lokasi tidak dapat diambil.');
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Coba Ambil Lokasi Lagi';
      },
      { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
    );
  });
});
</script>
@endpush
