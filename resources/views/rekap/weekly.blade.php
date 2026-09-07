@extends('layouts.app')

@section('title','Rekap Mingguan')
@section('mobile_title','Mingguan')

@section('content')

<x-ui.page-header
  title="Rekap Mingguan"
  subtitle="{{ $start->translatedFormat('d M Y') }} - {{ $end->translatedFormat('d M Y') }}"
  icon="bi-calendar-week"
>
  <x-slot:actions>
    <x-ui.button :href="route('rekap.index')" variant="secondary">
      Harian
    </x-ui.button>

    <x-ui.button :href="route('rekap.monthly')" variant="secondary">
      Bulanan
    </x-ui.button>
    <x-ui.button :href="route('rekap.weekly.export.excel', request()->query())" variant="secondary">
    <i class="bi bi-file-earmark-excel"></i>
    Excel
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<x-ui.card class="mb-6">
  <form method="GET">
    <div class="grid gap-4 lg:grid-cols-5">

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Minggu
        </label>
        <x-ui.input
          type="week"
          name="week"
          value="{{ $week }}" />
        <div class="mt-2 text-xs font-bold text-emerald-700">Periode: Sabtu–Jumat</div>
      </div>

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Sholat
        </label>
        <x-ui.select name="prayer_id">
          @foreach($prayers as $prayer)
            <option value="{{ $prayer->id }}" @selected($selectedPrayer?->id === $prayer->id)>
              {{ $prayer->name }}
            </option>
          @endforeach
        </x-ui.select>
      </div>

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Jenjang
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

      <div class="flex items-end">
        <x-ui.button type="submit" class="w-full justify-center">
          <i class="bi bi-search"></i>
          Filter
        </x-ui.button>
      </div>

    </div>
  </form>
</x-ui.card>

<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-7">
  <x-ui.stat-card label="Total" :value="$totalStudents" icon="bi-people" tone="blue" />
  <x-ui.stat-card label="Target" :value="$expected" icon="bi-calendar-check" tone="slate" />
  <x-ui.stat-card label="Hadir" :value="$hadirCount" icon="bi-check-circle" tone="emerald" />
  <x-ui.stat-card label="Telat" :value="$terlambatCount" icon="bi-clock" tone="amber" />
  <x-ui.stat-card label="Udzur" :value="$udzurCount" icon="bi-gender-female" tone="blue" />
  <x-ui.stat-card label="Sakit" :value="$sakitCount" icon="bi-heart-pulse" tone="red" />
  <x-ui.stat-card label="Alpa" :value="$alpaCount" icon="bi-x-circle" tone="red" />
</div>

<x-ui.card padding="p-0">
  <div class="border-b border-slate-100 p-5">
    <div class="text-lg font-black text-slate-900">
      Detail Rekap Mingguan
    </div>

    <div class="text-sm font-medium text-slate-500">
      {{ $selectedPrayer?->name ?? '-' }} • {{ $students->count() }} santri
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full min-w-[860px]">
      <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-400">
        <tr>
          <th class="px-6 py-4">Santri</th>
          <th class="px-6 py-4 text-center">Hadir</th>
          <th class="px-6 py-4 text-center">Telat</th>
          <th class="px-6 py-4 text-center">Udzur</th>
          <th class="px-6 py-4 text-center">Sakit</th>
          <th class="px-6 py-4 text-center">Pulang</th>
          <th class="px-6 py-4 text-center">Alpa</th>
          <th class="px-6 py-4 text-right">Total</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-slate-100">
        @forelse($students as $student)
          @php
            $studentAttendances = $attendances->where('student_id', $student->id);

            $h = $studentAttendances->where('status', 'hadir')->count();
            $t = $studentAttendances->where('status', 'terlambat')->count();
            $u = $studentAttendances->where('status', 'udzur')->count();
            $s = $studentAttendances->where('status', 'sakit')->count();
            $p = $studentAttendances->where('status', 'pulang')->count();

            $recorded = $h + $t + $u + $s + $p;
            $alpa = max(0, 7 - $recorded);
          @endphp

          <tr class="transition hover:bg-emerald-50/40">
            <td class="px-6 py-4">
              <div class="flex items-center gap-4">
                <img
                  src="{{ $student->photoUrl() }}"
                  class="h-14 w-14 rounded-2xl object-cover ring-2 ring-emerald-100"
                  alt="{{ $student->name }}">

                <div>
                  <div class="font-black text-slate-900">
                    {{ $student->name }}
                  </div>

                  <div class="text-sm font-semibold text-slate-500">
                    {{ $student->nis }} • {{ $student->kelas ?? '-' }} • {{ $student->kamar ?? '-' }}
                  </div>
                </div>
              </div>
            </td>

            <td class="px-6 py-4 text-center font-black text-emerald-600">{{ $h }}</td>
            <td class="px-6 py-4 text-center font-black text-amber-600">{{ $t }}</td>
            <td class="px-6 py-4 text-center font-black text-blue-600">{{ $u }}</td>
            <td class="px-6 py-4 text-center font-black text-red-600">{{ $s }}</td>
            <td class="px-6 py-4 text-center font-black text-slate-600">{{ $p }}</td>
            <td class="px-6 py-4 text-center font-black text-red-600">{{ $alpa }}</td>

            <td class="px-6 py-4 text-right font-black text-slate-900">
              {{ $recorded }}/7
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="p-10">
              <x-ui.empty-state
                title="Tidak ada santri"
                subtitle="Belum ada data santri untuk filter ini."
                icon="bi-people" />
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</x-ui.card>

@endsection
