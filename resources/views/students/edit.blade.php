@extends('layouts.app')
@section('title','Edit Santri')

@section('content')
<h4 class="fw-bold mb-3">Edit Santri</h4>

<div class="card p-3">
  <form method="POST"
        action="{{ route('students.update', $student) }}"
        enctype="multipart/form-data">
    @method('PUT')

    @include('students._form', ['student' => $student])

  </form>
</div>
@endsection