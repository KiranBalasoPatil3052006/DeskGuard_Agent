import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  FaArrowLeft, FaMicrochip, FaMemory, FaHdd,
  FaNetworkWired, FaShieldAlt, FaUsb,
  FaLock, FaExclamationCircle, FaExclamationTriangle,
  FaBatteryFull, FaCheckCircle, FaDesktop,
  FaClock, FaUser, FaList, FaSyncAlt, FaThermometerHalf,
  FaTachometerAlt, FaInfoCircle, FaCube, FaExpandArrowsAlt,
  FaTag, FaCalendarAlt, FaLayerGroup
} from 'react-icons/fa';
import { Line } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale, LinearScale, PointElement, LineElement,
  Title, Tooltip, Legend, Filler,
} from 'chart.js';
import {
  getMachine, getMachineStatus, getMachineHistory,
  getMachineInventory, getMachineSecurity, getMachineDevices,
  getMachineAlerts, getMachineTimeline, getMachineProcesses,
  getMachineServices, getMachineStartupPrograms,
  getMachineEventLogs, getMachineNetwork
} from '../../services/machines';
import { acknowledgeAlert, resolveAlert } from '../../services/alerts';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const formatBytes = (bytes) => {
  if (bytes == null) return '—';
  if (bytes === 0) return '0 B';
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
};

const MachineDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  const [machine, setMachine] = useState(null);
  const [status, setStatus] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const [activeTab, setActiveTab] = useState('Overview');

  const [history, setHistory] = useState([]);
  const [inventory, setInventory] = useState(null);
  const [security, setSecurity] = useState(null);
  const [devices, setDevices] = useState(null);
  const [machineAlertsList, setMachineAlertsList] = useState([]);
  const [timeline, setTimeline] = useState([]);
  const [processes, setProcesses] = useState([]);
  const [services, setServices] = useState([]);
  const [startupPrograms, setStartupPrograms] = useState([]);
  const [eventLogs, setEventLogs] = useState([]);
  const [network, setNetwork] = useState(null);

  const [tabLoading, setTabLoading] = useState(false);

  const [selectedDate, setSelectedDate] = useState(() => {
    const d = new Date();
    return d.toISOString().split('T')[0];
  });

  useEffect(() => {
    if (!id) return;
    let cancelled = false;
    async function fetchCore() {
      setLoading(true);
      setError(null);
      try {
        const [machineRes, statusRes] = await Promise.all([
          getMachine(id),
          getMachineStatus(id).catch(() => ({ data: null })),
        ]);
        if (!cancelled) {
          setMachine(machineRes?.data || machineRes);
          setStatus(statusRes?.data || statusRes);
        }
      } catch (err) {
        console.error('Failed to load machine details:', err);
        if (!cancelled) setError('Failed to load machine details.');
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    fetchCore();
    return () => { cancelled = true; };
  }, [id]);

  const fetchTabData = useCallback(async (tab) => {
    setTabLoading(true);
    try {
      switch (tab) {
        case 'Performance': {
          const from = selectedDate + ' 00:00:00';
          const to = selectedDate + ' 23:59:59';
          const res = await getMachineHistory(id, { from, to });
          setHistory(res?.data || res || []);
          break;
        }
        case 'Activity': {
          const [timelineRes, alertsRes] = await Promise.all([
            getMachineTimeline(id, { limit: 50 }),
            getMachineAlerts(id),
          ]);
          setTimeline(timelineRes?.data || timelineRes || []);
          setMachineAlertsList(alertsRes?.data || alertsRes || []);
          break;
        }
        case 'Inventory': {
          const res = await getMachineInventory(id);
          setInventory(res?.data || res);
          break;
        }
        case 'Security': {
          const res = await getMachineSecurity(id);
          setSecurity(res?.data || res);
          break;
        }
        case 'Devices': {
          const res = await getMachineDevices(id);
          setDevices(res?.data || res);
          break;
        }
        case 'Processes': {
          const pres = await getMachineProcesses(id);
          setProcesses(pres?.data || pres || []);
          break;
        }
        case 'Services': {
          const sres = await getMachineServices(id);
          setServices(sres?.data || sres || []);
          break;
        }
        case 'Network': {
          const nres = await getMachineNetwork(id);
          setNetwork(nres?.data || nres);
          break;
        }
        case 'System Logs': {
          const [elRes, spRes] = await Promise.all([
            getMachineEventLogs(id),
            getMachineStartupPrograms(id),
          ]);
          setEventLogs(elRes?.data || elRes || []);
          setStartupPrograms(spRes?.data || spRes || []);
          break;
        }
        default:
          break;
      }
    } catch (err) {
      console.error(`Failed to load ${tab} data:`, err);
    } finally {
      setTabLoading(false);
    }
  }, [id, selectedDate]);

  useEffect(() => {
    if (id && activeTab !== 'Overview') {
      fetchTabData(activeTab);
    }
  }, [activeTab, id, fetchTabData]);

  const handleAcknowledge = async (alertId) => {
    try {
      await acknowledgeAlert(alertId);
      setMachineAlertsList(prev =>
        prev.map(a => a.id === alertId ? { ...a, status: 'acknowledged' } : a)
      );
    } catch (err) {
      console.error('Failed to acknowledge alert:', err);
    }
  };

  const handleResolve = async (alertId) => {
    try {
      await resolveAlert(alertId);
      setMachineAlertsList(prev =>
        prev.map(a => a.id === alertId ? { ...a, status: 'resolved' } : a)
      );
    } catch (err) {
      console.error('Failed to resolve alert:', err);
    }
  };

  const cs = status || machine?.current_status || {};
  const tabs = ['Overview', 'Performance', 'Processes', 'Services', 'Network', 'Activity', 'Inventory', 'Security', 'Devices', 'System Logs'];

  const getHealthScore = () => {
    if (cs.health_score != null) return cs.health_score;
    let score = 100;
    const cpu = cs.cpu_percentage ?? cs.cpu_usage;
    const ram = cs.ram_percentage ?? cs.memory_usage;
    const disk = cs.disk_percentage ?? cs.disk_usage;
    if (cpu > 90) score -= 30; else if (cpu > 70) score -= 15;
    if (ram > 90) score -= 25; else if (ram > 70) score -= 10;
    if (disk > 90) score -= 20; else if (disk > 70) score -= 5;
    return Math.max(0, Math.min(100, score));
  };

  const healthScore = getHealthScore();
  const getHealthLabel = (s) => {
    if (s >= 90) return 'Excellent';
    if (s >= 70) return 'Good';
    if (s >= 50) return 'Warning';
    return 'Critical';
  };
  const getHealthColor = (s) => {
    if (s >= 90) return '#22C55E';
    if (s >= 70) return '#3B82F6';
    if (s >= 50) return '#F59E0B';
    return '#EF4444';
  };

  const HealthCircle = ({ score, label }) => {
    const color = getHealthColor(score);
    const radius = 50;
    const circumference = 2 * Math.PI * radius;
    const strokeDashoffset = circumference - (score / 100) * circumference;
    return (
      <div className="d-flex flex-column align-items-center justify-content-center position-relative" style={{ width: '140px', height: '140px' }}>
        <svg width="140" height="140" className="position-absolute" style={{ transform: 'rotate(-90deg)' }}>
          <circle cx="70" cy="70" r={radius} stroke="var(--border-color)" strokeWidth="12" fill="none" opacity="0.3" />
          <circle
            cx="70" cy="70" r={radius}
            stroke={color} strokeWidth="12" fill="none"
            strokeDasharray={circumference}
            strokeDashoffset={strokeDashoffset}
            strokeLinecap="round"
            style={{ transition: 'stroke-dashoffset 1s ease-in-out' }}
          />
        </svg>
        <div className="text-center position-absolute" style={{ top: '50%', transform: 'translateY(-50%)' }}>
          <h2 className="mb-0 fw-bold" style={{ color: 'var(--text-body)' }}>{score}</h2>
          <span className="small text-muted fw-semibold">{label}</span>
        </div>
      </div>
    );
  };

  const getChartOptions = (showLegend) => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: showLegend, position: 'top', labels: { boxWidth: 12, padding: 12 } },
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
  });

  const getTempChartOptions = () => ({
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
  });

  const generateChartData = (dataKey, color, label) => {
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
  };

  const generateTempChartData = () => {
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
  };

  const SeverityBadge = ({ severity }) => {
    const colors = {
      critical: 'bg-danger',
      warning: 'bg-warning text-dark',
      info: 'bg-info',
    };
    return <span className={`badge ${colors[severity] || 'bg-secondary'}`}>{severity}</span>;
  };

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

  const renderOverview = () => (
    <div className="row g-4">
      <div className="col-12 col-xl-4">
        <div className="card h-100 p-4" style={{ borderRadius: '16px' }}>
          <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>System Overview</h5>
          <div className="d-flex justify-content-center mb-4">
            <HealthCircle score={healthScore} label={getHealthLabel(healthScore)} />
          </div>
          <div className="d-flex flex-column gap-3">
            <div><span className="text-muted small">Computer Name:</span> <div className="fw-semibold">{machine?.device_name || machine?.hostname || machine?.machine_uid || id}</div></div>
            <div><span className="text-muted small">Employee Mobile:</span> <div className="fw-semibold">{machine?.employee_mobile_number || '—'}</div></div>
            <div><span className="text-muted small">Operating System:</span> <div className="fw-semibold">{machine?.operating_system || '—'}</div></div>
            <div><span className="text-muted small">OS Version:</span> <div className="fw-semibold">{machine?.os_version || '—'}</div></div>
            <div><span className="text-muted small">Status:</span>
              <div>
                <span className={`badge ${machine?.is_online ? 'bg-success' : 'bg-secondary'}`}>
                  {machine?.is_online ? 'Online' : 'Offline'}
                </span>
              </div>
            </div>
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
            { icon: <FaNetworkWired className="text-primary"/>, title: 'Network (↓)', value: cs.network_received_bytes != null ? formatBytes(cs.network_received_bytes) : '—' },
            { icon: <FaNetworkWired className="text-secondary"/>, title: 'Network (↑)', value: cs.network_sent_bytes != null ? formatBytes(cs.network_sent_bytes) : '—' },
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

  const renderPerformance = () => (
    <div className="row g-4">
      <div className="col-12 d-flex justify-content-between align-items-center">
        <h5 className="fw-bold mb-0" style={{ color: 'var(--text-body)' }}>Performance History</h5>
        <input
          type="date"
          className="form-control form-control-sm w-auto"
          value={selectedDate}
          max={new Date().toISOString().split('T')[0]}
          onChange={(e) => setSelectedDate(e.target.value)}
          style={{ backgroundColor: 'var(--bg-input)' }}
        />
      </div>

      {tabLoading ? (
        <div className="col-12 text-center py-5">
          <div className="spinner-border text-primary" role="status" />
        </div>
      ) : (Array.isArray(history) && history.length > 0) ? (
        <>
          {[
            { title: 'CPU Usage (%)', color: '#3B82F6', dataKey: 'cpu_percentage', label: 'CPU %' },
            { title: 'CPU Temperature (°C)', color: '#EF4444', dataKey: 'cpu_temperature', label: 'Temperature', isTemp: true },
            { title: 'Memory Usage (%)', color: '#8B5CF6', dataKey: 'ram_percentage', label: 'RAM %' },
            { title: 'Disk Usage (%)', color: '#F59E0B', dataKey: 'disk_percentage', label: 'Disk %' },
          ].map((chart, idx) => {
            const hasTempData = chart.isTemp && history.some(h => h.cpu_temperature != null);
            if (chart.isTemp && !hasTempData) return null;
            return (
              <div className="col-12 col-lg-6" key={idx}>
                <div className="card p-4 h-100" style={{ borderRadius: '16px' }}>
                  <h6 className="fw-semibold mb-4 text-muted">{chart.title}</h6>
                  <div style={{ height: '250px' }}>
                    <Line
                      data={chart.isTemp ? generateTempChartData() : generateChartData(chart.dataKey, chart.color, chart.label)}
                      options={chart.isTemp ? getTempChartOptions() : getChartOptions(true)}
                    />
                  </div>
                </div>
              </div>
            );
          })}
        </>
      ) : (
        <div className="col-12">
          <div className="card p-5 text-center" style={{ borderRadius: '16px' }}>
            <div style={{ fontSize: '48px', marginBottom: '12px' }}>📊</div>
            <h6 className="fw-bold mb-2" style={{ color: 'var(--text-body)' }}>No Performance Data</h6>
            <p className="text-muted mb-0">No health logs found for {selectedDate}. Select a different date.</p>
          </div>
        </div>
      )}
    </div>
  );

  const renderActivity = () => (
    <div className="row g-4">
      <div className="col-12">
        <div className="card p-4" style={{ borderRadius: '16px' }}>
          <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>
            Active Alerts
            {machineAlertsList.filter(a => a.status !== 'resolved').length > 0 && (
              <span className="badge bg-danger ms-2">{machineAlertsList.filter(a => a.status !== 'resolved').length}</span>
            )}
          </h5>
          {tabLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary" role="status" /></div>
          ) : machineAlertsList.filter(a => a.status !== 'resolved').length > 0 ? (
            <div className="table-responsive">
              <table className="table table-borderless table-sm align-middle">
                <thead><tr className="text-muted"><th>Severity</th><th>Alert</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                  {machineAlertsList.filter(a => a.status !== 'resolved').map((alert) => (
                    <tr key={alert.id}>
                      <td><SeverityBadge severity={alert.severity} /></td>
                      <td className="fw-semibold">{alert.title}</td>
                      <td className="text-muted small">{new Date(alert.created_at).toLocaleString()}</td>
                      <td><span className={`badge ${alert.status === 'acknowledged' ? 'bg-warning text-dark' : 'bg-danger'}`}>{alert.status}</span></td>
                      <td>
                        {alert.status === 'open' && (
                          <button className="btn btn-sm btn-outline-warning me-1" onClick={() => handleAcknowledge(alert.id)}>Acknowledge</button>
                        )}
                        {alert.status !== 'resolved' && (
                          <button className="btn btn-sm btn-outline-success" onClick={() => handleResolve(alert.id)}>Resolve</button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="text-center py-3">
              <FaCheckCircle className="text-success fs-2 mb-2" />
              <p className="text-muted mb-0">No active alerts for this machine</p>
            </div>
          )}
        </div>
      </div>

      <div className="col-12">
        <div className="card p-4" style={{ borderRadius: '16px' }}>
          <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>Activity Timeline</h5>
          {tabLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary" role="status" /></div>
          ) : (Array.isArray(timeline) && timeline.length > 0) ? (
            <div className="d-flex flex-column gap-3">
              {timeline.slice(0, 20).map((event, idx) => (
                <div key={idx} className="d-flex align-items-start gap-3 p-3" style={{ backgroundColor: 'var(--bg-input)', borderRadius: '12px' }}>
                  <div className="mt-1">
                    {event.type === 'alert' && <FaExclamationCircle className={event.severity === 'critical' ? 'text-danger' : 'text-warning'} />}
                    {event.type === 'login' && <FaUser className={event.severity === 'warning' ? 'text-warning' : 'text-info'} />}
                    {event.type === 'usb' && <FaUsb className="text-primary" />}
                    {event.type === 'device' && <FaDesktop className="text-secondary" />}
                  </div>
                  <div className="flex-grow-1">
                    <div className="fw-semibold">{event.title}</div>
                    {event.description && <div className="text-muted small">{event.description}</div>}
                  </div>
                  <div className="text-muted small text-nowrap">
                    {event.timestamp ? new Date(event.timestamp).toLocaleString() : '—'}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-muted text-center py-4">No activity events recorded yet</div>
          )}
        </div>
      </div>
    </div>
  );

  const renderInventory = () => (
    <div className="row g-4">
      {tabLoading ? (
        <div className="col-12 text-center py-5"><div className="spinner-border text-primary" role="status" /></div>
      ) : (
        <>
          <div className="col-12 col-lg-6">
            <div className="card p-4" style={{ borderRadius: '16px' }}>
              <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>Hardware Specifications</h5>
              <div className="d-flex flex-column gap-3">
                {[
                  ['Manufacturer', inventory?.hardware?.manufacturer || machine?.manufacturer],
                  ['Model', inventory?.hardware?.model || machine?.model],
                  ['Serial Number', inventory?.hardware?.serial_number || machine?.serial_number],
                  ['Processor', inventory?.hardware?.processor_name || inventory?.hardware?.processor || machine?.processor || cs.cpu_model],
                  ['Processor Cores', inventory?.hardware?.processor_cores != null ? String(inventory.hardware.processor_cores) : null],
                  ['Processor Threads', inventory?.hardware?.processor_threads != null ? String(inventory.hardware.processor_threads) : null],
                  ['Processor Clock Speed', inventory?.hardware?.processor_clock_speed != null ? `${inventory.hardware.processor_clock_speed} MHz` : null],
                  ['Memory (RAM)', inventory?.hardware?.ram_total_gb ? `${inventory.hardware.ram_total_gb} GB` : machine?.ram_gb ? `${machine.ram_gb} GB` : null],
                  ['RAM Type', inventory?.hardware?.ram_type || null],
                  ['GPU', inventory?.hardware?.gpu_name || null],
                  ['Disk Model', inventory?.hardware?.disk_model || null],
                  ['Disk Type', inventory?.hardware?.disk_type || null],
                  ['Disk Size', inventory?.hardware?.disk_size_gb != null ? `${inventory.hardware.disk_size_gb} GB` : null],
                  ['BIOS Version', inventory?.hardware?.bios_version || machine?.bios_version],
                  ['OS', inventory?.hardware?.operating_system || machine?.operating_system],
                  ['OS Version', inventory?.hardware?.os_version || machine?.os_version],
                ].map(([label, value], idx) => (
                  <div key={idx} className="d-flex justify-content-between border-bottom pb-2 border-secondary border-opacity-25">
                    <span className="text-muted">{label}</span>
                    <span className="fw-semibold">{value || '—'}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>

          <div className="col-12 col-lg-6">
            <div className="card p-4" style={{ borderRadius: '16px' }}>
              <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>
                Installed Software
                {inventory?.software && <span className="badge bg-primary ms-2">{inventory.software.length}</span>}
              </h5>
              {inventory?.software?.length > 0 ? (
                <div className="table-responsive" style={{ maxHeight: '400px', overflowY: 'auto' }}>
                  <table className="table table-borderless table-sm">
                    <thead><tr className="text-muted"><th>Name</th><th>Version</th><th>Publisher</th><th>Architecture</th><th>Install Date</th></tr></thead>
                    <tbody>
                      {inventory.software.map((sw, idx) => (
                        <tr key={idx}>
                          <td className="fw-semibold">{sw.software_name || sw.name || '—'}</td>
                          <td className="text-muted">{sw.version || '—'}</td>
                          <td className="text-muted">{sw.publisher || '—'}</td>
                          <td className="text-muted">{sw.architecture || '—'}</td>
                          <td className="text-muted small">{sw.install_date ? new Date(sw.install_date).toLocaleDateString() : '—'}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-muted text-center py-4">No software inventory data available</div>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  );

  const renderSecurity = () => (
    <div className="row g-4">
      {tabLoading ? (
        <div className="col-12 text-center py-5"><div className="spinner-border text-primary" role="status" /></div>
      ) : (
        <>
          <div className="col-12 col-xl-6">
            <div className="card p-4" style={{ borderRadius: '16px' }}>
              <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>Protection Status</h5>
              <div className="d-flex align-items-center p-3 mb-3" style={{ backgroundColor: 'var(--bg-input)', borderRadius: '12px' }}>
                <FaShieldAlt className="fs-3 text-success me-3" />
                <div className="flex-grow-1">
                  <div className="fw-bold">Antivirus</div>
                  <div className="text-muted small">{security?.antivirus?.display_name || cs.antivirus_status || 'Unknown'}</div>
                  {security?.antivirus?.is_enabled != null && (
                    <div className="text-muted small">
                      {security.antivirus.definition_status ? `Definition: ${security.antivirus.definition_status}` : ''}
                    </div>
                  )}
                </div>
                <div className="d-flex flex-column align-items-end gap-1">
                  <span className={`badge ${security?.antivirus?.is_enabled ? 'bg-success' : 'bg-danger'}`}>
                    {security?.antivirus?.is_enabled ? 'Enabled' : 'Disabled'}
                  </span>
                  {security?.antivirus?.is_updated != null && (
                    <span className={`badge ${security.antivirus.is_updated ? 'bg-success' : 'bg-warning text-dark'}`}>
                      {security.antivirus.is_updated ? 'Up to date' : 'Outdated'}
                    </span>
                  )}
                  {security?.antivirus?.definition_status && (
                    <span className="badge bg-info">{security.antivirus.definition_status}</span>
                  )}
                </div>
              </div>
              <div className="d-flex align-items-center p-3" style={{ backgroundColor: 'var(--bg-input)', borderRadius: '12px' }}>
                <FaLock className="fs-3 text-primary me-3" />
                <div className="flex-grow-1">
                  <div className="fw-bold">Firewall</div>
                  <div className="text-muted small">
                    Profile: {security?.firewall?.profile_name || '—'}
                  </div>
                  <div className="text-muted small">
                    {security?.firewall?.is_enabled != null ? (security.firewall.is_enabled ? 'Enabled' : 'Disabled') : '—'}
                  </div>
                </div>
                <span className={`badge ${security?.firewall?.is_enabled ? 'bg-success' : 'bg-danger'}`}>
                  {security?.firewall?.is_enabled ? 'On' : 'Off'}
                </span>
              </div>
            </div>
          </div>

          <div className="col-12 col-xl-6">
            <div className="card p-4" style={{ borderRadius: '16px' }}>
              <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>
                Windows Updates
                {security?.pending_updates?.length > 0 && <span className="badge bg-warning text-dark ms-2">{security.pending_updates.length} pending</span>}
              </h5>
              {security?.pending_updates?.length > 0 ? (
                <div style={{ maxHeight: '300px', overflowY: 'auto' }}>
                  {security.pending_updates.map((upd, idx) => (
                    <div key={idx} className="p-2 mb-2" style={{ backgroundColor: 'var(--bg-input)', borderRadius: '8px' }}>
                      <div className="d-flex justify-content-between align-items-start">
                        <div className="fw-semibold small">{upd.update_title || upd.title || upd.name || 'Update'}</div>
                        <div className="d-flex gap-1">
                          {upd.severity && <SeverityBadge severity={upd.severity} />}
                          {upd.category && <span className="badge bg-secondary">{upd.category}</span>}
                        </div>
                      </div>
                      <div className="text-muted" style={{ fontSize: '12px' }}>{upd.update_description || upd.description || upd.kb_article || '—'}</div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-3">
                  <FaCheckCircle className="text-success fs-3 mb-2" />
                  <p className="text-muted mb-0">System is up to date</p>
                </div>
              )}
            </div>
          </div>

          <div className="col-12">
            <div className="card p-4" style={{ borderRadius: '16px' }}>
              <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>Login Activity</h5>
              {security?.login_activity?.length > 0 ? (
                <div className="table-responsive">
                  <table className="table table-borderless table-sm">
                    <thead><tr className="text-muted"><th>User</th><th>Type</th><th>Session ID</th><th>Logon Time</th><th>Logoff Time</th><th>Result</th></tr></thead>
                    <tbody>
                      {security.login_activity.map((login, idx) => (
                        <tr key={idx}>
                          <td className="fw-semibold">{login.username || '—'}</td>
                          <td className="text-muted">{login.event_type || login.logon_type || '—'}</td>
                          <td className="text-muted small">{login.session_id || '—'}</td>
                          <td className="text-muted small">{login.logon_time ? new Date(login.logon_time).toLocaleString() : '—'}</td>
                          <td className="text-muted small">{login.logoff_time ? new Date(login.logoff_time).toLocaleString() : '—'}</td>
                          <td>
                            <span className={`badge ${login.is_success ? 'bg-success' : 'bg-danger'}`}>
                              {login.is_success ? 'Success' : 'Failed'}
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-muted text-center py-4">No login activity recorded</div>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  );

  const renderDevices = () => (
    <div className="row g-4">
      {tabLoading ? (
        <div className="col-12 text-center py-5"><div className="spinner-border text-primary" role="status" /></div>
      ) : (
        <>
          <div className="col-12 col-lg-6">
            <div className="card p-4" style={{ borderRadius: '16px' }}>
              <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>
                Connected Devices
                {devices?.connected_devices?.length > 0 && <span className="badge bg-primary ms-2">{devices.connected_devices.length}</span>}
              </h5>
              {devices?.connected_devices?.length > 0 ? (
                <div className="table-responsive" style={{ maxHeight: '400px', overflowY: 'auto' }}>
                  <table className="table table-borderless table-sm">
                    <thead><tr className="text-muted"><th>Name</th><th>Type</th><th>Manufacturer</th><th>Connection</th><th>Status</th><th>Last Seen</th></tr></thead>
                    <tbody>
                      {devices.connected_devices.map((dev, idx) => (
                        <tr key={idx}>
                          <td className="fw-semibold">{dev.device_name || dev.name || 'Unknown'}</td>
                          <td className="text-muted">{dev.device_type || dev.type || dev.device_class || '—'}</td>
                          <td className="text-muted">{dev.manufacturer || '—'}</td>
                          <td className="text-muted">{dev.connection_type || '—'}</td>
                          <td>
                            <span className={`badge ${dev.status === 'Connected' || dev.status === 'connected' ? 'bg-success' : 'bg-secondary'}`}>
                              {dev.status || '—'}
                            </span>
                          </td>
                          <td className="text-muted small">{dev.last_seen ? new Date(dev.last_seen).toLocaleString() : '—'}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-muted text-center py-4">No connected devices detected</div>
              )}
            </div>
          </div>

          <div className="col-12 col-lg-6">
            <div className="card p-4" style={{ borderRadius: '16px' }}>
              <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>
                <FaUsb className="me-2" />USB Activity
              </h5>
              {devices?.usb_activity?.length > 0 ? (
                <div className="table-responsive" style={{ maxHeight: '400px', overflowY: 'auto' }}>
                  <table className="table table-borderless table-sm">
                    <thead><tr className="text-muted"><th>Device</th><th>Serial</th><th>Drive Letter</th><th>Event</th><th>Time</th></tr></thead>
                    <tbody>
                      {devices.usb_activity.map((usb, idx) => (
                        <tr key={idx}>
                          <td className="fw-semibold">{usb.device_name || usb.friendly_name || '—'}</td>
                          <td className="text-muted small">{usb.device_serial || '—'}</td>
                          <td className="text-muted">{usb.drive_letter || '—'}</td>
                          <td><span className={`badge ${usb.event_type === 'Connected' || usb.event_type === 'Inserted' ? 'bg-success' : 'bg-secondary'}`}>{usb.event_type || '—'}</span></td>
                          <td className="text-muted small">{usb.created_at ? new Date(usb.created_at).toLocaleString() : '—'}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-muted text-center py-4">No USB activity recorded</div>
              )}
            </div>
          </div>

          <div className="col-12">
            <div className="card p-4" style={{ borderRadius: '16px' }}>
              <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>Device Events History</h5>
              {devices?.device_events?.length > 0 ? (
                <div className="table-responsive">
                  <table className="table table-borderless table-sm">
                    <thead><tr className="text-muted"><th>Device</th><th>Type</th><th>Manufacturer</th><th>Connection</th><th>Event</th><th>Event Time</th></tr></thead>
                    <tbody>
                      {devices.device_events.map((evt, idx) => (
                        <tr key={idx}>
                          <td className="fw-semibold">{evt.device_name || '—'}</td>
                          <td className="text-muted">{evt.device_type || '—'}</td>
                          <td className="text-muted">{evt.manufacturer || '—'}</td>
                          <td className="text-muted">{evt.connection_type || '—'}</td>
                          <td><span className={`badge ${evt.event_type === 'Connected' || evt.event_type === 'Inserted' ? 'bg-success' : 'bg-warning text-dark'}`}>{evt.event_type || '—'}</span></td>
                          <td className="text-muted small">{evt.event_time ? new Date(evt.event_time).toLocaleString() : '—'}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-muted text-center py-4">No device events recorded</div>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  );

  const renderProcesses = () => (
    <div className="row g-4">
      {tabLoading ? (
        <div className="col-12 text-center py-5"><div className="spinner-border text-primary" role="status" /></div>
      ) : (Array.isArray(processes) && processes.length > 0) ? (
        <div className="col-12">
          <div className="card p-4" style={{ borderRadius: '16px' }}>
            <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>
              Running Processes
              <span className="badge bg-primary ms-2">{processes.length}</span>
            </h5>
            <div className="table-responsive" style={{ maxHeight: '500px', overflowY: 'auto' }}>
              <table className="table table-borderless table-sm">
                <thead><tr className="text-muted"><th>#</th><th>Process Name</th><th>CPU %</th><th>Memory %</th><th>Collected At</th></tr></thead>
                <tbody>
                  {processes.map((p, idx) => (
                    <tr key={idx}>
                      <td className="text-muted">{idx + 1}</td>
                      <td className="fw-semibold">{p.process_name || '—'}</td>
                      <td>{p.cpu_usage != null ? `${Number(p.cpu_usage).toFixed(1)}%` : '—'}</td>
                      <td>{p.memory_usage != null ? `${Number(p.memory_usage).toFixed(1)}%` : '—'}</td>
                      <td className="text-muted small">{p.collected_at ? new Date(p.collected_at).toLocaleString() : '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      ) : (
        <div className="col-12">
          <div className="card p-5 text-center" style={{ borderRadius: '16px' }}>
            <h6 className="fw-bold mb-2" style={{ color: 'var(--text-body)' }}>No Process Data</h6>
            <p className="text-muted mb-0">No running processes recorded for this machine.</p>
          </div>
        </div>
      )}
    </div>
  );

  const renderServices = () => (
    <div className="row g-4">
      {tabLoading ? (
        <div className="col-12 text-center py-5"><div className="spinner-border text-primary" role="status" /></div>
      ) : (Array.isArray(services) && services.length > 0) ? (
        <div className="col-12">
          <div className="card p-4" style={{ borderRadius: '16px' }}>
            <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>
              Windows Services
              <span className="badge bg-primary ms-2">{services.length}</span>
            </h5>
            <div className="table-responsive" style={{ maxHeight: '500px', overflowY: 'auto' }}>
              <table className="table table-borderless table-sm">
                <thead><tr className="text-muted"><th>Service Name</th><th>Display Name</th><th>Status</th><th>Start Type</th><th>Collected At</th></tr></thead>
                <tbody>
                  {services.map((s, idx) => (
                    <tr key={idx}>
                      <td className="fw-semibold small">{s.service_name || '—'}</td>
                      <td className="text-muted small">{s.display_name || '—'}</td>
                      <td>
                        <span className={`badge ${s.status === 'Running' ? 'bg-success' : s.status === 'Stopped' ? 'bg-secondary' : 'bg-warning text-dark'}`}>
                          {s.status || '—'}
                        </span>
                      </td>
                      <td className="text-muted small">{s.start_type || '—'}</td>
                      <td className="text-muted small">{s.collected_at ? new Date(s.collected_at).toLocaleString() : '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      ) : (
        <div className="col-12">
          <div className="card p-5 text-center" style={{ borderRadius: '16px' }}>
            <h6 className="fw-bold mb-2" style={{ color: 'var(--text-body)' }}>No Services Data</h6>
            <p className="text-muted mb-0">No Windows services recorded for this machine.</p>
          </div>
        </div>
      )}
    </div>
  );

  const formatSpeed = (speed) => {
    if (speed == null) return '—';
    if (speed === 0) return '0 bps';
    const sizes = ['bps', 'Kbps', 'Mbps', 'Gbps'];
    const i = Math.floor(Math.log(speed) / Math.log(1000));
    return `${(speed / Math.pow(1000, i)).toFixed(1)} ${sizes[i]}`;
  };

  const renderNetwork = () => {
    const adapters = network?.adapters || [];
    const disks = network?.disks || [];
    return (
      <div className="row g-4">
        {tabLoading ? (
          <div className="col-12 text-center py-5"><div className="spinner-border text-primary" role="status" /></div>
        ) : (
          <>
            <div className="col-12">
              <div className="card p-4" style={{ borderRadius: '16px' }}>
                <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>
                  Network Adapters
                  {adapters.length > 0 && <span className="badge bg-primary ms-2">{adapters.length}</span>}
                </h5>
                {adapters.length > 0 ? (
                  <div className="table-responsive">
                    <table className="table table-borderless table-sm">
                      <thead><tr className="text-muted"><th>Adapter</th><th>IP Address</th><th>MAC Address</th><th>Speed</th><th>Bytes Sent</th><th>Bytes Received</th><th>Status</th></tr></thead>
                      <tbody>
                        {adapters.map((a, idx) => (
                          <tr key={idx}>
                            <td className="fw-semibold small">{a.adapter_name || '—'}</td>
                            <td className="text-muted small">{a.ip_address || '—'}</td>
                            <td className="text-muted small" style={{ fontFamily: 'monospace' }}>{a.mac_address || '—'}</td>
                            <td className="text-muted small">{formatSpeed(a.speed)}</td>
                            <td className="text-muted small">{formatBytes(a.bytes_sent)}</td>
                            <td className="text-muted small">{formatBytes(a.bytes_received)}</td>
                            <td>
                              <span className={`badge ${a.status === 'Up' || a.status === 'up' ? 'bg-success' : 'bg-secondary'}`}>
                                {a.status || '—'}
                              </span>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <div className="text-muted text-center py-4">No network adapters recorded</div>
                )}
              </div>
            </div>

            <div className="col-12">
              <div className="card p-4" style={{ borderRadius: '16px' }}>
                <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>
                  Disk Drives
                  {disks.length > 0 && <span className="badge bg-primary ms-2">{disks.length}</span>}
                </h5>
                {disks.length > 0 ? (
                  <div className="table-responsive">
                    <table className="table table-borderless table-sm">
                      <thead><tr className="text-muted"><th>Drive</th><th>Type</th><th>File System</th><th>Total</th><th>Used</th><th>Free</th><th>Health</th></tr></thead>
                      <tbody>
                        {disks.map((d, idx) => {
                          const usedPct = d.total_gb ? ((d.used_gb / d.total_gb) * 100).toFixed(1) : null;
                          return (
                            <tr key={idx}>
                              <td className="fw-semibold">{d.drive_letter || '—'}</td>
                              <td className="text-muted small">{d.drive_type || '—'}</td>
                              <td className="text-muted small">{d.file_system || '—'}</td>
                              <td className="text-muted small">{d.total_gb != null ? `${d.total_gb} GB` : '—'}</td>
                              <td className="text-muted small">{d.used_gb != null ? `${d.used_gb} GB (${usedPct}%)` : '—'}</td>
                              <td className="text-muted small">{d.free_gb != null ? `${d.free_gb} GB` : '—'}</td>
                              <td>{getDiskHealthBadge(d.health_status)}</td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <div className="text-muted text-center py-4">No disk drives recorded</div>
                )}
              </div>
            </div>
          </>
        )}
      </div>
    );
  };

  const renderSystemLogs = () => (
    <div className="row g-4">
      {tabLoading ? (
        <div className="col-12 text-center py-5"><div className="spinner-border text-primary" role="status" /></div>
      ) : (
        <>
          <div className="col-12 col-lg-6">
            <div className="card p-4" style={{ borderRadius: '16px' }}>
              <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>
                Startup Programs
                {startupPrograms.length > 0 && <span className="badge bg-primary ms-2">{startupPrograms.length}</span>}
              </h5>
              {startupPrograms.length > 0 ? (
                <div className="table-responsive" style={{ maxHeight: '400px', overflowY: 'auto' }}>
                  <table className="table table-borderless table-sm">
                    <thead><tr className="text-muted"><th>Program</th><th>Path</th><th>Registry Key</th><th>Startup Type</th><th>Status</th></tr></thead>
                    <tbody>
                      {startupPrograms.map((sp, idx) => (
                        <tr key={idx}>
                          <td className="fw-semibold small">{sp.program_name || '—'}</td>
                          <td className="text-muted small" style={{ maxWidth: '200px', overflow: 'hidden', textOverflow: 'ellipsis' }}>{sp.program_path || '—'}</td>
                          <td className="text-muted small" style={{ maxWidth: '150px', overflow: 'hidden', textOverflow: 'ellipsis' }}>{sp.registry_key || '—'}</td>
                          <td className="text-muted small">{sp.startup_type || '—'}</td>
                          <td>
                            <span className={`badge ${sp.status === 'Enabled' || sp.status === 'enabled' ? 'bg-success' : 'bg-secondary'}`}>
                              {sp.status || '—'}
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-muted text-center py-4">No startup programs recorded</div>
              )}
            </div>
          </div>

          <div className="col-12 col-lg-6">
            <div className="card p-4" style={{ borderRadius: '16px' }}>
              <h5 className="fw-bold mb-4" style={{ color: 'var(--text-body)' }}>
                System Event Logs
                {eventLogs.length > 0 && <span className="badge bg-primary ms-2">{eventLogs.length}</span>}
              </h5>
              {eventLogs.length > 0 ? (
                <div className="table-responsive" style={{ maxHeight: '400px', overflowY: 'auto' }}>
                  <table className="table table-borderless table-sm">
                    <thead><tr className="text-muted"><th>Level</th><th>Source</th><th>Message</th><th>Event Time</th></tr></thead>
                    <tbody>
                      {eventLogs.map((el, idx) => (
                        <tr key={idx}>
                          <td>
                            <span className={`badge ${el.level === 'Error' || el.level === 'error' || el.level === 'Critical' || el.level === 'critical' ? 'bg-danger' : el.level === 'Warning' || el.level === 'warning' ? 'bg-warning text-dark' : 'bg-info'}`}>
                              {el.level || '—'}
                            </span>
                          </td>
                          <td className="text-muted small">{el.source || '—'}</td>
                          <td className="text-muted small" style={{ maxWidth: '250px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={el.message}>{el.message || '—'}</td>
                          <td className="text-muted small">{el.event_time ? new Date(el.event_time).toLocaleString() : '—'}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-muted text-center py-4">No event logs recorded</div>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  );

  if (loading) {
    return (
      <div className="container-fluid p-0 d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
        <div className="text-center">
          <div className="spinner-border text-primary mb-3" role="status" />
          <p className="text-muted">Loading machine details...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="container-fluid p-0 d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
        <div className="text-center">
          <div className="mb-3" style={{ fontSize: '48px' }}>⚠️</div>
          <h5 className="fw-bold mb-2" style={{ color: 'var(--text-body)' }}>Machine Not Found</h5>
          <p className="text-muted mb-3">{error}</p>
          <button className="btn btn-primary" onClick={() => navigate('/machines')}>Back to Machines</button>
        </div>
      </div>
    );
  }

  return (
    <div className="container-fluid p-0">
      <div className="d-flex align-items-center mb-4">
        <button
          className="btn btn-link text-decoration-none d-flex align-items-center p-0 me-3"
          onClick={() => navigate('/machines')}
          style={{ color: 'var(--text-muted-color)' }}
        >
          <FaArrowLeft className="me-2" /> Back to Machines
        </button>
        <h3 className="fw-bold mb-0 ms-2" style={{ color: 'var(--text-body)' }}>
          {machine?.device_name || machine?.hostname || 'Machine Details'}
        </h3>
        <span className={`badge ms-3 ${machine?.is_online ? 'bg-success' : 'bg-secondary'}`}>
          {machine?.is_online ? '● Online' : '● Offline'}
        </span>
      </div>

      <div className="d-flex mb-4 gap-2 overflow-auto pb-2" style={{ borderBottom: '1px solid var(--border-color)' }}>
        {tabs.map(tab => (
          <button
            key={tab}
            className={`btn px-4 py-2 fw-semibold ${activeTab === tab ? 'border-bottom border-primary text-primary' : 'text-muted'}`}
            style={{
              borderRadius: '0',
              borderBottomWidth: activeTab === tab ? '2px' : '0',
              whiteSpace: 'nowrap',
              backgroundColor: 'transparent'
            }}
            onClick={() => setActiveTab(tab)}
          >
            {tab}
          </button>
        ))}
      </div>

      <div className="tab-content">
        {activeTab === 'Overview' && renderOverview()}
        {activeTab === 'Performance' && renderPerformance()}
        {activeTab === 'Processes' && renderProcesses()}
        {activeTab === 'Services' && renderServices()}
        {activeTab === 'Network' && renderNetwork()}
        {activeTab === 'Activity' && renderActivity()}
        {activeTab === 'Inventory' && renderInventory()}
        {activeTab === 'Security' && renderSecurity()}
        {activeTab === 'Devices' && renderDevices()}
        {activeTab === 'System Logs' && renderSystemLogs()}
      </div>
    </div>
  );
};

export default MachineDetails;
