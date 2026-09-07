@extends('layouts.app')

@section('title','Data Santri')
@section('mobile_title','Santri')

@section('content')

@php($isMadadInstitution = $institutions->firstWhere('id', (int) $institutionId)?->code === 'madad')

<x-ui.page-header
  title="Data Santri"
  subtitle="Kelola data santri & QR code"
  icon="bi-people"
>
  <x-slot:actions>
    <x-ui.button :href="route('students.promotions.index')" variant="secondary">
      <i class="bi bi-arrow-up-circle"></i>
      Kenaikan Kelas
    </x-ui.button>
    <x-ui.button :href="route('students.import.form', array_filter(['institution_id' => $institutionId]))" variant="secondary">
      <i class="bi bi-file-earmark-spreadsheet"></i>
      Import
    </x-ui.button>
    @if($institutionId)
      <x-ui.button
        :href="route('students.export.excel', ['institution_id' => $institutionId, 'institution_class' => $institutionClass])"
        variant="secondary">
        <i class="bi bi-file-earmark-excel"></i>
        Export
      </x-ui.button>
    @endif
    <x-ui.button :href="route('students.create', array_filter(['institution_id' => $institutionId]))">
      <i class="bi bi-plus-lg"></i>
      Tambah
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>
@if(session('success'))
  <div class="mb-6 rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-black text-emerald-700">
    <i class="bi bi-check-circle"></i>
    {{ session('success') }}
  </div>
@endif
@if(session('error'))
  <div class="mb-6 rounded-[1.5rem] border border-red-200 bg-red-50 px-5 py-4 text-sm font-black text-red-700">
    <i class="bi bi-exclamation-triangle"></i>
    {{ session('error') }}
  </div>
@endif
<x-ui.card class="mb-6">
  <form method="GET" id="studentFilterForm">
    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Cari
        </label>
        <x-ui.input
          id="studentSearchInput"
          name="q"
          :value="$q"
          placeholder="Ketik nama / NIS..." />
      </div>

      @if($canViewBoardingData)
        <div>
          <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
            Jenjang Pondok
          </label>
          <x-ui.input
            id="studentKelasFilter"
            name="kelas"
            :value="$kelas"
            placeholder="Contoh: SMP" />
        </div>
      @endif

      @if($canViewBoardingData)
        <div>
          <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
            Kamar
          </label>
          <x-ui.input
            id="studentKamarFilter"
            name="kamar"
            :value="$kamar"
            placeholder="Contoh: Umar" />
        </div>
      @endif
      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Jenis Santri
        </label>
        <x-ui.select id="studentGenderFilter" name="gender">
          <option value="">Semua</option>
          <option value="putra" @selected($gender === 'putra')>Putra</option>
          <option value="putri" @selected($gender === 'putri')>Putri</option>
        </x-ui.select>
      </div>

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Lembaga
        </label>
        <x-ui.select id="studentInstitutionFilter" name="institution_id">
          <option value="">Semua lembaga</option>
          @foreach($institutions as $institution)
            <option value="{{ $institution->id }}" @selected($institutionId == $institution->id)>
              {{ $institution->short_name }}
            </option>
          @endforeach
        </x-ui.select>
      </div>

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Kelas Lembaga
        </label>
        <x-ui.select id="studentInstitutionClassFilter" name="institution_class">
          <option value="">Semua kelas</option>
          @foreach($institutionClasses as $className)
            <option value="{{ $className }}" @selected($institutionClass === $className)>
              {{ $className }}
            </option>
          @endforeach
        </x-ui.select>
      </div>


      <div class="flex items-end gap-2">
        <x-ui.button type="submit" class="flex-1">
          <i class="bi bi-search"></i>
          Filter
        </x-ui.button>

        <x-ui.button :href="route('students.index')" variant="secondary">
          Reset
        </x-ui.button>
      </div>
    </div>
  </form>
</x-ui.card>

{{-- Desktop Table --}}
<div class="hidden lg:block">
  <x-ui.card padding="p-0">
    <div class="w-full overflow-x-auto">
      <table class="w-full min-w-[720px]">
        <thead class="border-b border-slate-100 bg-slate-50">
          <tr class="text-left text-xs font-black uppercase tracking-wide text-slate-400">
            <th class="px-6 py-4">Santri</th>
            <th class="px-6 py-4">Jenjang</th>
            <th class="px-6 py-4">Kamar</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Aksi</th>
          </tr>
        </thead>

        <tbody id="studentDesktopRows" class="divide-y divide-slate-100">
          @forelse($students as $student)
            <tr class="transition hover:bg-emerald-50/40">
              <td class="px-6 py-4">
                <div class="flex items-center gap-4">
                  <img
                    src="{{ $student->photoUrl() }}"
                    class="h-14 w-14 rounded-2xl object-cover ring-2 ring-white shadow"
                    alt="{{ $student->name }}">

                  <div class="min-w-0">
                    <div class="truncate font-black text-slate-900">
                      {{ $student->name }}
                    </div>
                          @if($student->gender)
                            <x-ui.badge tone="{{ $student->gender === 'putra' ? 'blue' : 'red' }}">
                                {{ $student->gender === 'putra' ? 'Putra' : 'Putri' }}
                            </x-ui.badge>
                            @endif
                    <div class="mt-1 text-sm font-semibold text-slate-500">
                      {{ $student->nis }}
                    </div>
                  </div>
                </div>
              </td>

              <td class="px-6 py-4">
                @php($studentEnrollment = $student->enrollments->first())
                <x-ui.badge tone="blue">
                  {{ $studentEnrollment
                    ? ($isMadadInstitution
                      ? (($studentEnrollment->level ?: 'Belum ada jenjang').' / Kelas '.($studentEnrollment->class_name ?: '-'))
                      : ($studentEnrollment->class_name ?: ($student->kelas ?: '-')))
                    : ($student->kelas ?: '-') }}
                </x-ui.badge>
              </td>

              <td class="px-6 py-4">
                <x-ui.badge tone="emerald">{{ $canViewBoardingData ? ($student->kamar ?: '-') : '-' }}</x-ui.badge>
              </td>

              <td class="px-6 py-4">
                @if($student->is_active)
                  <x-ui.badge tone="emerald">Aktif</x-ui.badge>
                @else
                  <x-ui.badge tone="red">Nonaktif</x-ui.badge>
                @endif
              </td>

              <td class="px-6 py-4">
                <div class="flex justify-end gap-2">
                  <x-ui.button :href="route('students.show', $student)" variant="secondary">
                    <i class="bi bi-eye"></i>
                  </x-ui.button>

                  <x-ui.button :href="route('students.edit', $student)" variant="secondary">
                    <i class="bi bi-pencil"></i>
                  </x-ui.button>

                  @if(auth()->user()?->hasRole('admin'))
                    <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Hapus santri yang belum terpakai ini?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" title="Hapus" class="inline-flex items-center justify-center rounded-2xl bg-red-50 px-4 py-2 text-sm font-black text-red-600 ring-1 ring-red-100 hover:bg-red-100">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="p-8">
                <x-ui.empty-state
                  title="Belum ada santri"
                  subtitle="Tambahkan data santri baru."
                  icon="bi-people" />
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-ui.card>
</div>

{{-- Mobile Card --}}
<div id="studentMobileList" class="space-y-4 lg:hidden">
  @forelse($students as $student)
    <x-ui.card>
      <div class="flex items-start gap-4">
        <img
          src="{{ $student->photoUrl() }}"
          class="h-16 w-16 rounded-2xl object-cover shadow"
          alt="{{ $student->name }}">

        <div class="min-w-0 flex-1">
          <div class="truncate text-base font-black text-slate-900">
            {{ $student->name }}
          </div>

          <div class="mt-1 text-sm font-semibold text-slate-500">
            {{ $student->nis }}
          </div>

          <div class="mt-3 flex flex-wrap gap-2">
            @php($studentEnrollment = $student->enrollments->first())
            <x-ui.badge tone="blue">
              {{ $studentEnrollment
                ? ($isMadadInstitution
                  ? (($studentEnrollment->level ?: 'Belum ada jenjang').' / Kelas '.($studentEnrollment->class_name ?: '-'))
                  : ($studentEnrollment->class_name ?: ($student->kelas ?: '-')))
                : ($student->kelas ?: '-') }}
            </x-ui.badge>
            <x-ui.badge tone="emerald">{{ $canViewBoardingData ? ($student->kamar ?: '-') : '-' }}</x-ui.badge>

            @if($student->is_active)
              <x-ui.badge tone="emerald">Aktif</x-ui.badge>
            @else
              <x-ui.badge tone="red">Nonaktif</x-ui.badge>
            @endif
          </div>

          <div class="mt-4 flex gap-2">
            <x-ui.button
              :href="route('students.show', $student)"
              variant="secondary"
              class="flex-1">
              Detail
            </x-ui.button>

            <x-ui.button
              :href="route('students.edit', $student)"
              class="flex-1">
              Edit
            </x-ui.button>

            @if(auth()->user()?->hasRole('admin'))
              <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Hapus santri yang belum terpakai ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" title="Hapus" class="inline-flex h-full items-center justify-center rounded-2xl bg-red-50 px-4 text-sm font-black text-red-600 ring-1 ring-red-100 hover:bg-red-100">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            @endif
          </div>
        </div>
      </div>
    </x-ui.card>
  @empty
    <x-ui.empty-state
      title="Belum ada santri"
      subtitle="Tambahkan data santri baru."
      icon="bi-people" />
  @endforelse
</div>

<div id="studentPagination">
  @if($students->hasPages())
    <div class="mt-6">
      {{ $students->links() }}
    </div>
  @endif
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('studentSearchInput');
  const kelasFilter = document.getElementById('studentKelasFilter');
  const kamarFilter = document.getElementById('studentKamarFilter');
  const genderFilter = document.getElementById('studentGenderFilter');
  const institutionFilter = document.getElementById('studentInstitutionFilter');
  const institutionClassFilter = document.getElementById('studentInstitutionClassFilter');
  const desktopRows = document.getElementById('studentDesktopRows');
  const mobileList = document.getElementById('studentMobileList');
  const pagination = document.getElementById('studentPagination');
  const canDeleteStudents = @json(auth()->user()?->hasRole('admin'));
  const canViewBoardingData = @json($canViewBoardingData);
  const isMadadInstitution = @json($isMadadInstitution);
  const csrfToken = @json(csrf_token());

  let timer = null;

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function badge(text, tone = 'emerald') {
    const tones = {
      emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
      blue: 'bg-blue-50 text-blue-700 ring-blue-100',
      red: 'bg-red-50 text-red-700 ring-red-100',
      slate: 'bg-slate-100 text-slate-600 ring-slate-200',
    };

    return `<span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black ring-1 ${tones[tone] ?? tones.emerald}">${escapeHtml(text)}</span>`;
  }

  function renderDesktop(students) {
    if (!desktopRows) return;

    if (!students.length) {
      desktopRows.innerHTML = `
        <tr>
          <td colspan="5" class="p-8 text-center text-sm font-bold text-slate-400">
            Data santri tidak ditemukan.
          </td>
        </tr>
      `;
      return;
    }

    desktopRows.innerHTML = students.map(student => `
      <tr class="transition hover:bg-emerald-50/40">
        <td class="px-6 py-4">
          <div class="flex items-center gap-4">
            <img src="${escapeHtml(student.photo_url)}" class="h-14 w-14 rounded-2xl object-cover ring-2 ring-white shadow">
            <div class="min-w-0">
              <div class="truncate font-black text-slate-900">${escapeHtml(student.name)}</div>
              <div class="mt-1 text-sm font-semibold text-slate-500">${escapeHtml(student.nis)}</div>
            </div>
          </div>
        </td>
        <td class="px-6 py-4">${badge(isMadadInstitution && student.institution_class ? `${student.institution_level ?? 'Belum ada jenjang'} / Kelas ${student.institution_class}` : (student.institution_class ?? student.kelas ?? '-'), 'blue')}</td>
        <td class="px-6 py-4">${badge(canViewBoardingData ? (student.kamar ?? '-') : '-', 'emerald')}</td>
        <td class="px-6 py-4">${student.is_active ? badge('Aktif', 'emerald') : badge('Nonaktif', 'red')}</td>
        <td class="px-6 py-4">
          <div class="flex justify-end gap-2">
            <a href="${escapeHtml(student.show_url)}" class="inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2 text-sm font-black transition active:scale-95 bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
              <i class="bi bi-eye"></i>
            </a>
            <a href="${escapeHtml(student.edit_url)}" class="inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2 text-sm font-black transition active:scale-95 bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
              <i class="bi bi-pencil"></i>
            </a>
            ${canDeleteStudents ? `
              <form method="POST" action="${escapeHtml(student.delete_url)}" onsubmit="return confirm('Hapus santri yang belum terpakai ini?')">
                <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-red-50 px-4 py-2 text-sm font-black text-red-600 ring-1 ring-red-100 hover:bg-red-100"><i class="bi bi-trash"></i></button>
              </form>` : ''}
          </div>
        </td>
      </tr>
    `).join('');
  }

  function renderMobile(students) {
    if (!mobileList) return;

    if (!students.length) {
      mobileList.innerHTML = `
        <div class="rounded-[1.75rem] border border-dashed border-emerald-200 bg-emerald-50/60 p-8 text-center text-sm font-bold text-slate-500">
          Data santri tidak ditemukan.
        </div>
      `;
      return;
    }

    mobileList.innerHTML = students.map(student => `
      <div class="rounded-[1.75rem] border border-white/70 bg-white p-4 shadow-xl shadow-slate-200/70">
        <div class="flex items-start gap-4">
          <img src="${escapeHtml(student.photo_url)}" class="h-16 w-16 rounded-2xl object-cover shadow">
          <div class="min-w-0 flex-1">
            <div class="truncate text-base font-black text-slate-900">${escapeHtml(student.name)}</div>
            <div class="mt-1 text-sm font-semibold text-slate-500">${escapeHtml(student.nis)}</div>

            <div class="mt-3 flex flex-wrap gap-2">
              ${badge(isMadadInstitution && student.institution_class ? `${student.institution_level ?? 'Belum ada jenjang'} / Kelas ${student.institution_class}` : (student.institution_class ?? student.kelas ?? '-'), 'blue')}
              ${badge(canViewBoardingData ? (student.kamar ?? '-') : '-', 'emerald')}
              ${student.is_active ? badge('Aktif', 'emerald') : badge('Nonaktif', 'red')}
            </div>

            <div class="mt-4 flex gap-2">
              <a href="${escapeHtml(student.show_url)}" class="flex-1 inline-flex items-center justify-center rounded-2xl bg-white px-4 py-2 text-sm font-black text-slate-700 ring-1 ring-slate-200">
                Detail
              </a>
              <a href="${escapeHtml(student.edit_url)}" class="flex-1 inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-emerald-600 to-lime-500 px-4 py-2 text-sm font-black text-white">
                Edit
              </a>
              ${canDeleteStudents ? `
                <form method="POST" action="${escapeHtml(student.delete_url)}" onsubmit="return confirm('Hapus santri yang belum terpakai ini?')">
                  <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                  <input type="hidden" name="_method" value="DELETE">
                  <button type="submit" class="h-full rounded-2xl bg-red-50 px-4 text-sm font-black text-red-600 ring-1 ring-red-100 hover:bg-red-100"><i class="bi bi-trash"></i></button>
                </form>` : ''}
            </div>
          </div>
        </div>
      </div>
    `).join('');
  }

  async function searchStudents() {
    const params = new URLSearchParams({
      q: searchInput?.value ?? '',
      kelas: kelasFilter?.value ?? '',
      kamar: kamarFilter?.value ?? '',
      gender: genderFilter?.value ?? '',
      institution_id: institutionFilter?.value ?? '',
      institution_class: institutionClassFilter?.value ?? '',
    });

    try {
      const res = await fetch(`{{ route('students.search.realtime') }}?${params.toString()}`, {
        headers: { 'Accept': 'application/json' }
      });

      if (!res.ok) throw new Error('Request gagal');

      const json = await res.json();

      renderDesktop(json.students ?? []);
      renderMobile(json.students ?? []);

      if (pagination) {
        pagination.style.display = (params.get('q') || params.get('kelas') || params.get('kamar') || params.get('gender') || params.get('institution_id') || params.get('institution_class'))
          ? 'none'
          : '';
      }
    } catch (e) {
      console.error(e);
    }
  }

  function debounceSearch() {
    clearTimeout(timer);
    timer = setTimeout(searchStudents, 300);
  }

  searchInput?.addEventListener('input', debounceSearch);
  kelasFilter?.addEventListener('input', debounceSearch);
  kamarFilter?.addEventListener('input', debounceSearch);
  genderFilter?.addEventListener('change', debounceSearch);
  institutionFilter?.addEventListener('change', () => document.getElementById('studentFilterForm')?.submit());
  institutionClassFilter?.addEventListener('change', debounceSearch);
});
</script>
@endpush
