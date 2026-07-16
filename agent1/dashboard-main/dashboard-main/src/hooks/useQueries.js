/**
 * PERFORMANCE OPTIMIZED: TanStack React Query hooks for DeskGuard.
 *
 * Key optimizations applied:
 * 1. Increased staleTime across the board:
 *    - Real-time status: 15s (unchanged — needs freshness)
 *    - Dashboard/machines/alerts: 60s (was 15-30s)
 *    - Inventory/security/services: 120s (rarely changes)
 *    - Changes/event logs: 120s (append-only data)
 *
 * 2. Added keepPreviousData to all paginated/filtered queries:
 *    - Machines list, Alerts list, Devices list
 *    - This keeps the old table visible during refetch, eliminating
 *      the "spinner flash" on filter/page changes.
 *
 * 3. Added gcTime (garbage collection time) for infrequently visited tabs:
 *    - Machine detail sub-resources get 10-minute cache retention
 */
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { getMachines, getMachine, getMachineStatus, getMachineHistory, getMachineInventory, getMachineSecurity, getMachineDevices, getDeviceIssues, getMachineAlerts, getMachineTimeline, getMachineProcesses, getMachineServices, getMachineStartupPrograms, getMachineEventLogs, getMachineNetwork, getMachineChanges } from '../services/machines';
import { getAlerts, acknowledgeAlert, resolveAlert } from '../services/alerts';
import { getCompanyDashboard, getRecentChanges as getDashRecentChanges } from '../services/dashboard';
import { getChanges, updateChangeStatus } from '../services/changes';

function extractList(res) {
  return Array.isArray(res?.data?.data) ? res.data.data : (Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []));
}

function extractItem(res) {
  return res?.data ?? res ?? null;
}

// ============================================================================
// MACHINE LIST & DETAIL
// ============================================================================

export function useMachines(params = {}, opts = {}) {
  return useQuery({
    queryKey: ['machines', params],
    queryFn: () => getMachines(params),
    staleTime: 60000, // PERFORMANCE: 60s (was 30s) — machine list doesn't change that often
    placeholderData: keepPreviousData, // PERFORMANCE: Keep old table visible during refetch
    select: (res) => ({ data: extractList(res), meta: res?.data?.meta || res?.meta || null }),
    ...opts,
  });
}

export function useMachine(id, opts = {}) {
  return useQuery({
    queryKey: ['machine', id],
    queryFn: () => getMachine(id),
    enabled: !!id,
    staleTime: 60000,
    select: extractItem,
    ...opts,
  });
}

export function useMachineStatus(id, opts = {}) {
  return useQuery({
    queryKey: ['machineStatus', id],
    queryFn: () => getMachineStatus(id),
    enabled: !!id,
    staleTime: 15000, // PERFORMANCE: Keep 15s — this is real-time health data
    select: extractItem,
    ...opts,
  });
}

// ============================================================================
// MACHINE DETAIL TABS (loaded on-demand via enabled flag)
// ============================================================================

export function useMachineHistory(id, params = {}, opts = {}) {
  return useQuery({
    queryKey: ['machineHistory', id, params],
    queryFn: () => getMachineHistory(id, params),
    enabled: !!id,
    staleTime: 60000, // PERFORMANCE: 60s (was 30s) — historical data is immutable within the day
    gcTime: 600000, // PERFORMANCE: Keep in cache 10 min after tab switch
    select: (res) => extractList(res),
    ...opts,
  });
}

export function useMachineInventory(id, opts = {}) {
  return useQuery({
    queryKey: ['machineInventory', id],
    queryFn: () => getMachineInventory(id),
    enabled: !!id,
    staleTime: 120000, // PERFORMANCE: 120s — hardware/software inventory rarely changes
    gcTime: 600000,
    select: extractItem,
    ...opts,
  });
}

export function useMachineSecurity(id, opts = {}) {
  return useQuery({
    queryKey: ['machineSecurity', id],
    queryFn: () => getMachineSecurity(id),
    enabled: !!id,
    staleTime: 120000, // PERFORMANCE: 120s (was 60s) — security posture is slow-changing
    gcTime: 600000,
    select: extractItem,
    ...opts,
  });
}

export function useMachineDevices(id, params = {}, opts = {}) {
  return useQuery({
    queryKey: ['machineDevices', id, params],
    queryFn: () => getMachineDevices(id, params),
    enabled: !!id,
    staleTime: 60000, // PERFORMANCE: 60s (was 30s)
    placeholderData: keepPreviousData, // PERFORMANCE: Keep old page during pagination
    gcTime: 600000,
    select: extractItem,
    ...opts,
  });
}

export function useDeviceIssues(id, deviceName, opts = {}) {
  return useQuery({
    queryKey: ['deviceIssues', id, deviceName],
    queryFn: () => getDeviceIssues(id, deviceName),
    enabled: !!id && !!deviceName,
    staleTime: 30000,
    select: extractItem,
    ...opts,
  });
}

export function useMachineAlerts(id, opts = {}) {
  return useQuery({
    queryKey: ['machineAlerts', id],
    queryFn: () => getMachineAlerts(id),
    enabled: !!id,
    staleTime: 30000, // PERFORMANCE: 30s (was 15s) — alerts don't change per-second
    gcTime: 600000,
    select: (res) => extractList(res),
    ...opts,
  });
}

export function useMachineTimeline(id, params = {}, opts = {}) {
  return useQuery({
    queryKey: ['machineTimeline', id, params],
    queryFn: () => getMachineTimeline(id, params),
    enabled: !!id,
    staleTime: 60000, // PERFORMANCE: 60s (was 30s) — timeline is append-only
    gcTime: 600000,
    select: (res) => extractList(res),
    ...opts,
  });
}

export function useMachineProcesses(id, opts = {}) {
  return useQuery({
    queryKey: ['machineProcesses', id],
    queryFn: () => getMachineProcesses(id),
    enabled: !!id,
    staleTime: 15000, // PERFORMANCE: Keep 15s — processes are dynamic
    gcTime: 300000,
    select: (res) => extractList(res),
    ...opts,
  });
}

export function useMachineServices(id, opts = {}) {
  return useQuery({
    queryKey: ['machineServices', id],
    queryFn: () => getMachineServices(id),
    enabled: !!id,
    staleTime: 120000, // PERFORMANCE: 120s (was 60s) — services rarely change
    gcTime: 600000,
    select: (res) => extractList(res),
    ...opts,
  });
}

export function useMachineStartupPrograms(id, opts = {}) {
  return useQuery({
    queryKey: ['machineStartup', id],
    queryFn: () => getMachineStartupPrograms(id),
    enabled: !!id,
    staleTime: 120000, // PERFORMANCE: 120s (was 60s) — startup programs rarely change
    gcTime: 600000,
    select: (res) => extractList(res),
    ...opts,
  });
}

export function useMachineEventLogs(id, opts = {}) {
  return useQuery({
    queryKey: ['machineEventLogs', id],
    queryFn: () => getMachineEventLogs(id),
    enabled: !!id,
    staleTime: 120000, // PERFORMANCE: 120s (was 60s) — event logs are append-only
    gcTime: 600000,
    select: (res) => extractList(res),
    ...opts,
  });
}

export function useMachineNetwork(id, opts = {}) {
  return useQuery({
    queryKey: ['machineNetwork', id],
    queryFn: () => getMachineNetwork(id),
    enabled: !!id,
    staleTime: 120000, // PERFORMANCE: 120s (was 60s) — network config rarely changes
    gcTime: 600000,
    select: extractItem,
    ...opts,
  });
}

export function useMachineChanges(id, opts = {}) {
  return useQuery({
    queryKey: ['machineChanges', id],
    queryFn: () => getMachineChanges(id, { category: 'hardware' }),
    enabled: !!id,
    staleTime: 120000, // PERFORMANCE: 120s (was 30s) — changes are infrequent
    gcTime: 600000,
    select: (res) => {
      const list = extractList(res);
      return list.filter(c => c.category === 'hardware' && c.previous_value != null && c.previous_value !== '' && c.previous_value !== '(none)');
    },
    ...opts,
  });
}

// ============================================================================
// ALERTS (PAGE-LEVEL)
// ============================================================================

export function useAlerts(params = {}, opts = {}) {
  return useQuery({
    queryKey: ['alerts', params],
    queryFn: () => getAlerts(params),
    staleTime: 30000, // PERFORMANCE: 30s (was 15s)
    placeholderData: keepPreviousData, // PERFORMANCE: Keep old table during filter/page changes
    select: (res) => ({ data: extractList(res), meta: res?.data?.meta || res?.meta || null }),
    ...opts,
  });
}

// ============================================================================
// DASHBOARD
// ============================================================================

export function useDashboard(opts = {}) {
  return useQuery({
    queryKey: ['dashboard'],
    queryFn: () => getCompanyDashboard(),
    staleTime: 60000, // PERFORMANCE: 60s (was 30s) — backend now caches chart data
    select: (res) => res?.data ?? res ?? null,
    ...opts,
  });
}

export function useChanges(params = {}, opts = {}) {
  return useQuery({
    queryKey: ['changes', params],
    queryFn: () => getChanges(params),
    staleTime: 60000, // PERFORMANCE: 60s (was 30s)
    placeholderData: keepPreviousData,
    select: (res) => {
      const items = extractList(res);
      const filtered = items.filter(c => c.previous_value != null && c.previous_value !== '' && c.previous_value !== '(none)');
      return { data: filtered, meta: res?.data?.meta || res?.meta || { total: filtered.length } };
    },
    ...opts,
  });
}

export function useDashboardRecentChanges(limit = 5, opts = {}) {
  return useQuery({
    queryKey: ['dashboardRecentChanges', limit],
    queryFn: () => getDashRecentChanges(limit),
    staleTime: 60000, // PERFORMANCE: 60s (was 30s)
    select: (res) => {
      const list = extractList(res);
      return list.filter(c => c.category === 'hardware' && c.previous_value != null && c.previous_value !== '' && c.previous_value !== '(none)');
    },
    ...opts,
  });
}

// ============================================================================
// MUTATIONS
// ============================================================================

export function useAcknowledgeAlert() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id) => acknowledgeAlert(id),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['alerts'] }); qc.invalidateQueries({ queryKey: ['machineAlerts'] }); },
  });
}

export function useResolveAlert() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, note }) => resolveAlert(id, note),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['alerts'] }); qc.invalidateQueries({ queryKey: ['machineAlerts'] }); },
  });
}

export function useUpdateChangeStatus() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ changeId, status, note }) => updateChangeStatus(changeId, status, note),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['changes'] }); qc.invalidateQueries({ queryKey: ['machineChanges'] }); },
  });
}
