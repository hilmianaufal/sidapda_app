@extends('layouts.app')
@section('title','Tambah Santri')

@section('content')
<h4 class="fw-bold mb-3">Tambah Santri</h4>
@if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
<div class="card p-3">
  <form method="POST"
        action="{{ route('students.store') }}"
        enctype="multipart/form-data">

    @include('students._form', ['student' => null])

  </form>
</div>
<script>
function previewImage(event) {
    const reader = new FileReader();

    reader.onload = function () {
        document.getElementById('preview').src = reader.result;
    }

    reader.readAsDataURL(event.target.files[0]);
}
</script>
@endsection