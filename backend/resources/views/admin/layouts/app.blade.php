<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') | WearDay Fashion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-800">
    <div class="min-h-full flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col flex-shrink-0 min-h-screen">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded bg-white text-slate-900 flex items-center justify-center font-bold text-lg tracking-wider">WD</div>
                    <div>
                        <div class="font-bold text-white tracking-wide text-sm">WEARDAY</div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Atelier Admin</div>
                    </div>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto text-xs">
                <!-- Group: Overview -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Overview</div>
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-chart-pie w-5 text-slate-400"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('admin.activity_logs') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.activity_logs*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-clock-rotate-left w-5 text-slate-400"></i>
                        <span>Activity Logs</span>
                    </a>
                </div>

                <!-- Group: Catalog -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Catalog</div>
                    <a href="{{ route('admin.products') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.products*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-shirt w-5 text-slate-400"></i>
                        <span>Products</span>
                    </a>
                    <a href="{{ route('admin.categories') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.categories*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-layer-group w-5 text-slate-400"></i>
                        <span>Categories</span>
                    </a>
                    <a href="{{ route('admin.brands') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.brands*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-tag w-5 text-slate-400"></i>
                        <span>Brands</span>
                    </a>
                    <a href="{{ route('admin.inventory') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.inventory*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-boxes-stacked w-5 text-slate-400"></i>
                        <span>Inventory & Stock</span>
                    </a>
                </div>

                <!-- Group: Sales & Fulfillment -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Orders & Fulfillment</div>
                    <a href="{{ route('admin.orders') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.orders*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-bag-shopping w-5 text-slate-400"></i>
                        <span>Orders</span>
                    </a>
                    <a href="{{ route('admin.payments') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.payments*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-credit-card w-5 text-slate-400"></i>
                        <span>Payments</span>
                    </a>
                    <a href="{{ route('admin.shipping') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.shipping*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-truck-fast w-5 text-slate-400"></i>
                        <span>Shipping & Delivery</span>
                    </a>
                    <a href="{{ route('admin.returns') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.returns*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-rotate-left w-5 text-slate-400"></i>
                        <span>Returns & Exchanges</span>
                    </a>
                    <a href="{{ route('admin.refunds') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.refunds*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-hand-holding-dollar w-5 text-slate-400"></i>
                        <span>Refunds</span>
                    </a>
                </div>

                <!-- Group: Customers & Marketing -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Customers & Marketing</div>
                    <a href="{{ route('admin.customers') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.customers*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-users w-5 text-slate-400"></i>
                        <span>Customers</span>
                    </a>
                    <a href="{{ route('admin.coupons') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.coupons*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-ticket w-5 text-slate-400"></i>
                        <span>Coupons & Promos</span>
                    </a>
                    <a href="{{ route('admin.reviews') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.reviews*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-star w-5 text-slate-400"></i>
                        <span>Reviews Moderation</span>
                    </a>
                </div>

                <!-- Group: CMS & Store Content -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">CMS & Content</div>
                    <a href="{{ route('admin.cms.banners') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.cms.banners*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-images w-5 text-slate-400"></i>
                        <span>Banners & Sections</span>
                    </a>
                    <a href="{{ route('admin.cms.pages') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.cms.pages*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-file-lines w-5 text-slate-400"></i>
                        <span>Static Pages & FAQ</span>
                    </a>
                    <a href="{{ route('admin.cms.blog') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.cms.blog*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-newspaper w-5 text-slate-400"></i>
                        <span>Blog Management</span>
                    </a>
                </div>

                <!-- Group: Administration -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Settings & Team</div>
                    <a href="{{ route('admin.settings') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.settings*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-gear w-5 text-slate-400"></i>
                        <span>Store Settings</span>
                    </a>
                    <a href="{{ route('admin.users') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.users*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-users-gear w-5 text-slate-400"></i>
                        <span>User Management</span>
                    </a>
                    <a href="{{ route('admin.email_logs') }}" class="flex items-center px-3 py-2 rounded-lg font-medium transition {{ request()->routeIs('admin.email_logs*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <i class="fa-solid fa-envelope-open-text w-5 text-slate-400"></i>
                        <span>Email Delivery Logs</span>
                    </a>
                </div>
            </nav>

            <!-- Current Admin User -->
            <div class="p-3 border-t border-slate-800 flex items-center justify-between text-xs">
                <div class="flex items-center space-x-2 truncate">
                    <div class="w-7 h-7 rounded-full bg-slate-700 flex items-center justify-center font-bold text-white text-[10px]">
                        {{ substr(auth()->user()->name ?? 'AD', 0, 2) }}
                    </div>
                    <div class="truncate">
                        <div class="font-medium text-white truncate">{{ auth()->user()->name ?? 'Administrator' }}</div>
                        <div class="text-[10px] text-slate-400 truncate">{{ auth()->user()->roles->first()->name ?? 'Staff' }}</div>
                    </div>
                </div>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Logout" class="text-slate-400 hover:text-rose-400 p-1.5 transition">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Topbar -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20">
                <div class="flex items-center space-x-3">
                    <h1 class="text-lg font-bold text-slate-900">@yield('page_title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="http://localhost:3000" target="_blank" class="text-xs text-slate-600 hover:text-slate-900 font-medium flex items-center space-x-1.5 border border-slate-200 px-3 py-1.5 rounded-lg transition">
                        <i class="fa-solid fa-arrow-up-right-from-square text-[11px]"></i>
                        <span>View Next.js Store</span>
                    </a>
                    <a href="/api/documentation" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center space-x-1 border border-indigo-100 bg-indigo-50/50 px-3 py-1.5 rounded-lg transition">
                        <i class="fa-solid fa-code text-[11px]"></i>
                        <span>API Docs</span>
                    </a>
                </div>
            </header>

            <!-- Flash Notifications -->
            @if(session('success'))
            <div class="m-6 mb-0 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="m-6 mb-0 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
            @endif

            <!-- Main Page Content Slot -->
            <main class="flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

