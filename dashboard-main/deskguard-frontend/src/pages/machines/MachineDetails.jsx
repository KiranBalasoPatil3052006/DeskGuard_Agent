import { useEffect, useState, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  getMachine, getMachineStatus, getMachineHistory, getMachineInventory,
  getMachineSecurity, getMachineDevices, getMachineAlerts, getMachineTimeline,
  getMachineProcesses, getMachineServices, getMachineStartupPrograms,
  getMachineEventLogs, getMachineNetwork,
} from '../../services/machines';
import { acknowledgeAlert, resolveAlert } from '../../services/alerts';
import { PageLoading } from '../../components/ui/LoadingState';
import { ErrorState } from '../../components/ui/ErrorState';
import { EmptyState } from '../../components/ui/EmptyState';
import { StatusBadge, SeverityBadge } from '../../components/ui/StatusBadge';
import { HealthGauge } from '../../components/ui/HealthGauge';
import { FiArrowLeft, FiCpu, FiHardDrive, FiMonitor, FiActivity, FiShield, FiGrid, FiClock, FiAlertTriangle, FiServer, FiTool, FiDatabase, FiThermometer, FiBattery, FiWifi, FiDownload, FiUpload } from 'react-icons/fi';
import { LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer, AreaChart, Area, BarChart, Bar, Cell } from 'recharts';

const TABS = [
  { id: 'overview', label: 'Overview', icon: FiMonitor },
  { id: 'performance', label: 'Performance', icon: FiActivity },
  { id: 'activity', label: 'Activity', icon: FiClock },
  { id: 'inventory', label: 'Inventory', icon: FiServer },
  { id: 'security', label: 'Security', icon: FiShield },
  { id: 'devices', label: 'Devices', icon: FiTool },
  { id: 'processes', label: 'Processes', icon: FiCpu },
  { id: 'services', label: 'Services', icon: FiServer },
  { id: 'network', label: 'Network', icon: FiHardDrive },
  { id: 'logs', label: 'System Logs', icon: FiDatabase },
];

function getPerformanceLevel(value, type) {
  const v = parseFloat(value);
  if (isNaN(v)) return { label: 'No Data', color: 'text-gray-400', bg: 'bg-gray-100', bar: '#9CA3AF' };
  switch (type) {
    case 'cpu':
      if (v <= 30) return { label: 'Idle', desc: 'Excellent performance', color: 'text-emerald-700', bg: 'bg-emerald-50 dark:bg-emerald-900/20', bar: '#10B981' };
      if (v <= 60) return { label: 'Moderate', desc: 'Normal workload', color: 'text-amber-700', bg: 'bg-amber-50 dark:bg-amber-900/20', bar: '#F59E0B' };
      return { label: 'High Load', desc: 'Consider closing apps', color: 'text-red-700', bg: 'bg-red-50 dark:bg-red-900/20', bar: '#EF4444' };
    case 'ram':
      if (v <= 50) return { label: 'Optimal', desc: 'Plenty of memory free', color: 'text-emerald-700', bg: 'bg-emerald-50 dark:bg-emerald-900/20', bar: '#10B981' };
      if (v <= 75) return { label: 'Normal', desc: 'Sufficient memory', color: 'text-amber-700', bg: 'bg-amber-50 dark:bg-amber-900/20', bar: '#F59E0B' };
      return { label: 'Critical', desc: 'Low memory available', color: 'text-red-700', bg: 'bg-red-50 dark:bg-red-900/20', bar: '#EF4444' };
    case 'disk':
      if (v <= 70) return { label: 'Healthy', desc: 'Plenty of free space', color: 'text-emerald-700', bg: 'bg-emerald-50 dark:bg-emerald-900/20', bar: '#10B981' };
      if (v <= 85) return { label: 'Warning', desc: 'Disk filling up', color: 'text-amber-700', bg: 'bg-amber-50 dark:bg-amber-900/20', bar: '#F59E0B' };
      return { label: 'Critical', desc: 'Almost full — free up space', color: 'text-red-700', bg: 'bg-red-50 dark:bg-red-900/20', bar: '#EF4444' };
    case 'temp':
      if (v <= 60) return { label: 'Cool', desc: 'Normal temperature', color: 'text-emerald-700', bg: 'bg-emerald-50 dark:bg-emerald-900/20', bar: '#10B981' };
      if (v <= 80) return { label: 'Warm', desc: 'Under load', color: 'text-amber-700', bg: 'bg-amber-50 dark:bg-amber-900/20', bar: '#F59E0B' };
      return { label: 'Hot', desc: 'Possible thermal throttling', color: 'text-red-700', bg: 'bg-red-50 dark:bg-red-900/20', bar: '#EF4444' };
    case 'battery_wear':
      if (v <= 10) return { label: 'Excellent', desc: 'Battery like new', color: 'text-emerald-700', bg: 'bg-emerald-50 dark:bg-emerald-900/20', bar: '#10B981' };
      if (v <= 25) return { label: 'Good', desc: 'Minor capacity loss', color: 'text-amber-700', bg: 'bg-amber-50 dark:bg-amber-900/20', bar: '#F59E0B' };
      if (v <= 50) return { label: 'Degraded', desc: 'Replace soon', color: 'text-orange-700', bg: 'bg-orange-50 dark:bg-orange-900/20', bar: '#F97316' };
      return { label: 'Critical', desc: 'Battery needs replacement', color: 'text-red-700', bg: 'bg-red-50 dark:bg-red-900/20', bar: '#EF4444' };
    default:
      return { label: 'Unknown', desc: '', color: 'text-gray-500', bg: 'bg-gray-100', bar: '#9CA3' };
  }
}

function PerformanceBar({ value, type, label, unit = '%' }) {
  const level = getPerformanceLevel(value, type);
  const pct = Math.min(parseFloat(value) || 0, 100);
  return (
    <div className={`rounded-lg p-4 ${level.bg}`}>
      <div className="flex items-center justify-between mb-2">
        <span className="text-sm font-medium text-gray-700">{label}</span>
        <span className={`text-sm font-bold ${level.color}`}>{pct}{unit}</span>
      </div>
      <div className="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
        <div className="h-full rounded-full transition-all duration-500" style={{ width: `${pct}%`, backgroundColor: level.bar }} />
      </div>
      <div className="flex items-center justify-between mt-1.5">
        <span className={`text-xs font-medium ${level.color}`}>{level.label}</span>
        <span className="text-[11px] text-gray-400">{level.desc}</span>
      </div>
    </div>
  );
}

export default function MachineDetails() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('overview');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [machine, setMachine] = useState(null);
  const [currentStatus, setCurrentStatus] = useState(null);
  const [history, setHistory] = useState([]);
  const [inventory, setInventory] = useState({ hardware: null, software: [] });
  const [security, setSecurity] = useState({ antivirus: null, firewall: null, logins: [], updates: [] });
  const [devices, setDevices] = useState({ connected: [], usb: [], events: [] });
  const [alerts, setAlerts] = useState([]);
  const [timeline, setTimeline] = useState([]);
  const [processes, setProcesses] = useState([]);
  const [services, setServices] = useState([]);
  const [startupPrograms, setStartupPrograms] = useState([]);
  const [eventLogs, setEventLogs] = useState([]);
  const [networkData, setNetworkData] = useState({ adapters: [], disks: [] });

  const fetchAll = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [machRes, statusRes, historyRes, invRes, secRes, devRes, alertRes, timelineRes, procRes, servRes, startupRes, eventLogRes, netRes] = await Promise.all([
        getMachine(id), getMachineStatus(id), getMachineHistory(id),
        getMachineInventory(id), getMachineSecurity(id), getMachineDevices(id),
        getMachineAlerts(id), getMachineTimeline(id), getMachineProcesses(id),
        getMachineServices(id), getMachineStartupPrograms(id),
        getMachineEventLogs(id), getMachineNetwork(id),
      ]);
      setMachine(machRes?.data || machRes);
      setCurrentStatus(statusRes?.data || statusRes);
      setHistory(Array.isArray(historyRes?.data) ? historyRes.data : (historyRes?.data || []));
      const inv = invRes?.data || invRes || {};
      setInventory({ hardware: inv.hardware || inv.hardware_inventory, software: inv.software || inv.software_inventory || [] });
      const sec = secRes?.data || secRes || {};
      setSecurity({
        antivirus: sec.antivirus || sec.antivirus_status,
        firewall: sec.firewall || sec.firewall_status,
        logins: sec.logins || sec.login_activities || [],
        updates: sec.updates || sec.windows_updates || [],
      });
      const dev = devRes?.data || devRes || {};
      setDevices({
        connected: dev.connected || dev.connected_devices || [],
        usb: dev.usb || dev.usb_activities || [],
        events: dev.events || dev.device_events || [],
      });
      setAlerts(alertRes?.data || alertRes?.data || []);
      setTimeline(timelineRes?.data || []);
      setProcesses(procRes?.data || []);
      setServices(servRes?.data || []);
      setStartupPrograms(startupRes?.data || []);
      setEventLogs(eventLogRes?.data || []);
      const net = netRes?.data || netRes || {};
      setNetworkData({ adapters: net.adapters || net.network_adapters || [], disks: net.disks || [] });
    } catch (err) {
      setError(err.message || 'Failed to load machine details');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const cs = currentStatus || {};
  const m = machine || {};
  const healthScore = cs.cpu_percentage != null && cs.ram_percentage != null
    ? Math.round(100 - ((parseFloat(cs.cpu_percentage) + parseFloat(cs.ram_percentage)) / 2))
    : null;

  if (loading) return <PageLoading />;
  if (error) return <ErrorState message={error} onRetry={fetchAll} />;

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <button onClick={() => navigate('/machines')} className="p-2 rounded-lg hover:bg-gray-100"><FiArrowLeft className="w-5 h-5 text-gray-500" /></button>
        <div>
          <h1 className="text-xl font-bold text-gray-900">{m.hostname || m.device_name || m.machine_uid}</h1>
          <p className="text-sm text-gray-500">{m.operating_system} {m.os_version} · {m.machine_uid}</p>
        </div>
        <StatusBadge status={m.is_online ? 'online' : 'offline'} className="ml-auto" />
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-1 flex overflow-x-auto">
        {TABS.map(tab => (
          <button key={tab.id} onClick={() => setActiveTab(tab.id)} className={`flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${activeTab === tab.id ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'}`}>
            <tab.icon className="w-4 h-4" />
            {tab.label}
          </button>
        ))}
      </div>

      {activeTab === 'overview' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2 space-y-6">
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h3 className="text-sm font-semibold text-gray-700 mb-4">Machine Information</h3>
              <div className="grid grid-cols-2 gap-4 text-sm">
                {[
                  ['Hostname', m.hostname],
                  ['Device Name', m.device_name],
                  ['Domain', m.domain_name],
                  ['Operating System', `${m.operating_system || ''} ${m.os_version || ''}`.trim()],
                  ['Architecture', m.architecture],
                  ['Manufacturer', m.manufacturer],
                  ['Model', m.model],
                  ['Serial Number', m.serial_number],
                  ['Processor', m.processor || cs.cpu_clock_speed ? `${m.processor || ''} @ ${cs.cpu_clock_speed} GHz` : m.processor],
                  ['Processor Cores', cs.cpu_core_count ? `${cs.cpu_core_count} Cores / ${(parseInt(cs.cpu_core_count) * 1.5).toFixed(0)} Threads (est.)` : null],
                  ['RAM', m.ram_gb ? `${m.ram_gb} GB` : null],
                  ['Uptime', m.uptime_seconds ? `${Math.floor(m.uptime_seconds / 3600)}h ${Math.floor((m.uptime_seconds % 3600) / 60)}m` : null],
                  ['Employee Mobile', m.employee_mobile_number],
                  ['Assigned User', m.employee_name || m.current_user],
                  ['IP Address', m.ip_address],
                  ['MAC Address', m.mac_address],
                ].filter(([, v]) => v != null).map(([label, value]) => (
                  <div key={label}><span className="text-gray-500">{label}</span><p className="font-medium text-gray-900">{value}</p></div>
                ))}
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h3 className="text-sm font-semibold text-gray-700 mb-4">Current Status</h3>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                <div className="bg-gray-50 rounded-lg p-3"><p className="text-xs text-gray-500">CPU</p><p className="text-lg font-bold text-gray-900">{cs.cpu_percentage ?? '—'}%</p></div>
                <div className="bg-gray-50 rounded-lg p-3"><p className="text-xs text-gray-500">RAM</p><p className="text-lg font-bold text-gray-900">{cs.ram_percentage ?? '—'}%</p></div>
                <div className="bg-gray-50 rounded-lg p-3"><p className="text-xs text-gray-500">Disk</p><p className="text-lg font-bold text-gray-900">{cs.disk_percentage ?? '—'}%</p></div>
                <div className="bg-gray-50 rounded-lg p-3"><p className="text-xs text-gray-500">Battery</p><p className="text-lg font-bold text-gray-900">{cs.battery_percentage != null ? `${cs.battery_percentage}%` : 'N/A'}</p></div>
              </div>

              <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center mt-4">
                <div className="bg-gray-50 rounded-lg p-3">
                  <p className="text-xs text-gray-500 flex items-center justify-center gap-1"><FiThermometer className="w-3 h-3" />CPU Temp</p>
                  <p className="text-lg font-bold text-gray-900">{cs.cpu_temperature != null ? `${cs.cpu_temperature}°C` : '—'}</p>
                </div>
                <div className="bg-gray-50 rounded-lg p-3">
                  <p className="text-xs text-gray-500 flex items-center justify-center gap-1"><FiBattery className="w-3 h-3" />Battery Wear</p>
                  <p className="text-lg font-bold text-gray-900">{cs.battery_wear_level != null ? `${cs.battery_wear_level}%` : '—'}</p>
                </div>
                <div className="bg-gray-50 rounded-lg p-3">
                  <p className="text-xs text-gray-500 flex items-center justify-center gap-1"><FiDownload className="w-3 h-3" />Download</p>
                  <p className="text-lg font-bold text-gray-900">{cs.network_received_bytes != null ? `${(cs.network_received_bytes / 1048576).toFixed(1)} MB` : '—'}</p>
                </div>
                <div className="bg-gray-50 rounded-lg p-3">
                  <p className="text-xs text-gray-500 flex items-center justify-center gap-1"><FiUpload className="w-3 h-3" />Upload</p>
                  <p className="text-lg font-bold text-gray-900">{cs.network_sent_bytes != null ? `${(cs.network_sent_bytes / 1048576).toFixed(1)} MB` : '—'}</p>
                </div>
              </div>

              <div className="flex items-center justify-between mt-4 text-sm">
                <span className="text-gray-500">
                  <span className="font-medium">Pending Updates:</span> {cs.pending_updates ?? 0}
                  {cs.pending_updates > 0 && <span className="text-amber-600 ml-1">(Action needed)</span>}
                  {cs.pending_updates === 0 && <span className="text-emerald-600 ml-1">(Up to date)</span>}
                </span>
                <span className="text-gray-500">
                  Last Communication: {cs.collected_at || cs.last_collected_at ? new Date(cs.collected_at || cs.last_collected_at).toLocaleString() : m.last_heartbeat_at ? new Date(m.last_heartbeat_at).toLocaleString() : 'Never'}
                </span>
              </div>
            </div>
          </div>
          <div className="space-y-6">
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h3 className="text-sm font-semibold text-gray-700 mb-4 text-center">Health Score</h3>
              <HealthGauge value={healthScore ?? 0} label="Overall Health" size="lg" />
            </div>
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h3 className="text-sm font-semibold text-gray-700 mb-4">Quick Summary</h3>
              <div className="space-y-3 text-sm">
                <div className="flex justify-between"><span className="text-gray-500">AV Protection</span><StatusBadge status={cs.antivirus_status === 'enabled' ? 'active' : 'critical'} /></div>
                <div className="flex justify-between"><span className="text-gray-500">Firewall</span><StatusBadge status={cs.firewall_status === 'enabled' ? 'active' : 'critical'} /></div>
                <div className="flex justify-between"><span className="text-gray-500">Pending Updates</span><span className="font-medium">{cs.pending_updates ?? 0}</span></div>
                <div className="flex justify-between"><span className="text-gray-500">Online Status</span><StatusBadge status={cs.online_status ? 'online' : 'offline'} /></div>
              </div>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'performance' && (
        <div className="space-y-6">
          {history.length > 0 ? (
            <>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <PerformanceBar value={cs.cpu_percentage} type="cpu" label="CPU Usage" />
                <PerformanceBar value={cs.ram_percentage} type="ram" label="Memory Usage" />
                <PerformanceBar value={cs.disk_percentage} type="disk" label="Disk Usage" />
                <PerformanceBar value={cs.cpu_temperature} type="temp" label="CPU Temperature" unit="°C" />
              </div>

              <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 className="text-sm font-semibold text-gray-700 mb-1">Performance Trend</h3>
                <p className="text-xs text-gray-400 mb-4">CPU, RAM, and Disk usage over time</p>
                <ResponsiveContainer width="100%" height={260}>
                  <AreaChart data={history}>
                    <defs>
                      <linearGradient id="cpuGrad" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#6366F1" stopOpacity={0.2}/><stop offset="95%" stopColor="#6366F1" stopOpacity={0}/></linearGradient>
                      <linearGradient id="ramGrad" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#10B981" stopOpacity={0.2}/><stop offset="95%" stopColor="#10B981" stopOpacity={0}/></linearGradient>
                      <linearGradient id="diskGrad" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#F59E0B" stopOpacity={0.2}/><stop offset="95%" stopColor="#F59E0B" stopOpacity={0}/></linearGradient>
                    </defs>
                    <XAxis dataKey={(v) => v.collected_at?.substring(11, 16) || ''} tick={{ fontSize: 10 }} axisLine={false} tickLine={false} />
                    <YAxis tick={{ fontSize: 10 }} axisLine={false} tickLine={false} domain={[0, 100]} />
                    <Tooltip />
                    <Area type="monotone" dataKey="cpu_percentage" stroke="#6366F1" strokeWidth={2} fill="url(#cpuGrad)" name="CPU" />
                    <Area type="monotone" dataKey="ram_percentage" stroke="#10B981" strokeWidth={2} fill="url(#ramGrad)" name="RAM" />
                    <Area type="monotone" dataKey="disk_percentage" stroke="#F59E0B" strokeWidth={2} fill="url(#diskGrad)" name="Disk" />
                  </AreaChart>
                </ResponsiveContainer>
              </div>

              {history.some(h => h.cpu_temperature != null) && (
                <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                  <h3 className="text-sm font-semibold text-gray-700 mb-1">CPU Temperature Trend</h3>
                  <p className="text-xs text-gray-400 mb-4">Thermal performance over time</p>
                  <ResponsiveContainer width="100%" height={200}>
                    <LineChart data={history}>
                      <XAxis dataKey={(v) => v.collected_at?.substring(11, 16) || ''} tick={{ fontSize: 10 }} axisLine={false} tickLine={false} />
                      <YAxis tick={{ fontSize: 10 }} axisLine={false} tickLine={false} domain={[30, 'auto']} />
                      <Tooltip />
                      <Line type="monotone" dataKey="cpu_temperature" stroke="#EF4444" strokeWidth={2} dot={false} name="Temp °C" />
                    </LineChart>
                  </ResponsiveContainer>
                </div>
              )}
            </>
          ) : <EmptyState title="No historical data" description="Performance data will appear after agent collection cycles." />}
        </div>
      )}

      {activeTab === 'activity' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Active Alerts</h3>
            {alerts.length > 0 ? (
              <div className="space-y-3">
                {alerts.filter(a => a.status !== 'resolved').slice(0, 10).map(alert => (
                  <div key={alert.id} className="flex items-start justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <div className="flex items-center gap-2"><SeverityBadge severity={alert.severity} /><span className="text-sm font-medium text-gray-900">{alert.title}</span></div>
                      <p className="text-xs text-gray-500 mt-1">{new Date(alert.created_at).toLocaleString()}</p>
                    </div>
                    <div className="flex gap-1">
                      {alert.status === 'open' && <button onClick={() => acknowledgeAlert(alert.id).then(fetchAll)} className="text-xs text-indigo-600 hover:text-indigo-800">Acknowledge</button>}
                      {alert.status !== 'resolved' && <button onClick={() => resolveAlert(alert.id).then(fetchAll)} className="text-xs text-emerald-600 hover:text-emerald-800">Resolve</button>}
                    </div>
                  </div>
                ))}
              </div>
            ) : <EmptyState title="No alerts" description="No active alerts for this machine." />}
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Activity Timeline</h3>
            {timeline.length > 0 ? (
              <div className="space-y-3 max-h-96 overflow-y-auto">
                {timeline.slice(0, 30).map((item, i) => (
                  <div key={i} className="flex gap-3 text-sm">
                    <div className="flex flex-col items-center">
                      <div className={`w-2 h-2 rounded-full mt-1.5 ${item.type === 'alert' ? 'bg-red-500' : item.type === 'login' ? 'bg-blue-500' : 'bg-gray-400'}`} />
                      {i < timeline.length - 1 && <div className="w-px h-full bg-gray-200" />}
                    </div>
                    <div>
                      <p className="font-medium text-gray-900">{item.title || item.description || item.type}</p>
                      <p className="text-xs text-gray-400">{item.created_at || item.timestamp || item.event_time ? new Date(item.created_at || item.timestamp || item.event_time).toLocaleString() : ''}</p>
                    </div>
                  </div>
                ))}
              </div>
            ) : <EmptyState title="No activity" description="No timeline events yet." />}
          </div>
        </div>
      )}

      {activeTab === 'inventory' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Hardware Information</h3>
            {inventory.hardware ? (
              <div className="grid grid-cols-2 gap-4 text-sm">
                {[
                  ['Manufacturer', inventory.hardware.manufacturer],
                  ['Model', inventory.hardware.model],
                  ['Serial Number', inventory.hardware.serial_number],
                  ['BIOS Version', inventory.hardware.bios_version],
                  ['BIOS Vendor', inventory.hardware.bios_vendor],
                  ['BIOS Release Date', inventory.hardware.bios_release_date],
                  ['Processor', inventory.hardware.processor_name],
                  ['Cores', inventory.hardware.processor_cores],
                  ['Threads', inventory.hardware.processor_threads],
                  ['Clock Speed', inventory.hardware.processor_clock_speed ? `${inventory.hardware.processor_clock_speed} GHz` : (cs.cpu_clock_speed ? `${cs.cpu_clock_speed} GHz (from status)` : null)],
                  ['Architecture', inventory.hardware.system_architecture],
                  ['RAM', inventory.hardware.ram_total_gb ? `${inventory.hardware.ram_total_gb} GB` : (m.ram_gb ? `${m.ram_gb} GB` : null)],
                  ['RAM Type', inventory.hardware.ram_type || '— (not reported by agent)'],
                  ['Disk Model', inventory.hardware.disk_model || '— (not reported by agent)'],
                  ['Disk Type', inventory.hardware.disk_type || '— (not reported by agent)'],
                  ['Disk Size', inventory.hardware.disk_size_gb ? `${inventory.hardware.disk_size_gb} GB` : null],
                  ['GPU', inventory.hardware.gpu_name || '— (not reported by agent)'],
                ].filter(([, v]) => v != null && v !== '').map(([l, v]) => (
                  <div key={l} className="break-words"><span className="text-gray-500 block text-xs">{l}</span><p className="font-medium text-gray-900 text-sm">{v}</p></div>
                ))}
              </div>
            ) : <EmptyState title="No hardware inventory" />}
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Installed Software ({inventory.software.length})</h3>
            {inventory.software.length > 0 ? (
              <div className="max-h-96 overflow-y-auto">
                <table className="w-full text-sm">
                  <thead><tr className="text-left text-xs text-gray-500"><th className="pb-2 pr-2">Software</th><th className="pb-2 pr-2">Version</th><th className="pb-2">Publisher</th></tr></thead>
                  <tbody className="divide-y divide-gray-100">
                    {inventory.software.map((sw, i) => (
                      <tr key={i} className="text-sm">
                        <td className="py-1.5 pr-2 font-medium text-gray-900">{sw.software_name}</td>
                        <td className="py-1.5 pr-2 text-gray-500">{sw.version || '—'}</td>
                        <td className="py-1.5 text-gray-500">{sw.publisher || '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : <EmptyState title="No software inventory" />}
          </div>
        </div>
      )}

      {activeTab === 'security' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Antivirus Status</h3>
            {security.antivirus ? (
              <div className="space-y-2 text-sm">
                <div className="flex justify-between"><span className="text-gray-500">Product</span><span className="font-medium">{security.antivirus.display_name || '—'}</span></div>
                <div className="flex justify-between"><span className="text-gray-500">Enabled</span><StatusBadge status={security.antivirus.is_enabled ? 'active' : 'critical'} dot={false} /></div>
                <div className="flex justify-between"><span className="text-gray-500">Updated</span><StatusBadge status={security.antivirus.is_updated ? 'active' : 'critical'} dot={false} /></div>
                <div className="flex justify-between"><span className="text-gray-500">Real-time Protection</span><StatusBadge status={security.antivirus.real_time_protection ? 'active' : 'critical'} dot={false} /></div>
                <div className="flex justify-between"><span className="text-gray-500">Definition Status</span><span className="font-medium">{security.antivirus.definition_status || '—'}</span></div>
              </div>
            ) : <EmptyState title="No antivirus data" />}
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Firewall Status</h3>
            {security.firewall ? (
              <div className="space-y-2 text-sm">
                <div className="flex justify-between"><span className="text-gray-500">Enabled</span><StatusBadge status={security.firewall.is_enabled ? 'active' : 'critical'} dot={false} /></div>
                <div className="flex justify-between"><span className="text-gray-500">Profile</span><span className="font-medium">{security.firewall.profile_name || '—'}</span></div>
              </div>
            ) : <EmptyState title="No firewall data" />}
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Pending Updates ({security.updates.length})</h3>
            {security.updates.length > 0 ? (
              <div className="max-h-64 overflow-y-auto">
                {security.updates.filter(u => !u.is_installed).slice(0, 20).map((u, i) => (
                  <div key={i} className="py-2 border-b border-gray-100 last:border-0 text-sm">
                    <p className="font-medium text-gray-900">{u.update_title || u.title || 'Update'}</p>
                    <p className="text-xs text-gray-400">{u.severity || u.category || ''} {u.kb_article ? `· ${u.kb_article}` : ''}</p>
                  </div>
                ))}
              </div>
            ) : <EmptyState title="No pending updates" />}
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Login Activity ({security.logins.length})</h3>
            {security.logins.length > 0 ? (
              <div className="max-h-64 overflow-y-auto">
                <table className="w-full text-sm">
                  <thead><tr className="text-left text-xs text-gray-500"><th className="pb-2 pr-2">User</th><th className="pb-2 pr-2">Type</th><th className="pb-2">Time</th></tr></thead>
                  <tbody className="divide-y divide-gray-100">
                    {security.logins.slice(0, 20).map((l, i) => (
                      <tr key={i}>
                        <td className="py-1.5 pr-2 font-medium text-gray-900">{l.username || '—'}</td>
                        <td className="py-1.5 pr-2"><StatusBadge status={l.is_success === false ? 'critical' : 'info'} label={l.event_type} dot={false} /></td>
                        <td className="py-1.5 text-xs text-gray-500">{l.collected_at || l.logon_time ? new Date(l.collected_at || l.logon_time).toLocaleString() : '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : <EmptyState title="No login activity" />}
          </div>
        </div>
      )}

      {activeTab === 'devices' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Connected Devices ({devices.connected.length})</h3>
            {devices.connected.length > 0 ? (
              <div className="space-y-2 max-h-64 overflow-y-auto">
                {devices.connected.map((d, i) => (
                  <div key={i} className="flex justify-between items-center p-2 bg-gray-50 rounded text-sm">
                    <div><p className="font-medium text-gray-900">{d.device_name || '—'}</p><p className="text-xs text-gray-400">{d.device_type || ''} {d.manufacturer || ''}</p></div>
                    <StatusBadge status={d.status || 'connected'} />
                  </div>
                ))}
              </div>
            ) : <EmptyState title="No connected devices" />}
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">USB Activity ({devices.usb.length})</h3>
            {devices.usb.length > 0 ? (
              <div className="space-y-2 max-h-64 overflow-y-auto">
                {devices.usb.slice(0, 20).map((u, i) => (
                  <div key={i} className="flex justify-between p-2 bg-gray-50 rounded text-sm">
                    <div><p className="font-medium text-gray-900">{u.device_name || '—'}</p><p className="text-xs text-gray-400">{u.device_serial || ''}</p></div>
                    <StatusBadge status={u.event_type === 'Insert' ? 'online' : 'offline'} label={u.event_type} />
                  </div>
                ))}
              </div>
            ) : <EmptyState title="No USB activity" />}
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Device Events ({devices.events.length})</h3>
            {devices.events.length > 0 ? (
              <div className="space-y-2 max-h-64 overflow-y-auto">
                {devices.events.slice(0, 20).map((e, i) => (
                  <div key={i} className="flex justify-between p-2 bg-gray-50 rounded text-sm">
                    <div><p className="font-medium text-gray-900">{e.device_name || '—'}</p><p className="text-xs text-gray-400">{e.device_type || ''} · {e.manufacturer || ''}</p></div>
                    <StatusBadge status={e.event_type === 'Connected' ? 'online' : 'offline'} label={e.event_type} />
                  </div>
                ))}
              </div>
            ) : <EmptyState title="No device events" />}
          </div>
        </div>
      )}

      {activeTab === 'processes' && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <h3 className="text-sm font-semibold text-gray-700 mb-4">Running Processes ({processes.length})</h3>
          {processes.length > 0 ? (
            <div className="overflow-x-auto max-h-96 overflow-y-auto">
              <table className="w-full text-sm">
                <thead><tr className="bg-gray-50 text-left text-xs text-gray-500"><th className="px-3 py-2">Process</th><th className="px-3 py-2">PID</th><th className="px-3 py-2">CPU %</th><th className="px-3 py-2">Memory</th><th className="px-3 py-2">Path</th></tr></thead>
                <tbody className="divide-y divide-gray-100">
                  {processes.map((p, i) => (
                    <tr key={i}>
                      <td className="px-3 py-2 font-medium text-gray-900">{p.process_name}</td>
                      <td className="px-3 py-2 text-gray-500">{p.process_id || '—'}</td>
                      <td className="px-3 py-2">{p.cpu_usage != null ? `${p.cpu_usage}%` : '—'}</td>
                      <td className="px-3 py-2">{p.memory_usage != null ? `${p.memory_usage} MB` : '—'}</td>
                      <td className="px-3 py-2 text-gray-400 text-xs max-w-xs truncate">{p.executable_path || '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : <EmptyState title="No processes" />}
        </div>
      )}

      {activeTab === 'services' && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <h3 className="text-sm font-semibold text-gray-700 mb-4">Windows Services ({services.length})</h3>
          {services.length > 0 ? (
            <div className="overflow-x-auto max-h-96 overflow-y-auto">
              <table className="w-full text-sm">
                <thead><tr className="bg-gray-50 text-left text-xs text-gray-500"><th className="px-3 py-2">Service</th><th className="px-3 py-2">Display Name</th><th className="px-3 py-2">Status</th><th className="px-3 py-2">Start Type</th></tr></thead>
                <tbody className="divide-y divide-gray-100">
                  {services.map((s, i) => (
                    <tr key={i}>
                      <td className="px-3 py-2 font-medium text-gray-900">{s.service_name}</td>
                      <td className="px-3 py-2 text-gray-500">{s.display_name || '—'}</td>
                      <td className="px-3 py-2"><StatusBadge status={s.status === 'Running' ? 'online' : 'offline'} label={s.status} dot={false} /></td>
                      <td className="px-3 py-2 text-gray-500">{s.start_type}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : <EmptyState title="No services" />}
        </div>
      )}

      {activeTab === 'network' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Network Adapters ({networkData.adapters.length})</h3>
            {networkData.adapters.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead><tr className="bg-gray-50 text-left text-xs text-gray-500"><th className="px-3 py-2">Name</th><th className="px-3 py-2">IP</th><th className="px-3 py-2">MAC</th><th className="px-3 py-2">Speed</th><th className="px-3 py-2">Status</th></tr></thead>
                  <tbody className="divide-y divide-gray-100">
                    {networkData.adapters.map((a, i) => (
                      <tr key={i}>
                        <td className="px-3 py-2 font-medium text-gray-900 text-xs">{a.adapter_name}</td>
                        <td className="px-3 py-2 text-gray-500 text-xs">{a.ip_address || a.ip_address_v6 || '—'}</td>
                        <td className="px-3 py-2 text-gray-500 text-xs">{a.mac_address || '—'}</td>
                        <td className="px-3 py-2 text-gray-500 text-xs">{a.speed ? `${a.speed} Mbps` : '—'}</td>
                        <td className="px-3 py-2"><StatusBadge status={a.status || 'unknown'} dot={false} /></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : <EmptyState title="No network adapters" />}
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Disk Drives ({networkData.disks.length})</h3>
            {networkData.disks.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead><tr className="bg-gray-50 text-left text-xs text-gray-500"><th className="px-3 py-2">Drive</th><th className="px-3 py-2">Label</th><th className="px-3 py-2">Size</th><th className="px-3 py-2">Used</th><th className="px-3 py-2">Free</th><th className="px-3 py-2">Type</th></tr></thead>
                  <tbody className="divide-y divide-gray-100">
                    {networkData.disks.map((d, i) => (
                      <tr key={i}>
                        <td className="px-3 py-2 font-medium text-gray-900">{d.drive_letter}</td>
                        <td className="px-3 py-2 text-gray-500">{d.volume_label || '—'}</td>
                        <td className="px-3 py-2 text-gray-500">{d.total_gb ? `${d.total_gb} GB` : '—'}</td>
                        <td className="px-3 py-2 text-gray-500">{d.used_gb ? `${d.used_gb} GB` : '—'}</td>
                        <td className="px-3 py-2 text-gray-500">{d.free_gb ? `${d.free_gb} GB` : '—'}</td>
                        <td className="px-3 py-2 text-gray-500">{d.drive_type || '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : <EmptyState title="No disk data" />}
          </div>
        </div>
      )}

      {activeTab === 'logs' && (
        <div className="space-y-6">
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Startup Programs ({startupPrograms.length})</h3>
            {startupPrograms.length > 0 ? (
              <div className="overflow-x-auto max-h-64 overflow-y-auto">
                <table className="w-full text-sm">
                  <thead><tr className="bg-gray-50 text-left text-xs text-gray-500"><th className="px-3 py-2">Program</th><th className="px-3 py-2">Path</th><th className="px-3 py-2">Type</th><th className="px-3 py-2">Status</th></tr></thead>
                  <tbody className="divide-y divide-gray-100">
                    {startupPrograms.map((sp, i) => (
                      <tr key={i}>
                        <td className="px-3 py-2 font-medium text-gray-900">{sp.program_name}</td>
                        <td className="px-3 py-2 text-gray-400 text-xs max-w-xs truncate">{sp.program_path || '—'}</td>
                        <td className="px-3 py-2 text-gray-500">{sp.startup_type}</td>
                        <td className="px-3 py-2"><StatusBadge status={sp.status || 'unknown'} dot={false} /></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : <EmptyState title="No startup programs" />}
          </div>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">Event Logs ({eventLogs.length})</h3>
            {eventLogs.length > 0 ? (
              <div className="overflow-x-auto max-h-96 overflow-y-auto">
                <table className="w-full text-sm">
                  <thead><tr className="bg-gray-50 text-left text-xs text-gray-500"><th className="px-3 py-2">Level</th><th className="px-3 py-2">Source</th><th className="px-3 py-2">Message</th><th className="px-3 py-2">Time</th></tr></thead>
                  <tbody className="divide-y divide-gray-100">
                    {eventLogs.slice(0, 50).map((el, i) => (
                      <tr key={i}>
                        <td className="px-3 py-2"><StatusBadge status={el.level?.toLowerCase() === 'error' || el.level?.toLowerCase() === 'critical' ? 'critical' : el.level?.toLowerCase() === 'warning' ? 'warning' : 'info'} label={el.level} dot={false} /></td>
                        <td className="px-3 py-2 text-gray-500 text-xs">{el.source || '—'}</td>
                        <td className="px-3 py-2 text-gray-500 text-xs max-w-xs truncate">{el.message || '—'}</td>
                        <td className="px-3 py-2 text-xs text-gray-400">{el.event_time || el.collected_at ? new Date(el.event_time || el.collected_at).toLocaleString() : '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : <EmptyState title="No event logs" />}
          </div>
        </div>
      )}
    </div>
  );
}