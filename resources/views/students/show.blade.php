@extends('layouts.app')

@section('title','Detail Santri')
@section('mobile_title','Detail Santri')

@section('content')

@php
  $displayEnrollment = $student->enrollments->first();
  $displayClass = $canViewBoardingData
    ? ($student->kelas ?: '-')
    : (($displayEnrollment?->level ? $displayEnrollment->level.' / ' : '').($displayEnrollment?->class_name ?: '-'));
@endphp

<x-ui.page-header
  title="{{ $student->name }}"
  subtitle="NIS: {{ $student->nis }} • Detail identitas & QR santri"
  icon="bi-person-badge"
>
  <x-slot:actions>
    <x-ui.button :href="route('students.edit', $student)" variant="secondary">
      <i class="bi bi-pencil"></i>
      Edit
    </x-ui.button>

    <x-ui.button :href="route('students.institutions.edit', $student)" variant="secondary">
      <i class="bi bi-building"></i>
      Lembaga & Kelas
    </x-ui.button>

    <x-ui.button :href="route('students.index')" variant="secondary">
      Kembali
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<div class="grid gap-6 lg:grid-cols-12">

  {{-- Profile Card --}}
  <div class="lg:col-span-4">
    <x-ui.card>

      <div class="text-center">
        <div class="mx-auto h-36 w-36 overflow-hidden rounded-[2rem] bg-emerald-50 shadow-xl shadow-emerald-100 ring-4 ring-white">
          <img
            src="{{ $student->photoUrl() }}"
            alt="{{ $student->name }}"
            class="h-full w-full object-cover">
        </div>

        <div class="mt-5 text-2xl font-black text-slate-900">
          {{ $student->name }}
        </div>

        <div class="mt-1 text-sm font-bold text-slate-500">
          {{ $student->nis }}
        </div>

        <div class="mt-4 flex justify-center gap-2">
          @if($student->is_active)
            <x-ui.badge tone="emerald">Aktif</x-ui.badge>
          @else
            <x-ui.badge tone="red">Nonaktif</x-ui.badge>
          @endif
          @if($canViewBoardingData)
            <x-ui.badge tone="blue">
              {{ $student->residency_status === 'non_mukim' ? 'Tidak Mukim' : 'Mukim' }}
            </x-ui.badge>
          @endif
        </div>
      </div>
        <div class="rounded-2xl bg-purple-50 p-4">
        <div class="text-xs font-black uppercase tracking-wide text-purple-400">
            Jenis Santri
        </div>
        <div class="mt-1 font-black text-purple-700">
            {{ $student->gender === 'putra' ? 'Putra' : ($student->gender === 'putri' ? 'Putri' : '-') }}
        </div>
        </div>
      <div class="mt-8 grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-blue-50 p-4 text-center">
          <div class="text-xs font-black uppercase tracking-wide text-blue-400">
            Kelas
          </div>
          <div class="mt-1 font-black text-blue-700">
            {{ $displayClass }}
          </div>
        </div>

        <div class="rounded-2xl bg-emerald-50 p-4 text-center">
          <div class="text-xs font-black uppercase tracking-wide text-emerald-400">
            Kamar
          </div>
          <div class="mt-1 font-black text-emerald-700">
            {{ $canViewBoardingData ? ($student->kamar ?: '-') : '-' }}
          </div>
        </div>
      </div>

      <div class="mt-6 space-y-3">
        <x-ui.button
          :href="route('students.institutions.edit', $student)"
          variant="secondary"
          class="w-full justify-center">
          <i class="bi bi-building"></i>
          Atur Lembaga & Kelas
        </x-ui.button>

        @if($canViewBoardingData)
          <x-ui.button
            :href="route('students.attendance.show', $student)"
            class="w-full justify-center">
            <i class="bi bi-calendar-check"></i>
            Riwayat Absensi
          </x-ui.button>
        @endif

        <x-ui.button
          :href="route('students.edit', $student)"
          variant="secondary"
          class="w-full justify-center">
          <i class="bi bi-pencil"></i>
          Edit Data
        </x-ui.button>
      </div>

    </x-ui.card>
  </div>

  {{-- QR Card --}}
{{-- Premium ID Card --}}
<div class="lg:col-span-4">
  <x-ui.card>

    <div class="mb-5 flex items-center justify-between">
      <div>
        <div class="text-lg font-black text-slate-900">
          ID Card Santri
        </div>
        <div class="text-sm font-medium text-slate-500">
          Kartu identitas digital
        </div>
      </div>

      <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-600">
        <i class="bi bi-person-vcard"></i>
      </div>
    </div>

   <div
  id="studentIdCard"
  class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-700 via-emerald-500 to-lime-400 p-5 text-white shadow-2xl shadow-emerald-300/50">

      {{-- Decorative glow --}}
      <div class="absolute -right-16 -top-16 h-40 w-40 rounded-full bg-white/20 blur-2xl"></div>
      <div class="absolute -bottom-20 -left-20 h-48 w-48 rounded-full bg-lime-300/30 blur-3xl"></div>

      <div class="relative z-10">
        {{-- Header --}}
        <div class="flex items-center justify-between">
          <div>
            <div class="text-xs font-black uppercase tracking-[0.25em] text-white/70">
              Student ID
            </div>
            <div class="mt-1 text-lg font-black">
              Pondok Digital
            </div>
          </div>

          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
            <i class="bi bi-stars text-xl"></i>
          </div>
        </div>

        {{-- Photo --}}
        <div class="mt-8 flex justify-center">
          <div class="relative">
            <div class="absolute inset-0 rounded-[2rem] bg-white/30 blur-xl"></div>

            <img
              src="{{ $student->photoUrl() }}"
              alt="{{ $student->name }}"
              class="relative h-32 w-32 rounded-[2rem] object-cover ring-4 ring-white/80 shadow-2xl">
          </div>
        </div>

        {{-- Identity --}}
        <div class="mt-5 text-center">
          <div class="text-2xl font-black leading-tight">
            {{ $student->name }}
          </div>

          <div class="mt-1 text-sm font-bold text-white/75">
            NIS {{ $student->nis }}
          </div>
        </div>

        {{-- Meta --}}
        <div class="mt-6 grid grid-cols-2 gap-3">
          <div class="rounded-2xl bg-white/18 p-3 text-center backdrop-blur">
            <div class="text-[10px] font-black uppercase tracking-wide text-white/60">
              Kelas
            </div>
            <div class="mt-1 text-sm font-black">
              {{ $displayClass }}
            </div>
          </div>

          <div class="rounded-2xl bg-white/18 p-3 text-center backdrop-blur">
            <div class="text-[10px] font-black uppercase tracking-wide text-white/60">
              Kamar
            </div>
            <div class="mt-1 text-sm font-black">
              {{ $canViewBoardingData ? ($student->kamar ?: '-') : '-' }}
            </div>
          </div>
        </div>

        {{-- QR --}}
        <div class="mt-6 rounded-[1.5rem] bg-white p-3 text-slate-900 shadow-xl">
        <div class="flex items-center gap-3">
            <img
            src="{{ route('students.qr.show', $student) }}"
            alt="QR {{ $student->nis }}"
            class="h-40 w-40 shrink-0 rounded-xl object-contain">

            <div class="min-w-0 flex-1">
              <div class="text-xs font-black uppercase tracking-wide text-slate-400">
                Scan QR
              </div>

              <div class="mt-1 text-sm font-black text-slate-900">
                Absensi Santri
              </div>

              <div class="mt-2 break-all font-mono text-[10px] font-bold text-slate-400">
                {{ Str::limit($student->qr_token, 32) }}
              </div>
            </div>
          </div>
        </div>

        {{-- Footer --}}
        <div class="mt-5 flex items-center justify-between text-xs font-bold text-white/70">
          <span>Valid Permanent</span>
          <span>{{ now()->format('Y') }}</span>
        </div>
      </div>
    </div>

    <div class="mt-5 grid grid-cols-2 gap-3">
    @if($canViewBoardingData)
      <a
        href="{{ route('students.id-card.png', $student) }}"
        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-lime-500 px-4 py-2 text-sm font-black text-white shadow-lg shadow-emerald-300/40 transition active:scale-95">
        <i class="bi bi-download"></i>
        Download ID Card
      </a>
    @endif
    <a
      href="{{ route('students.qr.download', $student) }}"
      class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-2 text-sm font-black text-white shadow-lg shadow-slate-300/40 transition active:scale-95">
      <i class="bi bi-qr-code"></i>
      Download QR
    </a>
          @if($student->parent_phone)
              @php
                $today = now()->translatedFormat('d F Y');
                $time = now()->format('H:i');

                $waMessage = urlencode(
                  "Assalamu'alaikum Bapak/Ibu.\n\n" .

                  "Kami informasikan bahwa ananda:\n\n" .

                  "Nama: {$student->name}\n" .
                  "NIS: {$student->nis}\n" .
                  "Kelas: " . ($student->kelas ?? '-') . "\n\n" .

                  "Telah melakukan absensi hari ini.\n\n" .

                  "Tanggal: {$today}\n" .
                  "Jam: {$time}\n\n" .

                  "Terima kasih.\n" .
                  "Pondok Pesantren Darussalam"
                );

                $waUrl = "https://wa.me/{$student->parent_phone}?text={$waMessage}";
              @endphp

            <x-ui.button
              :href="$waUrl"
              target="_blank"
              class="w-full justify-center">
              <i class="bi bi-whatsapp"></i>
              Kirim WA Ortu
            </x-ui.button>
          @else
            <div class="rounded-2xl bg-amber-50 p-4 text-sm font-bold text-amber-700">
              Nomor WA ortu belum diisi.
            </div>
          @endif
      @if($canViewBoardingData)
        <x-ui.button
          :href="route('students.attendance.show', $student)"
          variant="secondary"
          class="justify-center">
          Absensi
        </x-ui.button>
      @endif
    </div>

  </x-ui.card>
</div>

  {{-- Detail Info --}}
  <div class="lg:col-span-4">
    <x-ui.card>

      <div class="mb-5">
        <div class="text-lg font-black text-slate-900">
          Informasi Detail
        </div>
        <div class="text-sm font-medium text-slate-500">
          Data identitas santri
        </div>
      </div>

      <div class="space-y-4">

        <div class="rounded-2xl bg-blue-50 p-4">
          <div class="text-xs font-black uppercase tracking-wide text-blue-400">
            Keanggotaan Lembaga • {{ $academicYear }}
          </div>
          <div class="mt-3 space-y-2">
            @forelse($student->enrollments as $enrollment)
              <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2">
                <span class="text-sm font-black text-slate-700">
                  {{ $enrollment->institution->short_name }}
                </span>
                <span class="text-xs font-bold text-blue-600">
                  {{ $enrollment->class_name ?: 'Tanpa kelas' }}
                  @if($enrollment->level)
                    • {{ $enrollment->level }}
                  @endif
                </span>
              </div>
            @empty
              <div class="text-sm font-semibold text-amber-700">
                Belum terdaftar pada lembaga aktif.
              </div>
            @endforelse
          </div>
        </div>

        <div class="rounded-2xl bg-slate-50 p-4">
          <div class="text-xs font-black uppercase tracking-wide text-slate-400">
            Nama
          </div>
          <div class="mt-1 font-black text-slate-900">
            {{ $student->name }}
          </div>
        </div>

        <div class="rounded-2xl bg-slate-50 p-4">
          <div class="text-xs font-black uppercase tracking-wide text-slate-400">
            NIS
          </div>
          <div class="mt-1 font-black text-slate-900">
            {{ $student->nis }}
          </div>
        </div>

        <div class="rounded-2xl bg-slate-50 p-4">
          <div class="text-xs font-black uppercase tracking-wide text-slate-400">
            QR Token
          </div>
          <div class="mt-2 break-all rounded-xl bg-white p-3 font-mono text-xs font-bold text-slate-600 ring-1 ring-slate-100">
            {{ $student->qr_token }}
          </div>
        </div>

        <div class="rounded-2xl bg-emerald-50 p-4">
          <div class="text-xs font-black uppercase tracking-wide text-emerald-400">
            Catatan
          </div>
          <div class="mt-1 text-sm font-semibold text-emerald-700">
            QR token bersifat permanen dan digunakan untuk scan absensi.
          </div>
        </div>

      </div>

    </x-ui.card>
  </div>

</div>


@endsection
