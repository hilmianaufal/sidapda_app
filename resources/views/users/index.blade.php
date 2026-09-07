@extends('layouts.app')

@section('title','Manajemen User')
@section('mobile_title','Users')

@section('content')

<x-ui.page-header
  title="Manajemen User"
  subtitle="Kelola akun petugas, admin, dan role"
  icon="bi-person-gear"
>
  <x-slot:actions>
    <x-ui.button :href="route('users.create')">
      <i class="bi bi-plus-lg"></i>
      Tambah User
    </x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<x-ui.card class="mb-6">
  <form method="GET">
    <div class="grid gap-4 lg:grid-cols-4">

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Cari
        </label>
        <x-ui.input
          name="q"
          value="{{ $q }}"
          placeholder="Nama / email / HP..." />
      </div>

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Role
        </label>
        <x-ui.select name="role">
          <option value="">Semua</option>
          @foreach($roles as $r)
            <option value="{{ $r }}" @selected($role === $r)>
              {{ $r }}
            </option>
          @endforeach
        </x-ui.select>
      </div>

      <div>
        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">
          Status
        </label>
        <x-ui.select name="status">
          <option value="">Semua</option>
          <option value="active" @selected($status === 'active')>Aktif</option>
          <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
        </x-ui.select>
      </div>

      <div class="flex items-end">
        <x-ui.button type="submit" class="w-full justify-center">
          <i class="bi bi-search"></i>
          Filter
        </x-ui.button>
      </div>

    </div>
  </form>
</x-ui.card>

{{-- Desktop Table --}}
<div class="hidden lg:block">
  <x-ui.card padding="p-0">
    <div class="flex items-center justify-between border-b border-slate-100 p-5">
      <div>
        <div class="text-lg font-black text-slate-900">
          Daftar User
        </div>
        <div class="text-sm text-slate-500">
          {{ $users->total() }} akun terdaftar
        </div>
      </div>
    </div>

    <div class="w-full overflow-x-auto">
      <table class="w-full min-w-[820px]">
        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-400">
          <tr>
            <th class="px-6 py-4">User</th>
            <th class="px-6 py-4">Akses Lembaga</th>
            <th class="px-6 py-4">Role</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4">Login Terakhir</th>
            <th class="px-6 py-4 text-right">Aksi</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          @forelse($users as $u)
            @php
              $roleName = $u->roles->pluck('name')->first();
            @endphp

            <tr class="transition hover:bg-emerald-50/40">
              <td class="px-6 py-4">
                <div class="flex items-center gap-4">
                  <img
                    src="{{ method_exists($u, 'avatarUrl') ? $u->avatarUrl() : asset('images/avatar-default.png') }}"
                    class="h-14 w-14 rounded-2xl object-cover ring-2 ring-emerald-100"
                    alt="{{ $u->name }}">

                  <div class="min-w-0">
                    <div class="truncate font-black text-slate-900">
                      {{ $u->name }}
                    </div>
                    <div class="truncate text-sm font-semibold text-slate-500">
                      {{ $u->email }}
                    </div>
                    @if($u->phone)
                      <div class="text-xs font-semibold text-slate-400">
                        {{ $u->phone }}
                      </div>
                    @endif
                  </div>
                </div>
              </td>

              <td class="px-6 py-4">
                <div class="flex max-w-xs flex-wrap gap-1.5">
                  @if($u->hasRole('admin'))
                    <x-ui.badge tone="emerald">Semua lembaga</x-ui.badge>
                  @else
                  @forelse($u->institutions as $institution)
                    <x-ui.badge tone="slate">{{ $institution->short_name }}</x-ui.badge>
                  @empty
                    <x-ui.badge tone="red">Belum diatur</x-ui.badge>
                  @endforelse
                  @endif
                </div>
              </td>

              <td class="px-6 py-4">
                @if($roleName)
                  <x-ui.badge tone="blue">{{ $roleName }}</x-ui.badge>
                @else
                  <x-ui.badge tone="slate">-</x-ui.badge>
                @endif
              </td>

              <td class="px-6 py-4">
                @if($u->is_active)
                  <x-ui.badge tone="emerald">Aktif</x-ui.badge>
                @else
                  <x-ui.badge tone="red">Nonaktif</x-ui.badge>
                @endif
              </td>

              <td class="px-6 py-4 font-bold text-slate-500">
                {{ $u->last_login_at ? $u->last_login_at->format('d M Y H:i') : '-' }}
              </td>

              <td class="px-6 py-4">
                <div class="flex justify-end gap-2">
                  <x-ui.button
                    :href="route('users.edit', $u)"
                    variant="secondary">
                    <i class="bi bi-pencil"></i>
                  </x-ui.button>

                  <form
                    method="POST"
                    action="{{ route('users.destroy', $u) }}"
                    onsubmit="return confirm('Hapus user ini?')">
                    @csrf
                    @method('DELETE')

                    <button
                      class="inline-flex items-center justify-center rounded-2xl border border-red-100 bg-red-50 px-4 py-2 text-sm font-black text-red-600 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                      {{ auth()->id() === $u->id ? 'disabled' : '' }}>
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="p-10">
                <x-ui.empty-state
                  title="Belum ada user"
                  subtitle="Tambahkan akun petugas baru."
                  icon="bi-person-x" />
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($users->hasPages())
      <div class="border-t border-slate-100 p-5">
        {{ $users->links() }}
      </div>
    @endif
  </x-ui.card>
</div>

{{-- Mobile Cards --}}
<div class="space-y-4 lg:hidden">
  @forelse($users as $u)
    @php
      $roleName = $u->roles->pluck('name')->first();
    @endphp

    <x-ui.card>
      <div class="flex gap-4">
        <img
          src="{{ method_exists($u, 'avatarUrl') ? $u->avatarUrl() : asset('images/avatar-default.png') }}"
          class="h-16 w-16 rounded-2xl object-cover ring-2 ring-emerald-100"
          alt="{{ $u->name }}">

        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <div class="truncate text-base font-black text-slate-900">
                {{ $u->name }}
              </div>
              <div class="truncate text-sm font-semibold text-slate-500">
                {{ $u->email }}
              </div>
            </div>

            @if($u->is_active)
              <x-ui.badge tone="emerald">Aktif</x-ui.badge>
            @else
              <x-ui.badge tone="red">Off</x-ui.badge>
            @endif
          </div>

          <div class="mt-3 flex flex-wrap gap-2">
            @if($roleName)
              <x-ui.badge tone="blue">{{ $roleName }}</x-ui.badge>
            @else
              <x-ui.badge tone="slate">Tanpa Role</x-ui.badge>
            @endif

            @if($u->phone)
              <x-ui.badge tone="slate">{{ $u->phone }}</x-ui.badge>
            @endif

            @if($u->hasRole('admin'))
              <x-ui.badge tone="emerald">Semua lembaga</x-ui.badge>
            @else
            @forelse($u->institutions as $institution)
              <x-ui.badge tone="slate">{{ $institution->short_name }}</x-ui.badge>
            @empty
              <x-ui.badge tone="red">Belum diatur</x-ui.badge>
            @endforelse
            @endif
          </div>

          <div class="mt-4 grid grid-cols-2 gap-2">
            <x-ui.button
              :href="route('users.edit', $u)"
              variant="secondary"
              class="justify-center">
              Edit
            </x-ui.button>

            <form
              method="POST"
              action="{{ route('users.destroy', $u) }}"
              onsubmit="return confirm('Hapus user ini?')">
              @csrf
              @method('DELETE')

              <button
                class="w-full rounded-2xl border border-red-100 bg-red-50 px-4 py-2 text-sm font-black text-red-600 transition hover:bg-red-100 disabled:opacity-50"
                {{ auth()->id() === $u->id ? 'disabled' : '' }}>
                Hapus
              </button>
            </form>
          </div>
        </div>
      </div>
    </x-ui.card>
  @empty
    <x-ui.empty-state
      title="Belum ada user"
      subtitle="Tambahkan akun petugas baru."
      icon="bi-person-x" />
  @endforelse

  @if($users->hasPages())
    <div class="mt-6">
      {{ $users->links() }}
    </div>
  @endif
</div>

@endsection
