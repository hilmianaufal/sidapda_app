@php
  $mobileUser = auth()->user();
  $mobileInstitutions = $mobileUser?->accessibleInstitutions()->get() ?? collect();
  $mobileCodes = $mobileInstitutions->pluck('code')->all();
  $mobileFirstInstitution = $mobileInstitutions->first();
  $mobileSchool = $mobileInstitutions->first(fn ($institution) => in_array($institution->code, ['sekolah-pagi', 'mi'], true));
  $mobileHasPondok = in_array('ponpes', $mobileCodes, true);
  $mobileHasMadad = in_array('madad', $mobileCodes, true);

  $mobileDataUrl = $mobileUser?->can('manage_students') && $mobileFirstInstitution
    ? route('students.index', ['institution_id' => $mobileFirstInstitution->id])
    : ($mobileFirstInstitution ? route('dashboard.institution', $mobileFirstInstitution) : route('dashboard'));

  $mobileScanUrl = match (true) {
    $mobileUser?->can('scan_qr') && $mobileHasPondok => route('scan.index'),
    $mobileUser?->can('scan_qr') && $mobileHasMadad => route('activities.scan', ['category' => 'diniyah']),
    $mobileUser?->can('scan_qr') && $mobileSchool !== null => route('school-attendance.index', $mobileSchool),
    default => route('dashboard'),
  };

  $mobileReportUrl = match (true) {
    $mobileUser?->can('view_reports') && $mobileHasPondok => route('rekap.index'),
    $mobileUser?->can('view_reports') && $mobileHasMadad => route('rekap-diniyah.daily'),
    $mobileUser?->can('view_reports') && $mobileSchool !== null => route('school-attendance.reports.index', $mobileSchool),
    default => route('dashboard'),
  };
@endphp

<nav class="fixed inset-x-0 bottom-0 z-50 px-4 pb-4 lg:hidden">
  <div class="mx-auto max-w-md">
    <div class="relative rounded-[2rem] bg-gradient-to-r from-emerald-700 via-emerald-600 to-lime-500 p-2 shadow-2xl shadow-emerald-500/30">
      <div class="flex items-center justify-around">
        <a href="{{ route('dashboard') }}"
           class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl transition {{ request()->routeIs('dashboard') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
          <i class="bi bi-house-door"></i>
        </a>

        <a href="{{ $mobileDataUrl }}"
           class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl transition {{ request()->routeIs('students.*', 'dashboard.institution') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
          <i class="bi bi-people"></i>
        </a>

        <a href="{{ $mobileScanUrl }}"
           class="-mt-9 flex h-[68px] w-[68px] items-center justify-center rounded-[1.6rem] bg-white text-3xl text-emerald-600 shadow-xl shadow-emerald-950/20 ring-4 ring-emerald-100 transition active:scale-95">
          <i class="bi bi-qr-code-scan"></i>
        </a>

        <a href="{{ $mobileReportUrl }}"
           class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl transition {{ request()->routeIs('rekap.*', 'rekap-*', 'school-attendance.reports.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
          <i class="bi bi-clipboard-data"></i>
        </a>

        <a href="{{ route('profile.show') }}"
           class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl transition {{ request()->routeIs('profile.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
          <i class="bi bi-person"></i>
        </a>
      </div>
    </div>
  </div>
</nav>
