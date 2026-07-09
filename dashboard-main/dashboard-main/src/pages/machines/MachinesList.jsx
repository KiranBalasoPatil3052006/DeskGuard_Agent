import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
  FaServer, 
  FaCheckCircle, 
  FaMicrochip,
  FaMemory,
  FaEllipsisH,
  FaSearch
} from 'react-icons/fa';
import { getMachines } from '../../services/machines';

const MachinesList = () => {
  const navigate = useNavigate();
  const [machines, setMachines] = useState([]);
  const [summary, setSummary] = useState({ total: 0, online: 0, offline: 0, critical: 0 });
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('All');
  const [search, setSearch] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const itemsPerPage = 10;
  const [openDropdown, setOpenDropdown] = useState(null);
  const dropdownRef = useRef(null);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setOpenDropdown(null);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  useEffect(() => {
    let cancelled = false;
    async function fetch() {
      setLoading(true);
      try {
        const params = { page: currentPage, per_page: itemsPerPage };
        if (filter !== 'All') params.status = filter;
        if (search) params.search = search;
        const res = await getMachines(params);
        if (!cancelled) {
          const d = res.data;
          const list = d.data || d || [];
          setMachines(list);
          setLastPage(d.last_page || 1);
          setSummary({
            total: d.total || list.length,
            online: d.online_count || 0,
            offline: d.offline_count || 0,
            critical: d.critical_count || 0,
          });
        }
      } catch (err) {
        console.error('Failed to load machines:', err);
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    fetch();
    return () => { cancelled = true; };
  }, [filter, search, currentPage]);

  useEffect(() => {
    setCurrentPage(1);
  }, [filter, search]);

  const summaryCards = [
    { title: 'Total Machines', value: summary.total, icon: <FaServer />, gradClass: 'grad-teal' },
    { title: 'Active Machines', value: summary.online, icon: <FaCheckCircle />, gradClass: 'grad-purple' },
    { title: 'Offline Machines', value: summary.offline, icon: <FaMicrochip />, gradClass: 'grad-orange' },
    { title: 'Critical Alerts', value: summary.critical, icon: <FaMemory />, gradClass: 'grad-pink' },
  ];

  const hasExtraData = machines.some(m =>
    m.current_status?.cpu_percentage != null ||
    m.current_status?.ram_percentage != null ||
    m.current_status?.disk_percentage != null ||
    m.last_heartbeat_at != null
  );

  const statusBadge = (status) => {
    switch (status) {
      case 'Critical': case 'critical': return 'bg-danger';
      case 'Warning': case 'warning': return 'bg-warning text-dark';
      case 'Online': case 'online': return 'bg-success';
      case 'Offline': case 'offline': return 'bg-secondary';
      default: return 'bg-secondary';
    }
  };

  return (
    <div className="container-fluid p-0">
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <h3 className="fw-bold mb-0" style={{ color: 'var(--text-body)' }}>Machines Overview</h3>
        <div className="d-flex align-items-center gap-3">
          <div className="position-relative">
              <input 
                type="text" 
                className="form-control" 
                placeholder="Search Computer/IP..." 
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                style={{ 
                  paddingLeft: '35px', 
                  borderRadius: '20px', 
                  backgroundColor: 'var(--bg-input)', 
                  border: '1px solid var(--border-color)',
                  color: 'var(--text-body)',
                  minWidth: '220px'
                }} 
              />
            <FaSearch className="position-absolute text-muted" style={{ top: '50%', left: '12px', transform: 'translateY(-50%)' }} />
          </div>
          <div className="pill-group">
            {['All', 'Online', 'Offline', 'Critical'].map(f => (
              <button 
                key={f}
                className={`pill-btn ${filter === f ? 'active' : 'text-muted border-0'}`} 
                style={filter === f ? { backgroundColor: 'var(--primary-blue)', borderColor: 'var(--primary-blue)', color: '#fff' } : { backgroundColor: 'var(--bg-card)' }}
                onClick={() => setFilter(f)}
              >
                {f}
              </button>
            ))}
          </div>
        </div>
      </div>

      <div className="row g-4 mb-4">
        {summaryCards.map((card, idx) => (
          <div className="col-12 col-md-6 col-xl-3" key={idx}>
            <div className={`card h-100 border-0 ${card.gradClass}`} style={{ borderRadius: '16px', color: '#fff' }}>
              <div className="card-body p-4 d-flex flex-column">
                <div className="d-flex align-items-center mb-4 opacity-75">
                  <div className="me-2 rounded-circle d-flex align-items-center justify-content-center" style={{ width: '32px', height: '32px', backgroundColor: 'rgba(255,255,255,0.2)' }}>
                    {card.icon}
                  </div>
                  <span style={{ fontSize: '0.9rem' }}>{card.title}</span>
                </div>
                <div className="mt-auto d-flex justify-content-between align-items-end">
                  <h2 className="mb-0 fw-bold">{card.value}</h2>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card p-4" style={{ borderRadius: '16px' }}>
        {loading ? (
          <div className="text-center py-4">
            <div className="spinner-border text-primary" role="status">
              <span className="visually-hidden">Loading...</span>
            </div>
          </div>
        ) : (
        <div className="table-responsive">
          <table className="table table-borderless table-admin align-middle mb-0 w-100">
            <thead>
              <tr>
                <th className="ps-2">Computer Name</th>
                <th>IP Address</th>
                <th>Operating System</th>
                <th>Status</th>
                {hasExtraData && <th>CPU Usage</th>}
                {hasExtraData && <th>RAM Usage</th>}
                {hasExtraData && <th>Disk Usage</th>}
                {hasExtraData && <th>Last Heartbeat</th>}
                <th className="text-center pe-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {machines.length > 0 ? (
                machines.map((m, idx) => (
                  <tr key={m.id || idx}>
                    <td className="ps-2 text-muted fw-semibold">{m.device_name || m.hostname || m.machine_uid || '—'}</td>
                    <td className="text-muted">{m.current_status?.network_interfaces?.[0]?.ip_address || m.hostname || '—'}</td>
                    <td className="text-muted">{m.operating_system || '—'}</td>
                    <td>
                      <span className={`badge ${statusBadge(m.status || (m.is_online ? 'Online' : 'Offline'))}`}>
                        {m.status || (m.is_online ? 'Online' : 'Offline') || 'Unknown'}
                      </span>
                    </td>
                    {hasExtraData && <td className="text-muted">{m.current_status?.cpu_percentage != null ? `${m.current_status.cpu_percentage}%` : '—'}</td>}
                    {hasExtraData && <td className="text-muted">{m.current_status?.ram_percentage != null ? `${m.current_status.ram_percentage}%` : '—'}</td>}
                    {hasExtraData && <td className="text-muted">{m.current_status?.disk_percentage != null ? `${m.current_status.disk_percentage}%` : '—'}</td>}
                    {hasExtraData && <td className="text-muted">{m.last_heartbeat_at ? new Date(m.last_heartbeat_at).toLocaleString() : '—'}</td>}
                    <td 
                      className="text-center text-muted fs-5 pe-2 position-relative" 
                      style={{ cursor: 'pointer' }}
                      ref={openDropdown === idx ? dropdownRef : null}
                    >
                      <div onClick={() => setOpenDropdown(openDropdown === idx ? null : idx)}>
                        <FaEllipsisH />
                      </div>
                      {openDropdown === idx && (
                        <div 
                          className="position-absolute mt-2 shadow-lg text-start" 
                          style={{ 
                            backgroundColor: 'var(--bg-card)', 
                            border: '1px solid var(--border-color)', 
                            borderRadius: '8px', 
                            zIndex: 50, 
                            minWidth: '170px', 
                            overflow: 'hidden',
                            right: '30px',
                            top: '50%'
                          }}
                        >
                          <div 
                            className="px-3 py-2"
                            onClick={() => {
                              setOpenDropdown(null);
                              navigate(`/machines/${m.id}`);
                            }}
                            style={{ 
                              fontSize: '0.85rem', 
                              color: 'var(--text-body)',
                              cursor: 'pointer',
                              transition: 'all 0.2s'
                            }}
                            onMouseOver={(e) => { e.target.style.backgroundColor = 'var(--pill-bg-hover)' }}
                            onMouseOut={(e) => { e.target.style.backgroundColor = 'transparent' }}
                          >
                            View Details
                          </div>
                        </div>
                      )}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={hasExtraData ? 9 : 5} className="text-center py-4 text-muted">No machines found</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        )}

        {lastPage > 1 && (
          <div className="d-flex justify-content-between align-items-center mt-4">
            <div className="text-muted small">
              Page {currentPage} of {lastPage}
            </div>
            <div className="d-flex gap-2">
              <button 
                className="btn btn-sm btn-outline-secondary" 
                disabled={currentPage === 1}
                onClick={() => setCurrentPage(p => p - 1)}
              >
                Previous
              </button>
              {Array.from({ length: lastPage }, (_, i) => i + 1).map(page => (
                <button 
                  key={page}
                  className={`btn btn-sm ${currentPage === page ? 'btn-primary' : 'btn-outline-secondary'}`}
                  onClick={() => setCurrentPage(page)}
                >
                  {page}
                </button>
              ))}
              <button 
                className="btn btn-sm btn-outline-secondary" 
                disabled={currentPage === lastPage}
                onClick={() => setCurrentPage(p => p + 1)}
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default MachinesList;
