# Teaching Hospital Peradeniya HIMU ICT Operations, Inventory & Server Monitoring System

A Laravel 12 and PostgreSQL based ICT operations platform for the **Health Information Management Unit (HIMU), Teaching Hospital Peradeniya**.

The system combines ICT support ticketing, asset and inventory management, preventive maintenance, infrastructure monitoring, downtime tracking, software request management, procurement/replacement workflows, governance, audit verification, and reporting in a single offline-first application suitable for an internal hospital LAN.

## Technology Stack

- Laravel 12
- PHP 8.2+
- PostgreSQL
- Blade templates
- Local JavaScript and CSS assets
- No CDN dependencies for normal application use
- Database-backed sessions, notifications and queues
- SSH-based Ubuntu server monitoring
- Laravel Scheduler for recurring jobs
- HMAC-SHA-256 protected audit records
- Disable/supersede lifecycle instead of destructive deletion

## Project Features

### Authentication and Administration

- Mandatory login
- Role-based access control
- Multiple roles per user
- Least-privilege permission model
- User account enable/disable controls
- Administrator password reset
- Configurable application features and options
- Configurable hospital locations and support categories
- Failed-login and security event auditing

Initial roles include:

- System Administrator
- HIMU Administrator
- ICT Manager / Consultant
- Senior ICT Officer
- ICT Officer
- Technician
- Software Developer
- System / Database Officer
- Night Shift Officer
- Auditor / Verifier
- Department / Ward User
- Reporting User

### ICT Support Ticketing

- Support request creation
- Automatic ticket numbering
- Ticket categories and hospital locations
- Low, Normal, High and Critical priorities
- Assignment and reassignment to ICT staff
- Acknowledgement and work-start tracking
- Escalation workflow
- Resolution and verification
- Internal comments
- File attachments
- Private attachment storage
- Attachment integrity hashing
- Complete ticket timeline and status history
- SLA response and resolution tracking
- Automatic overdue escalation
- SLA breach notifications

### SLA Management

- Configurable response SLA
- Configurable resolution SLA
- Configurable escalation thresholds
- Priority-based SLA policies
- Automated SLA processing through Laravel Scheduler

### Asset and Inventory Management

- Full ICT asset register
- Asset tags
- Asset categories and models
- Manufacturer and serial number
- Hospital location assignment
- Purchase information
- Supplier details
- Purchase cost
- Warranty expiry
- Service agreement details
- IP/network information
- Lifecycle status
- Asset notes
- Asset movement history
- Fault history
- Fault resolution
- Repeated-fault tracking
- Replacement assessment
- Procurement linkage
- Historical audit trail
- Disable instead of delete

### Preventive Inspections

- Inspection templates
- Scheduled inspections
- Recurring preventive maintenance
- Weekly, monthly, quarterly, six-monthly and annual schedules
- Custom day intervals
- Inspection completion tracking
- Pending and overdue inspection visibility
- Automatic generation of inspection work items

### Server Monitoring

Designed for multiple Ubuntu servers, including mixed Ubuntu versions.

Each configured server can display:

- Online/offline state
- CPU utilisation
- Memory utilisation
- Disk utilisation
- Load average
- Process count
- Uptime
- Kernel information
- Ubuntu version
- Last successful monitoring snapshot

Server monitoring states include:

- Online
- Attention
- Stale
- Offline
- Not Checked

Resource warning thresholds can highlight high CPU, memory or disk usage.

### System / Workload Groups

Servers can be associated with operational workloads such as:

- HIMS
- LIMS
- RIS / PACS
- Infrastructure
- Other / Unassigned

The main dashboard can calculate workload availability from the actual monitored servers.

Availability states include:

- Online
- Degraded
- Offline
- Not Assigned

### Live Process Management

Authorised infrastructure users can inspect running processes including:

- PID
- Parent PID
- Process owner
- CPU percentage
- Memory percentage
- Process state
- Elapsed run time
- Command details

Process-control actions are restricted by role and governed by audit logging and process-protection rules.

### Governed Service Management

Approved services can be managed remotely through a restricted helper and allow-list.

Capabilities include:

- Service status inspection
- Start
- Stop
- Restart

The design avoids unrestricted administrative shell access from the web application.

### Secure SSH Monitoring

- Dedicated monitoring account
- SSH key authentication
- No stored bootstrap/admin passwords
- Host-key validation
- Restricted remote helper
- Remote action audit trail
- Configurable monitoring interval

### Historical Server Metrics

Periodic monitoring snapshots can record:

- CPU %
- Memory %
- Disk %
- Load averages
- Process count
- Uptime
- Reachability
- Monitoring errors
- Timestamp

This provides a foundation for:

- CPU trend reports
- Memory trend reports
- Disk-growth reports
- Infrastructure uptime reports
- Resource threshold alerts
- Server outage analysis
- Service-action history
- Process-control history

### System Downtime Tracking

Downtime records can include:

- Monitored system
- Outage classification
- Start and end date/time
- Duration
- Affected services/units
- Operational impact
- Root cause
- Resolution
- Reporting officer
- Verification officer
- Verification timestamp

HIMS, LIMS and RIS/PACS can be represented as monitored systems.

### Notification Centre

Database-backed notification centre for:

- SLA warnings
- SLA breaches
- Critical support requests
- Preventive inspection reminders
- Server/resource warnings
- Software request status changes
- Operational notifications

The application is structured for future locally hosted WebSocket/Reverb integration.

### Software Request Workflow

Software and development requests can follow a controlled lifecycle:

Requested → Reviewed → Approved → Development → Internal Test → UAT → Approved for Deployment → Deployed → Verified → Closed

Features include:

- Request ownership
- Priority
- Assigned developer
- Development tracking
- Testing status
- UAT status
- Deployment status
- Verification
- History and audit records

### Procurement and Replacement Workflow

Supports:

- Replacement requests
- New purchases
- Repairs
- Upgrades
- Linked assets
- Priority
- Technical justification
- Estimated cost
- Committee reference
- Approval status
- Decision notes
- Approving officer
- Approval timestamp

Replacing or retiring equipment does not delete the original asset history.

### Night-Shift Incident Management

- Night-shift incident register
- Incident reference number
- Date/time
- Location/unit
- Normal, High and Critical classifications
- Incident description
- Handover notes
- Status tracking
- Morning-team handover support

### Reports and Local Charts

Operational dashboards and reports cover:

- Support request trends
- Requests by category
- Requests by location
- Open and overdue requests
- Issues by status
- Average response time
- Average resolution time
- Repeated-fault assets
- Recurrent issues by location
- Preventive inspection completion
- Server availability
- Infrastructure health
- Night-shift incidents
- Software requests
- Procurement/replacement status

Charts use local application assets and do not require a CDN.

## Governance and Auditability

Significant actions can be written to a tamper-evident audit log containing:

- Event UUID
- Actor
- Action
- Entity type
- Record ID
- Previous values
- New values
- IP address
- User agent
- Request UUID
- Timestamp
- Previous audit hash
- HMAC integrity hash

### HMAC Audit Integrity

Audit integrity uses HMAC-SHA-256 with a secret separate from the Laravel application key.

Audit records can be reviewed and verified through the Audit Viewer.

### Audit Viewer and Verification

Authorised governance users can:

- Review audit events
- Inspect before/after values
- See actor and request metadata
- Verify HMAC integrity
- Mark audit records as verified
- Review infrastructure and process-control events

## No-Deletion Policy

Operational records are not physically deleted through normal application workflows.

Records are disabled, archived or superseded while retaining historical references and auditability.

## Offline-First Design

Normal application operation does not depend on external CDNs.

Local assets are used for:

- CSS
- JavaScript
- Charts
- Icons
- UI behaviour

This is intended for hospital LAN environments where internet connectivity may be limited or intentionally restricted.

## Database

PostgreSQL is the primary database for the project.

## Scheduler

Laravel Scheduler is used for:

- SLA escalation checks
- Recurring preventive inspection generation
- Server metric collection
- Scheduled operational tasks

## Security Principles

The application is designed around:

- Least privilege
- Need-to-know access
- Data minimisation
- Strong authentication controls
- CSRF protection
- Input validation
- Rate limiting
- Session protection
- Encryption for sensitive values where required
- Restricted infrastructure actions
- Auditability
- HMAC integrity verification
- No destructive record deletion
- Separation of application encryption and audit secrets

## Project Context

This project is intended for ICT operations within **Teaching Hospital Peradeniya** and is designed around hospital support, infrastructure availability, maintenance, asset governance and HIMU operational requirements.

Clinical or patient-identifiable information is not intended to be routinely stored in ICT support records unless specifically authorised and protected for an approved troubleshooting workflow.

## License

Project-specific licensing and deployment conditions should be defined by the project owner and Teaching Hospital Peradeniya governance requirements.

Laravel itself is licensed under the MIT License.
