<aside class="hidden min-h-screen w-72 shrink-0 border-r border-emerald-100 bg-white/90 backdrop-blur-xl lg:sticky lg:top-0 lg:flex lg:flex-col">

    {{-- Brand --}}
    <div class="flex h-24 items-center gap-3 px-6">
        <div class="flex h-[52px] w-[52px] items-center justify-center overflow-hidden rounded-[1.4rem] bg-white shadow-xl shadow-emerald-300/40 ring-2 ring-emerald-100">
            <img
                src="{{ asset('images/logo.png.png') }}"
                alt="Logo"
                class="h-10 w-10 object-contain"
            >
        </div>

        <div>
            <div class="text-xl font-black tracking-tight text-emerald-950">
                SIDAPDA
            </div>
            <div class="text-xs font-semibold uppercase tracking-wider text-emerald-500">
                Sistem Informasi Digital Absensi
            </div>
        </div>
    </div>

    {{-- Menu --}}
    <nav class="flex-1 space-y-2 overflow-y-auto px-4 pb-6">

        @php
            $currentUser = auth()->user();
            $accessCodes = $currentUser?->accessibleInstitutionCodes() ?? [];

            $menuGroups = [
                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'active' => ['dashboard'],
                    'icon' => 'bi-house-door',
                    'permission' => null,
                ],

                [
                    'label' => 'Scan Absensi',
                    'icon' => 'bi-qr-code-scan',
                    'permission' => 'scan_qr',
                    'children' => [
                        [
                            'label' => 'Scan Sholat',
                            'route' => 'scan.index',
                            'active' => ['scan.*'],
                            'icon' => 'bi-qr-code-scan',
                            'permission' => 'scan_qr',
                        ],
                        [
                            'label' => 'Scan Kegiatan',
                            'route' => 'activities.scan',
                            'active' => ['activities.scan'],
                            'icon' => 'bi-qr-code',
                            'permission' => 'scan_qr',
                        ],
                        [
                            'label' => 'Pulang / Kembali Pondok',
                            'route' => 'boarding-movements.index',
                            'active' => ['boarding-movements.*'],
                            'icon' => 'bi-house-door',
                            'permission' => 'scan_qr',
                        ],
                        [
                            'label' => 'Absensi Ekstrakurikuler',
                            'route' => 'school-extracurricular-attendance.index',
                            'params' => ['institution' => 'sekolah-pagi'],
                            'active' => ['school-extracurricular-attendance.index', 'school-extracurricular-attendance.store'],
                            'icon' => 'bi-trophy',
                            'permission' => 'scan_qr',
                        ],
                        [
                            'label' => 'Izin & Sakit Ekstra',
                            'route' => 'school-extracurricular-attendance.excuses.index',
                            'params' => ['institution' => 'sekolah-pagi'],
                            'active' => ['school-extracurricular-attendance.excuses.*'],
                            'icon' => 'bi-file-earmark-medical',
                            'permission' => 'scan_qr',
                        ],
                        [
                            'label' => 'Absensi Sekolah Pagi',
                            'route' => 'school-attendance.index',
                            'params' => ['institution' => 'sekolah-pagi'],
                            'active' => ['school-attendance.index', 'school-attendance.store'],
                            'icon' => 'bi-person-check',
                            'permission' => 'scan_qr',
                        ],
                        [
                            'label' => 'Izin & Sakit Sekolah',
                            'route' => 'school-attendance.excuses.index',
                            'params' => ['institution' => 'sekolah-pagi'],
                            'active' => ['school-attendance.excuses.*'],
                            'icon' => 'bi-file-earmark-medical',
                            'permission' => 'scan_qr',
                        ],
                        [
                            'label' => 'Absensi Guru Sekolah',
                            'route' => 'school-teacher-attendance.index',
                            'params' => ['institution' => 'sekolah-pagi'],
                            'active' => ['school-teacher-attendance.index', 'school-teacher-attendance.store'],
                            'icon' => 'bi-person-badge',
                            'permission' => 'scan_qr',
                        ],
                        [
                            'label' => 'Izin & Sakit Guru',
                            'route' => 'school-teacher-attendance.excuses.index',
                            'params' => ['institution' => 'sekolah-pagi'],
                            'active' => ['school-teacher-attendance.excuses.*'],
                            'icon' => 'bi-file-earmark-medical',
                            'permission' => 'scan_qr',
                        ],
                    ],
                ],

                [
                    'label' => 'Izin Kegiatan',
                    'icon' => 'bi-file-earmark-check',
                    'permission' => 'view_reports',
                    'children' => [
                        [
                            'label' => 'Izin Kegiatan Pondok',
                            'route' => 'activities.excuses.pondok',
                            'active' => ['activities.excuses.pondok'],
                            'icon' => 'bi-house-check',
                            'permission' => 'view_reports',
                        ],
                        [
                            'label' => 'Izin Kegiatan MADAD',
                            'route' => 'activities.excuses.madad',
                            'active' => ['activities.excuses.madad'],
                            'icon' => 'bi-book',
                            'permission' => 'view_reports',
                        ],
                    ],
                ],

                [
                    'label' => 'Rekap Ekstrakurikuler',
                    'route' => 'school-extracurricular-attendance.reports.index',
                    'params' => ['institution' => 'sekolah-pagi'],
                    'active' => ['school-extracurricular-attendance.reports.*'],
                    'icon' => 'bi-clipboard2-check',
                    'permission' => 'view_reports',
                ],

                [
                    'label' => 'Rekap Siswa Sekolah Pagi',
                    'route' => 'school-attendance.reports.index',
                    'params' => ['institution' => 'sekolah-pagi'],
                    'active' => ['school-attendance.reports.*'],
                    'icon' => 'bi-clipboard-data',
                    'permission' => 'view_reports',
                ],

                [
                    'label' => 'Rekap Guru Sekolah Pagi',
                    'route' => 'school-teacher-attendance.reports.index',
                    'params' => ['institution' => 'sekolah-pagi'],
                    'active' => ['school-teacher-attendance.reports.*'],
                    'icon' => 'bi-clipboard-check',
                    'permission' => 'view_reports',
                ],

                [
                    'label' => 'Master Data',
                    'icon' => 'bi-database',
                    'permission' => null,
                    'children' => [
                        [
                            'label' => 'Data Santri',
                            'route' => 'students.index',
                            'active' => [
                                'students.index',
                                'students.create',
                                'students.show',
                                'students.edit',
                                'students.institutions.*',
                                'students.import.*',
                                'students.search.*',
                                'students.export.*',
                                'students.qr.*',
                                'students.id-card*',
                                'students.attendance.*',
                            ],
                            'icon' => 'bi-people',
                            'permission' => 'manage_students',
                        ],
                        [
                            'label' => 'Kenaikan Kelas',
                            'route' => 'students.promotions.index',
                            'active' => ['students.promotions.*'],
                            'icon' => 'bi-arrow-up-circle',
                            'permission' => 'manage_students',
                        ],
                        [
                            'label' => 'Data Guru Sekolah Pagi',
                            'route' => 'school-teachers.index',
                            'params' => ['institution' => 'sekolah-pagi'],
                            'active' => ['school-teachers.*'],
                            'icon' => 'bi-person-vcard',
                            'permission' => 'manage_students',
                        ],
                        [
                            'label' => 'Ekstrakurikuler Sekolah',
                            'route' => 'school-extracurriculars.index',
                            'params' => ['institution' => 'sekolah-pagi'],
                            'active' => ['school-extracurriculars.*'],
                            'icon' => 'bi-trophy',
                            'permission' => 'manage_activities',
                        ],
                        [
                            'label' => 'Jadwal Sholat',
                            'route' => 'prayers.index',
                            'active' => ['prayers.*'],
                            'icon' => 'bi-clock-history',
                            'permission' => 'manage_prayers',
                        ],
                        [
                            'label' => 'Kegiatan',
                            'route' => 'activities.index',
                            'active' => ['activities.index', 'activities.create', 'activities.edit', 'activities.show'],
                            'icon' => 'bi-calendar-check',
                            'permission' => 'manage_activities',
                        ],
                    ],
                ],

                [
                    'label' => 'Rekap Sholat',
                    'icon' => 'bi-clipboard-data',
                    'permission' => 'view_reports',
                    'children' => [
                        [
                            'label' => 'Ringkasan Sholat',
                            'route' => 'rekap.prayer-summary.daily',
                            'active' => ['rekap-salat*', 'rekap.prayer-summary.*'],
                            'icon' => 'bi-list-check',
                            'permission' => 'view_reports',
                        ],
                        [
                            'label' => 'Rekap Harian',
                            'route' => 'rekap.index',
                            'active' => ['rekap.index'],
                            'icon' => 'bi-clipboard-data',
                            'permission' => 'view_reports',
                        ],
                        [
                            'label' => 'Rekap Mingguan',
                            'route' => 'rekap.weekly',
                            'active' => ['rekap.weekly'],
                            'icon' => 'bi-calendar-week',
                            'permission' => 'view_reports',
                        ],
                        [
                            'label' => 'Rekap Bulanan',
                            'route' => 'rekap.monthly',
                            'active' => ['rekap.monthly'],
                            'icon' => 'bi-calendar3',
                            'permission' => 'view_reports',
                        ],
                    ],
                ],

                [
                    'label' => 'Rekap Kegiatan',
                    'icon' => 'bi-journal-check',
                    'permission' => 'view_reports',
                    'children' => [
                        [
                            'label' => 'Kegiatan Harian',
                            'route' => 'rekap-kegiatan.daily',
                            'active' => ['rekap-kegiatan.daily'],
                            'icon' => 'bi-journal-check',
                            'permission' => 'view_reports',
                        ],
                        [
                            'label' => 'Kegiatan Mingguan',
                            'route' => 'rekap-kegiatan.weekly',
                            'active' => ['rekap-kegiatan.weekly'],
                            'icon' => 'bi-calendar-week',
                            'permission' => 'view_reports',
                        ],
                        [
                            'label' => 'Kegiatan Bulanan',
                            'route' => 'rekap-kegiatan.monthly',
                            'active' => ['rekap-kegiatan.monthly'],
                            'icon' => 'bi-calendar3',
                            'permission' => 'view_reports',
                        ],
                    ],
                ],

                [
                    'label' => 'Rekap Diniyah',
                    'icon' => 'bi-book',
                    'permission' => 'view_reports',
                    'children' => [
                        [
                            'label' => 'Harian',
                            'route' => 'rekap-diniyah.daily',
                            'active' => ['rekap-diniyah.daily'],
                            'icon' => 'bi-book',
                            'permission' => 'view_reports',
                        ],
                        [
                            'label' => 'Mingguan',
                            'route' => 'rekap-diniyah.weekly',
                            'active' => ['rekap-diniyah.weekly'],
                            'icon' => 'bi-calendar-week',
                            'permission' => 'view_reports',
                        ],
                        [
                            'label' => 'Bulanan',
                            'route' => 'rekap-diniyah.monthly',
                            'active' => ['rekap-diniyah.monthly'],
                            'icon' => 'bi-calendar3',
                            'permission' => 'view_reports',
                        ],
                    ],
                ],

                [
                    'label' => 'Pengaturan',
                    'icon' => 'bi-gear',
                    'permission' => null,
                    'children' => [
                        [
                            'label' => 'Profil & WA PONPES',
                            'route' => 'institution-settings.edit',
                            'params' => ['institution' => 'ponpes'],
                            'active' => ['institution-settings.*'],
                            'icon' => 'bi-building-gear',
                            'permission' => null,
                            'settings_access' => true,
                        ],
                        [
                            'label' => 'Profil & WA MI',
                            'route' => 'institution-settings.edit',
                            'params' => ['institution' => 'mi'],
                            'active' => ['institution-settings.*'],
                            'icon' => 'bi-building-gear',
                            'permission' => null,
                            'settings_access' => true,
                        ],
                        [
                            'label' => 'Profil & WA MTs & MA',
                            'route' => 'institution-settings.edit',
                            'params' => ['institution' => 'sekolah-pagi'],
                            'active' => ['institution-settings.*'],
                            'icon' => 'bi-building-gear',
                            'permission' => null,
                            'settings_access' => true,
                        ],
                        [
                            'label' => 'Profil & WA MADAD',
                            'route' => 'institution-settings.edit',
                            'params' => ['institution' => 'madad'],
                            'active' => ['institution-settings.*'],
                            'icon' => 'bi-building-gear',
                            'permission' => null,
                            'settings_access' => true,
                        ],
                        [
                            'label' => 'Waktu Absensi MI',
                            'route' => 'school-attendance-settings.edit',
                            'params' => ['institution' => 'mi'],
                            'active' => ['school-attendance-settings.*'],
                            'icon' => 'bi-clock-history',
                            'permission' => 'manage_users',
                        ],
                        [
                            'label' => 'Waktu Sekolah Pagi',
                            'route' => 'school-attendance-settings.edit',
                            'params' => ['institution' => 'sekolah-pagi'],
                            'active' => ['school-attendance-settings.*'],
                            'icon' => 'bi-alarm',
                            'permission' => 'manage_users',
                        ],
                        [
                            'label' => 'Users',
                            'route' => 'users.index',
                            'active' => ['users.*'],
                            'icon' => 'bi-person-gear',
                            'permission' => 'manage_users',
                            'admin_only' => true,
                        ],
                    ],
                ],
            ];

            $requiredCodesFor = function (array $item): array {
                $route = $item['route'] ?? '';
                $params = $item['params'] ?? [];

                if (! empty($params['institution'])) {
                    return [(string) $params['institution']];
                }

                if (str_starts_with($route, 'students.')) {
                    return ['ponpes', 'mi', 'sekolah-pagi', 'madad'];
                }

                if (str_starts_with($route, 'activities.')) {
                    if (($params['category'] ?? null) === 'diniyah' || $route === 'activities.excuses.madad') {
                        return ['madad'];
                    }
                    if (($params['category'] ?? null) === 'umum' || $route === 'activities.excuses.pondok') {
                        return ['ponpes'];
                    }

                    return ['ponpes', 'madad'];
                }

                return match (true) {
                    str_starts_with($route, 'scan.') => ['ponpes'],
                    str_starts_with($route, 'boarding-movements.') => ['ponpes'],
                    str_starts_with($route, 'prayers.') => ['ponpes'],
                    str_starts_with($route, 'rekap-diniyah.') => ['madad'],
                    str_starts_with($route, 'rekap-kegiatan.') => ['ponpes'],
                    str_starts_with($route, 'rekap.') => ['ponpes'],
                    default => [],
                };
            };

            $canAccessMenuItem = function (array $item) use ($currentUser, $accessCodes, $requiredCodesFor): bool {
                if (($item['admin_only'] ?? false) && ! $currentUser?->hasRole('admin')) {
                    return false;
                }

                if (! empty($item['permission']) && ! $currentUser?->can($item['permission'])) {
                    return false;
                }

                if (! empty($item['settings_access'])) {
                    $code = $item['params']['institution'] ?? null;
                    $settingsInstitution = $code
                        ? \App\Models\Institution::query()->where('code', $code)->first()
                        : null;

                    if (! $settingsInstitution || ! $currentUser?->canManageInstitutionSettings($settingsInstitution)) {
                        return false;
                    }
                }

                $requiredCodes = $requiredCodesFor($item);

                return $requiredCodes === [] || array_intersect($requiredCodes, $accessCodes) !== [];
            };
        @endphp

        @foreach($menuGroups as $menu)
            @php
                $hasChildren = isset($menu['children']);

                $visibleChildren = $hasChildren
                    ? collect($menu['children'])->filter($canAccessMenuItem)
                    : collect();

                $canShowMenu = $hasChildren
                    ? $visibleChildren->isNotEmpty()
                    : $canAccessMenuItem($menu);

                $isActive = false;

                if ($hasChildren) {
                    foreach ($visibleChildren as $child) {
                        foreach ($child['active'] as $activeRoute) {
                            if (request()->routeIs($activeRoute)) {
                                $isActive = true;
                            }
                        }
                    }
                } else {
                    foreach ($menu['active'] as $activeRoute) {
                        if (request()->routeIs($activeRoute)) {
                            $isActive = true;
                        }
                    }
                }
            @endphp

            @if($canShowMenu)
                @if($hasChildren)
                    <details class="group/dropdown" {{ $isActive ? 'open' : '' }}>
                        <summary
                            class="flex cursor-pointer list-none items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition-all duration-200 marker:hidden
                            {{ $isActive
                                ? 'bg-gradient-to-r from-emerald-600 to-lime-500 text-white shadow-lg shadow-emerald-300/50'
                                : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-700' }}"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl
                                {{ $isActive
                                    ? 'bg-white/20 text-white'
                                    : 'bg-slate-100 text-slate-500 group-hover/dropdown:bg-emerald-100 group-hover/dropdown:text-emerald-700' }}"
                            >
                                <i class="bi {{ $menu['icon'] }}"></i>
                            </div>

                            <span class="flex-1 truncate">
                                {{ $menu['label'] }}
                            </span>

                            <i class="bi bi-chevron-down text-xs transition group-open/dropdown:rotate-180"></i>
                        </summary>

                        <div class="mt-2 space-y-1 pl-5">
                            @foreach($visibleChildren as $child)
                                @php
                                    $childActive = false;

                                    foreach ($child['active'] as $activeRoute) {
                                        if (request()->routeIs($activeRoute)) {
                                            $childActive = true;
                                        }
                                    }
                                @endphp

                                <a
                                    href="{{ route($child['route'], $child['params'] ?? []) }}"
                                    class="group flex items-center gap-3 rounded-2xl px-4 py-2.5 text-sm font-bold transition-all duration-200
                                    {{ $childActive
                                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
                                        : 'text-slate-500 hover:bg-slate-50 hover:text-emerald-700' }}"
                                >
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-xl
                                        {{ $childActive
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-slate-100 text-slate-400 group-hover:bg-emerald-100 group-hover:text-emerald-700' }}"
                                    >
                                        <i class="bi {{ $child['icon'] }}"></i>
                                    </div>

                                    <span class="truncate">
                                        {{ $child['label'] }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @else
                    <a
                        href="{{ route($menu['route'], $menu['params'] ?? []) }}"
                        class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition-all duration-200
                        {{ $isActive
                            ? 'bg-gradient-to-r from-emerald-600 to-lime-500 text-white shadow-lg shadow-emerald-300/50'
                            : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-700' }}"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl
                            {{ $isActive
                                ? 'bg-white/20 text-white'
                                : 'bg-slate-100 text-slate-500 group-hover:bg-emerald-100 group-hover:text-emerald-700' }}"
                        >
                            <i class="bi {{ $menu['icon'] }}"></i>
                        </div>

                        <span class="truncate">
                            {{ $menu['label'] }}
                        </span>
                    </a>
                @endif
            @endif
        @endforeach

    </nav>

    {{-- User Card --}}
    @auth
        <div class="border-t border-emerald-100 p-4">
            <a
                href="{{ route('profile.show') }}"
                class="flex items-center gap-3 rounded-3xl bg-gradient-to-br from-emerald-50 to-lime-50 p-3 transition hover:shadow-lg hover:shadow-emerald-100"
            >
                <img
                    src="{{ auth()->user()->avatarUrl() }}"
                    alt="{{ auth()->user()->name }}"
                    class="h-12 w-12 rounded-2xl object-cover ring-2 ring-white"
                >

                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-black text-emerald-950">
                        {{ auth()->user()->name }}
                    </div>

                    <div class="truncate text-xs font-bold text-emerald-600">
                        {{ auth()->user()->roles->first()?->name ?? 'User' }}
                    </div>

                    <div class="truncate text-xs text-slate-500">
                        {{ auth()->user()->email }}
                    </div>
                </div>

                <i class="bi bi-chevron-right text-sm text-emerald-500"></i>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf

                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-red-100 bg-red-50 px-4 py-2 text-sm font-bold text-red-600 transition hover:bg-red-100"
                >
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </button>
            </form>
        </div>
    @endauth

</aside>
