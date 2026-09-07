@extends('layouts.app')

@section('title','Dashboard')
@section('mobile_title','Dashboard')

@section('content')

@php
  $totalHadir = collect($items)->sum('hadir');
  $totalTelat = collect($items)->sum('telat');
  $totalBelum = collect($items)->sum('belum');

  $labelsSholat = collect($items)->map(fn($it) => $it['prayer']->name)->values();
  $dataHadirPerSholat = collect($items)->map(fn($it) => (int) $it['hadir'])->values();
  $dataTelatPerSholat = collect($items)->map(fn($it) => (int) $it['telat'])->values();
@endphp

<x-ui.page-header
  title="Dashboard Absensi"
  subtitle="Ringkasan absensi hari ini • {{ $today }}"
  icon="bi-speedometer2"
>
  <x-slot:actions>
    @if($hasPonpesAccess)
      <x-ui.button :href="route('scan.index')" variant="secondary">
        <i class="bi bi-qr-code-scan"></i>
        Scan QR
      </x-ui.button>
    @endif
  </x-slot:actions>
</x-ui.page-header>

{{-- Dashboard lembaga --}}
<div class="mb-6">
  <div class="mb-3 flex items-end justify-between gap-3">
    <div>
      <div class="text-lg font-black text-slate-900">Dashboard Lembaga</div>
      <div class="text-sm font-medium text-slate-500">Pilih unit SIDAPDA • {{ $academicYear }}</div>
    </div>
    <x-ui.badge tone="emerald">{{ $institutions->count() }} Unit</x-ui.badge>
  </div>

  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @if($institutions->isEmpty())
      <div class="rounded-[1.6rem] border border-amber-200 bg-amber-50 p-5 text-sm font-bold text-amber-700 sm:col-span-2 xl:col-span-4">
        Akun ini belum diberi akses lembaga. Hubungi admin untuk memilih lembaga pada menu Pengaturan → Users.
      </div>
    @endif
    @foreach($institutions as $institution)
      @php
        $styles = match($institution->code) {
          'mi' => ['box' => 'border-blue-100 bg-blue-50', 'icon' => 'bg-blue-500', 'text' => 'text-blue-700'],
          'sekolah-pagi' => ['box' => 'border-purple-100 bg-purple-50', 'icon' => 'bg-purple-500', 'text' => 'text-purple-700'],
          'madad' => ['box' => 'border-amber-100 bg-amber-50', 'icon' => 'bg-amber-500', 'text' => 'text-amber-700'],
          default => ['box' => 'border-emerald-100 bg-emerald-50', 'icon' => 'bg-emerald-600', 'text' => 'text-emerald-700'],
        };
      @endphp

      <a href="{{ route('dashboard.institution', $institution) }}"
         class="group rounded-[1.6rem] border p-4 transition hover:-translate-y-1 hover:shadow-xl {{ $styles['box'] }}">
        <div class="flex items-start justify-between gap-3">
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl text-white shadow-lg {{ $styles['icon'] }}">
            @if($institution->logoUrl())
              <img src="{{ $institution->logoUrl() }}" alt="Logo {{ $institution->short_name }}" class="h-10 w-10 rounded-xl bg-white object-contain p-1">
            @else
              <i class="bi {{ $institution->icon }}"></i>
            @endif
          </div>
          <i class="bi bi-arrow-up-right text-lg {{ $styles['text'] }}"></i>
        </div>
        <div class="mt-4 font-black text-slate-900">{{ $institution->short_name }}</div>
        <div class="mt-1 min-h-10 text-xs font-semibold text-slate-500">{{ $institution->name }}</div>
        <div class="mt-3 text-xs font-black {{ $styles['text'] }}">
          {{ $institution->active_students_count }} siswa aktif
        </div>
      </a>
    @endforeach
  </div>
</div>

{{-- Menu cepat --}}
<div class="mb-6 grid grid-cols-4 gap-3 lg:grid-cols-8">
  @can('scan_qr')
    @if($hasPonpesAccess)
      <a href="{{ route('scan.index') }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white">
          <i class="bi bi-qr-code-scan"></i>
        </div>
        <div class="mt-2 text-xs font-black text-slate-700">Scan</div>
      </a>
    @endif

    @if($hasPonpesAccess || $hasMadadAccess)
      <a href="{{ route('activities.scan', $hasMadadAccess && !$hasPonpesAccess ? ['category' => 'diniyah'] : []) }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-lime-50 text-xl text-lime-600 group-hover:bg-lime-500 group-hover:text-white">
          <i class="bi bi-qr-code"></i>
        </div>
        <div class="mt-2 text-xs font-black text-slate-700">Kegiatan</div>
      </a>
    @endif
  @endcan

  @can('manage_students')
    @if($institutions->isNotEmpty())
    <a href="{{ route('students.index', $institutions->count() === 1 ? ['institution_id' => $institutions->first()->id] : []) }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-xl text-amber-600 group-hover:bg-amber-500 group-hover:text-white">
        <i class="bi bi-people"></i>
      </div>
      <div class="mt-2 text-xs font-black text-slate-700">Santri</div>
    </a>
    @endif
  @endcan

  @can('view_reports')
    @if($hasPonpesAccess)
    <a href="{{ route('rekap.index') }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-xl text-blue-600 group-hover:bg-blue-500 group-hover:text-white">
        <i class="bi bi-clipboard-data"></i>
      </div>
      <div class="mt-2 text-xs font-black text-slate-700">Rekap</div>
    </a>
    <a href="{{ route('rekap.prayer-summary.daily') }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white">
        <i class="bi bi-list-check"></i>
    </div>
    <div class="mt-2 text-xs font-black text-slate-700">Rekap Salat</div>
    </a>
    <a href="{{ route('rekap.monthly') }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-50 text-xl text-cyan-600 group-hover:bg-cyan-500 group-hover:text-white">
        <i class="bi bi-calendar3"></i>
      </div>
      <div class="mt-2 text-xs font-black text-slate-700">Bulanan</div>
    </a>

    <a href="{{ route('rekap.weekly') }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
  <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-xl text-purple-600 group-hover:bg-purple-500 group-hover:text-white">
    <i class="bi bi-calendar-week"></i>
  </div>
  <div class="mt-2 text-xs font-black text-slate-700">Mingguan</div>
</a>

<a href="{{ route('rekap-kegiatan.daily') }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
  <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-xl text-teal-600 group-hover:bg-teal-500 group-hover:text-white">
    <i class="bi bi-clipboard-check"></i>
  </div>
  <div class="mt-2 text-xs font-black text-slate-700">Rekap Kegiatan</div>
</a>
    @endif
    @if($institutions->isNotEmpty() && auth()->user()->can('manage_students'))
<a href="{{ route('students.import.form', $institutions->count() === 1 ? ['institution_id' => $institutions->first()->id] : []) }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
  <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-green-50 text-xl text-green-600 group-hover:bg-green-500 group-hover:text-white">
    <i class="bi bi-file-earmark-spreadsheet"></i>
  </div>
  <div class="mt-2 text-xs font-black text-slate-700">Import</div>
</a>
    @endif
  @endcan

  @can('manage_prayers')
    @if($hasPonpesAccess)
    <a href="{{ route('prayers.index') }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-600 group-hover:bg-slate-800 group-hover:text-white">
        <i class="bi bi-clock-history"></i>
      </div>
      <div class="mt-2 text-xs font-black text-slate-700">Jadwal</div>
    </a>
    @endif
  @endcan
@can('manage_activities')
  @if($hasPonpesAccess || $hasMadadAccess)
  <a href="{{ route('activities.index', $hasMadadAccess && !$hasPonpesAccess ? ['category' => 'diniyah'] : []) }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white">
      <i class="bi bi-calendar-check"></i>
    </div>
    <div class="mt-2 text-xs font-black text-slate-700">Aktivitas</div>
  </a>
  @endif
@endcan
  @role('admin')
    <a href="{{ route('users.index') }}" class="group rounded-[1.5rem] bg-white p-3 text-center shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:shadow-xl">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-xl text-red-600 group-hover:bg-red-500 group-hover:text-white">
        <i class="bi bi-person-gear"></i>
      </div>
      <div class="mt-2 text-xs font-black text-slate-700">Users</div>
    </a>
  @endrole
</div>

@if($hasPonpesAccess)
{{-- Status sholat aktif --}}
@if($activePrayer)
  <div class="mb-6 rounded-[1.75rem] border border-emerald-200 bg-gradient-to-r from-emerald-50 to-lime-50 p-4 shadow-lg shadow-emerald-100">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-200">
          <i class="bi bi-broadcast"></i>
        </div>
        <div>
          <div class="text-sm font-black text-emerald-950">
            Sholat aktif: {{ $activePrayer->name }}
          </div>
          <div class="text-xs font-semibold text-emerald-700">
            {{ $activePrayer->start_time }} – {{ $activePrayer->end_time }}
            • telat {{ $activePrayer->late_minutes }} menit
          </div>
        </div>
      </div>

      <x-ui.badge tone="emerald">LIVE</x-ui.badge>
    </div>
  </div>
@else
  <div class="mb-6 rounded-[1.75rem] border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-700">
    Tidak ada sholat aktif saat ini.
  </div>
@endif

{{-- Stats --}}
<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
  <x-ui.stat-card label="Total Santri" :value="$totalStudents" icon="bi-people" tone="emerald" />
  <x-ui.stat-card label="Hadir" :value="$totalHadir" icon="bi-check-circle" tone="blue" />
  <x-ui.stat-card label="Terlambat" :value="$totalTelat" icon="bi-clock" tone="amber" />
  <x-ui.stat-card label="Belum" :value="$totalBelum" icon="bi-x-circle" tone="red" />
</div>

{{-- Charts + Jadwal --}}
<div class="grid gap-6 lg:grid-cols-12">

  <x-ui.card class="lg:col-span-5">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <div class="text-lg font-black text-slate-900">Komposisi Hari Ini</div>
        <div class="text-sm font-medium text-slate-500">{{ $today }}</div>
      </div>
      <x-ui.badge tone="emerald">Realtime</x-ui.badge>
    </div>

    <div class="h-72">
      <canvas id="chartDonut"></canvas>
    </div>
  </x-ui.card>

  <x-ui.card class="lg:col-span-7">
    <div class="mb-4">
      <div class="text-lg font-black text-slate-900">Hadir per Sholat</div>
      <div class="text-sm font-medium text-slate-500">
        Total santri aktif: {{ $totalStudents }}
      </div>
    </div>

    <div class="h-72">
      <canvas id="chartBar"></canvas>
    </div>
  </x-ui.card>

</div>

<x-ui.card class="mt-6" padding="p-0">
  <div class="flex items-center justify-between border-b border-slate-100 p-4">
    <div>
      <div class="text-lg font-black text-slate-900">Jadwal Sholat</div>
      <div class="text-sm text-slate-500">Status hari ini</div>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full min-w-[640px] text-left text-sm">
      <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-400">
        <tr>
          <th class="px-4 py-3">Sholat</th>
          <th class="px-4 py-3">Jam</th>
          <th class="px-4 py-3">Telat</th>
          <th class="px-4 py-3">Hadir</th>
          <th class="px-4 py-3">Telat</th>
          <th class="px-4 py-3">Belum</th>
          <th class="px-4 py-3 text-right">Status</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-slate-100">
        @foreach($items as $it)
          @php
            $p = $it['prayer'];
            $st = $it['status'];
          @endphp

          <tr class="hover:bg-emerald-50/40">
            <td class="px-4 py-4 font-black text-slate-800">
              {{ $p->name }}
            </td>
            <td class="px-4 py-4 font-semibold text-slate-500">
              {{ $p->start_time }} – {{ $p->end_time }}
            </td>
            <td class="px-4 py-4 font-semibold text-slate-500">
              {{ $p->late_minutes }} menit
            </td>
            <td class="px-4 py-4 font-black text-emerald-600">
              {{ $it['hadir'] }}
            </td>
            <td class="px-4 py-4 font-black text-amber-600">
              {{ $it['telat'] }}
            </td>
            <td class="px-4 py-4 font-black text-red-600">
              {{ $it['belum'] }}
            </td>
            <td class="px-4 py-4 text-right">
              @if($st === 'live')
                <x-ui.badge tone="emerald">LIVE</x-ui.badge>
              @elseif($st === 'soon')
                <x-ui.badge tone="slate">Soon</x-ui.badge>
              @else
                <x-ui.badge tone="dark">Closed</x-ui.badge>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</x-ui.card>

@endif

@endsection

@push('scripts')
@if($hasPonpesAccess)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  const donutData = {
    hadir: @json((int) $totalHadir),
    telat: @json((int) $totalTelat),
    belum: @json((int) $totalBelum),
  };

  const labelsSholat = @json($labelsSholat);
  const hadirPerSholat = @json($dataHadirPerSholat);
  const telatPerSholat = @json($dataTelatPerSholat);

  new Chart(document.getElementById('chartDonut'), {
    type: 'doughnut',
    data: {
      labels: ['Hadir', 'Telat', 'Belum'],
      datasets: [{
        data: [donutData.hadir, donutData.telat, donutData.belum],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  });

  new Chart(document.getElementById('chartBar'), {
    type: 'bar',
    data: {
      labels: labelsSholat,
      datasets: [
        { label: 'Hadir', data: hadirPerSholat, borderWidth: 0 },
        { label: 'Telat', data: telatPerSholat, borderWidth: 0 },
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      },
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  });
</script>
@endif
@endpush
