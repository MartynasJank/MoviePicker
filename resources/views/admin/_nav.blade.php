<nav class="flex flex-wrap items-center gap-1 mb-8">
    <a href="{{ route('admin.dashboard') }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
        Dashboard
    </a>
    <a href="{{ route('admin.roulettes.index') }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.roulettes.*') ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
        Roulettes
    </a>
    <a href="{{ route('admin.rows.index') }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.rows.*') ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
        Row Order
    </a>
    <a href="{{ route('admin.users.index') }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
        Users
    </a>
</nav>
