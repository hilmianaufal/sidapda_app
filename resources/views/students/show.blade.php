@extends('layouts.app')
@section('title','Detail Santri')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h4 class="fw-bold mb-0">{{ $student->name }}</h4>
    <div class="text-muted">NIS: {{ $student->nis }}</div>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('students.edit',$student) }}" class="btn btn-outline-secondary">Edit</a>
    <a href="{{ route('students.index') }}" class="btn btn-light">Kembali</a>
  </div>
</div>
<a class="btn btn-outline-primary btn-sm"
   href="{{ route('students.attendance.show', $student) }}">
  Absensi
</a>


<div class="row g-3">
  <div class="col-md-7">
    <div class="card p-3">
      <div class="mb-3">
        <img src="{{ $student->photoUrl() }}"
            class="rounded"
            style="width:120px;height:120px;object-fit:cover;"
            alt="{{ $student->name }}">
      </div>
      <div class="row g-2">
        <div class="col-6">
          <div class="text-muted small">Kelas</div>
          <div class="fw-semibold">{{ $student->kelas ?? '-' }}</div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Kamar</div>
          <div class="fw-semibold">{{ $student->kamar ?? '-' }}</div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Status</div>
          <div class="fw-semibold">
            @if($student->is_active)
              <span class="badge bg-success-subtle text-success">Aktif</span>
            @else
              <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
            @endif
          </div>
        </div>
        <div class="col-12">
          <div class="text-muted small">QR Token (isi QR permanen)</div>
          <div class="fw-semibold font-monospace">{{ $student->qr_token }}</div>
          <div class="text-muted small mt-2">
            *Nanti QR image akan kita generate dari token ini.
          </div>
        </div>
      </div>
    </div>
  </div>

<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="fw-bold">Kartu QR</div>
        <a href="{{ route('students.qr.download', $student) }}"
        class="btn btn-outline-success btn-sm mt-2">
        Download JPG
        </a>
  </div>

  <div class="border rounded p-3 text-center bg-white">
        <img src="{{ route('students.qr.show', $student) }}"
         alt="QR {{ $student->nis }}"
         style="width:240px">
    <div class="mt-2 fw-semibold">{{ $student->name }}</div>
    <div class="text-muted small">{{ $student->nis }}</div>
  </div>

  <div class="text-muted small mt-2">
    Isi QR: <span class="font-monospace">{{ $student->qr_token }}</span>
  </div>
</div>

</div>
@endsection
