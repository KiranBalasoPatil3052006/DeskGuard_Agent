import React, { memo, useState, useMemo, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  FaArrowLeft, FaMicrochip, FaMemory, FaHdd,
  FaNetworkWired, FaShieldAlt, FaUsb,
  FaExclamationCircle, FaExclamationTriangle,
  FaBatteryFull, FaDesktop,
  FaClock, FaUser, FaList, FaSyncAlt, FaThermometerHalf,
  FaTachometerAlt, FaInfoCircle, FaCube,
  FaLayerGroup, FaServer
} from 'react-icons/fa';
import { Line } from 'react-chartjs-2';
import { useQueryClient } from '@tanstack/react-query';
import {
  Chart as ChartJS,
  CategoryScale, LinearScale, PointElement, LineElement,
  Title, Tooltip, Legend, Filler,
} from 'chart.js';
import {
  useMachine, useMachineStatus, useMachineHistory,
  useMachineInventory, useMachineSecurity, useMachineDevices,
  useMachineAlerts, useMachineTimeline,
  useMachineProcesses, useMachineServices,
  useMachineStartupPrograms, useMachineEventLogs,
  useMachineNetwork, useMachineChanges
} from '../../hooks/useQueries';
import VirtualizedTable from '../../components/VirtualizedTable';
import { acknowledgeAlert, resolveAlert } from '../../services/alerts';
import { getDeviceIssues as fetchDeviceIssues } from '../../services/machines';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

/**
 * PERFORMANCE: Shimmer skeleton component for tab content loading states.
 * Eliminates the "blank space" pattern when switching tabs.
 */
const _TabSkeleton = ({ rows = 5, cols = 4 }) => (
  <div className="table-responsive">
    <table className="table table-borderless align-middle mb-0">
      <thead><tr>{Array.from({ length: cols }, (_, i) => (
        <th key={i}><div style={{ height: '12px', width: '60%', borderRadius: '4px', background: 'var(--bg-input, #e8e8e8)' }} /></th>
      ))}</tr></thead>
      <tbody>{Array.from({ length: rows }, (_, i) => (
        <tr key={i}>{Array.from({ length: cols }, (_, j) => (
          <td key={j}><div style={{
            height: '14px', borderRadius: '4px', width: `${50 + (i * j * 7) % 40}%`,
            background: 'linear-gradient(110deg, var(--bg-card, #f0f0f0) 8%, var(--bg-input, #e8e8e8) 18%, var(--bg-card, #f0f0f0) 33%)',
            backgroundSize: '200% 100%', animation: 'shimmer 1.5s ease-in-out infinite'
          }} /></td>
        ))}</tr>
      ))}</tbody>
    </table>
    <style>{`@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }`}</style>
  </div>
);

const _ChartSkeleton = () => (
  <div className="row g-4">
    {[1, 2, 3, 4].map(i => (
      <div className="col-12 col-lg-6" key={i}>
        <div className="card p-3" style={{ borderRadius: '16px' }}>
          <div style={{ height: '12px', width: '30%', borderRadius: '4px', marginBottom: '16px', background: 'var(--bg-input, #e8e8e8)' }} />
          <div style={{
            height: '250px', borderRadius: '8px',
            background: 'linear-gradient(110deg, var(--bg-card, #f0f0f0) 8%, var(--bg-input, #e8e8e8) 18%, var(--bg-card, #f0f0f0) 33%)',
            backgroundSize: '200% 100%', animation: 'shimmer 1.5s ease-in-out infinite'
          }} />
        </div>
      </div>
    ))}
  </div>
);

/**
 * PERFORMANCE: Chart options defined at module level to prevent
 * re-creation on every render cycle. These are static configuration
 * objects that never change.
 */
const CHART_OPTIONS = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false, position: 'top', labels: { boxWidth: 12, padding: 12 } },
    tooltip: { enabled: true, mode: 'index', intersect: false }
  },
  scales: {
    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(100,100,100,0.1)' } },
    x: { grid: { display: false } }
  },
  elements: {
    line: { tension: 0.4 },
    point: { radius: 0, hitRadius: 10, hoverRadius: 4 }
  },
  hover: { mode: 'index', intersect: false }
};

const CHART_OPTIONS_WITH_LEGEND = {
  ...CHART_OPTIONS,
  plugins: { ...CHART_OPTIONS.plugins, legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 12 } } }
};

const TEMP_CHART_OPTIONS = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { enabled: true, mode: 'index', intersect: false }
  },
  scales: {
    y: { beginAtZero: true, grid: { color: 'rgba(100,100,100,0.1)' }, title: { display: true, text: '°C' } },
    x: { grid: { display: false } }
  },
  elements: {
    line: { tension: 0.4 },
    point: { radius: 0, hitRadius: 10, hoverRadius: 4 }
  },
  hover: { mode: 'index', intersect: false }
};

const formatBytes = (bytes) => {
  if (bytes == null) return '—';
  if (bytes === 0) return '0 B';
  const abs = Math.abs(bytes);
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(abs) / Math.log(1024));
  return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
};

const SeverityBadge = memo(({ severity }) => {
  const colors = {
    critical: 'bg-danger',
    warning: 'bg-warning text-dark',
    info: 'bg-info',
  };
  return <span className={`badge ${colors[severity] || 'bg-secondary'}`}>{severity}</span>;
});

const _useEnable = (tab) => (activeTab) => activeTab === tab;

const OverviewSection = memo(({ machine, status, id }) => {
  const cs = status || machine?.current_status || {};
  const healthScore = (() => {
    if (cs.health_score != null) return cs.health_score;
    let score = 100;
    const cpu = cs.cpu_percentage ?? cs.cpu_usage;
    const ram = cs.ram_percentage ?? cs.memory_usage;
    const disk = cs.disk_percentage ?? cs.disk_usage;
    if (cpu > 90) score -= 30; else if (cpu > 70) score -= 15;
    if (ram > 90) score -= 25; else if (ram > 70) score -= 10;
    if (disk > 90) score -= 20; else if (disk > 70) score -= 5;
    return Math.max(0, Math.min(100, score));
  })();
  const getHealthLabel = (s) => { if (s >= 90) return 'Excellent'; if (s >= 70) return 'Good'; if (s >= 50) return 'Warning'; return 'Critical'; };
  const getHealthColor = (s) => { if (s >= 90) return '#22C55E'; if (s >= 70) return '#3B82F6'; if (s >= 50) return '#F59E0B'; return '#EF4444'; };
  const getDiskHealthBadge = (status) => {
    if (!status || status === 'Unknown' || status === 'unknown') return <span className="badge bg-secondary">Unknown</span>;
    if (status === 'Good' || status === true || status === '1' || status === 1) return <span className="badge bg-success">Good</span>;
    if (status === 'Bad' || status === false || status === '0' || status === 0) return <span className="badge bg-danger">Bad</span>;
    return <span className="badge bg-secondary">Unknown</span>;
  };
  const getBatteryChargeStatus = (cs) => {
    const charge = cs.battery_charging_status;
    if (charge == null) return '—';
    if (charge === true || charge === 1 || charge === '1' || charge === 'Charging') return 'Charging';
    if (charge === false || charge === 0 || charge === '0' || charge === 'Not Charging') return 'Not Charging';
    if (charge === 'No Battery' || charge === 'no_battery') return 'No Battery';
    return String(charge);
  };
  const color = getHealthColor(healthScore);
  const radius = 50;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference - (healthScore / 100) * circumference;
  return (
    <div className="row g-4">
      <div className="col-12 col-xl-4">
        <div className="card h-100 p-4" style={{ borderRadius: '16px' }}>
          <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>System Overview</h5>
          <div className="d-flex justify-content-center mb-4">
            <div className="d-flex flex-column align-items-center justify-content-center position-relative" style={{ width: '140px', height: '140px' }}>
              <svg width="140" height="140" className="position-absolute" style={{ transform: 'rotate(-90deg)' }}>
                <circle cx="70" cy="70" r={radius} stroke="var(--border-color)" strokeWidth="12" fill="none" opacity="0.3" />
                <circle cx="70" cy="70" r={radius} stroke={color} strokeWidth="12" fill="none" strokeDasharray={circumference} strokeDashoffset={strokeDashoffset} strokeLinecap="round" style={{ transition: 'stroke-dashoffset 1s ease-in-out' }} />
              </svg>
              <div className="text-center position-absolute" style={{ top: '50%', transform: 'translateY(-50%)' }}>
                <h2 className="mb-0 fw-bold" style={{ color: 'var(--text-body)' }}>{healthScore}</h2>
                <span className="small text-muted fw-semibold">{getHealthLabel(healthScore)}</span>
              </div>
            </div>
          </div>
          <div className="d-flex flex-column gap-3">
            <div><span className="text-muted small">Computer Name:</span> <div className="fw-semibold">{machine?.device_name || machine?.hostname || machine?.machine_uid || id}</div></div>
            <div><span className="text-muted small">Employee Mobile:</span> <div className="fw-semibold">{machine?.employee_mobile_number || '—'}</div></div>
            <div><span className="text-muted small">Operating System:</span> <div className="fw-semibold">{machine?.operating_system || '—'}</div></div>
            <div><span className="text-muted small">OS Version:</span> <div className="fw-semibold">{machine?.os_version || '—'}</div></div>
            <div><span className="text-muted small">Status:</span><div><span className={`badge ${machine?.is_online ? 'bg-success' : 'bg-secondary'}`}>{machine?.is_online ? 'Online' : 'Offline'}</span></div></div>
            <div><span className="text-muted small">Last Active:</span> <div className="fw-semibold">{machine?.last_heartbeat_at ? new Date(machine.last_heartbeat_at).toLocaleString() : '—'}</div></div>
            <div><span className="text-muted small">Company:</span> <div className="fw-semibold">{machine?.company?.name || '—'}</div></div>
          </div>
        </div>
      </div>
      <div className="col-12 col-xl-8">
        <h5 className="fw-bold mb-3" style={{ color: 'var(--text-body)' }}>Current Health</h5>
        <div className="row g-3 mb-4">
          {[
            { icon: <FaMicrochip className="text-primary"/>, title: 'CPU Usage', value: (cs.cpu_percentage ?? cs.cpu_usage) != null ? `${cs.cpu_percentage ?? cs.cpu_usage}%` : '—' },
            { icon: <FaThermometerHalf className="text-danger"/>, title: 'CPU Temperature', value: cs.cpu_temperature != null ? `${cs.cpu_temperature}°C` : '—' },
            { icon: <FaTachometerAlt className="text-primary"/>, title: 'CPU Clock Speed', value: cs.cpu_clock_speed != null ? `${cs.cpu_clock_speed} MHz` : '—' },
            { icon: <FaCube className="text-info"/>, title: 'CPU Cores', value: cs.cpu_core_count != null ? String(cs.cpu_core_count) : '—' },
            { icon: <FaMemory className="text-info"/>, title: 'Memory', value: (cs.ram_percentage ?? cs.memory_usage) != null ? `${cs.ram_percentage ?? cs.memory_usage}%` : '—' },
            { icon: <FaHdd className="text-warning"/>, title: 'Disk', value: (cs.disk_percentage ?? cs.disk_usage) != null ? `${cs.disk_percentage ?? cs.disk_usage}%` : '—' },
            { icon: <FaInfoCircle className="text-secondary"/>, title: 'Disk Health', value: getDiskHealthBadge(cs.disk_health_status) },
            { icon: <FaBatteryFull className="text-success"/>, title: 'Battery', value: (cs.battery_percentage ?? cs.battery_level) != null ? `${cs.battery_percentage ?? cs.battery_level}%` : '—' },
            { icon: <FaBatteryFull className="text-warning"/>, title: 'Battery Status', value: getBatteryChargeStatus(cs) },
            { icon: <FaBatteryFull className="text-danger"/>, title: 'Battery Wear', value: cs.battery_wear_level != null ? `${cs.battery_wear_level}%` : '—' },
            { icon: <FaNetworkWired className="text-primary"/>, title: 'Network (↓)', value: cs.network_received_bytes != null && cs.network_received_bytes > 0 ? formatBytes(cs.network_received_bytes) : '—' },
            { icon: <FaNetworkWired className="text-secondary"/>, title: 'Network (↑)', value: cs.network_sent_bytes != null && cs.network_sent_bytes > 0 ? formatBytes(cs.network_sent_bytes) : '—' },
            { icon: <FaShieldAlt className="text-success"/>, title: 'Antivirus', value: cs.antivirus_status || '—' },
            { icon: <FaShieldAlt className="text-info"/>, title: 'Firewall', value: cs.firewall_status || '—' },
            { icon: <FaSyncAlt className="text-warning"/>, title: 'Pending Updates', value: cs.pending_updates != null ? String(cs.pending_updates) : '—' },
          ].map((item, idx) => (
            <div className="col-6 col-md-4 col-xl-4" key={idx}>
              <div className="card p-3 h-100 border-0" style={{ backgroundColor: 'var(--bg-input)', borderRadius: '12px' }}>
                <div className="d-flex align-items-center mb-2 fs-5">{item.icon}</div>
                <div className="text-muted small fw-semibold">{item.title}</div>
                <div className="fw-bold fs-5">{item.value}</div>
              </div>
            </div>
          ))}
        </div>
        <h5 className="fw-bold mb-3" style={{ color: 'var(--text-body)' }}>Recent Activity</h5>
        <div className="row g-3">
          {[
            { icon: <FaUser className="text-info"/>, title: 'Current User', value: cs.current_user || machine?.current_user || '—' },
            { icon: <FaClock className="text-warning"/>, title: 'Last Communication', value: machine?.last_heartbeat_at ? new Date(machine.last_heartbeat_at).toLocaleString() : '—' },
          ].map((item, idx) => (
            <div className="col-12 col-md-6" key={idx}>
              <div className="card p-3 border-0" style={{ backgroundColor: 'var(--bg-input)', borderRadius: '12px' }}>
                <div className="d-flex align-items-center gap-2">
                  {item.icon}
                  <div>
                    <div className="text-muted small">{item.title}</div>
                    <div className="fw-semibold">{item.value}</div>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
});

const PerformanceSection = memo(({ historyLoading, selectedDate, setSelectedDate, generateChartData, generateTempChartData, getChartOptions, getTempChartOptions }) => (
  <div className="row g-4">
    <div className="col-12 d-flex justify-content-between align-items-center">
      <h5 className="fw-bold mb-0" style={{ color: 'var(--text-body)' }}>Performance History</h5>
      <input type="date" className="form-control form-control-sm w-auto" value={selectedDate} max={new Date().toISOString().split('T')[0]} onChange={(e) => setSelectedDate(e.target.value)} style={{ backgroundColor: 'var(--bg-input)' }} />
    </div>
    {historyLoading ? (
      <div className="col-12 text-center py-5"><div className="spinner-border text-primary" role="status" /></div>
    ) : (
      <>
        <div className="col-12 col-lg-6"><div className="card p-3" style={{ borderRadius: '16px' }}><h6 className="fw-bold mb-3">CPU Usage (%)</h6><div style={{ height: '250px' }}><Line data={generateChartData('cpu_percentage', '#3B82F6', 'CPU %')} options={getChartOptions(false)} /></div></div></div>
        <div className="col-12 col-lg-6"><div className="card p-3" style={{ borderRadius: '16px' }}><h6 className="fw-bold mb-3">RAM Usage (%)</h6><div style={{ height: '250px' }}><Line data={generateChartData('ram_percentage', '#8B5CF6', 'RAM %')} options={getChartOptions(false)} /></div></div></div>
        <div className="col-12 col-lg-6"><div className="card p-3" style={{ borderRadius: '16px' }}><h6 className="fw-bold mb-3">Disk Usage (%)</h6><div style={{ height: '250px' }}><Line data={generateChartData('disk_percentage', '#F59E0B', 'Disk %')} options={getChartOptions(false)} /></div></div></div>
        <div className="col-12 col-lg-6"><div className="card p-3" style={{ borderRadius: '16px' }}><h6 className="fw-bold mb-3">CPU Temperature (°C)</h6><div style={{ height: '250px' }}><Line data={generateTempChartData()} options={getTempChartOptions()} /></div></div></div>
      </>
    )}
  </div>
));

const MachineDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState('Overview');
  const [selectedDevice, setSelectedDevice] = useState(null);
  const [deviceIssues, setDeviceIssues] = useState(null);
  const [devicePage, setDevicePage] = useState(1);
  const [selectedDate, setSelectedDate] = useState(() => new Date().toISOString().split('T')[0]);
  const PER_PAGE = 50;

  const { data: machine, isLoading, error } = useMachine(id);
  const { data: status } = useMachineStatus(id);

  const { data: history, isLoading: historyLoading } = useMachineHistory(id,
    { from: selectedDate + ' 00:00:00', to: selectedDate + ' 23:59:59' },
    { enabled: activeTab === 'Performance' }
  );

  const { data: inventory } = useMachineInventory(id, { enabled: activeTab === 'Inventory' });
  const { data: security } = useMachineSecurity(id, { enabled: activeTab === 'Security' });
  const { data: devices, isLoading: devicesLoading } = useMachineDevices(id, { per_page: PER_PAGE, page: devicePage }, { enabled: activeTab === 'Devices' });
  const { data: machineAlertsList } = useMachineAlerts(id, { enabled: activeTab === 'Activity' });
  const { data: timeline } = useMachineTimeline(id, { limit: 50 }, { enabled: activeTab === 'Activity' });
  const { data: processes, isLoading: processesLoading } = useMachineProcesses(id, { enabled: activeTab === 'Processes' });
  const { data: services, isLoading: servicesLoading } = useMachineServices(id, { enabled: activeTab === 'Services' });
  const { data: startupPrograms, isLoading: startupLoading } = useMachineStartupPrograms(id, { enabled: activeTab === 'System Logs' });
  const { data: eventLogs, isLoading: eventLogsLoading } = useMachineEventLogs(id, { enabled: activeTab === 'System Logs' });
  const { data: network } = useMachineNetwork(id, { enabled: activeTab === 'Network' });
  const { data: machineChanges } = useMachineChanges(id, { enabled: activeTab === 'Changes' });

  // PERFORMANCE: Use module-level constants instead of creating new objects per render
  const getChartOptions = (showLegend) => showLegend ? CHART_OPTIONS_WITH_LEGEND : CHART_OPTIONS;
  const getTempChartOptions = () => TEMP_CHART_OPTIONS;

  // PERFORMANCE: Memoize chart data generation to avoid recalculating on every render.
  // Only recalculates when `history` data changes.
  const generateChartData = useMemo(() => (dataKey, color, label) => {
    const historyArray = Array.isArray(history) ? history : [];
    const points = historyArray.map(h => h[dataKey] || 0);
    const labels = historyArray.map(h => {
      const d = new Date(h.collected_at || h.created_at);
      return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    });
    return {
      labels: labels.length ? labels : ['No data'],
      datasets: [{
        label: label || dataKey,
        data: points.length ? points : [0],
        borderColor: color,
        backgroundColor: `${color}33`,
        fill: true,
        borderWidth: 2
      }]
    };
  }, [history]);

  const generateTempChartData = useMemo(() => () => {
    const historyArray = Array.isArray(history) ? history : [];
    const points = historyArray.map(h => h.cpu_temperature ?? null).filter(v => v != null);
    const labels = historyArray.map(h => {
      const d = new Date(h.collected_at || h.created_at);
      return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    });
    return {
      labels: labels.length ? labels : ['No data'],
      datasets: [{
        label: 'CPU Temperature (°C)',
        data: points.length ? points : [0],
        borderColor: '#EF4444',
        backgroundColor: '#EF444433',
        fill: true,
        borderWidth: 2
      }]
    };
  }, [history]);

  const getDiskHealthBadge = (status) => {
    if (!status || status === 'Unknown' || status === 'unknown') return <span className="badge bg-secondary">Unknown</span>;
    if (status === 'Good' || status === true || status === '1' || status === 1) return <span className="badge bg-success">Good</span>;
    if (status === 'Bad' || status === false || status === '0' || status === 0) return <span className="badge bg-danger">Bad</span>;
    return <span className="badge bg-secondary">Unknown</span>;
  };

  const _getBatteryChargeStatus = (cs) => {
    const charge = cs.battery_charging_status;
    if (charge == null) return '—';
    if (charge === true || charge === 1 || charge === '1' || charge === 'Charging') return 'Charging';
    if (charge === false || charge === 0 || charge === '0' || charge === 'Not Charging') return 'Not Charging';
    if (charge === 'No Battery' || charge === 'no_battery') return 'No Battery';
    return String(charge);
  };

  const handleAcknowledge = async (alertId) => {
    try {
      await acknowledgeAlert(alertId);
      queryClient.invalidateQueries({ queryKey: ['machineAlerts'] });
      queryClient.invalidateQueries({ queryKey: ['machine'] });
    } catch (err) {
      console.error('Failed to acknowledge alert:', err);
    }
  };

  const handleResolve = async (alertId) => {
    try {
      await resolveAlert(alertId);
      queryClient.invalidateQueries({ queryKey: ['machineAlerts'] });
      queryClient.invalidateQueries({ queryKey: ['machine'] });
    } catch (err) {
      console.error('Failed to resolve alert:', err);
    }
  };

  const handleDeviceClick = async (device) => {
    setSelectedDevice(device);
    setDeviceIssues(null);
    try {
      const res = await fetchDeviceIssues(id, device.device_name);
      setDeviceIssues(res?.data || res);
    } catch (err) {
      console.error('Failed to load device issues:', err);
    }
  };

  const tabs = ['Overview', 'Performance', 'Processes', 'Services', 'Network', 'Activity', 'Inventory', 'Security', 'Devices', 'System Logs', 'Changes'];

  const getChangeTypeStyle = (type) => {
    const styles = {
      added: 'bg-success', removed: 'bg-danger', modified: 'bg-warning text-dark',
      enabled: 'bg-success', disabled: 'bg-secondary', connected: 'bg-info',
      disconnected: 'bg-secondary', updated: 'bg-primary',
    };
    return styles[type] || 'bg-secondary';
  };

  const getChangeSeverity = (severity) => {
    const styles = {
      critical: { bg: 'bg-danger', icon: '🔴' },
      important: { bg: 'bg-warning text-dark', icon: '⚠️' },
      warning: { bg: 'bg-warning-subtle text-dark', icon: '⚡' },
      information: { bg: 'bg-info-subtle text-dark', icon: 'ℹ️' },
    };
    return styles[severity] || styles.information;
  };

  const formatHardwareValue = (value) => {
    if (value == null) return '—';
    const num = parseInt(value);
    if (!isNaN(num) && num > 999) {
      return num > 999999 ? (num / 1073741824).toFixed(1) + ' GB' : (num / 1048576).toFixed(1) + ' MB';
    }
    return String(value);
  };

  const renderChanges = useCallback(() => {
    const changes = Array.isArray(machineChanges) ? machineChanges.filter(
      c => c.category === 'hardware' && c.previous_value != null && c.previous_value !== '' && c.previous_value !== '(none)'
    ) : [];
    if (!changes.length) return <div className="text-center py-5 text-muted">No hardware changes detected.</div>;
    return (
      <div className="row g-3">
        {changes.map((change) => {
          const severityStyle = getChangeSeverity(change.severity);
          return (
            <div className="col-12 col-md-6 col-lg-4" key={change.id}>
              <div className="card h-100 p-3" style={{ borderRadius: '12px', borderLeft: `4px solid var(--bs-${severityStyle.bg.includes('danger') ? 'danger' : severityStyle.bg.includes('warning') ? 'warning' : 'info'})` }}>
                <div className="d-flex justify-content-between align-items-start mb-2">
                  <span className={`badge ${getChangeTypeStyle(change.change_type)}`}>{change.change_type}</span>
                  <span className={`badge ${severityStyle.bg}`}>{change.severity}</span>
                </div>
                <h6 className="fw-semibold mb-1">{change.item_label || change.item_identifier}</h6>
                <p className="small text-muted mb-2">{change.description}</p>
                <div className="small">
                  <div><span className="text-muted">Before:</span> <span className="text-danger">{formatHardwareValue(change.previous_value)}</span></div>
                  <div><span className="text-muted">After:</span> <span className="text-success">{formatHardwareValue(change.new_value)}</span></div>
                </div>
                {change.detected_at && <div className="mt-2 small text-muted">{new Date(change.detected_at).toLocaleDateString()}</div>}
              </div>
            </div>
          );
        })}
      </div>
    );
  }, [machineChanges]);

  const renderTabContent = () => {
    switch (activeTab) {
      case 'Overview':
        return <OverviewSection machine={machine} status={status} id={id} />;
      case 'Performance':
        return <PerformanceSection history={history} historyLoading={historyLoading} selectedDate={selectedDate} setSelectedDate={setSelectedDate} generateChartData={generateChartData} generateTempChartData={generateTempChartData} getChartOptions={getChartOptions} getTempChartOptions={getTempChartOptions} />;
      case 'Processes':
        if (processesLoading) return <div className="text-center py-5"><div className="spinner-border text-primary" role="status" /></div>;
        return (
          <VirtualizedTable
            items={Array.isArray(processes) ? processes : []}
            emptyMessage="No processes found"
            columns={[
              { header: 'PID', key: 'process_id', flex: 0.5, minWidth: 60 },
              { header: 'Name', key: 'process_name', flex: 2, minWidth: 150, bold: true, render: (p) => p.process_name },
              { header: 'CPU %', flex: 0.7, minWidth: 70, render: (p) => p.cpu_usage != null ? `${p.cpu_usage}%` : '—' },
              { header: 'Memory', flex: 0.8, minWidth: 80, render: (p) => p.memory_usage != null ? formatBytes(p.memory_usage) : '—' },
              { header: 'Threads', key: 'thread_count', flex: 0.6, minWidth: 70 },
              { header: 'User', key: 'user_name', flex: 1, minWidth: 100, muted: true },
            ]}
          />
        );
      case 'Services':
        if (servicesLoading) return <div className="text-center py-5"><div className="spinner-border text-primary" role="status" /></div>;
        return (
          <VirtualizedTable
            items={Array.isArray(services) ? services : []}
            emptyMessage="No services found"
            columns={[
              { header: 'Service Name', key: 'service_name', flex: 1.5, minWidth: 150, bold: true, render: (s) => <span className="small fw-semibold">{s.service_name}</span> },
              { header: 'Display Name', key: 'display_name', flex: 1.5, minWidth: 150 },
              { header: 'Status', flex: 0.7, minWidth: 80, render: (s) => <span className={`badge ${s.status === 'Running' ? 'bg-success' : 'bg-secondary'}`}>{s.status}</span> },
              { header: 'Start Type', key: 'start_type', flex: 0.8, minWidth: 80 },
              { header: 'Service Type', key: 'service_type', flex: 0.8, minWidth: 80, muted: true },
            ]}
          />
        );
      case 'Network':
        return (
          <div className="row g-4">
            {network?.adapters && <div className="col-12"><h6 className="fw-bold mb-3">Network Adapters</h6>
              <div className="table-responsive"><table className="table table-hover align-middle mb-0">
                <thead className="table-light"><tr><th>Adapter</th><th>IP Address</th><th>MAC</th><th>Type</th><th>Speed</th><th>Status</th></tr></thead>
                <tbody>{(Array.isArray(network.adapters) ? network.adapters : []).map(a => (
                  <tr key={a.id}><td className="fw-semibold small">{a.adapter_name}</td><td className="small">{a.ip_address || '—'}</td><td className="small text-muted">{a.mac_address || '—'}</td><td className="small">{a.adapter_type || '—'}</td><td className="small">{a.speed ? `${(a.speed / 1000000).toFixed(0)} Mbps` : '—'}</td><td><span className={`badge ${a.status === 'Up' ? 'bg-success' : 'bg-secondary'}`}>{a.status || '—'}</span></td></tr>
                ))}</tbody>
              </table></div>
            </div>}
            {network?.disks && <div className="col-12"><h6 className="fw-bold mb-3">Disk Drives</h6>
              <div className="table-responsive"><table className="table table-hover align-middle mb-0">
                <thead className="table-light"><tr><th>Drive</th><th>Label</th><th>Total</th><th>Used</th><th>Free</th><th>File System</th><th>Type</th><th>Health</th></tr></thead>
                <tbody>{(Array.isArray(network.disks) ? network.disks : []).map(d => {
                  return (<tr key={d.id}><td className="fw-bold">{d.drive_letter}</td><td>{d.volume_label || '—'}</td><td>{d.total_gb ? `${d.total_gb.toFixed(1)} GB` : '—'}</td><td>{d.used_gb ? `${d.used_gb.toFixed(1)} GB` : '—'}</td><td>{d.free_gb ? `${d.free_gb.toFixed(1)} GB` : '—'}</td><td className="small text-muted">{d.file_system || '—'}</td><td className="small">{d.drive_type || '—'}</td><td>{getDiskHealthBadge(d.health_status)}</td></tr>);
                })}</tbody>
              </table></div>
            </div>}
          </div>
        );
      case 'Activity':
        return (
          <div className="row g-4">
            <div className="col-12">
              <h6 className="fw-bold mb-3">Timeline</h6>
              <div className="list-group" style={{ maxHeight: '500px', overflowY: 'auto' }}>
                {(Array.isArray(timeline) ? timeline : []).map((event, idx) => (
                  <div key={idx} className="list-group-item list-group-item-action border-0 d-flex align-items-start gap-3 py-3" style={{ backgroundColor: 'var(--bg-input)', borderRadius: '8px', marginBottom: '4px' }}>
                    <div className={`mt-1 ${event.type === 'alert' ? 'text-danger' : event.type === 'login' ? 'text-info' : 'text-secondary'}`}>{event.type === 'alert' ? <FaExclamationCircle/> : event.type === 'login' ? <FaUser/> : event.type === 'usb' ? <FaUsb/> : event.type === 'device' ? <FaDesktop/> : <FaList/>}</div>
                    <div className="flex-grow-1"><div className="fw-semibold small">{event.title}</div>{event.description && <div className="text-muted small">{event.description}</div>}</div>
                    <div className="small text-muted flex-shrink-0">{event.timestamp ? new Date(event.timestamp).toLocaleString() : ''}</div>
                  </div>
                ))}
              </div>
            </div>
            <div className="col-12">
              <h6 className="fw-bold mb-3">Alerts</h6>
              <div className="table-responsive"><table className="table table-hover align-middle mb-0">
                <thead className="table-light"><tr><th>Title</th><th>Severity</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>{(Array.isArray(machineAlertsList) ? machineAlertsList : []).map(a => (
                  <tr key={a.id}><td className="fw-semibold small">{a.title}</td><td><SeverityBadge severity={a.severity}/></td><td><span className={`badge ${a.status === 'open' ? 'bg-warning text-dark' : a.status === 'acknowledged' ? 'bg-info' : 'bg-success'}`}>{a.status}</span></td><td className="small text-muted">{a.created_at ? new Date(a.created_at).toLocaleString() : '—'}</td><td>{a.status === 'open' ? <button className="btn btn-sm btn-outline-info me-1" onClick={() => handleAcknowledge(a.id)}>Acknowledge</button> : null}{a.status !== 'resolved' ? <button className="btn btn-sm btn-outline-success" onClick={() => handleResolve(a.id)}>Resolve</button> : null}</td></tr>
                ))}</tbody>
              </table></div>
            </div>
          </div>
        );
      case 'Inventory':
        return (
          <div className="row g-4">
            <div className="col-12 col-lg-6">
              <h6 className="fw-bold mb-3">Hardware</h6>
              <div className="card p-3" style={{ borderRadius: '12px' }}>
                <table className="table table-borderless mb-0"><tbody>
                  {[
                    ['Manufacturer', inventory?.hardware?.manufacturer],
                    ['Model', inventory?.hardware?.model],
                    ['Processor', inventory?.hardware?.processor_name],
                    ['Cores', inventory?.hardware?.processor_cores],
                    ['RAM', inventory?.hardware?.ram_total_gb ? `${inventory.hardware.ram_total_gb} GB` : '—'],
                    ['RAM Type', inventory?.hardware?.ram_type],
                    ['Disk', inventory?.hardware?.disk_model],
                    ['Disk Size', inventory?.hardware?.disk_size_gb ? `${inventory.hardware.disk_size_gb} GB` : '—'],
                    ['GPU', inventory?.hardware?.gpu_name],
                  ].map(([label, value], idx) => (
                    <tr key={idx}><td className="text-muted small" style={{ width: '40%' }}>{label}</td><td className="fw-semibold">{value || '—'}</td></tr>
                  ))}
                </tbody></table>
              </div>
            </div>
            <div className="col-12 col-lg-6">
              <h6 className="fw-bold mb-3">Software ({Array.isArray(inventory?.software) ? inventory.software.length : 0})</h6>
              <div className="card p-0" style={{ borderRadius: '12px', maxHeight: '500px', overflowY: 'auto' }}>
                <table className="table table-hover mb-0"><thead className="table-light sticky-top"><tr><th>Software</th><th>Version</th><th>Publisher</th></tr></thead>
                <tbody>{(Array.isArray(inventory?.software) ? inventory.software : []).map(s => (
                  <tr key={s.id}><td className="fw-semibold small">{s.software_name}</td><td className="small text-muted">{s.version || '—'}</td><td className="small text-muted">{s.publisher || '—'}</td></tr>
                ))}</tbody>
              </table></div>
            </div>
          </div>
        );
      case 'Security':
        return (
          <div className="row g-4">
            <div className="col-12 col-lg-6"><h6 className="fw-bold mb-3">Antivirus</h6>
              <div className="card p-3" style={{ borderRadius: '12px' }}>
                {security?.antivirus ? (
                  <table className="table table-borderless mb-0"><tbody>
                    {[['Product', security.antivirus.display_name], ['Enabled', security.antivirus.is_enabled ? 'Yes' : 'No'], ['Updated', security.antivirus.is_updated ? 'Yes' : 'No']].map(([l, v], i) => (
                      <tr key={i}><td className="text-muted small">{l}</td><td className="fw-semibold">{v ?? '—'}</td></tr>
                    ))}
                  </tbody></table>
                ) : <div className="text-center py-4 text-muted">No antivirus data available.</div>}
              </div>
            </div>
            <div className="col-12 col-lg-6"><h6 className="fw-bold mb-3">Firewall</h6>
              <div className="card p-3" style={{ borderRadius: '12px' }}>
                {security?.firewall ? (
                  <table className="table table-borderless mb-0"><tbody>
                    {[['Enabled', security.firewall.is_enabled ? 'Yes' : 'No'], ['Domain Profile', security.firewall.domain_profile], ['Private Profile', security.firewall.private_profile], ['Public Profile', security.firewall.public_profile]].map(([l, v], i) => (
                      <tr key={i}><td className="text-muted small">{l}</td><td className="fw-semibold">{v ?? '—'}</td></tr>
                    ))}
                  </tbody></table>
                ) : <div className="text-center py-4 text-muted">No firewall data available.</div>}
              </div>
            </div>
            <div className="col-12"><h6 className="fw-bold mb-3">Pending Updates ({Array.isArray(security?.pending_updates) ? security.pending_updates.length : 0})</h6>
              {Array.isArray(security?.pending_updates) && security.pending_updates.length > 0 ? (
                <div className="table-responsive"><table className="table table-hover mb-0"><thead className="table-light"><tr><th>Title</th><th>KB ID</th><th>Severity</th><th>Category</th></tr></thead>
                <tbody>{security.pending_updates.map(u => (
                  <tr key={u.id}><td className="fw-semibold small">{u.title}</td><td className="small">{u.kb_id || '—'}</td><td><SeverityBadge severity={u.severity?.toLowerCase()}/></td><td className="small text-muted">{u.category || '—'}</td></tr>
                ))}</tbody></table></div>
              ) : <div className="text-center py-4 text-muted">No pending updates.</div>}
            </div>
            <div className="col-12"><h6 className="fw-bold mb-3">Login Activity</h6>
              {Array.isArray(security?.login_activity) && security.login_activity.length > 0 ? (
                <div className="table-responsive"><table className="table table-hover mb-0"><thead className="table-light"><tr><th>User</th><th>Type</th><th>Time</th></tr></thead>
                <tbody>{security.login_activity.map(l => (
                  <tr key={l.id}><td className="fw-semibold small">{l.username || '—'}</td><td className="small">{l.logon_type || '—'}</td><td className="small text-muted">{l.created_at ? new Date(l.created_at).toLocaleString() : '—'}</td></tr>
                ))}</tbody></table></div>
              ) : <div className="text-center py-4 text-muted">No login activity recorded.</div>}
            </div>
          </div>
        );
      case 'Devices':
        return (
          <div>
            <h6 className="fw-bold mb-3">Connected Devices</h6>
            {devicesLoading ? <div className="text-center py-5"><div className="spinner-border text-primary" role="status" /></div> : (
              <>
                <div className="table-responsive"><table className="table table-hover align-middle mb-0">
                  <thead className="table-light"><tr><th>Device</th><th>Type</th><th>Manufacturer</th><th>Connection</th><th>Status</th><th>Last Seen</th></tr></thead>
                  <tbody>{(Array.isArray(devices?.connected_devices?.data || devices?.connected_devices) ? (devices.connected_devices.data || devices.connected_devices) : []).map(d => (
                    <tr key={d.id} className="device-row" onClick={() => handleDeviceClick(d)} style={{ cursor: 'pointer' }}>
                      <td className="fw-semibold small">{d.device_name}</td><td className="small">{d.device_type || '—'}</td><td className="small text-muted">{d.manufacturer || '—'}</td><td className="small">{d.connection_type || '—'}</td>
                      <td><span className={`badge ${d.status === 'connected' ? 'bg-success' : 'bg-secondary'}`}>{d.status}</span></td>
                      <td className="small text-muted">{d.last_seen ? new Date(d.last_seen).toLocaleString() : '—'}</td>
                    </tr>
                  ))}</tbody>
                </table></div>
                {devices?.connected_devices?.last_page > 1 && (
                  <div className="d-flex justify-content-between align-items-center mt-3">
                    <span className="small text-muted">Page {devices.connected_devices.current_page} of {devices.connected_devices.last_page}</span>
                    <div>{Array.from({ length: devices.connected_devices.last_page }, (_, i) => i + 1).map(p => (
                      <button key={p} className={`btn btn-sm ${p === (devices.connected_devices.current_page || devicePage) ? 'btn-primary' : 'btn-outline-secondary'} me-1`} onClick={() => setDevicePage(p)}>{p}</button>
                    ))}</div>
                  </div>
                )}
              </>
            )}
            {selectedDevice && (
              <div className="modal d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                <div className="modal-dialog modal-lg modal-dialog-centered">
                  <div className="modal-content" style={{ borderRadius: '16px' }}>
                    <div className="modal-header border-0"><h5 className="modal-title fw-bold">{selectedDevice.device_name}</h5><button type="button" className="btn-close" onClick={() => setSelectedDevice(null)}/></div>
                    <div className="modal-body">
                      {deviceIssues ? (<>
                        <h6 className="fw-bold mb-2">Device Info</h6>
                        <div className="row g-2 mb-3 small">
                          <div className="col-6"><span className="text-muted">Type:</span> {deviceIssues.device?.device_type || selectedDevice.device_type || '—'}</div>
                          <div className="col-6"><span className="text-muted">Manufacturer:</span> {deviceIssues.device?.manufacturer || selectedDevice.manufacturer || '—'}</div>
                          <div className="col-6"><span className="text-muted">Connection:</span> {deviceIssues.device?.connection_type || selectedDevice.connection_type || '—'}</div>
                          <div className="col-6"><span className="text-muted">Status:</span> {deviceIssues.device?.status || selectedDevice.status || '—'}</div>
                        </div>
                        <h6 className="fw-bold mb-2">Related Alerts</h6>
                        <div className="table-responsive mb-3">{Array.isArray(deviceIssues.alerts) && deviceIssues.alerts.length > 0 ? (
                          <table className="table table-sm"><thead><tr><th>Title</th><th>Severity</th><th>Status</th><th>Date</th></tr></thead><tbody>{deviceIssues.alerts.map(a => (
                            <tr key={a.id}><td className="small">{a.title}</td><td><SeverityBadge severity={a.severity}/></td><td className="small">{a.status}</td><td className="small text-muted">{a.created_at ? new Date(a.created_at).toLocaleString() : '—'}</td></tr>
                          ))}</tbody></table>
                        ) : <p className="text-muted small">No related alerts.</p>}</div>
                        <h6 className="fw-bold mb-2">Device Events</h6>
                        <div className="table-responsive">{Array.isArray(deviceIssues.events) && deviceIssues.events.length > 0 ? (
                          <table className="table table-sm"><thead><tr><th>Event</th><th>Type</th><th>Date</th></tr></thead><tbody>{deviceIssues.events.map(e => (
                            <tr key={e.id}><td className="small">{e.event_type || '—'}</td><td className="small">{e.device_type || '—'}</td><td className="small text-muted">{e.event_time ? new Date(e.event_time).toLocaleString() : '—'}</td></tr>
                          ))}</tbody></table>
                        ) : <p className="text-muted small">No events recorded.</p>}</div>
                      </>) : <div className="text-center py-3"><div className="spinner-border spinner-border-sm" role="status" /></div>}
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        );
      case 'System Logs':
        if (startupLoading || eventLogsLoading) return <div className="text-center py-5"><div className="spinner-border text-primary" role="status" /></div>;
        return (
          <div className="row g-4">
            <div className="col-12">
              <h6 className="fw-bold mb-3">Startup Programs ({Array.isArray(startupPrograms) ? startupPrograms.length : 0})</h6>
              <div className="table-responsive"><table className="table table-hover mb-0"><thead className="table-light"><tr><th>Program</th><th>Path</th><th>Type</th><th>Status</th></tr></thead>
              <tbody>{(Array.isArray(startupPrograms) ? startupPrograms : []).map(s => (
                <tr key={s.id}><td className="fw-semibold small">{s.program_name}</td><td className="small text-muted" style={{ maxWidth: '300px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{s.program_path || '—'}</td><td className="small">{s.startup_type || '—'}</td><td><span className={`badge ${s.status === 'enabled' ? 'bg-success' : 'bg-secondary'}`}>{s.status || '—'}</span></td></tr>
              ))}</tbody></table></div>
            </div>
            <div className="col-12">
              <h6 className="fw-bold mb-3">Event Logs (Last 100)</h6>
              <VirtualizedTable
                items={Array.isArray(eventLogs) ? eventLogs : []}
                emptyMessage="No event logs found"
                maxHeight={450}
                columns={[
                  { header: 'Level', flex: 0.6, minWidth: 70, render: (e) => {
                    const cls = e.level === 'Error' ? 'bg-danger' : e.level === 'Warning' ? 'bg-warning text-dark' : 'bg-info';
                    return <span className={`badge ${cls}`}>{e.level || '—'}</span>;
                  }},
                  { header: 'Source', key: 'source', flex: 1, minWidth: 100, muted: true },
                  { header: 'Event ID', key: 'event_id', flex: 0.6, minWidth: 70 },
                  { header: 'Message', key: 'message', flex: 2, minWidth: 200, muted: true },
                  { header: 'Time', flex: 1, minWidth: 120, muted: true, render: (e) => e.event_time ? new Date(e.event_time).toLocaleString() : '—' },
                ]}
              />
            </div>
          </div>
        );
      case 'Changes':
        return renderChanges();
      default:
        return <div className="text-center py-5 text-muted">Select a tab to view data.</div>;
    }
  };

  if (isLoading) {
    return (
      <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '60vh' }}>
        <div className="text-center">
          <div className="spinner-border text-primary mb-3" role="status" style={{ width: '3rem', height: '3rem' }} />
          <p className="text-muted">Loading machine details...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '60vh' }}>
        <div className="text-center">
          <FaExclamationTriangle size={48} className="text-danger mb-3" />
          <h5 className="fw-bold text-danger">Failed to load machine details</h5>
          <p className="text-muted">{error.message || 'An unexpected error occurred.'}</p>
          <button className="btn btn-primary" onClick={() => navigate('/machines')}>Back to Machines</button>
        </div>
      </div>
    );
  }

  if (!machine) return null;

  return (
    <div className="container-fluid px-4 py-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div className="d-flex align-items-center gap-3">
          <button className="btn btn-sm btn-outline-secondary" onClick={() => navigate('/machines')}>
            <FaArrowLeft className="me-1" /> Back
          </button>
          <div>
            <h4 className="fw-bold mb-0" style={{ color: 'var(--text-body)' }}>{machine.device_name || machine.hostname || `Machine ${machine.id}`}</h4>
            <span className="text-muted small">{machine.operating_system || '—'} · {machine.hostname || '—'}</span>
          </div>
        </div>
        <span className={`badge fs-6 px-3 py-2 ${machine.is_online ? 'bg-success' : 'bg-secondary'}`}>
          {machine.is_online ? '● Online' : '○ Offline'}
        </span>
      </div>

      <ul className="nav nav-tabs mb-4 flex-nowrap overflow-auto" style={{ gap: '4px' }}>
        {tabs.map(tab => (
          <li className="nav-item" key={tab}>
            <button
              className={`nav-link ${activeTab === tab ? 'active fw-semibold' : ''}`}
              onClick={() => setActiveTab(tab)}
              style={{ whiteSpace: 'nowrap', borderRadius: '8px 8px 0 0', border: 'none', borderBottom: activeTab === tab ? '2px solid var(--bs-primary)' : '2px solid transparent' }}
            >
              {tab === 'Overview' && <><FaServer className="me-1" /> {tab}</>}
              {tab === 'Performance' && <><FaTachometerAlt className="me-1" /> {tab}</>}
              {tab === 'Processes' && <><FaMicrochip className="me-1" /> {tab}</>}
              {tab === 'Services' && <><FaList className="me-1" /> {tab}</>}
              {tab === 'Network' && <><FaNetworkWired className="me-1" /> {tab}</>}
              {tab === 'Activity' && <><FaClock className="me-1" /> {tab}</>}
              {tab === 'Inventory' && <><FaLayerGroup className="me-1" /> {tab}</>}
              {tab === 'Security' && <><FaShieldAlt className="me-1" /> {tab}</>}
              {tab === 'Devices' && <><FaUsb className="me-1" /> {tab}</>}
              {tab === 'System Logs' && <><FaList className="me-1" /> {tab}</>}
              {tab === 'Changes' && <><FaSyncAlt className="me-1" /> {tab}</>}
              {!['Overview', 'Performance', 'Processes', 'Services', 'Network', 'Activity', 'Inventory', 'Security', 'Devices', 'System Logs', 'Changes'].includes(tab) && tab}
            </button>
          </li>
        ))}
      </ul>

      {renderTabContent()}
    </div>
  );
};

export default MachineDetails;
