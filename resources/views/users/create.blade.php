@extends('layouts.app')

@section('title','Tambah User')
@section('mobile_title','Tambah User')

@section('content')

<x-ui.page-header
  title="Tambah User"
  subtitle="Buat akun petugas baru"
  icon="bi-person-plus"
>
  <x-slot:actions>
    <x-ui.button :href="route('users.index')" variant="secondary">
      Kembali
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<form
  method="POST"
  action="{{ route('users.store') }}"
  enctype="multipart/form-data">

  @csrf

  <div class="grid gap-6 lg:grid-cols-12">

    {{-- Form --}}
    <div class="space-y-6 lg:col-span-8">

      <x-ui.card>
        <div class="mb-6">
          <div class="text-lg font-black text-slate-900">
            Informasi Akun
          </div>
          <div class="mt-1 text-sm font-medium text-slate-500">
            Lengkapi data identitas dan akses user.
          </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">

          <x-ui.form-group label="Nama" required>
            <x-ui.input
              name="name"
              value="{{ old('name') }}"
              placeholder="Nama lengkap" />
          </x-ui.form-group>

          <x-ui.form-group label="Email" required>
            <x-ui.input
              type="email"
              name="email"
              value="{{ old('email') }}"
              placeholder="email@example.com" />
          </x-ui.form-group>

          <x-ui.form-group label="No HP">
            <x-ui.input
              name="phone"
              value="{{ old('phone') }}"
              placeholder="08xxxx" />
          </x-ui.form-group>

          <x-ui.form-group label="Role" required>
            <x-ui.select name="role" id="userRoleSelect">
              @foreach($roles as $r)
                <option value="{{ $r }}" @selected(old('role') === $r)>
                  {{ $r }}
                </option>
              @endforeach
            </x-ui.select>
          </x-ui.form-group>

          <x-ui.form-group label="Password" required>
            <x-ui.input
              type="password"
              name="password"
              placeholder="Password user" />
          </x-ui.form-group>

          <div class="md:col-span-2">
            <x-ui.form-group label="Catatan">
              <textarea
                name="notes"
                rows="4"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                placeholder="Keterangan user...">{{ old('notes') }}</textarea>
            </x-ui.form-group>
          </div>

        </div>
      </x-ui.card>

      @include('users.partials.institution-access')

    </div>

    {{-- Sidebar --}}
    <div class="space-y-6 lg:col-span-4">

      <x-ui.card>
        <div class="mb-5">
          <div class="text-lg font-black text-slate-900">
            Foto User
          </div>
          <div class="text-sm text-slate-500">
            Upload avatar akun.
          </div>
        </div>

        <div class="rounded-[1.75rem] border-2 border-dashed border-emerald-200 bg-emerald-50/60 p-6 text-center">
          <div class="mx-auto flex h-32 w-32 items-center justify-center overflow-hidden rounded-[2rem] bg-white shadow-lg">
            <img
              id="previewImage"
              src="{{ asset('images/default.jpg') }}"
              class="h-full w-full object-cover">
          </div>

          <div class="mt-5">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-lime-500 px-4 py-2 text-sm font-black text-white shadow-lg shadow-emerald-300/40">
              <i class="bi bi-upload"></i>
              Upload Foto

              <input
                type="file"
                name="avatar"
                id="avatarInput"
                accept="image/*"
                class="hidden">
            </label>
          </div>
        </div>
      </x-ui.card>

      <x-ui.card>
        <div class="mb-4">
          <div class="text-lg font-black text-slate-900">
            Status Akun
          </div>
          <div class="text-sm text-slate-500">
            User aktif dapat login ke aplikasi.
          </div>
        </div>

        <label class="flex cursor-pointer items-center justify-between rounded-2xl bg-emerald-50 p-4">
          <div>
            <div class="text-sm font-black text-emerald-900">
              Akun Aktif
            </div>
            <div class="text-xs font-semibold text-emerald-600">
              Izinkan user login
            </div>
          </div>

          <input
            type="checkbox"
            name="is_active"
            value="1"
            class="h-5 w-5 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
            @checked(old('is_active', true))>
        </label>
      </x-ui.card>

      <x-ui.card>
        <div class="space-y-3">
          <x-ui.button type="submit" class="w-full justify-center">
            <i class="bi bi-check-circle"></i>
            Simpan User
          </x-ui.button>

          <x-ui.button :href="route('users.index')" variant="secondary" class="w-full justify-center">
            Batal
          </x-ui.button>
        </div>
      </x-ui.card>

    </div>

  </div>
</form>

@endsection

@push('scripts')
<script>
document.getElementById('avatarInput')?.addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;

  document.getElementById('previewImage').src = URL.createObjectURL(file);
});

function toggleInstitutionAccess() {
  const isAdmin = document.getElementById('userRoleSelect')?.value === 'admin';
  document.getElementById('adminAccessNotice')?.classList.toggle('hidden', !isAdmin);
  document.getElementById('institutionAccessOptions')?.classList.toggle('opacity-50', isAdmin);
}

document.getElementById('userRoleSelect')?.addEventListener('change', toggleInstitutionAccess);
toggleInstitutionAccess();
</script>
@endpush
