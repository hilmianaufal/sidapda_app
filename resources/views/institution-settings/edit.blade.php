@extends('layouts.app')

@section('title', 'Pengaturan '.$institution->short_name)
@section('mobile_title', 'Pengaturan Lembaga')

@section('content')
<x-ui.page-header
  :title="'Pengaturan '.$institution->short_name"
  subtitle="Profil, identitas, logo, dan WhatsApp Gateway per lembaga"
  icon="bi-building-gear"
>
  <x-slot:actions>
    <x-ui.button :href="route('dashboard.institution', $institution)" variant="secondary">
      <i class="bi bi-arrow-left"></i>
      Dashboard Lembaga
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<form method="POST" action="{{ route('institution-settings.update', $institution) }}" enctype="multipart/form-data" class="space-y-6">
  @csrf
  @method('PUT')

  <x-ui.card>
    <div class="mb-5">
      <div class="text-lg font-black text-slate-900">Identitas Lembaga</div>
      <div class="text-sm font-medium text-slate-500">Informasi ini tampil sebagai identitas masing-masing unit.</div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
      <div>
        <div class="mb-2 text-xs font-black uppercase tracking-wide text-slate-400">Logo Lembaga</div>
        <div class="flex h-36 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
          @if($institution->logoUrl())
            <img src="{{ $institution->logoUrl() }}" alt="Logo {{ $institution->short_name }}" class="h-32 w-full object-contain">
          @else
            <div class="text-center text-slate-400">
              <i class="bi bi-image text-4xl"></i>
              <div class="mt-2 text-sm font-bold">Belum ada logo</div>
            </div>
          @endif
        </div>
        <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" class="mt-3 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
        <div class="mt-2 text-xs font-semibold text-slate-500">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
        @error('logo')<div class="mt-2 text-sm font-bold text-red-500">{{ $message }}</div>@enderror
        @if($institution->logo_path)
          <label class="mt-3 flex items-center gap-2 text-sm font-bold text-red-600">
            <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300">
            Hapus logo saat disimpan
          </label>
        @endif
      </div>

      <div class="grid gap-4 lg:col-span-2 sm:grid-cols-2">
        <x-ui.form-group label="Nama Lengkap" required name="name">
          <input name="name" value="{{ old('name', $institution->name) }}" required class="w-full rounded-2xl border border-slate-200 px-4 py-3">
        </x-ui.form-group>
        <x-ui.form-group label="Nama Singkat" required name="short_name">
          <input name="short_name" value="{{ old('short_name', $institution->short_name) }}" required maxlength="50" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
        </x-ui.form-group>
        <x-ui.form-group label="Pimpinan/Kepala" name="leader_name">
          <input name="leader_name" value="{{ old('leader_name', $institution->leader_name) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
        </x-ui.form-group>
        <x-ui.form-group label="Nomor Telepon" name="phone">
          <input name="phone" value="{{ old('phone', $institution->phone) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
        </x-ui.form-group>
        <x-ui.form-group label="Email" name="email">
          <input type="email" name="email" value="{{ old('email', $institution->email) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
        </x-ui.form-group>
        <x-ui.form-group label="Website" name="website">
          <input type="url" name="website" value="{{ old('website', $institution->website) }}" placeholder="https://..." class="w-full rounded-2xl border border-slate-200 px-4 py-3">
        </x-ui.form-group>
        <div class="sm:col-span-2">
          <x-ui.form-group label="Alamat" name="address">
            <textarea name="address" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3">{{ old('address', $institution->address) }}</textarea>
          </x-ui.form-group>
        </div>
        <div class="sm:col-span-2">
          <x-ui.form-group label="Deskripsi" name="description">
            <textarea name="description" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3">{{ old('description', $institution->description) }}</textarea>
          </x-ui.form-group>
        </div>
      </div>
    </div>
  </x-ui.card>

  <x-ui.card>
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="text-lg font-black text-slate-900">WhatsApp Gateway Fonnte</div>
        <div class="text-sm font-medium text-slate-500">Token, nomor pengirim, dan notifikasi terpisah untuk {{ $institution->short_name }}.</div>
      </div>
      @if($institution->fonnte_device_status)
        <span class="rounded-full px-3 py-1 text-xs font-black {{ $institution->fonnte_device_status === 'connect' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
          {{ strtoupper($institution->fonnte_device_status) }}
        </span>
      @endif
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
      <div class="space-y-4">
        <label class="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
          <input type="hidden" name="fonnte_enabled" value="0">
          <input type="checkbox" name="fonnte_enabled" value="1" @checked(old('fonnte_enabled', $institution->fonnte_enabled)) class="mt-1 rounded border-slate-300">
          <span>
            <span class="block font-black text-slate-800">Aktifkan Fonnte untuk lembaga ini</span>
            <span class="block text-sm font-semibold text-slate-500">Pesan hanya dikirim bila token tersimpan dan nomor WhatsApp orang tua valid.</span>
          </span>
        </label>

        <x-ui.form-group label="Token Perangkat Fonnte" name="fonnte_token">
          <input type="password" name="fonnte_token" autocomplete="new-password" placeholder="{{ $institution->fonnte_token ? 'Token sudah tersimpan — kosongkan bila tidak diganti' : 'Tempel token perangkat Fonnte' }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
        </x-ui.form-group>
        <div class="rounded-2xl bg-amber-50 p-4 text-sm font-semibold text-amber-800">
          Token disimpan terenkripsi dan tidak pernah ditampilkan kembali. Satu lembaga memakai satu token/perangkat sendiri.
        </div>

        <x-ui.form-group label="Penutup Pesan" name="fonnte_message_footer">
          <input name="fonnte_message_footer" value="{{ old('fonnte_message_footer', $institution->fonnte_message_footer) }}" placeholder="Pesan otomatis SIDAPDA, mohon tidak membalas." class="w-full rounded-2xl border border-slate-200 px-4 py-3">
        </x-ui.form-group>
      </div>

      <div>
        <div class="mb-3 text-xs font-black uppercase tracking-wide text-slate-400">Jenis Notifikasi ke Orang Tua</div>
        <div class="space-y-3">
          @foreach([
            'fonnte_notify_attendance' => ['Scan absensi', 'Hadir, terlambat, masuk, pulang, salat, kegiatan, dan ekstrakurikuler.'],
            'fonnte_notify_excuse' => ['Izin dan sakit', 'Pencatatan izin/sakit sekolah, kegiatan Pondok, MADAD, dan ekstra.'],
            'fonnte_notify_boarding' => ['Pulang dan kembali Pondok', 'Pemberitahuan ketika santri keluar atau kembali ke Pondok.'],
          ] as $field => [$label, $help])
            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4">
              <input type="hidden" name="{{ $field }}" value="0">
              <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $institution->{$field} ?? true)) class="mt-1 rounded border-slate-300">
              <span>
                <span class="block font-black text-slate-800">{{ $label }}</span>
                <span class="block text-sm font-semibold text-slate-500">{{ $help }}</span>
              </span>
            </label>
          @endforeach
        </div>

        <div class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
          <div class="font-black text-slate-800">Status terakhir</div>
          <div class="mt-2">Nomor perangkat: <strong>{{ $institution->fonnte_device_number ?: '-' }}</strong></div>
          <div>Diperiksa: <strong>{{ $institution->fonnte_last_tested_at?->format('d-m-Y H:i') ?: '-' }}</strong></div>
        </div>
      </div>
    </div>
  </x-ui.card>

  <div class="flex flex-wrap justify-end gap-3">
    <x-ui.button type="submit">
      <i class="bi bi-save"></i>
      Simpan Pengaturan
    </x-ui.button>
  </div>
</form>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
  <x-ui.card>
    <div class="font-black text-slate-900">Uji Koneksi Fonnte</div>
    <div class="mt-1 text-sm font-semibold text-slate-500">Mengecek token dan status perangkat tanpa mengirim pesan.</div>
    <form method="POST" action="{{ route('institution-settings.fonnte.test', $institution) }}" class="mt-4">
      @csrf
      <x-ui.button type="submit" variant="secondary">
        <i class="bi bi-wifi"></i>
        Cek Koneksi
      </x-ui.button>
    </form>
    <form method="POST" action="{{ route('institution-settings.fonnte.send-test', $institution) }}" class="mt-5 border-t border-slate-100 pt-5">
      @csrf
      <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Nomor Tujuan Tes</label>
      <input name="target" value="{{ old('target') }}" placeholder="081234567890" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
      @error('target')<div class="mt-2 text-sm font-bold text-red-500">{{ $message }}</div>@enderror
      <x-ui.button type="submit" class="mt-3">
        <i class="bi bi-whatsapp"></i>
        Kirim Pesan Tes
      </x-ui.button>
    </form>
  </x-ui.card>

  <x-ui.card class="lg:col-span-2">
    <div class="mb-4">
      <div class="font-black text-slate-900">10 Pengiriman Terakhir</div>
      <div class="text-sm font-semibold text-slate-500">Riwayat berhasil/gagal tanpa menampilkan token.</div>
    </div>
    @if($logs->isEmpty())
      <div class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-500">Belum ada pesan yang diproses.</div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="text-xs uppercase text-slate-400">
            <tr><th class="pb-3">Waktu</th><th class="pb-3">Siswa</th><th class="pb-3">Tujuan</th><th class="pb-3">Status</th></tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($logs as $log)
              <tr>
                <td class="py-3 font-semibold text-slate-600">{{ $log->created_at->format('d-m H:i') }}</td>
                <td class="py-3 font-bold text-slate-800">{{ $log->student?->name ?: '-' }}</td>
                <td class="py-3 font-mono text-xs text-slate-600">{{ substr($log->target, 0, 5) }}****{{ substr($log->target, -3) }}</td>
                <td class="py-3"><span class="rounded-full px-2 py-1 text-xs font-black {{ in_array($log->status, ['accepted', 'sent'], true) ? 'bg-emerald-100 text-emerald-700' : ($log->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ strtoupper($log->status) }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </x-ui.card>
</div>
@endsection
