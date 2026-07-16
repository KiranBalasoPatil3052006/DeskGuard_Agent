import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { FaServer, FaCheckCircle, FaTimesCircle, FaExclamationTriangle, FaSearch, FaEye, FaChevronLeft, FaChevronRight, FaLaptop } from 'react-icons/fa';
import { getAgents, getAgentSummary } from '../../services/agents';

const statusColors = {
  online: { bg: '#d4edda', text: '#155724', dot: '#28a745' },
  offline: { bg: '#f8d7da', text: '#721c24', dot: '#dc3545' },
  uninstalled: { bg: '#fff3cd', text: '#856404', dot: '#ffc107' },
  pending: { bg: '#e2e3e5', text: '#383d41', dot: '#6c757d' },
};

const AgentsList = () => {
  const [agents, setAgents] = useState([]);
  const [summary, setSummary] = useState({ total: 0, online_count: 0, offline_count: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const perPage = 10;

  const fetchAgents = async () => {
    setLoading(true);
    try {
      const params = { page, per_page: perPage };
      if (search) params.search = search;
      if (statusFilter !== 'all') params.status = statusFilter;
      const res = await getAgents(params);
      const d = res.data || res;
      if (Array.isArray(d)) {
        setAgents(d);
        setTotalPages(res.last_page || res.lastPage || 1);
      } else {
        setAgents(d.data || []);
        setTotalPages(d.last_page || d.lastPage || 1);
      }

      const summaryRes = await getAgentSummary();
      const s = summaryRes.data || summaryRes;
      setSummary({
        total: s.total_machines || s.total || 0,
        online_count: s.online_count || 0,
        offline_count: s.offline_count || 0,
      });
    } catch (err) {
      setAgents([]);
      setTotalPages(1);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchAgents(); }, [page, statusFilter]);

  useEffect(() => {
    const timer = setTimeout(() => { setPage(1); fetchAgents(); }, 300);
    return () => clearTimeout(timer);
  }, [search]);

  const getStatusInfo = (agent) => {
    if (!agent.is_active) return 'uninstalled';
    if (agent.is_online) return 'online';
    return 'offline';
  };

  const formatLastSeen = (date) => {
    if (!date) return 'Never';
    const d = new Date(date);
    const now = new Date();
    const diff = now - d;
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h ago`;
    return d.toLocaleDateString();
  };

  return (
    <div className="container-fluid px-4 py-3" style={{ backgroundColor: 'var(--bg-body, #f8f9fc)', minHeight: '100vh' }}>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 className="fw-bold mb-0" style={{ color: 'var(--text-body, #2d3748)' }}>
            <FaLaptop className="me-2" style={{ color: 'var(--primary-blue, #4e73df)' }} />
            Agent Management
          </h4>
          <p className="text-muted small mb-0">Monitor and track all installed DeskGuard agents</p>
        </div>
      </div>

      <div className="row g-3 mb-4">
        <div className="col-md-4">
          <div className="card border-0 shadow-sm h-100" style={{ borderRadius: '12px', background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)' }}>
            <div className="card-body text-white d-flex align-items-center">
              <FaServer className="me-3" size={32} />
              <div>
                <h6 className="mb-0 fw-bold">Total Agents</h6>
                <h3 className="mb-0 fw-bold">{summary.total}</h3>
              </div>
            </div>
          </div>
        </div>
        <div className="col-md-4">
          <div className="card border-0 shadow-sm h-100" style={{ borderRadius: '12px', background: 'linear-gradient(135deg, #1cc88a 0%, #13855c 100%)' }}>
            <div className="card-body text-white d-flex align-items-center">
              <FaCheckCircle className="me-3" size={32} />
              <div>
                <h6 className="mb-0 fw-bold">Online</h6>
                <h3 className="mb-0 fw-bold">{summary.online_count}</h3>
              </div>
            </div>
          </div>
        </div>
        <div className="col-md-4">
          <div className="card border-0 shadow-sm h-100" style={{ borderRadius: '12px', background: 'linear-gradient(135deg, #e74a3b 0%, #be2617 100%)' }}>
            <div className="card-body text-white d-flex align-items-center">
              <FaExclamationTriangle className="me-3" size={32} />
              <div>
                <h6 className="mb-0 fw-bold">Offline</h6>
                <h3 className="mb-0 fw-bold">{summary.offline_count}</h3>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="card border-0 shadow-sm" style={{ borderRadius: '12px' }}>
        <div className="card-body">
          <div className="d-flex justify-content-between align-items-center mb-3">
            <div className="d-flex gap-2">
              {['all', 'online', 'offline', 'uninstalled'].map(status => (
                <button
                  key={status}
                  className={`btn btn-sm ${statusFilter === status ? 'btn-primary' : 'btn-outline-secondary'}`}
                  onClick={() => { setStatusFilter(status); setPage(1); }}
                  style={{ borderRadius: '20px', fontSize: '0.8rem' }}
                >
                  {status.charAt(0).toUpperCase() + status.slice(1)}
                </button>
              ))}
            </div>
            <div className="input-group input-group-sm" style={{ maxWidth: '250px' }}>
              <span className="input-group-text bg-white border-end-0"><FaSearch size={12} /></span>
              <input
                type="text"
                className="form-control border-start-0"
                placeholder="Search agents..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </div>
          </div>

          {loading ? (
            <div className="text-center py-5">
              <div className="spinner-border" style={{ color: 'var(--primary-blue, #4e73df)' }} role="status" />
            </div>
          ) : agents.length === 0 ? (
            <div className="text-center py-5 text-muted">
              <FaServer size={48} className="mb-3 opacity-50" />
              <p className="mb-0">No agents found</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-hover align-middle mb-0" style={{ fontSize: '0.9rem' }}>
                <thead className="table-light">
                  <tr>
                    <th>Status</th>
                    <th>Hostname / Name</th>
                    <th>Machine UID</th>
                    <th>OS</th>
                    <th>Last Seen</th>
                    <th>User</th>
                    <th style={{ width: '80px' }}>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {agents.map(agent => {
                    const status = getStatusInfo(agent);
                    const colors = statusColors[status];
                    return (
                      <tr key={agent.id}>
                        <td>
                          <span className="badge d-inline-flex align-items-center gap-1 px-3 py-2"
                            style={{ backgroundColor: colors.bg, color: colors.text, borderRadius: '20px', fontSize: '0.75rem' }}>
                            <span style={{ width: 8, height: 8, borderRadius: '50%', backgroundColor: colors.dot, display: 'inline-block' }} />
                            {status.charAt(0).toUpperCase() + status.slice(1)}
                          </span>
                        </td>
                        <td className="fw-semibold">{agent.hostname || agent.device_name || agent.machine_uid?.substring(0, 16)}</td>
                        <td><code style={{ fontSize: '0.75rem' }}>{agent.machine_uid?.substring(0, 20)}...</code></td>
                        <td className="small text-muted">{agent.operating_system || '-'}</td>
                        <td className="small">{formatLastSeen(agent.last_heartbeat_at)}</td>
                        <td className="small">{agent.assigned_user?.name || agent.employee_mobile_number || '-'}</td>
                        <td>
                          <Link
                            to={`/agents/${agent.id}`}
                            className="btn btn-sm btn-outline-primary"
                            style={{ borderRadius: '8px' }}
                            title="View details"
                          >
                            <FaEye />
                          </Link>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}

          {totalPages > 1 && (
            <div className="d-flex justify-content-between align-items-center mt-3">
              <small className="text-muted">Page {page} of {totalPages}</small>
              <div className="btn-group btn-group-sm">
                <button className="btn btn-outline-secondary" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>
                  <FaChevronLeft />
                </button>
                <button className="btn btn-outline-secondary" disabled={page >= totalPages} onClick={() => setPage(p => p + 1)}>
                  <FaChevronRight />
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AgentsList;
