@extends('layouts.app')
@section('title','Rekap Kegiatan')

@section('content')

<style>
  .hero-card{
    background: linear-gradient(135deg,#0f766e,#16a34a);
    border-radius:24px;
    padding:20px;
    color:#fff;
    box-shadow:0 14px 35px rgba(15,118,110,.25);
  }

  .soft-card{
    border:0;
    border-radius:22px;
    box-shadow:0 10px 28px rgba(15,23,42,.06);
  }

  .stat-card{
    border-radius:18px;
    padding:16px;
    background:#fff;
    box-shadow:0 8px 20px rgba(15,23,42,.05);
  }

  .attendance-item{
    border:1px solid #eef2f7;
    border-radius:18px;
    padding:14px;
    background:#fff;
    box-shadow:0 8px 18px rgba(15,23,42,.05);
  }

  .avatar-box{
    width:46px;
    height:46px;
    border-radius:16px;
    background:linear-gradient(135deg,#dcfce7,#ccfbf1);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
    color:#0f766e;
    flex-shrink:0;
  }

  .soft-badge{
    border-radius:999px;
    padding:5px 10px;
    font-size:11px;
    font-weight:700;
  }

  .section-title{
    font-size:14px;
    font-weight:700;
    margin-bottom:12px;
  }

  .student-pagination svg{
    width:16px;
    height:16px;
  }

  .student-pagination nav{
    display:flex;
    justify-content:center;
  }

  .student-pagination .pagination{
    gap:4px;
    flex-wrap:wrap;
  }

  .student-pagination .page-link{
    border-radius:10px;
    font-size:13px;
  }
</style>

<div class="hero-card mb-3">
  <div class="d-flex justify-content-between align-items-start gap-3">
    <div>
      <div class="small opacity-75">Monitoring</div>
      <h4 class="fw-bold mb-1">Rekap Kegiatan</h4>
      <div class="small opacity-75">
        Absensi kegiatan keasramaan santri
      </div>
    </div>

    <div class="d-flex flex-column gap-2">
      <a href="{{ route('activities.scan') }}"
         class="btn btn-light btn-sm rounded-pill fw-semibold">
         Scan
      </a>

      <a href="{{ route('activities.recap.export.excel', request()->query()) }}"
         class="btn btn-outline-light btn-sm rounded-pill">
         Excel
      </a>
    </div>
  </div>
</div>

<form class="card soft-card p-3 mb-3" method="GET">

  <div class="row g-2">

    <div class="col-12">
      <label class="small mb-1">Tanggal</label>
      <input type="date"
             name="date"
             class="form-control form-control-sm rounded-pill"
             value="{{ $date }}">
    </div>

    <div class="col-12">
      <label class="small mb-1">Kegiatan</label>

      <select name="activity_id"
              class="form-select form-select-sm rounded-pill">

        @foreach($activities as $a)
          <option value="{{ $a->id }}"
            @selected($selectedActivity && $selectedActivity->id === $a->id)>
            {{ $a->name }}
          </option>
        @endforeach

      </select>
    </div>

    <div class="col-6">
      <label class="small mb-1">Kelas</label>

      <select name="kelas"
              class="form-select form-select-sm rounded-pill">

        <option value="">Semua</option>

        @foreach($kelasList as $k)
          <option value="{{ $k }}" @selected($kelas === $k)>
            {{ $k }}
          </option>
        @endforeach

      </select>
    </div>

    <div class="col-6">
      <label class="small mb-1">Kamar</label>

      <select name="kamar"
              class="form-select form-select-sm rounded-pill">

        <option value="">Semua</option>

        @foreach($kamarList as $km)
          <option value="{{ $km }}" @selected($kamar === $km)>
            {{ $km }}
          </option>
        @endforeach

      </select>
    </div>

    <div class="col-8 d-grid">
      <button class="btn btn-success btn-sm rounded-pill fw-semibold">
        Terapkan
      </button>
    </div>

    <div class="col-4 d-grid">
      <a href="{{ route('activities.recap') }}"
         class="btn btn-light btn-sm rounded-pill">
         Reset
      </a>
    </div>

  </div>
</form>

<div class="row g-2 mb-3">

  <div class="col-6">
    <div class="stat-card">
      <div class="small text-muted">Total</div>
      <div class="fs-5 fw-bold">{{ $totalStudents }}</div>
    </div>
  </div>

  <div class="col-6">
    <div class="stat-card">
      <div class="small text-muted">Hadir</div>
      <div class="fs-5 fw-bold text-success">{{ $hadirCount }}</div>
    </div>
  </div>

  <div class="col-6">
    <div class="stat-card">
      <div class="small text-muted">Telat</div>
      <div class="fs-5 fw-bold text-warning">{{ $terlambatCount }}</div>
    </div>
  </div>

  <div class="col-6">
    <div class="stat-card">
      <div class="small text-muted">Belum</div>
      <div class="fs-5 fw-bold text-danger">{{ $belumCount }}</div>
    </div>
  </div>

</div>

<div class="section-title">
  Sudah Absen
</div>

<div class="d-grid gap-2 mb-4">

@forelse($attendances as $a)

<div class="attendance-item">

  <div class="d-flex gap-3">

    <div class="avatar-box">
      {{ strtoupper(substr($a->student->name,0,1)) }}
    </div>

    <div class="flex-grow-1">

      <div class="d-flex justify-content-between gap-2">

        <div>
          <div class="fw-bold">
            {{ $a->student->name }}
          </div>

          <div class="small text-muted">
            {{ $a->student->nis }}
          </div>
        </div>

        <div class="text-end">

          @if($a->status === 'hadir')
            <span class="soft-badge bg-success-subtle text-success">
              Hadir
            </span>

          @elseif($a->status === 'terlambat')
            <span class="soft-badge bg-warning-subtle text-warning-emphasis">
              Telat
            </span>

          @elseif($a->status === 'izin')
            <span class="soft-badge bg-primary-subtle text-primary">
              Izin
            </span>

          @elseif($a->status === 'sakit')
            <span class="soft-badge bg-danger-subtle text-danger">
              Sakit
            </span>

          @elseif($a->status === 'pulang')
            <span class="soft-badge bg-secondary-subtle text-secondary">
              Pulang
            </span>
          @endif

          <div class="small text-muted mt-1">
            {{ optional($a->scanned_at)->format('H:i') }}
          </div>

        </div>

      </div>

      <div class="d-flex gap-2 mt-2 flex-wrap">
        <span class="soft-badge bg-light text-dark border">
          {{ $a->student->kelas ?? '-' }}
        </span>

        <span class="soft-badge bg-light text-dark border">
          {{ $a->student->kamar ?? '-' }}
        </span>
      </div>

    </div>

  </div>

</div>

@empty

<div class="attendance-item text-center text-muted py-4">
  Belum ada data absensi.
</div>

@endforelse

</div>

<div class="section-title">
  Belum Absen
</div>

<div class="d-grid gap-2">

@forelse($absentStudents as $s)

<div class="attendance-item">

  <div class="d-flex gap-3">

    <div class="avatar-box">
      {{ strtoupper(substr($s->name,0,1)) }}
    </div>

    <div class="flex-grow-1">

      <div class="fw-bold">
        {{ $s->name }}
      </div>

      <div class="small text-muted">
        {{ $s->nis }}
      </div>

      <div class="d-flex gap-2 mt-2 flex-wrap">
        <span class="soft-badge bg-light text-dark border">
          {{ $s->kelas ?? '-' }}
        </span>

        <span class="soft-badge bg-light text-dark border">
          {{ $s->kamar ?? '-' }}
        </span>
      </div>

      <div class="d-flex gap-1 flex-wrap mt-3">

        <form method="POST"
              action="{{ route('activities.recap.mark-status') }}">
          @csrf

          <input type="hidden"
                 name="activity_id"
                 value="{{ $selectedActivity?->id }}">

          <input type="hidden"
                 name="student_id"
                 value="{{ $s->id }}">

          <input type="hidden"
                 name="date"
                 value="{{ $date }}">

          <input type="hidden"
                 name="status"
                 value="izin">

          <button class="btn btn-outline-primary btn-sm rounded-pill">
            Izin
          </button>
        </form>

        <form method="POST"
              action="{{ route('activities.recap.mark-status') }}">
          @csrf

          <input type="hidden"
                 name="activity_id"
                 value="{{ $selectedActivity?->id }}">

          <input type="hidden"
                 name="student_id"
                 value="{{ $s->id }}">

          <input type="hidden"
                 name="date"
                 value="{{ $date }}">

          <input type="hidden"
                 name="status"
                 value="sakit">

          <button class="btn btn-outline-warning btn-sm rounded-pill">
            Sakit
          </button>
        </form>

        <form method="POST"
              action="{{ route('activities.recap.mark-status') }}">
          @csrf

          <input type="hidden"
                 name="activity_id"
                 value="{{ $selectedActivity?->id }}">

          <input type="hidden"
                 name="student_id"
                 value="{{ $s->id }}">

          <input type="hidden"
                 name="date"
                 value="{{ $date }}">

          <input type="hidden"
                 name="status"
                 value="pulang">

          <button class="btn btn-outline-secondary btn-sm rounded-pill">
            Pulang
          </button>
        </form>

      </div>

    </div>

  </div>

</div>

@empty

<div class="attendance-item text-center text-muted py-4">
  Semua santri sudah terdata.
</div>

@endforelse

</div>

@endsection