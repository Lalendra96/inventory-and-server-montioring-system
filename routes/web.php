<?php

use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\ServerController as AdminServerController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\NightShiftIncidentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServerMonitorController;
use App\Http\Controllers\SoftwareRequestController;
use App\Http\Controllers\SystemDowntimeController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:8,1')
        ->name('login.store');
});

Route::middleware(['auth', 'active.user'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    Route::get('/', DashboardController::class)
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Support Requests
    |--------------------------------------------------------------------------
    */
    Route::middleware('feature:tickets')->group(function (): void {
        Route::get('/tickets', [TicketController::class, 'index'])
            ->name('tickets.index');
        Route::get('/tickets/create', [TicketController::class, 'create'])
            ->name('tickets.create');
        Route::post('/tickets', [TicketController::class, 'store'])
            ->name('tickets.store');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])
            ->name('tickets.show');
        Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])
            ->name('tickets.assign');
        Route::post('/tickets/{ticket}/status', [TicketController::class, 'status'])
            ->name('tickets.status');
        Route::post('/tickets/{ticket}/comments', [TicketController::class, 'comment'])
            ->name('tickets.comments.store');
        Route::get(
            '/tickets/{ticket}/attachments/{attachment}',
            [TicketController::class, 'attachment']
        )->name('tickets.attachments.download');
    });

    /*
    |--------------------------------------------------------------------------
    | Assets / Inventory
    |--------------------------------------------------------------------------
    |
    | /assets is the canonical route. /inventory is provided as an explicit
    | alias so users can reach the same module using either operational term.
    |
    */
    Route::middleware('feature:assets')->group(function (): void {
        Route::get('/assets', [AssetController::class, 'index'])
            ->name('assets.index');
        Route::get('/assets/create', [AssetController::class, 'create'])
            ->name('assets.create');
        Route::post('/assets', [AssetController::class, 'store'])
            ->name('assets.store');
        Route::get('/assets/{asset}', [AssetController::class, 'show'])
            ->name('assets.show');
        Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])
            ->name('assets.edit');
        Route::put('/assets/{asset}', [AssetController::class, 'update'])
            ->name('assets.update');
        Route::post('/assets/{asset}/move', [AssetController::class, 'move'])
            ->name('assets.move');
        Route::post('/assets/{asset}/faults', [AssetController::class, 'fault'])
            ->name('assets.faults.store');
        Route::post(
            '/assets/{asset}/faults/{fault}/resolve',
            [AssetController::class, 'resolveFault']
        )->name('assets.faults.resolve');
        Route::post('/assets/{asset}/disable', [AssetController::class, 'disable'])
            ->name('assets.disable');

        Route::get('/inventory', function () {
            return redirect()->route('assets.index');
        })->name('inventory.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Server Monitoring
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'feature:server_monitoring',
        'role:system_admin,himu_admin,ict_manager,system_db_officer,senior_ict_officer,ict_officer,technician,auditor',
    ])->group(function (): void {
        Route::get('/servers', [ServerMonitorController::class, 'index'])
            ->name('servers.index');
        Route::get('/servers/{server}', [ServerMonitorController::class, 'show'])
            ->name('servers.show');
        Route::get('/servers/{server}/live', [ServerMonitorController::class, 'live'])
            ->name('servers.live');
        Route::get('/servers/{server}/processes', [ServerMonitorController::class, 'processes'])
            ->name('servers.processes');
        Route::get('/servers/{server}/services', [ServerMonitorController::class, 'services'])
            ->name('servers.services');

        Route::post(
            '/servers/{server}/process-action',
            [ServerMonitorController::class, 'processAction']
        )
            ->middleware('role:system_admin,himu_admin,ict_manager,system_db_officer')
            ->name('servers.process-action');

        Route::post(
            '/servers/{server}/service-action',
            [ServerMonitorController::class, 'serviceAction']
        )
            ->middleware('role:system_admin,himu_admin,ict_manager,system_db_officer')
            ->name('servers.service-action');
    });

    /*
    |--------------------------------------------------------------------------
    | Maintenance, Monitoring and Workflows
    |--------------------------------------------------------------------------
    */
    Route::get('/inspections', [InspectionController::class, 'index'])
        ->middleware('feature:inspections')
        ->name('inspections.index');
    Route::post('/inspections/{inspection}/complete', [InspectionController::class, 'complete'])
        ->middleware('feature:inspections')
        ->name('inspections.complete');

    Route::get('/downtime', [SystemDowntimeController::class, 'index'])
        ->middleware('feature:downtime')
        ->name('downtime.index');
    Route::post('/downtime', [SystemDowntimeController::class, 'store'])
        ->middleware('feature:downtime')
        ->name('downtime.store');
    Route::post('/downtime/{downtime}/verify', [SystemDowntimeController::class, 'verify'])
        ->middleware(['feature:downtime', 'role:system_admin,himu_admin,ict_manager'])
        ->name('downtime.verify');

    Route::get('/software', [SoftwareRequestController::class, 'index'])
        ->middleware('feature:software_requests')
        ->name('software.index');
    Route::post('/software', [SoftwareRequestController::class, 'store'])
        ->middleware('feature:software_requests')
        ->name('software.store');
    Route::post('/software/{softwareRequest}/status', [SoftwareRequestController::class, 'status'])
        ->middleware([
            'feature:software_requests',
            'role:system_admin,himu_admin,software_developer,ict_manager',
        ])
        ->name('software.status');

    Route::get('/procurement', [ProcurementController::class, 'index'])
        ->middleware('feature:procurement')
        ->name('procurement.index');
    Route::post('/procurement', [ProcurementController::class, 'store'])
        ->middleware('feature:procurement')
        ->name('procurement.store');
    Route::post('/procurement/{procurement}/approve', [ProcurementController::class, 'approve'])
        ->middleware(['feature:procurement', 'role:system_admin,himu_admin,ict_manager'])
        ->name('procurement.approve');

    Route::get('/night-shift', [NightShiftIncidentController::class, 'index'])
        ->middleware('feature:night_shift')
        ->name('incidents.index');
    Route::post('/night-shift', [NightShiftIncidentController::class, 'store'])
        ->middleware('feature:night_shift')
        ->name('incidents.store');

    /*
    |--------------------------------------------------------------------------
    | Notifications, Reports and Audit
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('feature:reports')
        ->name('reports.index');

    Route::middleware('role:system_admin,himu_admin,ict_manager,auditor')
        ->group(function (): void {
            Route::get('/audit', [AuditController::class, 'index'])
                ->middleware('feature:audit')
                ->name('audit.index');
            Route::post('/audit/{auditLog}/verify', [AuditController::class, 'verify'])
                ->middleware('role:system_admin,himu_admin,auditor')
                ->name('audit.verify');
        });

    /*
    |--------------------------------------------------------------------------
    | Administration
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:system_admin,himu_admin')
        ->group(function (): void {
            Route::get('/users', [UserController::class, 'index'])
                ->name('users.index');
            Route::post('/users', [UserController::class, 'store'])
                ->name('users.store');
            Route::post('/users/{user}/roles', [UserController::class, 'updateRoles'])
                ->name('users.roles');
            Route::post('/users/{user}/disable', [UserController::class, 'disable'])
                ->name('users.disable');
            Route::post('/users/{user}/enable', [UserController::class, 'enable'])
                ->name('users.enable');
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
                ->name('users.reset-password');

            Route::get('/servers', [AdminServerController::class, 'index'])
                ->name('servers.index');
            Route::post('/servers', [AdminServerController::class, 'store'])
                ->name('servers.store');
            Route::put('/servers/{server}', [AdminServerController::class, 'update'])
                ->name('servers.update');
            Route::post('/servers/{server}/disable', [AdminServerController::class, 'disable'])
                ->name('servers.disable');
            Route::post('/servers/{server}/enable', [AdminServerController::class, 'enable'])
                ->name('servers.enable');

            Route::get('/features', [FeatureController::class, 'index'])
                ->name('features.index');
            Route::post('/features/{feature}', [FeatureController::class, 'update'])
                ->name('features.update');
        });
});
