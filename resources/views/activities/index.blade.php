@extends('layouts.app')
@section('title','Jadwal Kegiatan')

@section('content')
<style>
  .activity-hero {
    background: linear-gradient(135deg, #0f766e, #16a34a);
    border-radius: 24px;
    padding: 20px;
    color: #fff;
    box-shadow: 0 14px 35px rgba(15, 118, 110, .25);
  }

  .premium-card {
    border: 0;
    border-radius: 22px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .07);
    overflow: hidden;
  }

  .activity-item {
    border: 1px solid #eef2f7;
    border-radius: 20px;
    padding: 15px;
    background: #fff;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
  }

  .activity-icon {
    width: 48px;
    height: 48px;
    border-radius: 17px;
    background: linear-gradient(135deg, #dcfce7, #ccfbf1);
    color: #0f766e;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    flex-shrink: 0;
  }

  .soft-badge {
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 11px;
    font-weight: 700;
  }

  .action-pill {
    border-radius: 999px;
    padding: 6px 12px;
  }

  .info-pill {
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 12px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
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
@endphp

<div class="activity-hero mb-3">
  <div class="d-flex justify-content-between align-items-start gap-3">
    <div>
      <div class="small opacity-75 mb-1">Manajemen Jadwal</div>
      <h4 class="fw-bold mb-1">Jadwal Kegiatan</h4>
      <div class="small opacity-75">
        Atur kegiatan rutin, absen dadakan, check in, dan check out
      </div>
    </div>

    <a href="{{ route('activities.create') }}"
       class="btn btn-light btn-sm rounded-pill fw-semibold">
      + Tambah
    </a>
  </div>

  <div class="row g-2 mt-3">
    <div class="col-4">
      <div class="bg-white bg-opacity-25 rounded-4 p-2 text-center">
        <div class="small opacity-75">Aktif</div>
        <div class="fw-bold fs-5">{{ $totalAktif }}</div>
      </div>
    </div>

    <div class="col-4">
      <div class="bg-white bg-opacity-25 rounded-4 p-2 text-center">
        <div class="small opacity-75">Rutin</div>
        <div class="fw-bold fs-5">{{ $totalRutin }}</div>
      </div>
    </div>

    <div class="col-4">
      <div class="bg-white bg-opacity-25 rounded-4 p-2 text-center">
        <div class="small opacity-75">Manual</div>
        <div class="fw-bold fs-5">{{ $totalManual }}</div>
      </div>
    </div>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success py-2 small rounded-4">
    {{ session('success') }}
  </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-2">
  <div class="fw-semibold small">Daftar Kegiatan</div>

  <a href="{{ route('dashboard') }}"
     class="btn btn-light btn-sm rounded-pill">
    Dashboard
  </a>
</div>

{{-- Mobile Premium Card --}}
<div class="mobile-list d-grid gap-2">
  @forelse($activities as $a)
    <div class="activity-item">
      <div class="d-flex gap-3">
        <div class="activity-icon">
          @if($a->type === 'routine')
            📅
          @else
            ⚡
          @endif
        </div>

        <div class="flex-grow-1 min-w-0">
          <div class="d-flex justify-content-between gap-2 align-items-start">
            <div>
              <div class="fw-bold">{{ $a->name }}</div>

              <div class="small text-muted mt-1">
                {{ substr($a->start_time, 0, 5) }}
                -
                {{ substr($a->end_time, 0, 5) }}
                • telat {{ $a->late_minutes }} menit
              </div>
            </div>

            @if($a->is_active)
              <span class="soft-badge bg-success-subtle text-success">Aktif</span>
            @else
              <span class="soft-badge bg-secondary-subtle text-secondary">Off</span>
            @endif
          </div>

          <div class="d-flex gap-1 flex-wrap mt-2">
            @if($a->type === 'routine')
              <span class="soft-badge bg-primary-subtle text-primary">Rutin</span>

              @foreach(($a->days ?? []) as $d)
                <span class="info-pill">{{ $dayNames[$d] ?? $d }}</span>
              @endforeach
            @else
              <span class="soft-badge bg-warning-subtle text-warning-emphasis">Manual</span>
              <span class="info-pill">
                {{ $a->event_date ? $a->event_date->format('d M Y') : 'Belum ada tanggal' }}
              </span>
            @endif
          </div>

          <div class="d-flex gap-2 mt-3">
            <a href="{{ route('activities.edit', $a) }}"
               class="btn btn-outline-primary btn-sm action-pill flex-fill">
              Edit
            </a>

            <form method="POST"
                  action="{{ route('activities.destroy', $a) }}"
                  onsubmit="return confirm('Hapus kegiatan ini?')"
                  class="flex-fill">
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
    <div class="activity-item text-center text-muted py-4">
      Belum ada kegiatan.
    </div>
  @endforelse
</div>

{{-- Desktop Table --}}
<div class="card premium-card desktop-table">
  <div class="table-responsive">
    <table class="table table-hover table-sm align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th>Kegiatan</th>
          <th>Tipe</th>
          <th>Hari/Tanggal</th>
          <th>Jam</th>
          <th>Telat</th>
          <th>Status</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>

      <tbody>
        @forelse($activities as $a)
          <tr>
            <td class="fw-semibold">
              <div class="d-flex align-items-center gap-2">
                <div class="activity-icon" style="width:34px;height:34px;border-radius:12px;">
                  {{ $a->type === 'routine' ? '📅' : '⚡' }}
                </div>
                <span>{{ $a->name }}</span>
              </div>
            </td>

            <td>
              @if($a->type === 'routine')
                <span class="soft-badge bg-primary-subtle text-primary">Rutin</span>
              @else
                <span class="soft-badge bg-warning-subtle text-warning-emphasis">Manual</span>
              @endif
            </td>

            <td>
              @if($a->type === 'routine')
                <div class="d-flex gap-1 flex-wrap">
                  @foreach(($a->days ?? []) as $d)
                    <span class="info-pill">{{ $dayNames[$d] ?? $d }}</span>
                  @endforeach
                </div>
              @else
                <span class="text-muted">
                  {{ $a->event_date ? $a->event_date->format('d M Y') : '-' }}
                </span>
              @endif
            </td>

            <td class="text-muted">
              {{ substr($a->start_time, 0, 5) }}
              -
              {{ substr($a->end_time, 0, 5) }}
            </td>

            <td class="text-muted">{{ $a->late_minutes }} menit</td>

            <td>
              @if($a->is_active)
                <span class="soft-badge bg-success-subtle text-success">Aktif</span>
              @else
                <span class="soft-badge bg-secondary-subtle text-secondary">Off</span>
              @endif
            </td>

            <td class="text-end">
              <a href="{{ route('activities.edit', $a) }}"
                 class="btn btn-outline-primary btn-sm rounded-pill">
                Edit
              </a>

              <form method="POST"
                    action="{{ route('activities.destroy', $a) }}"
                    class="d-inline"
                    onsubmit="return confirm('Hapus kegiatan ini?')">
                @csrf
                @method('DELETE')

                <button class="btn btn-outline-danger btn-sm rounded-pill">
                  Hapus
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">
              Belum ada kegiatan.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection