@extends('layouts.app')

@section('title', 'Absensi Ekstrakurikuler')
@section('mobile_title', 'Absensi Ekstra')

@section('content')
<x-ui.page-header
  :title="'Absensi Ekstrakurikuler '.$institution->short_name"
  :subtitle="$institution->name.' • Tahun ajaran '.$academicYear"
  icon="bi-qr-code-scan"
>
  <x-slot:actions>
    <x-ui.button :href="route('school-extracurricular-attendance.reports.index', ['institution' => $institution, 'extracurricular_id' => $selectedExtracurricular?->id])" variant="secondary">
      <i class="bi bi-clipboard2-check"></i>
      Rekap
    </x-ui.button>
    <x-ui.button :href="route('school-extracurricular-attendance.excuses.index', ['institution' => $institution, 'extracurricular_id' => $selectedExtracurricular?->id])" variant="secondary">
      <i class="bi bi-file-earmark-medical"></i>
      Izin & Sakit
    </x-ui.button>
    <x-ui.button :href="route('school-extracurriculars.index', $institution)" variant="secondary">
      <i class="bi bi-trophy"></i>
      Master Data
    </x-ui.button>
    <x-ui.button :href="route('dashboard.institution', $institution)" variant="secondary">
      <i class="bi bi-arrow-left"></i>
      Dashboard {{ $institution->short_name }}
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

@if(!$selectedExtracurricular)
  <div class="rounded-[2rem] border border-amber-200 bg-amber-50 p-8 text-center">
    <i class="bi bi-trophy text-5xl text-amber-500"></i>
    <div class="mt-4 text-xl font-black text-amber-950">Belum ada ekstrakurikuler aktif</div>
    <div class="mt-2 text-sm font-semibold text-amber-700">Tambahkan dan aktifkan jadwal Pramuka atau kegiatan Jumat terlebih dahulu.</div>
    <a href="{{ route('school-extracurriculars.index', $institution) }}" class="mt-5 inline-flex rounded-2xl bg-amber-500 px-5 py-3 text-sm font-black text-white">
      Buka Master Data
    </a>
  </div>
@else
  <x-ui.card class="mb-6">
    <form method="GET" action="{{ route('school-extracurricular-attendance.index', $institution) }}" class="grid gap-4 md:grid-cols-12 md:items-end">
      <div class="md:col-span-8">
        <label for="extracurricular_id" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Pilih Ekstrakurikuler</label>
        <select id="extracurricular_id" name="extracurricular_id" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100">
          @foreach($extracurriculars as $extracurricular)
            <option value="{{ $extracurricular->id }}" @selected($selectedExtracurricular->id === $extracurricular->id)>
              {{ $extracurricular->name }} • {{ $extracurricular->dayLabel() }} • {{ $extracurricular->startTimeLabel() }}–{{ $extracurricular->endTimeLabel() }} • {{ $extracurricular->levelLabel() }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="md:col-span-4">
        <button type="submit" class="w-full rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white">
          <i class="bi bi-check-circle"></i>
          Gunakan Kegiatan
        </button>
      </div>
    </form>
  </x-ui.card>

  <div class="mb-6 rounded-[1.75rem] border {{ $scheduledToday ? 'border-indigo-200 bg-indigo-50 shadow-indigo-100' : 'border-amber-200 bg-amber-50 shadow-amber-100' }} p-5 shadow-lg">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="text-xs font-black uppercase tracking-wide {{ $scheduledToday ? 'text-indigo-400' : 'text-amber-500' }}">Kegiatan Dipilih</div>
        <div class="mt-1 text-xl font-black {{ $scheduledToday ? 'text-indigo-950' : 'text-amber-950' }}">{{ $selectedExtracurricular->name }}</div>
        <div class="mt-1 text-sm font-semibold {{ $scheduledToday ? 'text-indigo-700' : 'text-amber-700' }}">
          {{ $selectedExtracurricular->dayLabel() }} • {{ $selectedExtracurricular->startTimeLabel() }}–{{ $selectedExtracurricular->endTimeLabel() }} • {{ $selectedExtracurricular->levelLabel() }}
        </div>
      </div>
      <div class="rounded-2xl bg-white/80 px-5 py-3 text-sm font-bold text-slate-700">
        Scan dibuka {{ $scanOpenLabel }} • Telat setelah {{ $selectedExtracurricular->late_minutes }} menit
      </div>
    </div>

    @if(!$scheduledToday)
      <div class="mt-4 rounded-2xl border border-amber-200 bg-white/70 px-4 py-3 text-sm font-bold text-amber-700">
        <i class="bi bi-exclamation-triangle"></i>
        Hari ini bukan jadwal {{ $selectedExtracurricular->name }}. Scan hanya dapat disimpan pada hari {{ $selectedExtracurricular->dayLabel() }}.
      </div>
    @endif
  </div>

  <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <x-ui.stat-card label="Siswa Peserta" :value="$totalStudents" icon="bi-people" tone="blue" />
    <x-ui.stat-card label="Sudah Absen" :value="$present" icon="bi-person-check" tone="emerald" />
    <x-ui.stat-card label="Belum Absen" :value="$notPresent" icon="bi-hourglass" tone="slate" />
    <x-ui.stat-card label="Terlambat" :value="$late" icon="bi-alarm" tone="amber" />
    <x-ui.stat-card label="Izin" :value="$excused" icon="bi-file-earmark-text" tone="blue" />
    <x-ui.stat-card label="Sakit" :value="$sick" icon="bi-bandaid" tone="red" />
    <x-ui.stat-card label="Lainnya" :value="$otherExcuses" icon="bi-three-dots" tone="slate" />
  </div>

  <div class="grid gap-6 lg:grid-cols-12">
    <div class="lg:col-span-7">
      <x-ui.card>
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="text-lg font-black text-slate-900">Scanner QR/NIS Siswa</div>
            <div class="text-sm font-medium text-slate-500">Kamera, scanner USB, atau input manual.</div>
          </div>

          <div class="grid grid-cols-3 overflow-hidden rounded-2xl bg-slate-100 p-1 text-xs font-black text-slate-500">
            <label class="cursor-pointer rounded-xl px-3 py-2 text-center transition has-[:checked]:bg-white has-[:checked]:text-emerald-700 has-[:checked]:shadow">
              <input type="radio" class="hidden" name="scan_mode" id="modeCamera" checked>
              Kamera
            </label>
            <label class="cursor-pointer rounded-xl px-3 py-2 text-center transition has-[:checked]:bg-white has-[:checked]:text-emerald-700 has-[:checked]:shadow">
              <input type="radio" class="hidden" name="scan_mode" id="modeScanner">
              Scanner
            </label>
            <label class="cursor-pointer rounded-xl px-3 py-2 text-center transition has-[:checked]:bg-white has-[:checked]:text-emerald-700 has-[:checked]:shadow">
              <input type="radio" class="hidden" name="scan_mode" id="modeManual">
              Manual
            </label>
          </div>
        </div>

        <div id="cameraWrap">
          <div class="rounded-[2rem] bg-gradient-to-br from-slate-900 to-indigo-950 p-4 shadow-2xl shadow-indigo-200">
            <div class="overflow-hidden rounded-[1.5rem] bg-black">
              <div id="reader" class="mx-auto w-full max-w-[440px]"></div>
            </div>
          </div>

          <div class="mt-4 flex gap-3">
            <button id="btnStart" type="button" class="flex-1 rounded-2xl bg-gradient-to-r from-emerald-600 to-lime-500 px-4 py-3 text-sm font-black text-white shadow-lg shadow-emerald-300/40">
              <i class="bi bi-camera"></i>
              Start Kamera
            </button>
            <button id="btnStop" type="button" disabled class="rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-black text-red-600 disabled:opacity-50">
              Stop
            </button>
          </div>
        </div>

        <div id="scannerWrap" class="hidden">
          <div class="rounded-[2rem] border-2 border-dashed border-emerald-200 bg-emerald-50 p-8 text-center">
            <i class="bi bi-upc-scan text-4xl text-emerald-600"></i>
            <div class="mt-3 font-black text-emerald-950">Mode Scanner Aktif</div>
            <input id="scannerInput" class="mt-5 w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-center text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100" placeholder="Scan QR/barcode lalu Enter" autocomplete="off">
          </div>
        </div>

        <div id="manualWrap" class="hidden">
          <div class="rounded-[2rem] border border-slate-100 bg-slate-50 p-6">
            <div class="font-black text-slate-900">Input Manual QR/NIS</div>
            <div class="mt-4 flex gap-3">
              <input id="manualToken" class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-100" placeholder="Token QR atau NIS" autocomplete="off">
              <button id="btnManual" type="button" class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white">Kirim</button>
            </div>
          </div>
        </div>
      </x-ui.card>
    </div>

    <div class="lg:col-span-5">
      <x-ui.card>
        <div class="mb-5">
          <div class="text-lg font-black text-slate-900">Hasil Scan</div>
          <div class="text-sm font-medium text-slate-500">Identitas siswa dan status absensi tampil di sini.</div>
        </div>

        <div id="alertBox" class="mb-4 hidden rounded-2xl px-4 py-3 text-sm font-bold"></div>

        <div id="resultBox" class="rounded-[2rem] border border-dashed border-slate-200 bg-slate-50 p-8 text-center">
          <i class="bi bi-qr-code-scan text-4xl text-slate-400"></i>
          <div class="mt-4 text-sm font-black text-slate-700">Belum ada scan</div>
          <div class="mt-1 text-sm text-slate-400">Scan QR siswa untuk {{ $selectedExtracurricular->name }}.</div>
        </div>

        <audio id="beepSound" src="{{ asset('sounds/beep.mp3') }}" preload="auto"></audio>
      </x-ui.card>
    </div>
  </div>

  <div class="mt-6">
    <x-ui.card>
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
          <div class="text-lg font-black text-slate-900">Absensi {{ $selectedExtracurricular->name }} Hari Ini</div>
          <div class="text-sm font-medium text-slate-500">{{ $today }} • Maksimal 50 scan terbaru</div>
        </div>
        <button type="button" onclick="window.location.reload()" class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700">
          <i class="bi bi-arrow-clockwise"></i>
          Muat Ulang
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-xs font-black uppercase tracking-wide text-slate-400">
              <th class="px-3 py-3">Siswa</th>
              <th class="px-3 py-3">Jenjang/Kelas</th>
              <th class="px-3 py-3">Jam Scan</th>
              <th class="px-3 py-3">Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($todayAttendances as $attendance)
              <tr class="border-b border-slate-100">
                <td class="px-3 py-3">
                  <div class="font-black text-slate-800">{{ $attendance->student?->name ?? 'Siswa dihapus' }}</div>
                  <div class="text-xs font-semibold text-slate-400">NIS {{ $attendance->student?->nis ?? $attendance->nis_snapshot }}</div>
                </td>
                <td class="px-3 py-3 font-bold text-slate-600">
                  {{ match($attendance->level_snapshot) { 'mts' => 'MTs', 'ma' => 'MA', 'mts_ma' => 'MTs & MA', default => '-' } }} / {{ $attendance->class_name_snapshot ?: '-' }}
                </td>
                <td class="px-3 py-3 font-black text-slate-700">{{ $attendance->scanned_at?->format('H:i:s') ?? '-' }}</td>
                <td class="px-3 py-3">
                  <span class="rounded-full px-3 py-1 text-xs font-black {{ $attendance->status === 'terlambat' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ $attendance->status === 'terlambat' ? 'Terlambat' : 'Hadir' }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="px-4 py-10 text-center font-bold text-slate-400">Belum ada scan {{ $selectedExtracurricular->name }} hari ini.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </x-ui.card>
  </div>

  <script src="https://unpkg.com/html5-qrcode" defer></script>
@endif
@endsection

@if($selectedExtracurricular)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const csrf = @json(csrf_token());
  const scanUrl = @json(route('school-extracurricular-attendance.store', $institution));
  const extracurricularId = @json($selectedExtracurricular->id);
  const defaultPhoto = @json(asset('images/default.jpg'));

  const alertBox = document.getElementById('alertBox');
  const resultBox = document.getElementById('resultBox');
  const btnStart = document.getElementById('btnStart');
  const btnStop = document.getElementById('btnStop');
  const scannerInput = document.getElementById('scannerInput');
  const manualToken = document.getElementById('manualToken');
  const btnManual = document.getElementById('btnManual');
  const cameraWrap = document.getElementById('cameraWrap');
  const scannerWrap = document.getElementById('scannerWrap');
  const manualWrap = document.getElementById('manualWrap');
  const beep = document.getElementById('beepSound');

  let html5QrCode = null;
  let isScanning = false;
  let lock = false;
  let lastKey = null;

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function showAlert(type, message) {
    const classes = {
      success: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
      danger: 'bg-red-50 text-red-700 border border-red-200',
      warning: 'bg-amber-50 text-amber-700 border border-amber-200',
      info: 'bg-blue-50 text-blue-700 border border-blue-200',
      secondary: 'bg-slate-100 text-slate-600 border border-slate-200',
    };

    alertBox.className = `mb-4 rounded-2xl px-4 py-3 text-sm font-bold ${classes[type] ?? classes.info}`;
    alertBox.textContent = message;
    alertBox.classList.remove('hidden');
  }

  function speak(text) {
    if (!('speechSynthesis' in window)) return;
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID';
    utterance.rate = 0.95;
    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utterance);
  }

  function playBeep() {
    if (!beep) return;
    beep.currentTime = 0;
    beep.play().catch(() => {});
  }

  async function sendToken(rawToken) {
    const token = String(rawToken ?? '').trim();
    if (!token || lock) return;

    const key = `${extracurricularId}:${token}`;
    if (lastKey === key) return;

    lock = true;
    lastKey = key;
    setTimeout(() => {
      if (lastKey === key) lastKey = null;
    }, 2500);

    try {
      const response = await fetch(scanUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
        },
        body: JSON.stringify({ token, extracurricular_id: extracurricularId }),
      });

      const json = await response.json();

      if (!response.ok || !json.ok) {
        const message = json.message ?? 'QR/NIS tidak valid.';
        showAlert('danger', message);
        speak(message);
        resultBox.innerHTML = `
          <div class="rounded-[2rem] bg-red-50 p-6 text-center text-red-700">
            <i class="bi bi-x-circle text-4xl"></i>
            <div class="mt-3 text-sm font-black">${escapeHtml(message)}</div>
          </div>`;
        return;
      }

      const warning = json.status === 'terlambat';
      const photo = json.student.photo_url ?? defaultPhoto;
      const tone = warning ? 'from-amber-500 to-orange-400' : 'from-emerald-600 to-lime-500';

      showAlert(json.already ? 'warning' : 'success', json.message ?? 'Berhasil.');
      playBeep();
      speak(`${json.student.name}, ${json.status_label}`);
      if (navigator.vibrate) navigator.vibrate(80);

      resultBox.innerHTML = `
        <div class="rounded-[2rem] bg-gradient-to-br ${tone} p-5 text-white shadow-xl">
          <div class="flex items-start gap-4 text-left">
            <img src="${escapeHtml(photo)}" class="h-20 w-20 rounded-3xl object-cover ring-4 ring-white/70 shadow-xl" alt="Foto siswa">
            <div class="min-w-0 flex-1">
              <div class="text-xl font-black leading-tight">${escapeHtml(json.student.name)}</div>
              <div class="mt-1 text-sm font-bold text-white/80">NIS: ${escapeHtml(json.student.nis)}</div>
              <div class="mt-3 flex flex-wrap gap-2">
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black">${escapeHtml(json.student.level ?? '-')}</span>
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black">Kelas ${escapeHtml(json.student.class_name ?? '-')}</span>
              </div>
            </div>
          </div>
          <div class="mt-5 grid grid-cols-2 gap-3 rounded-2xl bg-white p-4 text-slate-900">
            <div>
              <div class="text-xs font-black uppercase text-slate-400">Kegiatan</div>
              <div class="mt-1 font-black">${escapeHtml(json.extracurricular.name)}</div>
            </div>
            <div class="text-right">
              <div class="text-xs font-black uppercase text-slate-400">Jam</div>
              <div class="mt-1 font-black">${escapeHtml(json.scanned_at)}</div>
            </div>
          </div>
          <div class="mt-4 inline-flex rounded-full bg-white px-4 py-2 text-xs font-black ${warning ? 'text-amber-700' : 'text-emerald-700'}">
            ${escapeHtml(json.status_label).toUpperCase()}
          </div>
        </div>`;
    } catch (error) {
      showAlert('danger', 'Koneksi bermasalah. Coba kembali.');
      speak('Koneksi bermasalah');
    } finally {
      setTimeout(() => lock = false, 900);
    }
  }

  async function startScan() {
    if (isScanning) return;
    if (typeof Html5Qrcode === 'undefined') {
      showAlert('danger', 'Library QR belum siap. Muat ulang halaman.');
      return;
    }

    html5QrCode = new Html5Qrcode('reader');

    try {
      const devices = await Html5Qrcode.getCameras();
      if (!devices || devices.length === 0) {
        showAlert('danger', 'Kamera tidak ditemukan.');
        return;
      }

      const backCamera = devices.find(device => /back|rear|environment/i.test(device.label));
      const cameraId = backCamera ? backCamera.id : devices[0].id;

      isScanning = true;
      btnStart.disabled = true;
      btnStop.disabled = false;

      await html5QrCode.start(
        { deviceId: { exact: cameraId } },
        { fps: 10, qrbox: { width: 240, height: 240 } },
        decodedText => {
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

      showAlert('info', 'Kamera aktif. Arahkan ke QR siswa.');
    } catch (error) {
      isScanning = false;
      btnStart.disabled = false;
      btnStop.disabled = true;
      showAlert('danger', 'Kamera gagal dibuka. Pastikan izin kamera aktif.');
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
    showAlert('secondary', 'Kamera dihentikan.');
  }

  function showMode(mode) {
    cameraWrap.classList.toggle('hidden', mode !== 'camera');
    scannerWrap.classList.toggle('hidden', mode !== 'scanner');
    manualWrap.classList.toggle('hidden', mode !== 'manual');

    if (mode !== 'camera' && isScanning) stopScan();
    if (mode === 'scanner') setTimeout(() => scannerInput.focus(), 150);
    if (mode === 'manual') setTimeout(() => manualToken.focus(), 150);
  }

  document.getElementById('modeCamera').addEventListener('change', event => event.target.checked && showMode('camera'));
  document.getElementById('modeScanner').addEventListener('change', event => event.target.checked && showMode('scanner'));
  document.getElementById('modeManual').addEventListener('change', event => event.target.checked && showMode('manual'));

  btnStart.addEventListener('click', startScan);
  btnStop.addEventListener('click', stopScan);

  scannerInput.addEventListener('keydown', event => {
    if (event.key !== 'Enter') return;
    const token = scannerInput.value.trim();
    scannerInput.value = '';
    sendToken(token);
  });

  btnManual.addEventListener('click', () => {
    const token = manualToken.value.trim();
    if (!token) {
      showAlert('warning', 'QR/NIS masih kosong.');
      return;
    }
    manualToken.value = '';
    sendToken(token);
  });

  manualToken.addEventListener('keydown', event => {
    if (event.key === 'Enter') btnManual.click();
  });

  showAlert('secondary', 'Pilih Start Kamera atau gunakan scanner/manual.');
});
</script>
@endpush
@endif
