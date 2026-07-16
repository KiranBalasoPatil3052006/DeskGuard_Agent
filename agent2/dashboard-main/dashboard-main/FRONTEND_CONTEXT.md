# DeskGuard Frontend - Frontend Context

## Account Management Module

### Overview
The Account Management page at `/accounts` allows Super Admin users to manage administrator accounts. It combines a creation/editing form with a data table on a single page.

### Route
| Path | Component | Auth |
|------|-----------|------|
| `/accounts` | `AccountsList` | Protected (Super Admin via sidebar visibility) |

### New Files
| File | Path | Purpose |
|------|------|---------|
| `accounts.js` | `src/services/` | API service for account CRUD operations |
| `AccountsList.jsx` | `src/pages/accounts/` | Complete account management page |

### Modified Files
| File | Change |
|------|--------|
| `App.jsx` | Added lazy-loaded route for `/accounts` |
| `Sidebar.jsx` | Added "Create Account" menu item (visible only to Super Admin role) |

### Component: AccountsList.jsx
The page has two columns on large screens:
- **Left (5 cols)**: Create/Edit form
- **Right (7 cols)**: Searchable, filterable, paginated data table

#### Form Fields
| Field | Type | Validation |
|-------|------|------------|
| Full Name | Text | Required |
| Email | Email | Required, valid format |
| Password | Password (hidden on edit) | Required on create, min 6 chars |
| Confirm Password | Password (hidden on edit) | Must match password |
| Employee ID | Text (auto-generated) | Required, unique, EMP-XXXX format |

#### Table Columns
| Column | Description |
|--------|-------------|
| Employee ID | Monospace formatted |
| Name | Full name |
| Email | Email address |
| Role | Role badge |
| Status | Active/Disabled badge |
| Created | Date |
| Last Login | DateTime |
| Actions | View, Edit, Disable/Enable, Delete buttons |

#### Filters
- **Search**: By name, email, or employee ID
- **Status**: All / Active / Disabled toggle buttons

#### Pagination
- Shows page numbers, prev/next buttons
- Displays "Showing X-Y of Z" text

#### Modals
- **View modal**: Shows all account details in a read-only dialog
- **Delete confirmation**: "Are you sure?" dialog with Cancel/Delete options

#### States
- **Loading**: Spinner during data fetch
- **Empty**: "No administrator accounts found" with contextual message
- **Error**: Alert banner with error details
- **Success**: Alert banner on successful create/update/delete

### API Service (`accounts.js`)
| Function | Method | Route |
|----------|--------|-------|
| `getAccounts(params)` | GET | `/accounts` |
| `getAccount(id)` | GET | `/accounts/{id}` |
| `createAccount(data)` | POST | `/accounts` |
| `updateAccount(id, data)` | PUT | `/accounts/{id}` |
| `deleteAccount(id)` | DELETE | `/accounts/{id}` |
| `disableAccount(id)` | PATCH | `/accounts/{id}/disable` |
| `enableAccount(id)` | PATCH | `/accounts/{id}/enable` |
| `generateEmployeeId()` | GET | `/accounts/employee-id/next` |

### Role-Based Visibility
- The "Create Account" sidebar link only appears when `user.role === 'Super Admin'`
- The `/accounts` route is protected by the existing `ProtectedRoute` (requires JWT auth)
- All CRUD operations validate the Super Admin role on the backend

---

## Alert Threshold Management Module

### Overview
The Alert Threshold Management page at `/settings/alert-thresholds` allows administrators to create, edit, duplicate, and delete alert profiles. Each profile contains categorized thresholds that define when alerts should trigger for different types of systems.

### Route
| Path | Component | Auth |
|------|-----------|------|
| `/settings/alert-thresholds` | `AlertThresholds` | Protected |

### New Files
| File | Path | Purpose |
|------|------|---------|
| `alertProfiles.js` | `src/services/` | API service for alert profile CRUD + assignment |
| `AlertThresholds.jsx` | `src/pages/settings/` | Complete alert threshold management page |

### Modified Files
| File | Change |
|------|--------|
| `App.jsx` | Added lazy-loaded route for `/settings/alert-thresholds` |
| `Settings.jsx` | Replaced old Alert Thresholds tab (tab 4) with link to new dedicated page |

### Component: AlertThresholds.jsx
Two-column layout on large screens:
- **Left (5 cols)**: Profile list with search and pagination
- **Right (7 cols)**: Threshold editor for selected profile

#### Profile List (Table)
| Column | Description |
|--------|-------------|
| Name | Profile name with DEFAULT badge |
| Companies | Count of assigned companies |
| Machines | Count of assigned machine overrides |
| Actions | Duplicate (copy), Delete (disabled when assigned) |

#### Profile Details Panel
- Editable name and description inputs
- Assigned companies list with unassign button
- "Assign Company" button → modal with company ID input
- **Threshold Editor**: Categorized form sections:
  - **Performance**: CPU/RAM warning %, critical %, duration
  - **Storage**: Disk warning %, critical %, SMART enabled
  - **Availability**: Offline warning/critical minutes
  - **Authentication**: Failed login warning/critical count
  - **Network**: Network disconnect warning count
- **Save Changes** button
- **System Critical Events** read-only table (8 fixed rules)

#### Modals
- **Create Profile**: Name + Description form
- **Assign Company**: Company ID input
- **Delete Confirmation**: Warning dialog (disabled if profile assigned)

#### States
- **Loading**: Text indicator during data fetch
- **Empty**: "No alert profiles found" with icon
- **Error**: Alert banner with error details
- **Profile loading**: Text indicator during profile detail load

### API Service (`alertProfiles.js`)
| Function | Method | Route |
|----------|--------|-------|
| `getAlertProfiles(params)` | GET | `/alert-profiles` |
| `getAlertProfile(id)` | GET | `/alert-profiles/{id}` |
| `createAlertProfile(data)` | POST | `/alert-profiles` |
| `updateAlertProfile(id, data)` | PUT | `/alert-profiles/{id}` |
| `deleteAlertProfile(id)` | DELETE | `/alert-profiles/{id}` |
| `duplicateAlertProfile(id)` | POST | `/alert-profiles/{id}/duplicate` |
| `assignProfileToCompany(profileId, companyId)` | POST | `/alert-profiles/{id}/companies` |
| `unassignProfileFromCompany(profileId, companyId)` | DELETE | `/alert-profiles/{id}/companies/{companyId}` |
| `assignProfileToMachine(profileId, machineId)` | POST | `/alert-profiles/{id}/machines` |
| `unassignProfileFromMachine(profileId, machineId)` | DELETE | `/alert-profiles/{id}/machines/{machineId}` |

### Fixed Rules (Always-Enabled Critical Events)
These 8 events are displayed in a read-only panel and cannot be disabled:
- RAM Changed, SSD Changed, HDD Changed, CPU Changed
- Motherboard Changed, BIOS Changed
- Antivirus Removed, Firewall Disabled
