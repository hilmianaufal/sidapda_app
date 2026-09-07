@extends('layouts.app')

@section('title', $activity->category === 'diniyah' ? 'Edit Kegiatan MADAD' : 'Edit Kegiatan')
@section('mobile_title', $activity->category === 'diniyah' ? 'Edit MADAD' : 'Edit Kegiatan')

@section('content')

<x-ui.page-header
  :title="$activity->category === 'diniyah' ? 'Edit Kegiatan MADAD' : 'Edit Kegiatan'"
  subtitle="{{ $activity->name }}"
  icon="bi-pencil-square"
>
  <x-slot:actions>
    <x-ui.button :href="route('activities.index', array_filter(['category' => $returnCategory ?? null]))" variant="secondary">
      Kembali
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<form method="POST" action="{{ route('activities.update', $activity) }}">
  @csrf
  @method('PUT')

  <div class="grid gap-6 lg:grid-cols-12">

    <div class="lg:col-span-8">
      <x-ui.card>
        <div class="mb-6">
          <div class="text-lg font-black text-slate-900">
            Informasi Kegiatan
          </div>
          <div class="mt-1 text-sm font-medium text-slate-500">
            Perbarui jadwal dan pengaturan kegiatan.
          </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">

          <div class="md:col-span-2">
            <x-ui.form-group label="Nama Kegiatan" required>
              <x-ui.input
                name="name"
                value="{{ old('name', $activity->name) }}"
                placeholder="Contoh: Muhadhoroh, Roan, Kajian Malam" />
            </x-ui.form-group>
          </div>
            <x-ui.form-group label="Kategori Kegiatan">
            <x-ui.select name="category">
                @if(auth()->user()->canAccessInstitution('ponpes'))
                  <option value="umum" @selected(old('category', $activity->category ?? 'umum') === 'umum')>
                  Kegiatan Umum
                  </option>
                @endif

                @if(auth()->user()->canAccessInstitution('madad'))
                  <option value="diniyah" @selected(old('category', $activity->category ?? 'umum') === 'diniyah')>
                  Kegiatan Diniyah
                  </option>
                @endif
            </x-ui.select>
            </x-ui.form-group>
          <x-ui.form-group label="Tipe Kegiatan" required>
            <x-ui.select name="type" id="type">
              <option value="routine" @selected(old('type', $activity->type) === 'routine')>
                Rutin
              </option>
              <option value="manual" @selected(old('type', $activity->type) === 'manual')>
                Manual / Event
              </option>
            </x-ui.select>
          </x-ui.form-group>

          <x-ui.form-group label="Toleransi Telat" required>
            <x-ui.input
              type="number"
              name="late_minutes"
              min="0"
              max="180"
              value="{{ old('late_minutes', $activity->late_minutes) }}"
              placeholder="Menit" />
          </x-ui.form-group>

          <x-ui.form-group label="Jam Mulai" required>
            <x-ui.input
              type="time"
              name="start_time"
              value="{{ old('start_time', substr($activity->start_time, 0, 5)) }}" />
          </x-ui.form-group>

          <x-ui.form-group label="Jam Selesai" required>
            <x-ui.input
              type="time"
              name="end_time"
              value="{{ old('end_time', substr($activity->end_time, 0, 5)) }}" />
          </x-ui.form-group>

          {{-- Hari Rutin --}}
          <div class="md:col-span-2" id="daysBox">
            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
              Hari Rutin
            </label>

            @php
              $selectedDays = old('days', $activity->days ?? []);
              $days = [
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                0 => 'Ahad',
              ];
            @endphp

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
              @foreach($days as $num => $label)
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-600 transition has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-700">
                  <input
                    type="checkbox"
                    name="days[]"
                    value="{{ $num }}"
                    class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                    @checked(in_array($num, $selectedDays ?? []))>
                  {{ $label }}
                </label>
              @endforeach
            </div>
          </div>

          {{-- Tanggal Event --}}
          <div class="md:col-span-2" id="eventDateBox">
            <x-ui.form-group label="Tanggal Event">
              <x-ui.input
                type="date"
                name="event_date"
                value="{{ old('event_date', optional($activity->event_date)->format('Y-m-d')) }}" />
            </x-ui.form-group>
          </div>

        </div>
      </x-ui.card>
    </div>

    <div class="lg:col-span-4">
      <div class="space-y-6">

        <x-ui.card>
          <div class="mb-4">
            <div class="text-lg font-black text-slate-900">
              Status
            </div>
            <div class="text-sm text-slate-500">
              Aktifkan agar kegiatan bisa dipakai untuk scan.
            </div>
          </div>

          <label class="flex cursor-pointer items-center justify-between rounded-2xl bg-emerald-50 p-4">
            <div>
              <div class="text-sm font-black text-emerald-900">
                Aktif
              </div>
              <div class="text-xs font-semibold text-emerald-600">
                Kegiatan muncul di sistem scan
              </div>
            </div>

            <input
              type="checkbox"
              name="is_active"
              value="1"
              class="h-5 w-5 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
              @checked(old('is_active', $activity->is_active))>
          </label>
        </x-ui.card>

        <x-ui.card>
          <div class="space-y-3">
            <x-ui.button type="submit" class="w-full justify-center">
              <i class="bi bi-check-circle"></i>
              Simpan Perubahan
            </x-ui.button>

            <x-ui.button :href="route('activities.index')" variant="secondary" class="w-full justify-center">
              Batal
            </x-ui.button>
          </div>
        </x-ui.card>

      </div>
    </div>

  </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const type = document.getElementById('type');
  const daysBox = document.getElementById('daysBox');
  const eventDateBox = document.getElementById('eventDateBox');

  function toggleFields() {
    if (type.value === 'routine') {
      daysBox.classList.remove('hidden');
      eventDateBox.classList.add('hidden');
    } else {
      daysBox.classList.add('hidden');
      eventDateBox.classList.remove('hidden');
    }
  }

  type?.addEventListener('change', toggleFields);
  toggleFields();
});
</script>
@endpush
