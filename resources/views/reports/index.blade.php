@extends('layouts.app')
@section('title','Reports')
@section('page-title','Operational Reports')
@section('content')
    <div class="stats">
        <div class="stat">
            <div class="stat-label">Tickets</div>
            <div class="stat-value">{{ $summary['tickets'] }}</div>
        </div>
        <div class="stat warn">
            <div class="stat-label">Open</div>
            <div class="stat-value">{{ $summary['open'] }}</div>
        </div>
        <div class="stat ok">
            <div class="stat-label">Assets</div>
            <div class="stat-value">{{ $summary['assets'] }}</div>
        </div>
        <div class="stat purple">
            <div class="stat-label">Inspections Due</div>
            <div class="stat-value">{{ $summary['inspections_due'] }}</div>
        </div>
        <div class="stat danger">
            <div class="stat-label">Downtimes</div>
            <div class="stat-value">{{ $summary['downtimes'] }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Software Requests</div>
            <div class="stat-value">{{ $summary['software'] }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-h">Report Catalogue</div>
        <div class="card-b">
            <div class="metric-row">
                <div class="metric">SLA Performance</div>
                <div class="metric">Asset Fault Frequency</div>
                <div class="metric">Inspection Compliance</div>
                <div class="metric">Downtime & Availability</div>
                <div class="metric">Night Shift Handover</div>
                <div class="metric">Procurement / Replacement</div>
                <div class="metric">Software Workflow</div>
                <div class="metric">Audit Verification</div>
            </div>
            <p class="muted">Print-friendly report views use local HTML/CSS. PDF export can be added with a locally installed server-side PDF package without CDN dependencies.</p>
            <button class="btn btn-light no-print" onclick="window.print()">Print Current Summary</button>
        </div>
    </div>
@endsection
