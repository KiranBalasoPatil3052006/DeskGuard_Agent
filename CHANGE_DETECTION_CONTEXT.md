# DeskGuard Change Detection System - Architecture & Context

## 1. Overview

The Change Detection System automatically detects hardware replacements, peripheral changes,
security changes, software installations/removals, network changes, and configuration changes
by comparing the current machine state against an approved baseline.

### Business Goals
- Protect AMC companies from false blame on unauthorized modifications
- Detect hardware replacements (RAM, SSD, CPU, etc.)
- Track peripheral connections/disconnections
- Monitor security posture changes (antivirus disabled, firewall off)
- Detect software installations and removals
- Provide instant answers to "What changed in this machine?"

## 2. Architecture

```
Agent (C#)
  │
  ├── Collectors gather current state
  ├── BaselineManager compares vs. stored baseline
  ├── ChangeDetectionService generates ChangeEvent list
  ├── ApiSenderService sends to POST /api/v1/agent/changes
  │
  ▼
Backend (PHP/Laravel)
  │
  ├── AgentChangeController receives payload
  ├── Validates and deduplicates
  ├── Stores in change_history table
  ├── AlertProcessor creates alerts for critical changes
  ├── Email notifications for high-severity changes
  │
  ▼
Frontend (React)
  │
  ├── Dashboard Recent Changes widget
  ├── Changes List page (filterable)
  ├── Machine Details → Changes tab (card-based UI)
  ├── Change Detail modal (previous/current state)
  ├── Approval workflow (approve/resync baseline)
```

## 3. Modules Affected

### Agent (DeskGuardAgent/)
- `Services/BaselineManager.cs` - Baseline comparison engine
- `Services/ChangeDetectionService.cs` - Change event generation and sending
- `Services/MonitoringService.cs` - Orchestration of baseline initialization
- `Models/HealthPayload.cs` - Payload structure
- `Interfaces/IApiSenderService.cs` - Change API contract
- `Services/ApiSenderService.cs` - Change payload transmission
- `Constants/ApiRoutes.cs` - Change endpoint route

### Backend (Backend/)
- `app/Models/ChangeHistory.php` - Change event model
- `app/Models/HardwareBaseline.php` - Hardware baseline model
- `app/Models/SoftwareBaseline.php` - Software baseline model
- `app/Models/SecurityBaseline.php` - NEW: Security baseline model
- `app/Models/ConfigurationBaseline.php` - NEW: Configuration baseline model
- `app/Services/BaselineService.php` - Baseline sync service
- `app/Http/Controllers/Api/V1/ChangeController.php` - Change listing/filtering
- `app/Http/Controllers/Api/V1/BaselineController.php` - Baseline management
- `app/Http/Controllers/Api/V1/AgentChangeController.php` - Agent change ingestion
- `app/Services/PayloadProcessors/AlertProcessor.php` - Alert generation from changes
- `database/migrations/` - New migrations for severity, security_baselines, configuration_baselines

### Frontend (dashboard-main/deskguard-frontend/)
- `src/pages/machines/MachineDetails.jsx` - Changes tab (card-based UI)
- `src/pages/changes/ChangesList.jsx` - Change history page
- `src/pages/dashboard/Dashboard.jsx` - Recent Changes widget (enhanced)
- `src/services/machines.js` - Change API calls
- `src/services/dashboard.js` - Recent changes API call

## 4. Database Schema

### Existing Tables
- `change_history` - Stores all change events
  - id, company_id, machine_id, category, change_type, item_identifier, item_label
  - previous_value, new_value, description, metadata, detected_at, timestamps
  - **NEW**: severity (enum: information, warning, important, critical)

- `hardware_baselines` - Approved hardware state
  - id, company_id, machine_id, component, manufacturer, model, serial_number
  - capacity, speed, slot_info, properties, baselined_at

- `software_baselines` - Approved software inventory
  - id, company_id, machine_id, software_name, version, publisher, architecture, baselined_at

### NEW Tables
- `security_baselines` - Approved security state
  - id, company_id, machine_id, component, value, collected_at

- `configuration_baselines` - Approved configuration state
  - id, company_id, machine_id, setting_key, setting_value, collected_at

## 5. Change Categories & Severity

| Category | Severity | Examples |
|----------|----------|---------|
| Hardware | Critical | RAM replaced, SSD swapped, CPU changed |
| Hardware | Important | BIOS updated, GPU changed |
| Security | Critical | Antivirus disabled, Firewall disabled |
| Security | Important | Signature out of date, Multiple logins failed |
| Peripheral | Warning | Printer removed, USB disconnected |
| Peripheral | Information | New USB connected, Monitor connected |
| Software | Important | Critical software removed |
| Software | Information | New software installed, Software updated |
| Network | Warning | MAC address changed, Adapter disabled |
| Network | Information | IP changed, Speed changed |
| Configuration | Important | Startup program disabled, Service stopped |
| Configuration | Warning | Service start type changed |

## 6. Change Event Payload Format

```json
{
  "machine_uid": "DG-HASH-001",
  "changes": [
    {
      "category": "hardware",
      "change_type": "modified",
      "severity": "critical",
      "item_identifier": "RAM-SLOT-0",
      "item_label": "RAM Module 0",
      "previous_value": "Samsung 16GB DDR4 3200MHz",
      "new_value": "Kingston 4GB DDR4 2400MHz",
      "description": "RAM replaced: Samsung 16GB DDR4 → Kingston 4GB DDR4",
      "detected_at": "2026-07-14T08:15:00.000Z"
    }
  ]
}
```

## 7. Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Duplicate change events | Agent-side dedup: skip sending if same change already detected in current cycle |
| False positives from normal operations | Severity-based filtering; baselines update on approved changes |
| Baseline data loss | Agent persists baselines in Storage/baseline.json; backend stores server-side |
| Performance impact of comparison | Comparison is agent-side (local, fast); only changes sent over network |
| Migration conflicts | New migrations have unique timestamps; check existing before creating |

## 8. Implementation Phases

1. ✅ Discovery & Architecture Planning
2. 🔄 Baseline System (security_baselines, configuration_baselines tables + models)
3. Comparison Engine (Agent-side diff for all categories)
4. Change Event Generation (standardized payload with severity)
5. Backend Processing (severity column, validation, dedup)
6. Severity Engine (auto-classification rules)
7. Notification Engine (email for critical changes)
8. Frontend Changes Tab (card-based UI)
9. Change Detail Page (modal with full details)
10. Approval Workflow (approve/resync baseline)
11. Dashboard Widget (enhanced recent changes)
12. Optimization (indexes, batch processing)
13. Testing (end-to-end verification)
14. Documentation Update

## 9. Dependencies

- Agent must have initialized baseline before comparison starts
- Backend migrations must run in order (severity column before controller updates)
- Frontend API calls depend on backend endpoints existing
- Email system requires email_recipients table and mail configuration
