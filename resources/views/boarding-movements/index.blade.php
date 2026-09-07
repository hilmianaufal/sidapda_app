@extends('layouts.app')

@section('title', 'Pulang & Kembali Pondok')
@section('mobile_title', 'Pulang Pondok')

@section('content')
<x-ui.page-header
  title="Pulang & Kembali Pondok"
  subtitle="Scan QR untuk menonaktifkan atau mengaktifkan kembali kewajiban santri"
  icon="bi-house-door"
>
  <x-slot:actions>
    <x-ui.button :href="route('dashboard.institution', 'ponpes')" variant="secondary">
      <i class="bi bi-speedometer2"></i>
      Dashboard Pondok
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<div class="mb-6 grid grid-cols-2 gap-4">
  <x-ui.stat-card label="Sedang Pulang" :value="$awayCount" icon="bi-box-arrow-right" tone="amber" />
  <x-ui.stat-card label="Kembali Hari Ini" :value="$returnedToday" icon="bi-box-arrow-in-left" tone="emerald" />
</div>

<div class="grid gap-6 xl:grid-cols-12">
  <div class="xl:col-span-7">
    <x-ui.card>
      <div class="mb-5 grid grid-cols-2 gap-3 rounded-2xl bg-slate-100 p-2">
        <button type="button" id="modeDepart" class="movement-button rounded-xl bg-amber-500 px-4 py-3 text-sm font-black text-white shadow">
          <i class="bi bi-box-arrow-right"></i>
          Scan Pulang
        </button>
        <button type="button" id="modeReturn" class="movement-button rounded-xl px-4 py-3 text-sm font-black text-slate-500">
          <i class="bi bi-box-arrow-in-left"></i>
          Scan Kembali
        </button>
      </div>

      <div id="movementInfo" class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-700">
        Mode pulang aktif. Setelah berhasil, kewajiban salat dan kegiatan siswa berhenti sementara.
      </div>

      <div class="mb-5 grid gap-4 sm:grid-cols-2">
        <x-ui.form-group label="Alasan Pulang">
          <x-ui.input id="reason" name="reason" placeholder="Contoh: izin keluarga" />
        </x-ui.form-group>
        <x-ui.form-group label="Catatan">
          <x-ui.input id="notes" name="notes" placeholder="Opsional" />
        </x-ui.form-group>
      </div>

      <div class="rounded-[2rem] bg-gradient-to-br from-slate-900 to-emerald-950 p-4 shadow-xl">
        <div class="overflow-hidden rounded-[1.5rem] bg-black">
          <div id="reader" class="mx-auto w-full max-w-[440px]"></div>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-2 gap-3">
        <button type="button" id="btnStart" class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">
          <i class="bi bi-camera"></i>
          Mulai Kamera
        </button>
        <button type="button" id="btnStop" disabled class="rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-black text-red-600 disabled:opacity-50">
          Stop Kamera
        </button>
      </div>

      <div class="my-5 flex items-center gap-3 text-xs font-black uppercase tracking-widest text-slate-300">
        <div class="h-px flex-1 bg-slate-200"></div>
        Scanner USB / Manual
        <div class="h-px flex-1 bg-slate-200"></div>
      </div>

      <div class="flex gap-3">
        <input id="tokenInput" autocomplete="off" class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" placeholder="Scan atau tempel token QR...">
        <button type="button" id="btnSubmit" class="rounded-2xl bg-slate-800 px-5 py-3 text-sm font-black text-white">
          Proses
        </button>
      </div>
    </x-ui.card>
  </div>

  <div class="xl:col-span-5">
    <x-ui.card>
      <div class="mb-4">
        <div class="text-lg font-black text-slate-900">Hasil Scan</div>
        <div class="text-sm font-medium text-slate-500">Status siswa akan tampil di sini.</div>
      </div>

      <div id="alertBox" class="mb-4 hidden rounded-2xl px-4 py-3 text-sm font-bold"></div>
      <div id="resultBox" class="rounded-[2rem] border border-dashed border-slate-200 bg-slate-50 p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-2xl text-slate-400 shadow">
          <i class="bi bi-qr-code"></i>
        </div>
        <div class="mt-4 text-sm font-black text-slate-700">Belum ada scan</div>
        <div class="mt-1 text-sm text-slate-400">Pilih mode pulang atau kembali, lalu scan QR siswa.</div>
      </div>
    </x-ui.card>
  </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
  <x-ui.card padding="p-0">
    <div class="border-b border-slate-100 p-5">
      <div class="text-lg font-black text-slate-900">Santri Sedang Pulang</div>
      <div class="text-sm font-medium text-slate-500">Tidak dihitung dalam kewajiban Pondok selama status ini aktif.</div>
    </div>
    <div class="divide-y divide-slate-100">
      @forelse($activeLeaves as $leave)
        <div class="flex items-center justify-between gap-3 p-4">
          <div>
            <div class="font-black text-slate-900">{{ $leave->student->name }}</div>
            <div class="text-xs font-semibold text-slate-500">
              {{ $leave->student->nis }} • {{ $leave->student->kelas ?? '-' }} • {{ $leave->student->kamar ?? '-' }}
            </div>
          </div>
          <div class="text-right">
            <x-ui.badge tone="amber">Pulang</x-ui.badge>
            <div class="mt-1 text-xs font-bold text-slate-400">{{ $leave->departed_at->format('d-m-Y H:i') }}</div>
          </div>
        </div>
      @empty
        <div class="p-8 text-center text-sm font-bold text-slate-400">Tidak ada santri yang sedang pulang.</div>
      @endforelse
    </div>
    @if($activeLeaves->hasPages())
      <div class="border-t border-slate-100 p-4">{{ $activeLeaves->links() }}</div>
    @endif
  </x-ui.card>

  <x-ui.card padding="p-0">
    <div class="border-b border-slate-100 p-5">
      <div class="text-lg font-black text-slate-900">Riwayat Kembali Terbaru</div>
      <div class="text-sm font-medium text-slate-500">Sepuluh scan kembali terakhir.</div>
    </div>
    <div class="divide-y divide-slate-100">
      @forelse($recentReturns as $leave)
        <div class="flex items-center justify-between gap-3 p-4">
          <div>
            <div class="font-black text-slate-900">{{ $leave->student->name }}</div>
            <div class="text-xs font-semibold text-slate-500">Pulang {{ $leave->departed_at->format('d-m-Y H:i') }}</div>
          </div>
          <div class="text-right">
            <x-ui.badge tone="emerald">Kembali</x-ui.badge>
            <div class="mt-1 text-xs font-bold text-slate-400">{{ $leave->returned_at->format('d-m-Y H:i') }}</div>
          </div>
        </div>
      @empty
        <div class="p-8 text-center text-sm font-bold text-slate-400">Belum ada riwayat kembali.</div>
      @endforelse
    </div>
  </x-ui.card>
</div>

<script src="https://unpkg.com/html5-qrcode" defer></script>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const endpoint = @json(route('boarding-movements.store'));
  const csrf = @json(csrf_token());
  const defaultPhoto = @json(asset('images/default.jpg'));
  const departButton = document.getElementById('modeDepart');
  const returnButton = document.getElementById('modeReturn');
  const movementInfo = document.getElementById('movementInfo');
  const reason = document.getElementById('reason');
  const notes = document.getElementById('notes');
  const tokenInput = document.getElementById('tokenInput');
  const alertBox = document.getElementById('alertBox');
  const resultBox = document.getElementById('resultBox');
  const btnStart = document.getElementById('btnStart');
  const btnStop = document.getElementById('btnStop');
  const btnSubmit = document.getElementById('btnSubmit');

  let movement = 'depart';
  let scanner = null;
  let scanning = false;
  let locked = false;
  let lastToken = null;

  const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;').replaceAll("'", '&#039;');

  function setMovement(next) {
    movement = next;
    const depart = next === 'depart';
    departButton.className = `movement-button rounded-xl px-4 py-3 text-sm font-black ${depart ? 'bg-amber-500 text-white shadow' : 'text-slate-500'}`;
    returnButton.className = `movement-button rounded-xl px-4 py-3 text-sm font-black ${depart ? 'text-slate-500' : 'bg-emerald-600 text-white shadow'}`;
    reason.closest('div').classList.toggle('opacity-50', !depart);
    notes.closest('div').classList.toggle('opacity-50', !depart);
    movementInfo.className = `mb-5 rounded-2xl border p-4 text-sm font-bold ${depart ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`;
    movementInfo.textContent = depart
      ? 'Mode pulang aktif. Setelah berhasil, kewajiban salat dan kegiatan siswa berhenti sementara.'
      : 'Mode kembali aktif. Setelah berhasil, seluruh kewajiban siswa aktif lagi.';
    lastToken = null;
    tokenInput.focus();
  }

  function showAlert(ok, message) {
    alertBox.className = `mb-4 rounded-2xl border px-4 py-3 text-sm font-bold ${ok ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'}`;
    alertBox.textContent = message;
  }

  async function submitToken(rawToken) {
    const token = String(rawToken ?? '').trim();
    if (!token || locked || token === lastToken) return;
    locked = true;

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          token,
          movement,
          reason: movement === 'depart' ? reason.value.trim() : null,
          notes: movement === 'depart' ? notes.value.trim() : null
        })
      });
      const data = await response.json();

      if (!response.ok || !data.ok) {
        showAlert(false, data.message ?? 'Scan gagal diproses.');
        return;
      }

      lastToken = token;
      showAlert(true, data.message ?? 'Berhasil.');
      const returned = data.movement === 'return';
      resultBox.innerHTML = `
        <div class="rounded-[2rem] ${returned ? 'bg-emerald-600' : 'bg-amber-500'} p-5 text-left text-white shadow-xl">
          <div class="flex items-start gap-4">
            <img src="${escapeHtml(data.student.photo_url || defaultPhoto)}" class="h-20 w-20 rounded-3xl object-cover ring-4 ring-white/70" alt="Foto siswa">
            <div class="min-w-0 flex-1">
              <div class="text-xl font-black">${escapeHtml(data.student.name)}</div>
              <div class="mt-1 text-sm font-bold text-white/80">NIS: ${escapeHtml(data.student.nis)}</div>
              <div class="mt-3 text-sm font-black">${returned ? 'SUDAH KEMBALI' : 'SEDANG PULANG'}</div>
            </div>
          </div>
          <div class="mt-4 rounded-2xl bg-white/20 p-3 text-sm font-bold">
            ${returned ? `Kembali: ${escapeHtml(data.returned_at)}` : `Pulang: ${escapeHtml(data.departed_at)}`}
          </div>
        </div>`;

      tokenInput.value = '';
      if (!data.already) setTimeout(() => window.location.reload(), 1400);
    } catch (error) {
      showAlert(false, 'Koneksi bermasalah. Silakan coba lagi.');
    } finally {
      setTimeout(() => { locked = false; }, 900);
    }
  }

  async function startCamera() {
    if (scanning || typeof Html5Qrcode === 'undefined') {
      if (typeof Html5Qrcode === 'undefined') showAlert(false, 'Library kamera belum siap. Refresh halaman.');
      return;
    }

    scanner = new Html5Qrcode('reader');
    try {
      scanning = true;
      btnStart.disabled = true;
      btnStop.disabled = false;
      await scanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 240, height: 240 } },
        (decodedText) => {
          let token = decodedText;
          try {
            if (decodedText.startsWith('http')) {
              token = new URL(decodedText).searchParams.get('token') || decodedText;
            }
          } catch {}
          submitToken(token);
        }
      );
    } catch (error) {
      scanning = false;
      btnStart.disabled = false;
      btnStop.disabled = true;
      showAlert(false, 'Kamera tidak dapat dibuka. Periksa izin kamera.');
    }
  }

  async function stopCamera() {
    if (!scanner || !scanning) return;
    try { await scanner.stop(); await scanner.clear(); } catch {}
    scanning = false;
    btnStart.disabled = false;
    btnStop.disabled = true;
  }

  departButton.addEventListener('click', () => setMovement('depart'));
  returnButton.addEventListener('click', () => setMovement('return'));
  btnStart.addEventListener('click', startCamera);
  btnStop.addEventListener('click', stopCamera);
  btnSubmit.addEventListener('click', () => submitToken(tokenInput.value));
  tokenInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      submitToken(tokenInput.value);
    }
  });

  tokenInput.focus();
});
</script>
@endpush
