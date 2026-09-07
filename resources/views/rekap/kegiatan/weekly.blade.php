@extends('layouts.app')

@section('title', 'Rekap Kegiatan Mingguan')
@section('mobile_title', 'Kegiatan Mingguan')

@section('content')

<x-ui.page-header
    title="Rekap Kegiatan Mingguan"
    subtitle="{{ $start->translatedFormat('d M Y') }} - {{ $end->translatedFormat('d M Y') }}"
    icon="bi-calendar-week"
/>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

    <div class="flex flex-wrap gap-3">
        <a
            href="{{ route('rekap-kegiatan.daily') }}"
            class="rounded-2xl px-5 py-3 font-black transition
            {{ request()->routeIs('rekap-kegiatan.daily')
                ? 'bg-gradient-to-r from-emerald-600 to-lime-500 text-white shadow-lg shadow-emerald-200'
                : 'border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50' }}"
        >
            Harian
        </a>

        <a
            href="{{ route('rekap-kegiatan.weekly') }}"
            class="rounded-2xl px-5 py-3 font-black transition
            {{ request()->routeIs('rekap-kegiatan.weekly')
                ? 'bg-gradient-to-r from-emerald-600 to-lime-500 text-white shadow-lg shadow-emerald-200'
                : 'border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50' }}"
        >
            Mingguan
        </a>

        <a
            href="{{ route('rekap-kegiatan.monthly') }}"
            class="rounded-2xl px-5 py-3 font-black transition
            {{ request()->routeIs('rekap-kegiatan.monthly')
                ? 'bg-gradient-to-r from-emerald-600 to-lime-500 text-white shadow-lg shadow-emerald-200'
                : 'border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50' }}"
        >
            Bulanan
        </a>
    </div>

    <x-ui.button
        :href="route('rekap-kegiatan.export.excel', ['period' => 'weekly'] + request()->query())"
        variant="secondary"
        class="justify-center"
    >
        <i class="bi bi-file-earmark-excel"></i>
        Export Excel
    </x-ui.button>

</div>

<x-ui.card class="mb-8">
    <form method="GET">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">

            <x-ui.form-group label="Tanggal">
                <x-ui.input
                    type="week"
                    name="week"
                    value="{{ $week }}"
                />
                <div class="mt-2 text-xs font-bold text-emerald-700">Periode: Sabtu–Jumat</div>
            </x-ui.form-group>

            <x-ui.form-group label="Jenis Santri">
                <x-ui.select name="gender">
                    <option value="">Semua</option>
                    <option value="putra" @selected($gender === 'putra')>Putra</option>
                    <option value="putri" @selected($gender === 'putri')>Putri</option>
                </x-ui.select>
            </x-ui.form-group>

            <x-ui.form-group label="Jenjang">
                <x-ui.select name="kelas">
                    <option value="">Semua</option>

                    @foreach($kelasList as $item)
                        <option value="{{ $item }}" @selected($kelas === $item)>
                            {{ $item }}
                        </option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>

            <x-ui.form-group label="Kamar">
                <x-ui.select name="kamar">
                    <option value="">Semua</option>

                    @foreach($kamarList as $item)
                        <option value="{{ $item }}" @selected($kamar === $item)>
                            {{ $item }}
                        </option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>

            <div class="flex items-end md:col-span-2 xl:col-span-2">
                <x-ui.button
                    type="submit"
                    class="w-full justify-center"
                >
                    <i class="bi bi-search"></i>
                    Filter Data
                </x-ui.button>
            </div>

        </div>
    </form>
</x-ui.card>

<div class="mb-8 grid grid-cols-2 gap-5 md:grid-cols-4 xl:grid-cols-7">
    <x-ui.stat-card label="Santri" :value="$summary['total_santri']" icon="bi-people" tone="blue" />
    <x-ui.stat-card label="Target" :value="$summary['total_target']" icon="bi-calendar-check" tone="slate" />
    <x-ui.stat-card label="Hadir" :value="$summary['hadir']" icon="bi-check-circle" tone="emerald" />
    <x-ui.stat-card label="Telat" :value="$summary['telat']" icon="bi-clock" tone="amber" />
    <x-ui.stat-card label="Izin" :value="$summary['izin']" icon="bi-envelope-check" tone="blue" />
    <x-ui.stat-card label="Sakit" :value="$summary['sakit']" icon="bi-heart-pulse" tone="red" />
    <x-ui.stat-card label="Alpa" :value="$summary['alpa']" icon="bi-x-circle" tone="red" />
</div>

<x-ui.card
    padding="p-0"
    class="overflow-hidden shadow-xl shadow-slate-200/60"
>
    <div class="border-b border-slate-100 bg-white p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <div class="text-lg font-black text-slate-900">
                    Detail Rekap Kegiatan Mingguan
                </div>

                <div class="mt-1 text-sm font-medium text-slate-500">
                    {{ $start->translatedFormat('d F Y') }}
                    -
                    {{ $end->translatedFormat('d F Y') }}
                    •
                    {{ $activities->pluck('name')->join(', ') ?: 'Belum ada kegiatan umum' }}
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="rounded-2xl bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700">
                    {{ $summary['total_santri'] }} Santri
                </span>

                <span class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700">
                    {{ $summary['total_target'] }} Target
                </span>
            </div>

        </div>
    </div>

    <div class="w-full overflow-x-auto">
        <table class="w-full min-w-[920px]">
            <thead class="sticky top-0 z-10 bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-6 py-4">Santri</th>
                    <th class="px-6 py-4 text-center">Hadir</th>
                    <th class="px-6 py-4 text-center">Telat</th>
                    <th class="px-6 py-4 text-center">Izin</th>
                    <th class="px-6 py-4 text-center">Sakit</th>
                    <th class="px-6 py-4 text-center">Pulang</th>
                    <th class="px-6 py-4 text-center">Alpa</th>
                    <th class="px-6 py-4 text-right">Total</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($rows as $row)
                    @php
                        $student = $row['student'];
                    @endphp

                    <tr class="transition hover:bg-emerald-50/40">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <img
                                    src="{{ $student->photoUrl() }}"
                                    alt="{{ $student->name }}"
                                    class="h-12 w-12 rounded-2xl object-cover ring-2 ring-emerald-100"
                                >

                                <div class="min-w-0">
                                    <div class="truncate font-black text-slate-900">
                                        {{ $student->name }}
                                    </div>

                                    <div class="mt-1 text-sm font-semibold text-slate-500">
                                        {{ $student->nis }}
                                        • {{ $student->kelas ?? '-' }}
                                        • {{ $student->kamar ?? '-' }}
                                    </div>

                                    @if($student->gender)
                                        <div class="mt-2">
                                            <x-ui.badge tone="{{ $student->gender === 'putra' ? 'blue' : 'red' }}">
                                                {{ $student->gender === 'putra' ? 'Putra' : 'Putri' }}
                                            </x-ui.badge>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="font-black text-emerald-600">
                                {{ $row['hadir'] }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="font-black text-amber-600">
                                {{ $row['telat'] }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="font-black text-blue-600">
                                {{ $row['izin'] }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="font-black text-red-600">
                                {{ $row['sakit'] }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="font-black text-slate-600">
                                {{ $row['pulang'] }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="font-black text-red-600">
                                {{ $row['alpa'] }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex rounded-xl bg-slate-100 px-3 py-1 text-sm font-black text-slate-800">
                                {{ $row['total'] }}/{{ $row['target'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-10">
                            <x-ui.empty-state
                                title="Tidak ada data"
                                subtitle="Belum ada data santri untuk filter ini."
                                icon="bi-people"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

@endsection
