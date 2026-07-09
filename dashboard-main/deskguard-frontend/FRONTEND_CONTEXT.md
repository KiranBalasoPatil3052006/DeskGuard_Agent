# DeskGuard Frontend - Architecture & Context

## Tech Stack
- **Framework**: React 19 + Vite 8
- **Styling**: Tailwind CSS 4
- **Routing**: React Router DOM 7
- **State**: React Context (Auth) + custom hooks
- **HTTP**: Axios with interceptors
- **Charts**: Recharts

## Folder Structure
```
src/
├── types/index.js              # Shared constants (severity, status enums)
├── services/
│   ├── api.js                  # Axios instance, interceptors, auth header
│   ├── auth.js                 # Login/logout/me
│   ├── dashboard.js            # Company/employee dashboard + chart trends
│   ├── machines.js             # CRUD + sub-resources (status, history, etc.)
│   ├── alerts.js               # Alerts + alert rules
│   └── settings.js             # Email recipients, notification prefs
├── context/
│   └── AuthContext.jsx         # Auth state, login/logout/refresh
├── hooks/
│   └── useApi.js               # Generic API hook with loading/error/pagination
├── layouts/
│   ├── MainLayout.jsx          # Sidebar + Navbar + Outlet
│   ├── Sidebar.jsx             # Navigation (Dashboard, Machines, Alerts, Settings, Profile)
│   └── Navbar.jsx              # Top bar (user info, logout)
├── components/
│   ├── auth/ProtectedRoute.jsx # Route guard
│   └── ui/
│       ├── LoadingState.jsx    # Spinner, LoadingRow, LoadingCard, PageLoading
│       ├── ErrorState.jsx      # Error with retry button
│       ├── EmptyState.jsx      # Empty data placeholder
│       ├── StatusBadge.jsx     # Status/Severity badge components
│       ├── Pagination.jsx      # Page navigation
│       └── HealthGauge.jsx     # SVG gauge for health score
├── pages/
│   ├── auth/Login.jsx          # Login form
│   ├── dashboard/Dashboard.jsx # Summary cards, CPU/RAM charts, recent machines
│   ├── machines/
│   │   ├── MachinesList.jsx    # Search, filter (online/offline/alert), pagination
│   │   └── MachineDetails.jsx  # 10 tabs: Overview, Performance, Activity, Inventory, Security, Devices, Processes, Services, Network, System Logs
│   ├── alerts/AlertsList.jsx    # Severity/status filters, acknowledge/resolve actions
│   └── settings/
│       ├── Settings.jsx        # Email recipients management + Profile tab
│       └── UserProfile.jsx     # User account details display
├── App.jsx                     # Route definitions
├── main.jsx                    # Entry point
└── index.css                   # Global styles + Tailwind imports
```

## API Data Flow
```
Component → useApi hook / manual fetch → service function → axios instance → proxy → Backend API
                                                 ↕
                                          interceptor:
                                          - injects Bearer token
                                          - handles 401 → redirect login
                                          - unwraps response.data
                                          - normalizes errors
```

## State Management
- **Auth**: React Context (user object, token from localStorage)
- **Data**: Per-page state via `useState` + `useCallback` fetch pattern
- **Pagination**: Custom `usePagination` hook with query param management

## Pages & API Endpoints

| Page | Endpoints Used | Key States |
|------|---------------|------------|
| Dashboard | GET /dashboard/company, GET /machines, GET /dashboard/charts/cpu, GET /dashboard/charts/ram, GET /alerts/critical (optional) | Loading, Error, Empty |
| Machines List | GET /machines (with search, status, page params) | Loading, Error, Empty, Pagination |
| Machine Details | GET /machines/{id}, /{id}/status, /{id}/history, /{id}/inventory, /{id}/security, /{id}/devices, /{id}/alerts, /{id}/timeline, /{id}/processes, /{id}/services, /{id}/startup-programs, /{id}/event-logs, /{id}/network | Loading, Error individual sections |
| Alerts | GET /alerts, POST /alerts/{id}/acknowledge, POST /alerts/{id}/resolve | Loading, Error, Empty, Modal |
| Settings | GET /settings/email-recipients, POST /settings/email-recipients, PUT /settings/email-recipients/{id}, DELETE /settings/email-recipients/{id} | Loading, Error, Empty, Add/Remove feedback |

## Error Handling
- **API Layer**: Axios interceptor catches 401 (auto-logout), formats error messages
- **Component Layer**: Every page shows Loading (spinner/skeleton), Error (with retry), Empty (with icon+message) states
- **User Feedback**: Settings page has inline success/error messages with auto-dismiss

## No Hardcoded Data
- All components display data from backend API responses only
- No mock data, no hardcoded lists, no fake metrics
- Every field maps to actual backend model columns via API responses