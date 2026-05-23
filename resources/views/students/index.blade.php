@extends('layouts.app')
@section('title','Data Santri')

@section('content')
<style>
  .student-hero {
    background: linear-gradient(135deg, #0f766e, #16a34a);
    border-radius: 22px;
    padding: 18px;
    color: #fff;
    box-shadow: 0 12px 30px rgba(15, 118, 110, .25);
  }

  .student-filter {
    border: 0;
    border-radius: 18px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .07);
  }

  .student-card {
    border: 0;
    border-radius: 20px;
    box-shadow: 0 10px 26px rgba(15, 23, 42, .08);
    overflow: hidden;
  }

  .student-avatar {
    width: 44px;
    height: 44px;
    border-radius: 16px;
    background: linear-gradient(135deg, #dcfce7, #ccfbf1);
    color: #0f766e;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    flex: 0 0 auto;
  }

  .student-mobile-card {
    border: 1px solid #eef2f7;
    border-radius: 18px;
    padding: 14px;
    background: #fff;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
  }

  .action-pill {
    border-radius: 999px;
    padding: 6px 10px;
  }

  .soft-badge {
    border-radius: 999px;
    padding: 5px 9px;
    font-size: 11px;
    font-weight: 700;
  }

  .student-pagination nav {
  display: flex;
  justify-content: center;
}

.student-pagination svg {
  width: 16px !important;
  height: 16px !important;
}

.student-pagination .pagination {
  gap: 4px;
  flex-wrap: wrap;
  justify-content: center;
}

.student-pagination .page-link {
  border-radius: 10px;
  min-width: 34px;
  text-align: center;
  font-size: 13px;
}

  @media (max-width: 767.98px) {
    .desktop-table {
      display: none;
    }
  }

  @media (min-width: 768px) {
    .mobile-list {
      display: none;
    }
  }
</style>

<div class="student-hero mb-3">
  <div class="d-flex align-items-start justify-content-between gap-3">
    <div>
      <div class="small opacity-75 mb-1">Master Data</div>
      <h4 class="fw-bold mb-1">Data Santri</h4>
      <div class="small opacity-75">Kelola data santri dan QR token</div>
    </div>

    <a href="{{ route('students.create') }}" class="btn btn-light btn-sm fw-semibold rounded-pill">
      + Tambah
    </a>
  </div>
</div>

<form class="card student-filter p-3 mb-3" method="GET" action="{{ route('students.index') }}">
  <div class="row g-2">
    <div class="col-12">
      <input name="q" value="{{ $q }}" class="form-control form-control-sm rounded-pill"
             placeholder="Cari NIS atau nama santri...">
    </div>

    <div class="col-6">
      <select name="kelas" class="form-select form-select-sm rounded-pill">
        <option value="">Semua Kelas</option>
        @foreach($kelasList as $k)
          <option value="{{ $k }}" @selected($kelas===$k)>{{ $k }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-6">
      <select name="kamar" class="form-select form-select-sm rounded-pill">
        <option value="">Semua Kamar</option>
        @foreach($kamarList as $km)
          <option value="{{ $km }}" @selected($kamar===$km)>{{ $km }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-8 d-grid">
      <button class="btn btn-primary btn-sm rounded-pill fw-semibold">Filter</button>
    </div>

    <div class="col-4 d-grid">
      <a href="{{ route('students.index') }}" class="btn btn-light btn-sm rounded-pill">Reset</a>
    </div>
  </div>
</form>

{{-- Mobile Premium Card View --}}
<div class="mobile-list">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="fw-semibold small">Daftar Santri</div>
    <div class="text-muted small">{{ $students->total() }} data</div>
  </div>

  <div class="d-grid gap-2">
    @forelse($students as $s)
      <div class="student-mobile-card">
        <div class="d-flex gap-3">
        <img src="{{ $s->photoUrl() }}"
            class="student-avatar"
            style="object-fit:cover;"
            alt="{{ $s->name }}">

          <div class="flex-grow-1 min-w-0">
            <div class="d-flex justify-content-between gap-2">
              <div>
                <div class="fw-bold text-truncate">{{ $s->name }}</div>
                <div class="text-muted small">NIS: {{ $s->nis }}</div>
              </div>

              @if($s->is_active)
                <span class="soft-badge bg-success-subtle text-success">Aktif</span>
              @else
                <span class="soft-badge bg-secondary-subtle text-secondary">Nonaktif</span>
              @endif
            </div>

            <div class="d-flex flex-wrap gap-1 mt-2">
              <span class="soft-badge bg-light text-dark border">Kelas: {{ $s->kelas ?? '-' }}</span>
              <span class="soft-badge bg-light text-dark border">Kamar: {{ $s->kamar ?? '-' }}</span>
            </div>

            <div class="d-flex gap-2 mt-3">
              <a class="btn btn-outline-primary btn-sm action-pill flex-fill"
                 href="{{ route('students.show',$s) }}">
                Detail
              </a>

              <a class="btn btn-outline-secondary btn-sm action-pill flex-fill"
                 href="{{ route('students.edit',$s) }}">
                Edit
              </a>

              <form method="POST" action="{{ route('students.destroy',$s) }}"
                    onsubmit="return confirm('Hapus santri ini?')" class="flex-fill">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger btn-sm action-pill w-100">
                  Hapus
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="student-mobile-card text-center text-muted py-4">
        Belum ada data santri.
      </div>
    @endforelse
  </div>
</div>

{{-- Desktop Table View --}}
<div class="card student-card desktop-table">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 table-sm small">
      <thead class="table-light">
        <tr>
          <th style="width:110px;">NIS</th>
          <th>Nama</th>
          <th style="width:90px;">Kelas</th>
          <th style="width:110px;">Kamar</th>
          <th style="width:90px;">Status</th>
          <th class="text-center" style="width:160px;">Aksi</th>
        </tr>
      </thead>

      <tbody>
        @forelse($students as $s)
          <tr>
            <td class="fw-semibold">{{ $s->nis }}</td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="student-avatar" style="width:34px;height:34px;border-radius:12px;">
                  {{ strtoupper(substr($s->name, 0, 1)) }}
                </div>
                <div class="fw-semibold">{{ $s->name }}</div>
              </div>
            </td>
            <td>{{ $s->kelas ?? '-' }}</td>
            <td>{{ $s->kamar ?? '-' }}</td>
            <td>
              @if($s->is_active)
                <span class="soft-badge bg-success-subtle text-success">Aktif</span>
              @else
                <span class="soft-badge bg-secondary-subtle text-secondary">Nonaktif</span>
              @endif
            </td>
            <td class="text-center">
              <div class="btn-group btn-group-sm">
                <a class="btn btn-outline-primary" href="{{ route('students.show',$s) }}">👁</a>
                <a class="btn btn-outline-secondary" href="{{ route('students.edit',$s) }}">✏️</a>
                <form method="POST" action="{{ route('students.destroy',$s) }}"
                      onsubmit="return confirm('Hapus santri ini?')" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-outline-danger">🗑</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center text-muted py-4">Belum ada data.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="student-pagination mt-3 mb-5">
  {{ $students->links('pagination::bootstrap-5') }}
</div>
@endsection