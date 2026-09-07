@extends('layouts.app')

@section('title','Tambah Santri')
@section('mobile_title','Tambah Santri')

@section('content')

<x-ui.page-header
  title="Tambah Santri"
  subtitle="Tambahkan data santri baru"
  icon="bi-person-plus"
/>

<form
  method="POST"
  action="{{ route('students.store') }}"
  enctype="multipart/form-data">

  @csrf

  <div class="grid gap-6 lg:grid-cols-12">

    {{-- Left --}}
    <div class="space-y-6 lg:col-span-8">

      <x-ui.card>

        <div class="mb-6">
          <div class="text-lg font-black text-slate-900">
            Informasi Santri
          </div>

          <div class="mt-1 text-sm font-medium text-slate-500">
            Lengkapi identitas santri.
          </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">

          <x-ui.form-group label="Nama Santri" required>
            <x-ui.input
              name="name"
              :value="old('name')"
              placeholder="Nama lengkap santri" />
          </x-ui.form-group>

          <x-ui.form-group label="NIS" required>
            <x-ui.input
              name="nis"
              :value="old('nis')"
              placeholder="Nomor induk santri" />
          </x-ui.form-group>

          @if(auth()->user()->hasRole('admin') || auth()->user()->canAccessInstitution('ponpes'))
            <x-ui.form-group label="Jenjang Pondok">
              <x-ui.input
                name="kelas"
                :value="old('kelas')"
                placeholder="Contoh: SMP" />
            </x-ui.form-group>

            <x-ui.form-group label="Kamar">
              <x-ui.input
                name="kamar"
                :value="old('kamar')"
                placeholder="Contoh: Al Mukaromah" />
            </x-ui.form-group>
          @endif

            <x-ui.form-group label="Jenis Santri">
            <x-ui.select name="gender">
                <option value="">Pilih jenis santri</option>

                <option value="putra" @selected(old('gender') === 'putra')>
                Putra
                </option>

                <option value="putri" @selected(old('gender') === 'putri')>
                Putri
                </option>
            </x-ui.select>
            </x-ui.form-group>

            @if(auth()->user()->hasRole('admin') || auth()->user()->canAccessInstitution('ponpes'))
              <x-ui.form-group label="Status Tempat Tinggal">
                <x-ui.select name="residency_status">
                  <option value="mukim" @selected(old('residency_status', 'mukim') === 'mukim')>
                    Mukim di Pondok
                  </option>
                  <option value="non_mukim" @selected(old('residency_status') === 'non_mukim')>
                    Tidak Mukim
                  </option>
                </x-ui.select>
              </x-ui.form-group>
            @else
              <input type="hidden" name="residency_status" value="non_mukim">
            @endif

          <x-ui.form-group label="Nomor WhatsApp Ortu">
              <x-ui.input
                name="parent_phone"
                :value="old('parent_phone')"
                placeholder="6281234567890" />
            </x-ui.form-group>

          <div class="md:col-span-2">
            <x-ui.form-group label="Status">
              <x-ui.select name="is_active">
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
              </x-ui.select>
            </x-ui.form-group>
          </div>

        </div>

      </x-ui.card>

      <x-ui.card>
        <div class="mb-5">
          <div class="text-lg font-black text-slate-900">Lembaga Awal</div>
          <div class="mt-1 text-sm font-medium text-slate-500">
            Data baru langsung ditempatkan pada lembaga yang dipilih.
          </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
          <x-ui.form-group label="Lembaga" :required="!auth()->user()->hasRole('admin')">
            <x-ui.select name="institution_id" id="studentInstitutionSelect">
              @if(auth()->user()->hasRole('admin'))
                <option value="">Belum ditentukan</option>
              @endif
              @foreach($institutions as $institution)
                <option
                  value="{{ $institution->id }}"
                  data-code="{{ $institution->code }}"
                  @selected((int) old('institution_id', $selectedInstitutionId) === (int) $institution->id)>
                  {{ $institution->short_name }} — {{ $institution->name }}
                </option>
              @endforeach
            </x-ui.select>
          </x-ui.form-group>

          <x-ui.form-group label="Kelas Lembaga">
            <x-ui.input
              name="institution_class"
              :value="old('institution_class')"
              placeholder="Contoh: 1 / 7A / 10 IPA" />
          </x-ui.form-group>

          <div id="madadLevelField" class="hidden md:col-span-2">
            <x-ui.form-group label="Jenjang MADAD">
              <x-ui.select name="institution_level">
                <option value="">Pilih jenjang MADAD</option>
                @foreach(\App\Models\StudentEnrollment::madadLevels() as $madadLevel)
                  <option value="{{ $madadLevel }}" @selected(old('institution_level') === $madadLevel)>
                    {{ $madadLevel }}
                  </option>
                @endforeach
              </x-ui.select>
            </x-ui.form-group>
          </div>
        </div>
      </x-ui.card>

    </div>

    {{-- Right --}}
    <div class="space-y-6 lg:col-span-4">

      <x-ui.card>

        <div class="mb-5">
          <div class="text-lg font-black text-slate-900">
            Foto Santri
          </div>

          <div class="mt-1 text-sm text-slate-500">
            Upload foto profil santri.
          </div>
        </div>

        <div
          class="rounded-[1.75rem] border-2 border-dashed border-emerald-200 bg-emerald-50/60 p-6 text-center">

          <div
            id="previewBox"
            class="mx-auto flex h-32 w-32 items-center justify-center overflow-hidden rounded-[2rem] bg-white shadow-lg">

            <img
              id="previewImage"
              src="{{ asset('images/default.jpg') }}"
              class="h-full w-full object-cover">
          </div>

          <div class="mt-5">
            <label
              class="inline-flex cursor-pointer items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-lime-500 px-4 py-2 text-sm font-black text-white shadow-lg shadow-emerald-300/40">

              <i class="bi bi-upload"></i>
              Upload Foto

              <input
                type="file"
                name="photo"
                id="photoInput"
                accept="image/*"
                class="hidden">
            </label>
          </div>

        </div>

      </x-ui.card>

      <x-ui.card>

        <div class="space-y-3">

          <x-ui.button
            type="submit"
            class="w-full justify-center">
            <i class="bi bi-check-circle"></i>
            Simpan Santri
          </x-ui.button>

          <x-ui.button
            :href="route('students.index')"
            variant="secondary"
            class="w-full justify-center">
            Kembali
          </x-ui.button>

        </div>

      </x-ui.card>

    </div>

  </div>

</form>

@endsection

@push('scripts')
<script>
  const input = document.getElementById('photoInput');
  const preview = document.getElementById('previewImage');

  input?.addEventListener('change', function(e) {
    const file = e.target.files[0];

    if (!file) return;

    preview.src = URL.createObjectURL(file);
  });

  const institutionSelect = document.getElementById('studentInstitutionSelect');
  const madadLevelField = document.getElementById('madadLevelField');

  function toggleMadadLevel() {
    const selectedOption = institutionSelect?.options[institutionSelect.selectedIndex];
    madadLevelField?.classList.toggle('hidden', selectedOption?.dataset.code !== 'madad');
  }

  institutionSelect?.addEventListener('change', toggleMadadLevel);
  toggleMadadLevel();
</script>
@endpush
