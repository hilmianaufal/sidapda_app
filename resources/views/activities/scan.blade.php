@extends('layouts.app')

@php
  $isMadad = ($category ?? null) === 'diniyah';
  $pageTitle = $isMadad ? 'Scan Absensi MADAD' : 'Scan Kegiatan';
@endphp

@section('title', $pageTitle)
@section('mobile_title', $isMadad ? 'Scan MADAD' : 'Scan Kegiatan')

@section('content')

<x-ui.page-header
  :title="$isMadad ? 'Scan Absensi MADAD' : 'Scan Kegiatan Santri'"
  :subtitle="$isMadad ? 'Absensi khusus kegiatan Diniyah untuk siswa aktif MADAD' : 'Absensi kegiatan berbasis QR code'"
  :icon="$isMadad ? 'bi-book' : 'bi-qr-code'"
>
  <x-slot:actions>
    <x-ui.button :href="route('activities.index', array_filter(['category' => $category ?? null]))" variant="secondary">
      <i class="bi bi-calendar-check"></i>
      {{ $isMadad ? 'Jadwal MADAD' : 'Jadwal Kegiatan' }}
    </x-ui.button>

    @if($isMadad)
      <x-ui.button :href="route('rekap-diniyah.daily')" variant="secondary">
        <i class="bi bi-clipboard-check"></i>
        Rekap MADAD
      </x-ui.button>
      <x-ui.button :href="route('dashboard.institution', 'madad')" variant="secondary">
        <i class="bi bi-speedometer2"></i>
        Dashboard MADAD
      </x-ui.button>
    @else
      <x-ui.button :href="route('rekap-kegiatan.daily')" variant="secondary">
        <i class="bi bi-clipboard-check"></i>
        Rekap Kegiatan
      </x-ui.button>

      <x-ui.button :href="route('rekap-diniyah.daily')" variant="secondary">
        <i class="bi bi-book"></i>
        Rekap Diniyah
      </x-ui.button>
    @endif
  </x-slot:actions>
</x-ui.page-header>

@if($activeActivity)
  <div class="mb-6 rounded-[1.75rem] border border-emerald-200 bg-gradient-to-r from-emerald-50 to-lime-50 p-4 shadow-lg shadow-emerald-100">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-200">
          <i class="bi bi-broadcast"></i>
        </div>

        <div>
          <div class="text-sm font-black text-emerald-950">
            Kegiatan Aktif: {{ $activeActivity->name }}
          </div>

          <div class="text-xs font-semibold text-emerald-700">
            {{ $activeActivity->start_time }} – {{ $activeActivity->end_time }}
            • Telat {{ $activeActivity->late_minutes }} menit
          </div>
        </div>
      </div>

      <x-ui.badge tone="emerald">LIVE</x-ui.badge>
    </div>
  </div>
@else
  <div class="mb-6 rounded-[1.75rem] border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-700">
    Tidak ada kegiatan aktif saat ini. Scan ditunda.
  </div>
@endif

<div class="grid gap-6 lg:grid-cols-12">

  {{-- Scanner --}}
  <div class="lg:col-span-7">
    <x-ui.card>

      <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div class="text-lg font-black text-slate-900">
            {{ $isMadad ? 'Scanner MADAD' : 'Scanner Kegiatan' }}
          </div>

          <div class="text-sm font-medium text-slate-500">
            Pilih metode scan
          </div>
        </div>

        <div class="grid grid-cols-3 overflow-hidden rounded-2xl bg-slate-100 p-1 text-xs font-black text-slate-500">
          <label class="cursor-pointer rounded-xl px-3 py-2 text-center transition has-[:checked]:bg-white has-[:checked]:text-emerald-700 has-[:checked]:shadow">
            <input type="radio" class="hidden" name="mode" id="modeCamera" checked>
            Kamera
          </label>

          <label class="cursor-pointer rounded-xl px-3 py-2 text-center transition has-[:checked]:bg-white has-[:checked]:text-emerald-700 has-[:checked]:shadow">
            <input type="radio" class="hidden" name="mode" id="modeScanner">
            Scanner
          </label>

          <label class="cursor-pointer rounded-xl px-3 py-2 text-center transition has-[:checked]:bg-white has-[:checked]:text-emerald-700 has-[:checked]:shadow">
            <input type="radio" class="hidden" name="mode" id="modeManual">
            Manual
          </label>
        </div>
      </div>

      {{-- Kamera --}}
      <div id="cameraWrap">
        <div class="rounded-[2rem] bg-gradient-to-br from-slate-900 to-emerald-950 p-4 shadow-2xl shadow-emerald-200">
          <div class="overflow-hidden rounded-[1.5rem] bg-black">
            <div id="reader" class="mx-auto w-full max-w-[440px]"></div>
          </div>
        </div>

        <div class="mt-4 flex gap-3">
          <button
            id="btnStart"
            class="flex-1 rounded-2xl bg-gradient-to-r from-emerald-600 to-lime-500 px-4 py-3 text-sm font-black text-white shadow-lg shadow-emerald-300/40 transition active:scale-95">
            <i class="bi bi-camera"></i>
            Start Kamera
          </button>

          <button
            id="btnStop"
            disabled
            class="rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-black text-red-600 disabled:opacity-50">
            Stop
          </button>
        </div>

        <div class="mt-3 text-center text-xs font-semibold text-slate-400">
          Gunakan kamera belakang HP untuk hasil terbaik.
        </div>
      </div>

      {{-- Scanner HID --}}
      <div id="scannerWrap" class="hidden">
        <div class="rounded-[2rem] border-2 border-dashed border-emerald-200 bg-emerald-50/60 p-8 text-center">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-2xl text-emerald-600 shadow-lg">
            <i class="bi bi-upc-scan"></i>
          </div>

          <div class="mt-4 text-lg font-black text-emerald-950">
            Mode Scanner Aktif
          </div>

          <div class="mt-1 text-sm font-medium text-slate-500">
            Arahkan scanner USB/Bluetooth ke QR santri.
          </div>

          <input
            id="scannerInput"
            class="mt-6 w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-center text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100"
            placeholder="Arahkan scanner..."
            autocomplete="off">
        </div>
      </div>

      {{-- Manual --}}
      <div id="manualWrap" class="hidden">
        <div class="rounded-[2rem] border border-slate-100 bg-slate-50 p-6">
          <div class="mb-4">
            <div class="text-lg font-black text-slate-900">
              Input Manual
            </div>
            <div class="text-sm font-medium text-slate-500">
              Tempel token QR secara manual.
            </div>
          </div>

          <div class="flex gap-3">
            <input
              id="manualToken"
              class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100"
              placeholder="Token QR..."
              autocomplete="off">

            <button
              id="btnManual"
              class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white">
              Kirim
            </button>
          </div>
        </div>
      </div>

    </x-ui.card>
  </div>

  {{-- Result --}}
  <div class="lg:col-span-5">
    <x-ui.card>

      <div class="mb-5">
        <div class="text-lg font-black text-slate-900">
          Hasil Scan
        </div>

        <div class="text-sm font-medium text-slate-500">
          Informasi santri akan tampil di sini.
        </div>
      </div>

      <div id="alertBox" class="mb-4 hidden rounded-2xl px-4 py-3 text-sm font-bold"></div>

      <div id="resultBox" class="rounded-[2rem] border border-dashed border-slate-200 bg-slate-50 p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-2xl text-slate-400 shadow">
          <i class="bi bi-qr-code"></i>
        </div>

        <div class="mt-4 text-sm font-black text-slate-700">
          Belum ada scan kegiatan
        </div>

        <div class="mt-1 text-sm text-slate-400">
          Scan QR santri untuk mulai absensi kegiatan.
        </div>
      </div>

      <audio id="beepSound" src="{{ asset('sounds/beep.mp3') }}" preload="auto"></audio>

    </x-ui.card>
  </div>

</div>

<script src="https://unpkg.com/html5-qrcode" defer></script>

<script>
window.__HAS_ACTIVE_ACTIVITY__ = @json((bool) $activeActivity);
</script>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const csrf = @json(csrf_token());
  const scanUrl = @json(route('activities.scan.store'));
  const scanCategory = @json($category ?? null);
  const hasActiveActivity = window.__HAS_ACTIVE_ACTIVITY__;

  const alertBox  = document.getElementById('alertBox');
  const resultBox = document.getElementById('resultBox');

  const btnStart = document.getElementById('btnStart');
  const btnStop  = document.getElementById('btnStop');

  const modeCamera  = document.getElementById('modeCamera');
  const modeScanner = document.getElementById('modeScanner');
  const modeManual  = document.getElementById('modeManual');

  const cameraWrap  = document.getElementById('cameraWrap');
  const scannerWrap = document.getElementById('scannerWrap');
  const manualWrap  = document.getElementById('manualWrap');

  const scannerInput = document.getElementById('scannerInput');
  const manualToken  = document.getElementById('manualToken');
  const btnManual    = document.getElementById('btnManual');

  const beep = document.getElementById('beepSound');

  let html5QrCode = null;
  let isScanning = false;
  let lastToken = null;
  let lock = false;

  function playBeep() {
    if (!beep) return;
    beep.currentTime = 0;
    beep.play().catch(() => {});
  }

  function speak(text) {
    if (!('speechSynthesis' in window)) return;

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID';
    utterance.rate = 0.95;
    utterance.pitch = 1;
    utterance.volume = 1;

    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utterance);
  }

  btnStart?.addEventListener('click', async () => {
    try {
      if (!beep) return;
      await beep.play();
      beep.pause();
      beep.currentTime = 0;
    } catch {}
  });

  function showAlert(type, msg) {
    if (!alertBox) return;

    const classes = {
      success: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
      danger: 'bg-red-50 text-red-700 border border-red-200',
      warning: 'bg-amber-50 text-amber-700 border border-amber-200',
      info: 'bg-blue-50 text-blue-700 border border-blue-200',
      secondary: 'bg-slate-100 text-slate-600 border border-slate-200',
    };

    alertBox.className = `mb-4 rounded-2xl px-4 py-3 text-sm font-bold ${classes[type] ?? classes.info}`;
    alertBox.textContent = msg;
    alertBox.classList.remove('hidden');
  }

  function hideAlert() {
    alertBox?.classList.add('hidden');
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  async function sendToken(token) {
    if (!hasActiveActivity) {
      showAlert('warning', 'Tidak ada kegiatan aktif. Scan ditunda.');
      speak('Tidak ada kegiatan aktif');
      return;
    }

    token = String(token ?? '').trim();
    if (!token) return;

    if (token === lastToken) return;
    if (lock) return;

    lock = true;

    try {
      hideAlert();

      const res = await fetch(scanUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ token, category: scanCategory })
      });

      const json = await res.json();

      if (!res.ok || !json.ok) {
        const message = json.message ?? 'QR tidak valid.';

        showAlert('danger', message);
        speak(message);

        resultBox.innerHTML = `
          <div class="rounded-[2rem] bg-red-50 p-6 text-center text-red-700">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-2xl">
              <i class="bi bi-x-circle"></i>
            </div>
            <div class="mt-4 text-sm font-black">${escapeHtml(message)}</div>
          </div>
        `;
      } else {
        const isLate = json.status === 'terlambat';
        const statusText = isLate ? 'terlambat' : 'hadir';
        const photoUrl = json.student.photo_url ?? "{{ asset('images/default.jpg') }}";

        showAlert('success', json.message ?? 'Berhasil.');
        lastToken = token;

        playBeep();
        speak(`${json.student.name}, ${statusText} kegiatan`);
        if (navigator.vibrate) navigator.vibrate(80);

        resultBox.innerHTML = `
          <div class="rounded-[2rem] bg-gradient-to-br from-emerald-600 to-lime-500 p-5 text-white shadow-xl shadow-emerald-200">

            <div class="flex items-start gap-4">
              <img
                src="${escapeHtml(photoUrl)}"
                class="h-20 w-20 rounded-3xl object-cover ring-4 ring-white/70 shadow-xl"
                alt="Foto Santri">

              <div class="min-w-0 flex-1">
                <div class="text-xl font-black leading-tight">
                  ${escapeHtml(json.student.name)}
                </div>

                <div class="mt-1 text-sm font-bold text-white/80">
                  NIS: ${escapeHtml(json.student.nis)}
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                  <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black">
                    Kelas: ${escapeHtml(json.student.kelas ?? '-')}
                  </span>

                  <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black">
                    Kamar: ${escapeHtml(json.student.kamar ?? '-')}
                  </span>
                </div>
              </div>

              <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black">
                ${json.already ? 'SUDAH' : 'BARU'}
              </span>
            </div>

            <div class="mt-5 rounded-2xl bg-white p-4 text-slate-900">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-xs font-black uppercase text-slate-400">Kegiatan</div>
                  <div class="mt-1 font-black">${escapeHtml(json.activity)}</div>
                </div>

                <div class="text-right">
                  <div class="text-xs font-black uppercase text-slate-400">Jam</div>
                  <div class="mt-1 font-black">${escapeHtml(json.scanned_at)}</div>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <span class="inline-flex rounded-full px-4 py-2 text-xs font-black ${isLate ? 'bg-amber-100 text-amber-700' : 'bg-white text-emerald-700'}">
                ${isLate ? 'TERLAMBAT' : 'HADIR'}
              </span>
            </div>

          </div>
        `;
      }

    } catch (e) {
      showAlert('danger', 'Koneksi bermasalah. Coba lagi.');
      speak('Koneksi bermasalah');
    } finally {
      setTimeout(() => lock = false, 900);
    }
  }

  async function startScan() {
    if (!hasActiveActivity) {
      showAlert('warning', 'Tidak ada kegiatan aktif. Scan ditunda.');
      speak('Tidak ada kegiatan aktif');
      return;
    }

    if (isScanning) return;

    if (typeof Html5Qrcode === 'undefined') {
      showAlert('danger', 'Library QR belum siap. Refresh halaman.');
      return;
    }

    html5QrCode = new Html5Qrcode("reader");

    try {
      const devices = await Html5Qrcode.getCameras();

      if (!devices || devices.length === 0) {
        showAlert('danger', 'Kamera tidak ditemukan.');
        return;
      }

      const backCam = devices.find(d => /back|rear|environment/i.test(d.label));
      const cameraId = backCam ? backCam.id : devices[0].id;

      isScanning = true;
      btnStart.disabled = true;
      btnStop.disabled = false;

      await html5QrCode.start(
        { deviceId: { exact: cameraId } },
        { fps: 10, qrbox: { width: 240, height: 240 } },
        (decodedText) => {
          let token = decodedText;

          try {
            if (decodedText.startsWith('http')) {
              const url = new URL(decodedText);
              token = url.searchParams.get('token') || decodedText;
            }
          } catch {}

          sendToken(token);
        }
      );

      showAlert('info', 'Mode Kamera aktif. Arahkan ke QR santri.');
    } catch (err) {
      showAlert('danger', 'Gagal akses kamera. Pastikan izin kamera aktif.');
      isScanning = false;
      btnStart.disabled = false;
      btnStop.disabled = true;
    }
  }

  async function stopScan() {
    if (!html5QrCode || !isScanning) return;

    try {
      await html5QrCode.stop();
      await html5QrCode.clear();
    } catch {}

    isScanning = false;
    btnStart.disabled = false;
    btnStop.disabled = true;

    showAlert('secondary', 'Mode Kamera dihentikan.');
  }

  function enableCameraMode() {
    scannerWrap.classList.add('hidden');
    manualWrap.classList.add('hidden');
    cameraWrap.classList.remove('hidden');

    btnStart.disabled = !hasActiveActivity;
    btnStop.disabled = true;

    showAlert('secondary', 'Pilih Start untuk mengaktifkan kamera.');
  }

  function enableScannerMode() {
    if (isScanning) stopScan();

    cameraWrap.classList.add('hidden');
    scannerWrap.classList.remove('hidden');
    manualWrap.classList.add('hidden');

    btnStart.disabled = true;
    btnStop.disabled = true;

    setTimeout(() => scannerInput?.focus(), 150);
    showAlert('info', 'Mode Scanner aktif.');
  }

  function enableManualMode() {
    if (isScanning) stopScan();

    cameraWrap.classList.add('hidden');
    scannerWrap.classList.add('hidden');
    manualWrap.classList.remove('hidden');

    btnStart.disabled = true;
    btnStop.disabled = true;

    setTimeout(() => manualToken?.focus(), 150);
    showAlert('info', 'Mode Manual aktif.');
  }

  btnStart?.addEventListener('click', startScan);
  btnStop?.addEventListener('click', stopScan);

  scannerInput?.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;

    const token = scannerInput.value.trim();
    scannerInput.value = '';

    if (token) sendToken(token);
  });

  btnManual?.addEventListener('click', () => {
    const token = manualToken.value.trim();

    if (!token) {
      showAlert('warning', 'Token masih kosong.');
      speak('Token masih kosong');
      return;
    }

    sendToken(token);
  });

  modeCamera?.addEventListener('change', () => {
    if (modeCamera.checked) enableCameraMode();
  });

  modeScanner?.addEventListener('change', () => {
    if (modeScanner.checked) enableScannerMode();
  });

  modeManual?.addEventListener('change', () => {
    if (modeManual.checked) enableManualMode();
  });

  if (!hasActiveActivity) {
    btnStart.disabled = true;
    btnManual.disabled = true;
    showAlert('warning', 'Tidak ada kegiatan aktif. Scan ditunda.');
  } else {
    enableCameraMode();
  }
});
</script>
@endpush
