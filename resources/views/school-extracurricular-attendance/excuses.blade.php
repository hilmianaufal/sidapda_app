@extends('layouts.app')

@section('title', 'Izin & Sakit Ekstrakurikuler')
@section('mobile_title', 'Izin Ekstra')

@section('content')
<x-ui.page-header
  title="Izin & Sakit Ekstrakurikuler Sekolah Pagi"
  :subtitle="$institution->name.' • Tahun ajaran '.$academicYear"
  icon="bi-file-earmark-medical"
>
  <x-slot:actions>
    <x-ui.button :href="route('school-extracurricular-attendance.reports.index', ['institution' => $institution, 'extracurricular_id' => $selectedExtracurricular?->id, 'date' => $date])" variant="secondary">
      <i class="bi bi-clipboard2-check"></i>
      Rekap
    </x-ui.button>
    <x-ui.button :href="route('school-extracurricular-attendance.index', ['institution' => $institution, 'extracurricular_id' => $selectedExtracurricular?->id])" variant="secondary">
      <i class="bi bi-qr-code-scan"></i>
      Kembali ke Scan
    </x-ui.button>
    <x-ui.button :href="route('school-extracurriculars.index', $institution)" variant="secondary">
      <i class="bi bi-trophy"></i>
      Master Data
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

@if(session('success'))
  <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-black text-emerald-700">
    <i class="bi bi-check-circle"></i>
    {{ session('success') }}
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

@if(!$selectedExtracurricular)
  <div class="rounded-[2rem] border border-amber-200 bg-amber-50 p-8 text-center">
    <i class="bi bi-trophy text-5xl text-amber-500"></i>
    <div class="mt-4 text-xl font-black text-amber-950">Belum ada ekstrakurikuler aktif</div>
    <div class="mt-2 text-sm font-semibold text-amber-700">Tambahkan dan aktifkan jadwal ekstrakurikuler terlebih dahulu.</div>
    <a href="{{ route('school-extracurriculars.index', $institution) }}" class="mt-5 inline-flex rounded-2xl bg-amber-500 px-5 py-3 text-sm font-black text-white">
      Buka Master Data
    </a>
  </div>
@else
  <x-ui.card class="mb-6">
    <form method="GET" action="{{ route('school-extracurricular-attendance.excuses.index', $institution) }}" class="grid gap-4 md:grid-cols-12 md:items-end">
      <div class="md:col-span-6">
        <label for="extracurricular_id" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Ekstrakurikuler</label>
        <select id="extracurricular_id" name="extracurricular_id" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          @foreach($extracurriculars as $extracurricular)
            <option value="{{ $extracurricular->id }}" @selected($selectedExtracurricular->id === $extracurricular->id)>
              {{ $extracurricular->name }} • {{ $extracurricular->dayLabel() }} • {{ $extracurricular->levelLabel() }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="md:col-span-3">
        <label for="picker_date" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Tanggal</label>
        <input id="picker_date" name="date" type="date" value="{{ $date }}" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
      </div>
      <div class="md:col-span-3">
        <button type="submit" class="w-full rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white">
          <i class="bi bi-check-circle"></i>
          Gunakan Data
        </button>
      </div>
    </form>
  </x-ui.card>

  <div class="mb-6 rounded-[1.75rem] border {{ $scheduledDate ? 'border-indigo-200 bg-indigo-50' : 'border-amber-200 bg-amber-50' }} p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="text-xs font-black uppercase tracking-wide {{ $scheduledDate ? 'text-indigo-400' : 'text-amber-500' }}">Kegiatan Dipilih</div>
        <div class="mt-1 text-xl font-black {{ $scheduledDate ? 'text-indigo-950' : 'text-amber-950' }}">{{ $selectedExtracurricular->name }}</div>
        <div class="mt-1 text-sm font-semibold {{ $scheduledDate ? 'text-indigo-700' : 'text-amber-700' }}">
          {{ $selectedExtracurricular->dayLabel() }} • {{ $selectedExtracurricular->startTimeLabel() }}–{{ $selectedExtracurricular->endTimeLabel() }} • {{ $selectedExtracurricular->levelLabel() }}
        </div>
      </div>
      <div class="rounded-2xl bg-white/80 px-5 py-3 text-sm font-bold text-slate-700">Tanggal {{ $date }}</div>
    </div>
    @if(!$scheduledDate)
      <div class="mt-4 rounded-2xl border border-amber-200 bg-white/70 px-4 py-3 text-sm font-bold text-amber-700">
        <i class="bi bi-exclamation-triangle"></i>
        Tanggal ini bukan hari {{ $selectedExtracurricular->dayLabel() }}. Status hanya dapat disimpan sesuai jadwal kegiatan.
      </div>
    @endif
  </div>

  <div class="mb-6 grid grid-cols-3 gap-3">
    <x-ui.stat-card label="Izin" :value="$totals['izin']" icon="bi-file-earmark-text" tone="blue" />
    <x-ui.stat-card label="Sakit" :value="$totals['sakit']" icon="bi-bandaid" tone="red" />
    <x-ui.stat-card label="Lainnya" :value="$totals['lainnya']" icon="bi-three-dots" tone="slate" />
  </div>

  <div class="grid gap-6 lg:grid-cols-12">
    <div class="lg:col-span-5">
      <x-ui.card>
        <div class="mb-5">
          <div class="text-lg font-black text-slate-900">Catat Izin/Sakit</div>
          <div class="text-sm font-medium text-slate-500">Satu status per siswa, kegiatan, dan tanggal.</div>
        </div>

        @if($studentOptions->isEmpty())
          <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-700">
            Belum ada siswa aktif jenjang {{ $selectedExtracurricular->levelLabel() }} pada tahun ajaran {{ $academicYear }}.
          </div>
        @else
          <form method="POST" action="{{ route('school-extracurricular-attendance.excuses.store', $institution) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="school_extracurricular_id" value="{{ $selectedExtracurricular->id }}">

            <div>
              <label for="attendance_date" class="mb-2 block text-sm font-black text-slate-700">Tanggal</label>
              <input id="attendance_date" name="attendance_date" type="date" value="{{ old('attendance_date', $date) }}" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
              <div class="mt-2 text-xs font-semibold text-slate-400">Harus sesuai jadwal hari {{ $selectedExtracurricular->dayLabel() }}.</div>
            </div>

            <div>
              <label for="student_id" class="mb-2 block text-sm font-black text-slate-700">Siswa</label>
              <select id="student_id" name="student_id" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
                <option value="">Pilih siswa</option>
                @foreach($studentOptions as $enrollment)
                  <option value="{{ $enrollment->student_id }}" @selected((string) old('student_id') === (string) $enrollment->student_id)>
                    {{ $enrollment->student?->name }} — {{ $enrollment->student?->nis }} — {{ $enrollment->level ?: '-' }}/{{ $enrollment->class_name ?: '-' }}
                  </option>
                @endforeach
              </select>
            </div>

            <div>
              <label for="status" class="mb-2 block text-sm font-black text-slate-700">Status</label>
              <select id="status" name="status" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
                <option value="izin" @selected(old('status') === 'izin')>Izin</option>
                <option value="sakit" @selected(old('status') === 'sakit')>Sakit</option>
                <option value="lainnya" @selected(old('status') === 'lainnya')>Lainnya</option>
              </select>
            </div>

            <div>
              <label for="notes" class="mb-2 block text-sm font-black text-slate-700">Keterangan</label>
              <textarea id="notes" name="notes" rows="3" maxlength="1000" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-emerald-100" placeholder="Contoh: Sakit atau keperluan keluarga">{{ old('notes') }}</textarea>
            </div>

            <div>
              <label for="attachment" class="mb-2 block text-sm font-black text-slate-700">Lampiran Surat</label>
              <input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600">
              <div class="mt-2 text-xs font-semibold text-slate-400">Wajib untuk izin/sakit. PDF, JPG, JPEG, atau PNG maksimal 5 MB.</div>
            </div>

            <button type="submit" class="w-full rounded-2xl bg-gradient-to-r from-emerald-600 to-lime-500 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-300/40">
              <i class="bi bi-check-circle"></i>
              Simpan Status
            </button>
          </form>
        @endif
      </x-ui.card>
    </div>

    <div class="lg:col-span-7">
      <x-ui.card>
        <div class="mb-4">
          <div class="text-lg font-black text-slate-900">Filter Data</div>
          <div class="text-sm font-medium text-slate-500">Cari status pada {{ $selectedExtracurricular->name }}.</div>
        </div>

        <form method="GET" action="{{ route('school-extracurricular-attendance.excuses.index', $institution) }}" class="grid gap-3 sm:grid-cols-2">
          <input type="hidden" name="extracurricular_id" value="{{ $selectedExtracurricular->id }}">
          <input type="date" name="date" value="{{ $date }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">

          <select name="status" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
            <option value="">Semua status</option>
            <option value="izin" @selected($status === 'izin')>Izin</option>
            <option value="sakit" @selected($status === 'sakit')>Sakit</option>
            <option value="lainnya" @selected($status === 'lainnya')>Lainnya</option>
          </select>

          <select name="class_name" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
            <option value="">Semua kelas</option>
            @foreach($classOptions as $class)
              <option value="{{ $class }}" @selected($className === $class)>{{ $class }}</option>
            @endforeach
          </select>

          <input type="search" name="q" value="{{ $q }}" placeholder="Nama atau NIS" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">

          <div class="flex gap-3 sm:col-span-2">
            <button type="submit" class="flex-1 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">
              <i class="bi bi-funnel"></i>
              Terapkan Filter
            </button>
            <a href="{{ route('school-extracurricular-attendance.excuses.index', ['institution' => $institution, 'extracurricular_id' => $selectedExtracurricular->id]) }}" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-700">Reset</a>
          </div>
        </form>
      </x-ui.card>
    </div>
  </div>

  <div class="mt-6">
    <x-ui.card>
      <div class="mb-4">
        <div class="text-lg font-black text-slate-900">Daftar Izin & Sakit {{ $selectedExtracurricular->name }}</div>
        <div class="text-sm font-medium text-slate-500">Tanggal {{ $date }} • Tahun ajaran {{ $academicYear }}</div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-xs font-black uppercase tracking-wide text-slate-400">
              <th class="px-3 py-3">Siswa</th>
              <th class="px-3 py-3">Jenjang/Kelas</th>
              <th class="px-3 py-3">Status</th>
              <th class="px-3 py-3">Keterangan</th>
              <th class="px-3 py-3">Lampiran</th>
              <th class="px-3 py-3">Penginput</th>
              <th class="px-3 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($excuses as $excuse)
              @php
                $statusClass = match($excuse->status) {
                  'izin' => 'bg-blue-100 text-blue-700',
                  'sakit' => 'bg-red-100 text-red-700',
                  default => 'bg-slate-100 text-slate-600',
                };
              @endphp
              <tr class="border-b border-slate-100 align-top">
                <td class="px-3 py-3">
                  <div class="font-black text-slate-800">{{ $excuse->student?->name ?? 'Siswa dihapus' }}</div>
                  <div class="text-xs font-semibold text-slate-400">NIS {{ $excuse->student?->nis ?? $excuse->nis_snapshot }}</div>
                </td>
                <td class="px-3 py-3 font-bold text-slate-600">
                  {{ match($excuse->level_snapshot) { 'mts' => 'MTs', 'ma' => 'MA', 'mts_ma' => 'MTs & MA', default => '-' } }} / {{ $excuse->class_name_snapshot ?: '-' }}
                </td>
                <td class="px-3 py-3">
                  <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClass }}">{{ strtoupper($excuse->status) }}</span>
                </td>
                <td class="max-w-xs px-3 py-3 font-semibold text-slate-600">{{ $excuse->notes ?: '-' }}</td>
                <td class="px-3 py-3">
                  @if($excuse->attachment_path)
                    <a href="{{ route('school-extracurricular-attendance.excuses.download', [$institution, $excuse]) }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700">
                      <i class="bi bi-download"></i>
                      Unduh
                    </a>
                    <div class="mt-1 max-w-xs truncate text-xs text-slate-400" title="{{ $excuse->attachment_original_name }}">{{ $excuse->attachment_original_name }}</div>
                  @else
                    <span class="text-xs font-bold text-slate-400">Tidak ada</span>
                  @endif
                </td>
                <td class="px-3 py-3">
                  <div class="font-bold text-slate-600">{{ $excuse->recorder?->name ?? 'Pengguna dihapus' }}</div>
                  <div class="text-xs text-slate-400">{{ $excuse->created_at->format('H:i') }}</div>
                </td>
                <td class="px-3 py-3 text-right">
                  <form method="POST" action="{{ route('school-extracurricular-attendance.excuses.destroy', [$institution, $excuse]) }}" onsubmit="return confirm('Hapus status dan lampiran ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-xl bg-red-50 px-3 py-2 text-xs font-black text-red-600">
                      <i class="bi bi-trash"></i>
                      Hapus
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-4 py-10 text-center font-bold text-slate-400">Belum ada data izin/sakit pada filter ini.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($excuses->hasPages())
        <div class="mt-5">{{ $excuses->links() }}</div>
      @endif
    </x-ui.card>
  </div>
@endif
@endsection
