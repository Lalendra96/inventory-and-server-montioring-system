<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'ICT Operations') - Teaching Hospital Peradeniya</title>
        <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    </head>
    <body>
        <div class="app-shell">
            <aside class="sidebar no-print">
                <div class="brand">
                    <div class="brand-title">TEACHING HOSPITAL PERADENIYA</div>
                    <div class="brand-sub">HIMU · ICT Operations & Support</div>
                </div>
                <nav>
                    <div class="nav-group">
                        <div class="nav-label">Operations</div>
                        <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <span class="nav-icon">⌂</span>Dashboard</a>
                        <a class="nav-item {{ request()->routeIs('tickets.*') ? 'active' : '' }}" href="{{ route('tickets.index') }}">
                            <span class="nav-icon">☷</span>Support Requests</a>
                        <a class="nav-item {{ request()->routeIs('inspections.*') ? 'active' : '' }}" href="{{ route('inspections.index') }}">
                            <span class="nav-icon">✓</span>Inspections</a>
                        <a class="nav-item {{ request()->routeIs('assets.*') ? 'active' : '' }}" href="{{ route('assets.index') }}">
                            <span class="nav-icon">▣</span>Assets / Inventory</a>
                        <a class="nav-item {{ request()->routeIs('downtime.*') ? 'active' : '' }}" href="{{ route('downtime.index') }}">
                            <span class="nav-icon">◉</span>System Downtime</a>
                        @if(auth()->user()->hasRole('system_admin','himu_admin','ict_manager','system_db_officer','senior_ict_officer','ict_officer','technician','auditor'))
                            <a class="nav-item {{ request()->routeIs('servers.*') ? 'active' : '' }}" href="{{ route('servers.index') }}">
                                <span class="nav-icon">▦</span>Server Monitoring</a>
                        @endif
                        <a class="nav-item {{ request()->routeIs('software.*') ? 'active' : '' }}" href="{{ route('software.index') }}">
                            <span class="nav-icon">⌘</span>Software Requests</a>
                        <a class="nav-item {{ request()->routeIs('procurement.*') ? 'active' : '' }}" href="{{ route('procurement.index') }}">
                            <span class="nav-icon">▤</span>Procurement / Replacement</a>
                        <a class="nav-item {{ request()->routeIs('incidents.*') ? 'active' : '' }}" href="{{ route('incidents.index') }}">
                            <span class="nav-icon">☾</span>Night Shift Incidents</a>
                    </div>
                    <div class="nav-group">
                        <div class="nav-label">Governance</div>
                        <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                            <span class="nav-icon">▥</span>Reports</a>
                        @if(auth()->user()->hasRole('system_admin','himu_admin','ict_manager','auditor'))
                            <a class="nav-item {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}">
                                <span class="nav-icon">◎</span>Audit Viewer</a>
                        @endif
                        <a class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                            <span class="nav-icon">●</span>Notifications</a>
                    </div>
                    @if(auth()->user()->isAdmin())
                        <div class="nav-group">
                            <div class="nav-label">Administration</div>
                            <a class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                <span class="nav-icon">♟</span>Users & Roles</a>
                            <a class="nav-item {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}" href="{{ route('admin.servers.index') }}">
                                <span class="nav-icon">▦</span>Server Configuration</a>
                            <a class="nav-item {{ request()->routeIs('admin.features.*') ? 'active' : '' }}" href="{{ route('admin.features.index') }}">
                                <span class="nav-icon">⚙</span>Features & Options</a>
                        </div>
                    @endif
                </nav>
            </aside>
            <main class="main">
                <header class="topbar no-print">
                    <div>
                        <div class="page-title">@yield('page-title', 'ICT Operations Dashboard')</div>
                        <div class="muted" style="font-size:11px">Health Information Management Unit (HIMU)</div>
                    </div>
                    <div class="top-actions">
                        <a class="btn btn-light btn-sm" href="{{ route('notifications.index') }}">
                            Notifications
                            @if(auth()->user()->unreadNotifications()->count())
                                ({{ auth()->user()->unreadNotifications()->count() }})
                            @endif
                        </a>
                        <div class="user-chip">
                            <strong>{{ auth()->user()->name }}</strong>
                            <br>
                            <span class="muted" style="font-size:11px">{{ auth()->user()->roles->pluck('label')->join(', ') }}</span>
                        </div>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-light btn-sm">Logout</button>
                        </form>
                    </div>
                </header>
                <div class="content">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-error">
                            <strong>Please correct the following:</strong>
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @yield('content')
                </div>
            </main>
        </div>
        <script src="{{ asset('assets/js/app.js') }}" defer>
        </script>
        @stack('scripts')
    </body>
</html>
