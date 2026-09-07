@php
  $isMadad = $category === 'diniyah';
  $pageTitle = $isMadad ? 'Izin Kegiatan MADAD' : 'Izin Kegiatan Pondok';
  $returnRoute = $isMadad ? 'activities.excuses.madad' : 'activities.excuses.pondok';
@endphp

@extends('layouts.app')

@section('title', $pageTitle)
@section('mobile_title', $pageTitle)

@section('content')

<x-ui.page-header
  :title="$pageTitle"
  :subtitle="$isMadad ? 'Catat izin, sakit, dan pulang untuk kegiatan MADAD' : 'Catat izin, sakit, dan pulang untuk kegiatan Pondok'"
  icon="bi-clipboard-check"
>
  <x-slot:actions>
    <x-ui.button :href="route('activities.scan', ['category' => $category])" variant="secondary">
      <i class="bi bi-qr-code"></i>
      Scan
    </x-ui.button>

    @if($selectedActivity)
      <x-ui.button :href="route('activities.recap.export.excel', array_filter(array_merge(request()->query(), ['activity_id' => $selectedActivity->id, 'category' => $category])))" variant="secondary">
        <i class="bi bi-file-earmark-excel"></i>
        Excel
      </x-ui.button>
    @endif
  </x-slot:actions>
</x-ui.page-header>

@if(session('success'))
  <div class="mb-6 rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-black text-emerald-700">
    <i class="bi bi-check-circle"></i>
    {{ session('success') }}
  </div>
@endif

@if(session('error'))
  <div class="mb-6 rounded-[1.5rem] border border-red-200 bg-red-50 px-5 py-4 text-sm font-black text-red-700">
    <i class="bi bi-x-circle"></i>
    {{ session('error') }}
  </div>
@endif

<div class="mb-6 rounded-[1.5rem] border border-blue-200 bg-blue-50 px-5 py-4 text-sm font-semibold text-blue-700">
  <div class="font-black"><i class="bi bi-info-circle"></i> Cara mencatat izin</div>
  <div class="mt-1">Pilih tanggal dan kegiatan, cari siswa pada bagian <b>Alpa / Belum Absen</b>, lalu tekan Izin, Sakit, atau Pulang.</div>
</div>

<x-ui.card class="mb-6">
  <form method="GET">
    <input type="hidden" name="category" value="{{ $category }}">
    <div class="grid gap-4 {{ $isMadad ? 'lg:grid-cols-6' : 'lg:grid-cols-5' }}">

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Tanggal
        </label>
        <x-ui.input type="date" name="date" value="{{ $date }}" />
      </div>

      @if($isMadad)
        <div>
          <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
            Jenjang MADAD
          </label>
          <x-ui.select name="level">
            <option value="">Semua</option>
            @foreach($levelList as $madadLevel)
              <option value="{{ $madadLevel }}" @selected($level === $madadLevel)>{{ $madadLevel }}</option>
            @endforeach
          </x-ui.select>
        </div>
      @endif

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Kegiatan
        </label>
        <x-ui.select name="activity_id">
          @foreach($activities as $activity)
            <option value="{{ $activity->id }}" @selected($selectedActivity && $selectedActivity->id === $activity->id)>
              {{ $activity->name }}
            </option>
          @endforeach
        </x-ui.select>
      </div>

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          {{ $isMadad ? 'Kelas MADAD' : 'Jenjang' }}
        </label>
        <x-ui.select name="kelas">
          <option value="">Semua</option>
          @foreach($kelasList as $k)
            <option value="{{ $k }}" @selected($kelas === $k)>
              {{ $k }}
            </option>
          @endforeach
        </x-ui.select>
      </div>

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Kamar
        </label>
        <x-ui.select name="kamar">
          <option value="">Semua</option>
          @foreach($kamarList as $km)
            <option value="{{ $km }}" @selected($kamar === $km)>
              {{ $km }}
            </option>
          @endforeach
        </x-ui.select>
      </div>

      <div class="flex items-end gap-2">
        <x-ui.button type="submit" class="flex-1 justify-center">
          <i class="bi bi-search"></i>
          Filter
        </x-ui.button>

        <x-ui.button :href="route($returnRoute)" variant="secondary">
          Reset
        </x-ui.button>
      </div>

    </div>
  </form>
</x-ui.card>

<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-6">
  <x-ui.stat-card label="Total" :value="$totalStudents" icon="bi-people" tone="blue" />
  <x-ui.stat-card label="Hadir" :value="$hadirCount" icon="bi-check-circle" tone="emerald" />
  <x-ui.stat-card label="Telat" :value="$terlambatCount" icon="bi-clock" tone="amber" />
  <x-ui.stat-card label="Izin" :value="$izinCount" icon="bi-envelope-check" tone="blue" />
  <x-ui.stat-card label="Sakit" :value="$sakitCount" icon="bi-heart-pulse" tone="red" />
  <x-ui.stat-card label="Alpa" :value="$belumCount" icon="bi-x-circle" tone="red" />
</div>
<div class="mb-6">
  <x-ui.card>
    <div class="mb-5 flex items-center justify-between gap-3">
      <div>
        <div class="text-lg font-black text-slate-900">
          Diagram Statistik Kegiatan
        </div>
        <div class="text-sm font-medium text-slate-500">
          Komposisi status {{ $selectedActivity?->name ?? '-' }}
        </div>
      </div>

      <button
        type="button"
        id="downloadActivityChart"
        class="rounded-2xl bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700 hover:bg-emerald-100">
        <i class="bi bi-download"></i>
        Download
      </button>
    </div>

    <div class="mx-auto flex justify-center">
      <div class="h-[220px] w-[220px]">
        <canvas id="activityRecapChart"></canvas>
      </div>
    </div>
  </x-ui.card>
</div>
<div class="grid gap-6 lg:grid-cols-12">

  <div class="lg:col-span-8">
    <x-ui.card padding="p-0">

      <div class="border-b border-slate-100 p-5">
        <div class="text-lg font-black text-slate-900">
          Sudah Absen
        </div>

        <div class="text-sm font-medium text-slate-500">
          {{ $selectedActivity?->name ?? '-' }} • {{ $date }} • {{ $attendances->count() }} data
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full min-w-[760px]">
          <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-400">
            <tr>
              <th class="px-6 py-4">Santri</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4">Jam</th>
              <th class="px-6 py-4">{{ $isMadad ? 'Jenjang/Kelas MADAD' : 'Jenjang' }}/Kamar</th>
              <th class="px-6 py-4 text-right">Aksi</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            @forelse($attendances as $attendance)
              <tr class="transition hover:bg-emerald-50/40">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-4">
                    <img
                      src="{{ $attendance->student->photoUrl() }}"
                      alt="{{ $attendance->student->name }}"
                      class="h-14 w-14 rounded-2xl object-cover ring-2 ring-emerald-100">

                    <div>
                      <div class="font-black text-slate-900">
                        {{ $attendance->student->name }}
                      </div>

                      <div class="text-sm font-semibold text-slate-500">
                        {{ $attendance->student->nis }}
                      </div>
                    </div>
                  </div>
                </td>

                <td class="px-6 py-4">
                  @if($attendance->status === 'hadir')
                    <x-ui.badge tone="emerald">Hadir</x-ui.badge>
                  @elseif($attendance->status === 'terlambat')
                    <x-ui.badge tone="amber">Telat</x-ui.badge>
                  @elseif($attendance->status === 'izin')
                    <x-ui.badge tone="blue">Izin</x-ui.badge>
                  @elseif($attendance->status === 'sakit')
                    <x-ui.badge tone="red">Sakit</x-ui.badge>
                  @elseif($attendance->status === 'pulang')
                    <x-ui.badge tone="slate">Pulang</x-ui.badge>
                  @else
                    <x-ui.badge tone="slate">{{ ucfirst($attendance->status) }}</x-ui.badge>
                  @endif
                </td>

                <td class="px-6 py-4 font-bold text-slate-600">
                  {{ optional($attendance->scanned_at)->format('H:i') ?? '-' }}
                </td>

                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-2">
                    <x-ui.badge tone="blue">
                      {{ $isMadad ? (($attendance->student->institution_level ?: 'Belum ada jenjang').' / Kelas '.($attendance->student->institution_class ?: '-')) : ($attendance->student->kelas ?? '-') }}
                    </x-ui.badge>

                    <x-ui.badge tone="emerald">
                      {{ $attendance->student->kamar ?? '-' }}
                    </x-ui.badge>
                  </div>
                </td>

                <td class="px-6 py-4 text-right">
                  @if(in_array($attendance->status, ['izin', 'sakit', 'pulang']))
                    <form
                      method="POST"
                      action="{{ route('activities.recap.cancel-status') }}"
                      onsubmit="return confirm('Batalkan status manual ini?')">
                      @csrf

                      <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

                      <button
                        type="submit"
                        class="rounded-xl bg-red-50 px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-100">
                        Batalkan
                      </button>
                    </form>
                  @else
                    <span class="text-xs font-bold text-slate-300">-</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="p-10">
                  <x-ui.empty-state
                    title="Belum ada absensi"
                    subtitle="Belum ada santri yang scan kegiatan."
                    icon="bi-clipboard-x" />
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

    </x-ui.card>
  </div>

  <div class="lg:col-span-4">
    <x-ui.card padding="p-0">

      <div class="border-b border-slate-100 p-5">
        <div class="text-lg font-black text-slate-900">
          Alpa / Belum Absen
        </div>

        <div class="text-sm font-medium text-slate-500">
          {{ $absentStudents->count() }} santri
        </div>
      </div>
          <div class="mt-4">
            <input
              type="text"
              id="searchActivityAbsentStudent"
              placeholder="Cari santri belum absen..."
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
          </div>
      <div class="max-h-[720px] overflow-y-auto">
        @forelse($absentStudents as $student)
            <div
            class="activity-absent-student-item border-b border-slate-100 p-4"
            data-search="{{ strtolower($student->name.' '.$student->nis.' '.($isMadad ? $student->institution_level.' '.$student->institution_class : $student->kelas).' '.$student->kamar) }}">
            <div class="flex items-start gap-4">
              <img
                src="{{ $student->photoUrl() }}"
                alt="{{ $student->name }}"
                class="h-14 w-14 rounded-2xl object-cover ring-2 ring-red-100">

              <div class="min-w-0 flex-1">
                <div class="truncate font-black text-slate-900">
                  {{ $student->name }}
                </div>

                <div class="text-sm font-semibold text-slate-500">
                  {{ $student->nis }}
                </div>

                <div class="mt-2 flex flex-wrap gap-2">
                  <x-ui.badge tone="blue">
                    {{ $isMadad ? (($student->institution_level ?: 'Belum ada jenjang').' / Kelas '.($student->institution_class ?: '-')) : ($student->kelas ?? '-') }}
                  </x-ui.badge>

                  <x-ui.badge tone="emerald">
                    {{ $student->kamar ?? '-' }}
                  </x-ui.badge>
                </div>

                <div class="mt-4 overflow-x-auto scrollbar-hide">
                    <div class="flex min-w-max gap-2 pb-1">

                        {{-- Izin --}}
                        <form method="POST" action="{{ route('activities.recap.mark-status') }}">
                            @csrf
                            <input type="hidden" name="activity_id" value="{{ $selectedActivity?->id }}">
                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                            <input type="hidden" name="date" value="{{ $date }}">
                            <input type="hidden" name="status" value="izin">

                            <button
                                type="submit"
                                class="w-28 rounded-2xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-black text-blue-600 transition hover:bg-blue-100">
                                Izin
                            </button>
                        </form>

                        {{-- Sakit --}}
                        <form method="POST" action="{{ route('activities.recap.mark-status') }}">
                            @csrf
                            <input type="hidden" name="activity_id" value="{{ $selectedActivity?->id }}">
                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                            <input type="hidden" name="date" value="{{ $date }}">
                            <input type="hidden" name="status" value="sakit">

                            <button
                                type="submit"
                                class="w-28 rounded-2xl border border-amber-100 bg-amber-50 px-3 py-2 text-xs font-black text-amber-600 transition hover:bg-amber-100">
                                Sakit
                            </button>
                        </form>

                        {{-- Pulang --}}
                        <form method="POST" action="{{ route('activities.recap.mark-status') }}">
                            @csrf
                            <input type="hidden" name="activity_id" value="{{ $selectedActivity?->id }}">
                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                            <input type="hidden" name="date" value="{{ $date }}">
                            <input type="hidden" name="status" value="pulang">

                            <button
                                type="submit"
                                class="w-28 rounded-2xl border border-slate-200 bg-slate-100 px-3 py-2 text-xs font-black text-slate-600 transition hover:bg-slate-200">
                                Pulang
                            </button>
                        </form>

                    </div>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="p-8">
            <x-ui.empty-state
              title="Semua sudah terdata"
              subtitle="Tidak ada santri yang alpa/belum absen."
              icon="bi-check-circle" />
          </div>
        @endforelse
      </div>

    </x-ui.card>
  </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const chartEl = document.getElementById('activityRecapChart');
  const downloadBtn = document.getElementById('downloadActivityChart');

  if (!chartEl || typeof Chart === 'undefined') return;

  const chart = new Chart(chartEl, {
    type: 'doughnut',
    data: {
      labels: ['Hadir', 'Telat', 'Izin', 'Sakit', 'Alpa'],
      datasets: [{
        data: [
          {{ (int) $hadirCount }},
          {{ (int) $terlambatCount }},
          {{ (int) $izinCount }},
          {{ (int) $sakitCount }},
          {{ (int) $belumCount }}
        ],
        backgroundColor: [
          '#10b981',
          '#f59e0b',
          '#3b82f6',
          '#ef4444',
          '#dc2626'
        ],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'right',
          labels: {
            usePointStyle: true,
            boxWidth: 8,
            font: {
              weight: 'bold'
            }
          }
        }
      }
    }
  });

  downloadBtn?.addEventListener('click', () => {
    const link = document.createElement('a');
    link.download = 'diagram-rekap-kegiatan.png';
    link.href = chart.toBase64Image('image/png', 1);
    link.click();
  });
});


const searchActivityAbsentInput = document.getElementById('searchActivityAbsentStudent');
const activityAbsentItems = document.querySelectorAll('.activity-absent-student-item');

searchActivityAbsentInput?.addEventListener('input', () => {
  const keyword = searchActivityAbsentInput.value.toLowerCase().trim();

  activityAbsentItems.forEach((item) => {
    const text = item.dataset.search || '';
    item.style.display = text.includes(keyword) ? '' : 'none';
  });
});
</script>
@endpush
