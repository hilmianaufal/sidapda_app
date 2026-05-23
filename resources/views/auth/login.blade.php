<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login • Absensi Jamaah Pondok Pesantren</title>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#16a34a">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{
      --g1:#0f766e;
      --g2:#16a34a;
    }
    body{
      min-height:100vh;
      background:
        radial-gradient(1000px 700px at 15% 15%, rgba(255,255,255,.18), transparent 55%),
        linear-gradient(135deg, var(--g1), var(--g2));
      display:flex;
      align-items:center;
      justify-content:center;
      padding:20px;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }
    .card{
      border-radius:20px;
      box-shadow:0 30px 80px rgba(0,0,0,.28);
      border:none;
      overflow:hidden;
    }
    .brand{
      background: linear-gradient(135deg, var(--g1), var(--g2));
      color:#fff;
      padding:22px;
      text-align:center;
    }
    .brand svg{ width:42px;height:42px; }
    .brand h1{
      font-size:18px;
      font-weight:800;
      margin:12px 0 4px;
    }
    .brand p{
      font-size:12px;
      opacity:.9;
      margin:0;
    }
    .form-wrap{
      padding:22px;
    }
    .btn-green{
      background: linear-gradient(135deg, var(--g1), var(--g2));
      border:none;
      font-weight:700;
      border-radius:12px;
      padding:10px;
    }
    .logo-app{
      width:72px;
      height:72px;
      object-fit:contain;
      background:#fff;
      border-radius:20px;
      padding:10px;
      box-shadow:0 10px 25px rgba(0,0,0,.15);
    }
  </style>
</head>

<body>
  <div class="card" style="max-width:380px;width:100%;">
    <div class="brand">
    <img src="{{ asset('images/logo.png.png') }}"
        alt="Logo SIDAPDA"
        class="logo-app">

      <h1>SIDAPDA</h1>

      <p>Sistem Informasi Digital Absensi Pondok Darussalam</p>
    </div>

    <div class="form-wrap">
      <h5 class="fw-bold mb-1">Login Petugas</h5>

      <div class="text-muted small mb-3">Masuk untuk mengakses sistem absensi</div>

      @if ($errors->any())
        <div class="alert alert-danger py-2 small">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('login.post') }}">
        @csrf

        <div class="mb-2">
          <label class="form-label small">Email</label>
          <input type="email" name="email" value="{{ old('email') }}"
                 class="form-control form-control-sm" required autofocus>
        </div>

        <div class="mb-3">
          <label class="form-label small">Password</label>
          <input type="password" name="password"
                 class="form-control form-control-sm" required>
        </div>

        <button class="btn btn-green w-100">Masuk</button>
     <button id="btnInstall" class="btn btn-outline-success w-100 mt-2 d-none">
      📲 Install Aplikasi
    </button>
      </form>


    </div>
  </div>

 <script>
let deferredPrompt = null;
const btnInstall = document.getElementById('btnInstall');

// tampilkan tombol dari awal (tidak bergantung event)
btnInstall.classList.remove('d-none');

window.addEventListener('beforeinstallprompt', (e) => {
  // event ini kadang tidak muncul di desktop localhost
  e.preventDefault();
  deferredPrompt = e;
  btnInstall.innerHTML = '📲 Install Aplikasi';
});

btnInstall.addEventListener('click', async () => {
  // Kalau event tersedia, munculkan prompt asli
  if (deferredPrompt) {
    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
    btnInstall.classList.add('d-none');
    return;
  }

  // fallback: kalau event tidak ada, kasih instruksi install manual
  alert('Install via menu browser:\n\nChrome (⋮) → Install app / Add to Home screen\n\nJika di PC: (⋮) → More tools → Create shortcut (centang Open as window).');
});

// register service worker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      await navigator.serviceWorker.register('/sw.js');
      // console.log('SW registered');
    } catch (e) {
      console.error('SW register failed', e);
    }
  });
}
</script>


</body>
</html>
