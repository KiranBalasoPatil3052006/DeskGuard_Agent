import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { 
  FaSync, FaMicrochip, FaMemory, FaHdd, FaBatteryFull, 
  FaNetworkWired, FaDesktop, FaExclamationTriangle
} from 'react-icons/fa';
import {
  Chart as ChartJS, CategoryScale, LinearScale,
  PointElement, LineElement, Title, Tooltip, Legend, Filler
} from 'chart.js';
import { Line } from 'react-chartjs-2';
import {
  getMachines, getMachine, getMachineStatus, getMachineHistory, getMachineProcesses
} from '../../services/machines';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const LiveMonitoring = () => {
  const [machines, setMachines] = useState([]);
  const [selectedMachineId, setSelectedMachineId] = useState('');
  
  // Real data states
  const [machineInfo, setMachineInfo] = useState(null);
  const [status, setStatus] = useState(null);
  const [history, setHistory] = useState([]);
  const [processes, setProcesses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [lastUpdated, setLastUpdated] = useState(null);

  // 1. Fetch Machine List on Mount
  useEffect(() => {
    let cancelled = false;
    getMachines({ per_page: 100 })
      .then(res => {
        if (cancelled) return;
        const list = res?.data?.data || res?.data || res || [];
        setMachines(list);
        if (list.length > 0) {
          setSelectedMachineId(list[0].id);
        } else {
          setLoading(false);
        }
      })
      .catch(err => {
        console.error('Failed to load machines list', err);
        setLoading(false);
      });
    return () => { cancelled = true; };
  }, []);

  // 2. Fetch Live Data for Selected Machine
  const fetchLiveData = useCallback(async (machineId, showLoading = true) => {
    if (!machineId) return;
    if (showLoading) setLoading(true);
    try {
      // Create a 2-hour window for charts
      const now = new Date();
      const from = new Date(now.getTime() - 2 * 60 * 60 * 1000).toISOString().split('.')[0].replace('T', ' ');
      const to = now.toISOString().split('.')[0].replace('T', ' ');

      const [mRes, sRes, hRes, pRes] = await Promise.all([
        getMachine(machineId).catch(() => ({ data: null })),
        getMachineStatus(machineId).catch(() => ({ data: null })),
        getMachineHistory(machineId, { from, to }).catch(() => ({ data: [] })),
        getMachineProcesses(machineId).catch(() => ({ data: [] }))
      ]);

      setMachineInfo(mRes?.data || mRes);
      setStatus(sRes?.data || sRes);
      setHistory(hRes?.data || hRes || []);
      setProcesses(pRes?.data || pRes || []);
      setLastUpdated(new Date().toLocaleTimeString());
    } catch (err) {
      console.error('Failed to load live data', err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (selectedMachineId) {
      fetchLiveData(selectedMachineId, true);
      // Auto-refresh every 30 seconds
      const interval = setInterval(() => {
        fetchLiveData(selectedMachineId, false);
      }, 30000);
      return () => clearInterval(interval);
    }
  }, [selectedMachineId, fetchLiveData]);

  // Handle Loading/Empty
  if (loading && !machineInfo) {
    return (
      <div className="container-fluid p-0 d-flex justify-content-center align-items-center" style={{ minHeight: '60vh' }}>
        <div className="spinner-border text-primary" role="status"><span className="visually-hidden">Loading...</span></div>
      </div>
    );
  }

  if (machines.length === 0) {
    return (
      <div className="container-fluid p-0 text-center py-5">
        <FaDesktop size={50} className="text-muted mb-3" />
        <h4 className="text-dark-blue">No Machines Found</h4>
        <p className="text-muted">Install the DeskGuard Agent to start monitoring.</p>
      </div>
    );
  }

  // Current values mapping
  const cs = status || machineInfo?.current_status || {};
  const isOnline = machineInfo?.is_online || machineInfo?.status === 'Online';
  const cpuPct = cs?.cpu_percentage ?? 0;
  const ramPct = cs?.ram_percentage ?? 0;
  const diskPct = cs?.disk_percentage ?? 0;
  const batteryPct = cs?.battery_percentage ?? 'N/A';
  
  // Format network traffic if available
  let networkTraffic = '0 Mbps';
  if (cs?.network_sent_bytes || cs?.network_received_bytes) {
     const totalBytesPerSec = ((cs.network_sent_bytes || 0) + (cs.network_received_bytes || 0)) / 125000;
     networkTraffic = `${totalBytesPerSec.toFixed(2)} Mbps`;
  }

  const performanceCards = [
    { title: 'CPU Usage', value: `${cpuPct}%`, icon: <FaMicrochip />, color: 'primary', bg: 'dbeafe' },
    { title: 'RAM Usage', value: `${ramPct}%`, icon: <FaMemory />, color: 'warning', bg: 'fef3c7' },
    { title: 'Disk Usage', value: `${diskPct}%`, icon: <FaHdd />, color: 'success', bg: 'dcfce7' },
    { title: 'Battery Status', value: batteryPct === 'N/A' ? 'N/A' : `${batteryPct}%`, icon: <FaBatteryFull />, color: 'info', bg: 'cff4fc' },
    { title: 'Network Status', value: networkTraffic, icon: <FaNetworkWired />, color: 'secondary', bg: 'e2e3e5' },
  ];

  // History Chart Setup
  const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: false, 
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, max: 100 },
      x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } }
    },
    elements: { point: { radius: 0, hitRadius: 10 }, line: { tension: 0.4 } }
  };

  const networkOptions = { ...lineOptions, scales: { ...lineOptions.scales, y: { beginAtZero: true } } };

  // Data mapping for charts
  const hist = Array.isArray(history) ? history : [];
  const chartLabels = hist.length > 0 ? hist.map(h => new Date(h.collected_at || h.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})) : ['No Data'];
  
  const createChartData = (label, dataKey, colorStr, bgColorStr) => {
    const dataPoints = hist.length > 0 ? hist.map(h => h[dataKey] || 0) : [0];
    return {
      labels: chartLabels,
      datasets: [{
        fill: true,
        label,
        data: dataPoints,
        borderColor: colorStr,
        backgroundColor: bgColorStr,
        borderWidth: 2
      }]
    };
  };

  const cpuData = createChartData('CPU Usage (%)', 'cpu_percentage', '#6366F1', 'rgba(99, 102, 241, 0.2)');
  const ramData = createChartData('Memory Usage (%)', 'ram_percentage', '#F59E0B', 'rgba(245, 158, 11, 0.2)');
  const diskData = createChartData('Disk Usage (%)', 'disk_percentage', '#EF4444', 'rgba(239, 68, 68, 0.2)');
  const networkData = createChartData('Network Traffic (bytes/s)', 'network_bytes_sent_per_sec', '#22C55E', 'rgba(34, 197, 94, 0.2)');

  return (
    <div className="container-fluid p-0">
      
      {/* 1. Page Header & Health Indicator */}
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
          <div className="d-flex align-items-center mb-1">
            <h3 className="text-dark-blue fw-bold mb-0 me-3">Live Monitoring</h3>
            <span className={`badge ${isOnline ? 'bg-success text-success' : 'bg-secondary text-secondary'} bg-opacity-10 border border-${isOnline ? 'success' : 'secondary'} px-3 py-2 rounded-pill d-flex align-items-center`}>
              <span className="me-2" style={{ width: '8px', height: '8px', backgroundColor: 'currentColor', borderRadius: '50%', display: 'inline-block' }}></span>
              {isOnline ? 'Online / Live' : 'Offline'}
            </span>
          </div>
          <span className="text-muted small">Real-time telemetry and performance analysis</span>
        </div>
        
        <div className="d-flex flex-wrap gap-2 align-items-center">
          <div className="input-group" style={{ width: '250px' }}>
            <span className="input-group-text bg-white"><FaDesktop className="text-muted" /></span>
            <select 
              className="form-select border-start-0 fw-semibold" 
              value={selectedMachineId}
              onChange={(e) => setSelectedMachineId(e.target.value)}
            >
              {machines.map(m => (
                <option key={m.id} value={m.id}>
                  {m.device_name || m.hostname || `Machine ${m.id}`} {m.is_online ? '(Online)' : '(Offline)'}
                </option>
              ))}
            </select>
          </div>
          <button className="btn btn-primary d-flex align-items-center" onClick={() => fetchLiveData(selectedMachineId, true)}>
            <FaSync className="me-2" /> Refresh
          </button>
          <span className="text-muted small ms-2 d-none d-xl-inline">Last updated: {lastUpdated}</span>
        </div>
      </div>

      {!machineInfo ? (
         <div className="text-center py-5"><span className="spinner-border text-primary"></span></div>
      ) : (
      <>
        <div className="row g-4 mb-4">
          {/* 2. System Information */}
          <div className="col-12 col-xl-4">
            <div className="card h-100 border-0 glass-card">
              <div className="card-header bg-transparent border-bottom border-light fw-bold text-dark-blue">
                System Information
              </div>
              <div className="card-body">
                <ul className="list-group list-group-flush">
                  <li className="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                    <span className="text-muted">Machine Name</span>
                    <span className="fw-semibold text-dark-blue">{machineInfo.device_name || machineInfo.hostname}</span>
                  </li>
                  <li className="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                    <span className="text-muted">Username</span>
                    <span className="fw-semibold">{machineInfo.employee_name || 'SYSTEM'}</span>
                  </li>
                  <li className="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                    <span className="text-muted">Operating System</span>
                    <span className="fw-semibold">{machineInfo.operating_system || 'Unknown'}</span>
                  </li>
                  <li className="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                    <span className="text-muted">Hostname</span>
                    <span className="fw-semibold">{machineInfo.hostname || 'N/A'}</span>
                  </li>
                  <li className="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                    <span className="text-muted">IP Address</span>
                    <span className="fw-semibold font-monospace">{machineInfo?.ip_address || 'N/A'}</span>
                  </li>
                  <li className="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                    <span className="text-muted">MAC Address</span>
                    <span className="fw-semibold font-monospace">{machineInfo?.mac_address || 'N/A'}</span>
                  </li>
                  <li className="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                    <span className="text-muted">System Uptime</span>
                    <span className="fw-semibold">{machineInfo?.uptime_seconds ? Math.floor(machineInfo.uptime_seconds / 3600) + ' Hrs' : 'N/A'}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          {/* 3. Performance Cards */}
          <div className="col-12 col-xl-8">
            <div className="row g-4 h-100">
              {performanceCards.map((card, idx) => (
                <div className="col-12 col-sm-6 col-lg-4" key={idx}>
                  <div className="card border-0 glass-card h-100">
                    <div className="card-body d-flex align-items-center">
                      <div 
                        className={`text-${card.color} me-3 d-flex align-items-center justify-content-center rounded`}
                        style={{ width: '56px', height: '56px', backgroundColor: `#${card.bg}`, fontSize: '1.5rem' }}
                      >
                        {card.icon}
                      </div>
                      <div>
                        <h6 className="text-muted mb-1 small">{card.title}</h6>
                        <h3 className="mb-0 fw-bold text-dark-blue">{card.value}</h3>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* 4. Performance Charts */}
        <div className="row g-4 mb-4">
          <div className="col-12 col-lg-6">
            <div className="card border-0 glass-card h-100">
              <div className="card-header bg-transparent border-bottom border-light fw-bold text-dark-blue">CPU Usage (Live)</div>
              <div className="card-body" style={{ height: '300px' }}>
                <Line options={lineOptions} data={cpuData} />
              </div>
            </div>
          </div>
          
          <div className="col-12 col-lg-6">
            <div className="card border-0 glass-card h-100">
              <div className="card-header bg-transparent border-bottom border-light fw-bold text-dark-blue">Memory Usage (Live)</div>
              <div className="card-body" style={{ height: '300px' }}>
                <Line options={lineOptions} data={ramData} />
              </div>
            </div>
          </div>
          
          <div className="col-12 col-lg-6">
            <div className="card border-0 glass-card h-100">
              <div className="card-header bg-transparent border-bottom border-light fw-bold text-dark-blue">Disk Usage (Live)</div>
              <div className="card-body" style={{ height: '300px' }}>
                <Line options={lineOptions} data={diskData} />
              </div>
            </div>
          </div>
          
          <div className="col-12 col-lg-6">
            <div className="card border-0 glass-card h-100">
              <div className="card-header bg-transparent border-bottom border-light fw-bold text-dark-blue">Network Traffic (Live)</div>
              <div className="card-body" style={{ height: '300px' }}>
                <Line options={networkOptions} data={networkData} />
              </div>
            </div>
          </div>
        </div>

        {/* 5. Running Processes & Details */}
        <div className="row g-4 mb-4">
          <div className="col-12">
            <div className="card border-0 glass-card h-100">
              <div className="card-header bg-transparent border-bottom border-light d-flex justify-content-between align-items-center">
                <span className="fw-bold text-dark-blue">Running Processes (Live)</span>
              </div>
              <div className="card-body p-0">
                <div className="table-responsive" style={{ maxHeight: '400px', overflowY: 'auto' }}>
                  <table className="table table-hover align-middle mb-0">
                    <thead className="table-light text-muted sticky-top" style={{ fontSize: '0.85rem' }}>
                      <tr>
                        <th className="ps-4">Process Name</th>
                        <th>PID</th>
                        <th>CPU Usage</th>
                        <th>Memory Usage</th>
                        <th className="pe-4">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {processes && processes.length > 0 ? processes.slice(0, 15).map((proc, index) => (
                        <tr key={proc.id || index} style={{ fontSize: '0.9rem' }}>
                          <td className="ps-4 fw-semibold text-dark-blue">{proc.process_name}</td>
                          <td className="text-muted font-monospace">{proc.process_id || proc.pid}</td>
                          <td className={`${(proc.cpu_usage || 0) > 50 ? 'text-danger fw-bold' : (proc.cpu_usage || 0) > 20 ? 'text-warning fw-bold' : 'text-dark fw-semibold'}`}>
                            {proc.cpu_usage || 0}%
                          </td>
                          <td className="text-muted">
                            {proc.memory_usage
                              ? proc.memory_usage > 1000
                                ? `${(proc.memory_usage / 1024).toFixed(1)} GB`
                                : `${proc.memory_usage.toFixed(1)} MB`
                              : (proc.memory_usage_bytes / (1024 * 1024)).toFixed(1) + ' MB'}
                          </td>
                          <td className="pe-4">
                            <span className="badge bg-success">Running</span>
                          </td>
                        </tr>
                      )) : (
                        <tr>
                          <td colSpan="5" className="text-center py-4 text-muted">No process data available</td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
              <div className="card-footer bg-transparent border-top border-light text-center">
                <small className="text-muted">Showing top 15 processes by resource usage</small>
              </div>
            </div>
          </div>
        </div>
      </>
      )}

    </div>
  );
};

export default LiveMonitoring;
