@csrf

<div class="row g-3">
  <div class="col-md-4">
    <label class="form-label fw-semibold">NIS</label>
    <input name="nis" value="{{ old('nis', $student->nis ?? '') }}" class="form-control" required>
  </div>

  <div class="col-md-8">
    <label class="form-label fw-semibold">Nama</label>
    <input name="name" value="{{ old('name', $student->name ?? '') }}" class="form-control" required>
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold">Kelas</label>
    <input name="kelas" value="{{ old('kelas', $student->kelas ?? '') }}" class="form-control" placeholder="contoh: 7A">
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold">Kamar</label>
    <input name="kamar" value="{{ old('kamar', $student->kamar ?? '') }}" class="form-control" placeholder="contoh: Umar">
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold">Foto</label>

    <img id="preview"
        src="{{ $student?->photoUrl() ?? asset('images/default.jpg') }}"
        style="width:90px;height:90px;object-fit:cover;"
        class="rounded mb-2">

    <input type="file"
          name="photo"
          class="form-control"
          accept="image/*"
          onchange="previewImage(event)">

    @if($student && $student->photo)
      <div class="mt-2">
        <img src="{{ $student->photoUrl() }}"
             class="rounded"
             style="width:90px;height:90px;object-fit:cover;">
      </div>
    @endif
  </div>

  <div class="col-12">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="is_active" value="1"
             @checked(old('is_active', $student->is_active ?? true))>
      <label class="form-check-label">Aktif</label>
    </div>
  </div>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-success">Simpan</button>
    <a href="{{ route('students.index') }}" class="btn btn-light">Batal</a>
  </div>
</div>