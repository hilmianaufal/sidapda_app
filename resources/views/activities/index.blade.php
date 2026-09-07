@extends('layouts.app')

@section('title', ($category ?? null) === 'diniyah' ? 'Jadwal MADAD' : 'Jadwal Kegiatan')
@section('mobile_title', ($category ?? null) === 'diniyah' ? 'MADAD' : 'Kegiatan')

@section('content')

@php
  $dayNames = [
    0 => 'Ahad',
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
  ];

  $totalAktif = collect($activities)->where('is_active', true)->count();
  $totalRutin = collect($activities)->where('type', 'routine')->count();
  $totalManual = collect($activities)->where('type', 'manual')->count();
  $isMadad = ($category ?? null) === 'diniyah';
@endphp

<x-ui.page-header
  :title="$isMadad ? 'Jadwal Kegiatan MADAD' : 'Jadwal Kegiatan'"
  :subtitle="$isMadad ? 'Kegiatan Diniyah terpisah dari kegiatan Pondok' : 'Kelola kegiatan rutin, event, dan absensi kegiatan'"
  :icon="$isMadad ? 'bi-book' : 'bi-calendar-check'"
>
  <x-slot:actions>
    <x-ui.button :href="route('activities.create', array_filter(['category' => $category ?? null]))">
      <i class="bi bi-plus-lg"></i>
      Tambah
    </x-ui.button>

    <x-ui.button :href="route('activities.scan', array_filter(['category' => $category ?? null]))" variant="secondary">
      <i class="bi bi-qr-code"></i>
      Scan
    </x-ui.button>

    @if($isMadad)
      <x-ui.button :href="route('dashboard.institution', 'madad')" variant="secondary">
        <i class="bi bi-speedometer2"></i>
        Dashboard MADAD
      </x-ui.button>
    @endif
  </x-slot:actions>
</x-ui.page-header>

<div class="mb-6 flex flex-wrap gap-2 rounded-2xl bg-white p-2 shadow-sm">
  @if(auth()->user()->canAccessInstitution('ponpes') && auth()->user()->canAccessInstitution('madad'))
    <a href="{{ route('activities.index') }}" class="rounded-xl px-4 py-2 text-sm font-black {{ empty($category) ? 'bg-slate-800 text-white' : 'text-slate-500 hover:bg-slate-100' }}">Semua</a>
  @endif
  @if(auth()->user()->canAccessInstitution('ponpes'))
    <a href="{{ route('activities.index', ['category' => 'umum']) }}" class="rounded-xl px-4 py-2 text-sm font-black {{ ($category ?? null) === 'umum' ? 'bg-emerald-600 text-white' : 'text-slate-500 hover:bg-slate-100' }}">Kegiatan Pondok</a>
  @endif
  @if(auth()->user()->canAccessInstitution('madad'))
    <a href="{{ route('activities.index', ['category' => 'diniyah']) }}" class="rounded-xl px-4 py-2 text-sm font-black {{ $isMadad ? 'bg-amber-500 text-white' : 'text-slate-500 hover:bg-slate-100' }}">Kegiatan MADAD</a>
  @endif
</div>

<div class="mb-6 grid grid-cols-3 gap-4">
  <x-ui.stat-card label="Aktif" :value="$totalAktif" icon="bi-check-circle" tone="emerald" />
  <x-ui.stat-card label="Rutin" :value="$totalRutin" icon="bi-calendar-week" tone="blue" />
  <x-ui.stat-card label="Manual" :value="$totalManual" icon="bi-lightning" tone="amber" />
</div>

{{-- Desktop Table --}}
<div class="hidden lg:block">
  <x-ui.card padding="p-0">
    <div class="flex items-center justify-between border-b border-slate-100 p-5">
      <div>
        <div class="text-lg font-black text-slate-900">
          Daftar Kegiatan
        </div>
        <div class="text-sm text-slate-500">
          Total {{ count($activities) }} kegiatan
        </div>
      </div>
    </div>

    <div class="w-full overflow-x-auto">
      <table class="w-full min-w-[820px]">
        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-400">
          <tr>
            <th class="px-6 py-4">Kegiatan</th>
            <th class="px-6 py-4">Tipe</th>
            <th class="px-6 py-4">Hari / Tanggal</th>
            <th class="px-6 py-4">Jam</th>
            <th class="px-6 py-4">Telat</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Aksi</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          @forelse($activities as $activity)
            <tr class="transition hover:bg-emerald-50/40">
              <td class="px-6 py-4">
                <div class="flex items-center gap-4">
                  <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-600">
                    <i class="bi {{ $activity->type === 'routine' ? 'bi-calendar-week' : 'bi-lightning-charge' }}"></i>
                  </div>

                  <div>
                    <div class="font-black text-slate-900">
                      {{ $activity->name }}
                    </div>
                    <div class="text-sm font-semibold text-slate-500">
                      Urutan {{ $activity->order }}
                    </div>
                    <div class="mt-1 text-xs font-black {{ $activity->category === 'diniyah' ? 'text-amber-600' : 'text-emerald-600' }}">
                      {{ $activity->category === 'diniyah' ? 'MADAD / Diniyah' : 'Pondok' }}
                    </div>
                  </div>
                </div>
              </td>

              <td class="px-6 py-4">
                @if($activity->type === 'routine')
                  <x-ui.badge tone="blue">Rutin</x-ui.badge>
                @else
                  <x-ui.badge tone="amber">Manual</x-ui.badge>
                @endif
              </td>

              <td class="px-6 py-4">
                @if($activity->type === 'routine')
                  <div class="flex flex-wrap gap-2">
                    @forelse(($activity->days ?? []) as $day)
                      <x-ui.badge tone="slate">
                        {{ $dayNames[$day] ?? $day }}
                      </x-ui.badge>
                    @empty
                      <span class="text-sm font-bold text-slate-400">-</span>
                    @endforelse
                  </div>
                @else
                  <span class="text-sm font-bold text-slate-600">
                    {{ $activity->event_date ? $activity->event_date->format('d M Y') : '-' }}
                  </span>
                @endif
              </td>

              <td class="px-6 py-4 font-bold text-slate-500">
                {{ substr($activity->start_time, 0, 5) }}
                -
                {{ substr($activity->end_time, 0, 5) }}
              </td>

              <td class="px-6 py-4 font-bold text-slate-500">
                {{ $activity->late_minutes }} menit
              </td>

              <td class="px-6 py-4">
                @if($activity->is_active)
                  <x-ui.badge tone="emerald">Aktif</x-ui.badge>
                @else
                  <x-ui.badge tone="slate">Off</x-ui.badge>
                @endif
              </td>

              <td class="px-6 py-4">
                <div class="flex justify-end gap-2">
                  <x-ui.button
                    :href="route('activities.edit', ['activity' => $activity] + ($category ? ['category' => $category] : []))"
                    variant="secondary">
                    <i class="bi bi-pencil"></i>
                  </x-ui.button>

                  <form
                    method="POST"
                    action="{{ route('activities.destroy', ['activity' => $activity] + ($category ? ['category' => $category] : [])) }}"
                    onsubmit="return confirm('Hapus kegiatan ini?')">
                    @csrf
                    @method('DELETE')

                    <button
                      class="inline-flex items-center justify-center rounded-2xl border border-red-100 bg-red-50 px-4 py-2 text-sm font-black text-red-600 transition hover:bg-red-100">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="p-8">
                <x-ui.empty-state
                  title="Belum ada kegiatan"
                  subtitle="Tambahkan jadwal kegiatan baru."
                  icon="bi-calendar-check" />
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-ui.card>
</div>

{{-- Mobile Cards --}}
<div class="space-y-4 lg:hidden">
  @forelse($activities as $activity)
    <x-ui.card>
      <div class="flex gap-4">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-2xl text-emerald-600">
          <i class="bi {{ $activity->type === 'routine' ? 'bi-calendar-week' : 'bi-lightning-charge' }}"></i>
        </div>

        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-2">
            <div>
              <div class="truncate text-base font-black text-slate-900">
                {{ $activity->name }}
              </div>

              <div class="mt-1 text-sm font-bold text-slate-500">
                {{ substr($activity->start_time, 0, 5) }}
                -
                {{ substr($activity->end_time, 0, 5) }}
              </div>
              <div class="mt-1 text-xs font-black {{ $activity->category === 'diniyah' ? 'text-amber-600' : 'text-emerald-600' }}">
                {{ $activity->category === 'diniyah' ? 'MADAD / Diniyah' : 'Pondok' }}
              </div>
            </div>

            @if($activity->is_active)
              <x-ui.badge tone="emerald">Aktif</x-ui.badge>
            @else
              <x-ui.badge tone="slate">Off</x-ui.badge>
            @endif
          </div>

          <div class="mt-3 flex flex-wrap gap-2">
            @if($activity->type === 'routine')
              <x-ui.badge tone="blue">Rutin</x-ui.badge>

              @foreach(($activity->days ?? []) as $day)
                <x-ui.badge tone="slate">
                  {{ $dayNames[$day] ?? $day }}
                </x-ui.badge>
              @endforeach
            @else
              <x-ui.badge tone="amber">Manual</x-ui.badge>

              <x-ui.badge tone="slate">
                {{ $activity->event_date ? $activity->event_date->format('d M Y') : '-' }}
              </x-ui.badge>
            @endif

            <x-ui.badge tone="slate">
              Telat {{ $activity->late_minutes }} menit
            </x-ui.badge>
          </div>

          <div class="mt-4 grid grid-cols-2 gap-2">
            <x-ui.button
              :href="route('activities.edit', ['activity' => $activity] + ($category ? ['category' => $category] : []))"
              variant="secondary"
              class="justify-center">
              Edit
            </x-ui.button>

            <form
              method="POST"
              action="{{ route('activities.destroy', ['activity' => $activity] + ($category ? ['category' => $category] : [])) }}"
              onsubmit="return confirm('Hapus kegiatan ini?')">
              @csrf
              @method('DELETE')

              <button
                class="w-full rounded-2xl border border-red-100 bg-red-50 px-4 py-2 text-sm font-black text-red-600 transition hover:bg-red-100">
                Hapus
              </button>
            </form>
          </div>
        </div>
      </div>
    </x-ui.card>
  @empty
    <x-ui.empty-state
      title="Belum ada kegiatan"
      subtitle="Tambahkan jadwal kegiatan baru."
      icon="bi-calendar-check" />
  @endforelse
</div>

@endsection
