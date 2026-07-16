import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { FaArrowLeft, FaServer, FaCheckCircle, FaTimesCircle, FaExclamationTriangle, FaClock, FaUser, FaLaptop, FaHdd, FaMicrochip, FaMemory, FaShieldAlt, FaInfoCircle, FaBell, FaThermometerHalf } from 'react-icons/fa';
import { getAgentDetail, getAgentAlerts } from '../../services/agents';

const AgentDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [agent, setAgent] = useState(null);
  const [alerts, setAlerts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      try {
        const [agentRes, alertsRes] = await Promise.all([
          getAgentDetail(id),
          getAgentAlerts(id)
        ]);
        setAgent(agentRes.data || agentRes);
        setAlerts(alertsRes.data || []);
      } catch (err) {
        navigate('/agents');
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [id]);

  const getStatusBadge = (agent) => {
    if (!agent?.is_active) return { label: 'Uninstalled', color: '#856404', bg: '#fff3cd' };
    if (agent?.is_online) return { label: 'Online', color: '#155724', bg: '#d4edda' };
    return { label: 'Offline', color: '#721c24', bg: '#f8d7da' };
  };

  const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString();
  };

  if (loading) {
    return (
      <div className="container-fluid px-4 py-3 text-center py-5">
        <div className="spinner-border" style={{ color: 'var(--primary-blue, #4e73df)' }} role="status" />
      </div>
    );
  }

  if (!agent) return null;

  const status = getStatusBadge(agent);

  return (
    <div className="container-fluid px-4 py-3" style={{ backgroundColor: 'var(--bg-body, #f8f9fc)', minHeight: '100vh' }}>
      <div className="d-flex align-items-center gap-3 mb-4">
        <Link to="/agents" className="btn btn-sm btn-outline-secondary" style={{ borderRadius: '8px' }}>
          <FaArrowLeft />
        </Link>
        <div>
          <h4 className="fw-bold mb-0" style={{ color: 'var(--text-body, #2d3748)' }}>
            <FaLaptop className="me-2" style={{ color: 'var(--primary-blue, #4e73df)' }} />
            {agent.hostname || agent.device_name || 'Agent'}
          </h4>
          <p className="text-muted small mb-0">Agent Details & Status</p>
        </div>
        <span className="badge d-inline-flex align-items-center gap-1 px-3 py-2 ms-auto"
          style={{ backgroundColor: status.bg, color: status.color, borderRadius: '20px', fontSize: '0.8rem' }}>
          <span style={{ width: 8, height: 8, borderRadius: '50%', backgroundColor: status.color, display: 'inline-block' }} />
          {status.label}
        </span>
      </div>

      <div className="row g-4">
        <div className="col-md-8">
          <div className="card border-0 shadow-sm mb-4" style={{ borderRadius: '12px' }}>
            <div className="card-header bg-transparent border-bottom-0 pt-3 pb-0 px-4">
              <h6 className="fw-bold mb-0" style={{ color: 'var(--text-body, #2d3748)' }}>
                <FaInfoCircle className="me-2" style={{ color: 'var(--primary-blue, #4e73df)' }} />
                Machine Information
              </h6>
            </div>
            <div className="card-body px-4">
              <div className="row g-3">
                <div className="col-md-6">
                  <div className="d-flex align-items-center gap-2 mb-2">
                    <FaLaptop className="text-muted" size={14} />
                    <small className="text-muted">Hostname</small>
                  </div>
                  <p className="fw-semibold mb-0">{agent.hostname || '-'}</p>
                </div>
                <div className="col-md-6">
                  <div className="d-flex align-items-center gap-2 mb-2">
                    <FaServer className="text-muted" size={14} />
                    <small className="text-muted">Machine UID</small>
                  </div>
                  <code style={{ fontSize: '0.75rem', wordBreak: 'break-all' }}>{agent.machine_uid || '-'}</code>
                </div>
                <div className="col-md-6">
                  <div className="d-flex align-items-center gap-2 mb-2">
                    <FaDesktop className="text-muted" size={14} />
                    <small className="text-muted">Operating System</small>
                  </div>
                  <p className="fw-semibold mb-0">{agent.operating_system || '-'}</p>
                </div>
                <div className="col-md-6">
                  <div className="d-flex align-items-center gap-2 mb-2">
                    <FaUser className="text-muted" size={14} />
                    <small className="text-muted">Assigned User</small>
                  </div>
                  <p className="fw-semibold mb-0">{agent.assigned_user?.name || agent.employee_mobile_number || '-'}</p>
                </div>
                <div className="col-md-6">
                  <div className="d-flex align-items-center gap-2 mb-2">
                    <FaClock className="text-muted" size={14} />
                    <small className="text-muted">Last Heartbeat</small>
                  </div>
                  <p className="fw-semibold mb-0">{formatDate(agent.last_heartbeat_at)}</p>
                </div>
                <div className="col-md-6">
                  <div className="d-flex align-items-center gap-2 mb-2">
                    <FaClock className="text-muted" size={14} />
                    <small className="text-muted">Installed At</small>
                  </div>
                  <p className="fw-semibold mb-0">{formatDate(agent.created_at)}</p>
                </div>
              </div>
            </div>
          </div>

          {agent.current_status && (
            <div className="card border-0 shadow-sm mb-4" style={{ borderRadius: '12px' }}>
              <div className="card-header bg-transparent border-bottom-0 pt-3 pb-0 px-4">
                <h6 className="fw-bold mb-0" style={{ color: 'var(--text-body, #2d3748)' }}>
                  <FaMicrochip className="me-2" style={{ color: 'var(--primary-blue, #4e73df)' }} />
                  Current Status
                </h6>
              </div>
              <div className="card-body px-4">
                <div className="row g-3">
                  <div className="col-6 col-md-3 text-center">
                    <FaMicrochip size={24} className="text-primary mb-1" />
                    <h5 className="fw-bold mb-0">{agent.current_status.cpu_percentage ?? '-'}%</h5>
                    <small className="text-muted">CPU</small>
                  </div>
                  <div className="col-6 col-md-3 text-center">
                    <FaMemory size={24} className="text-success mb-1" />
                    <h5 className="fw-bold mb-0">{agent.current_status.ram_percentage ?? '-'}%</h5>
                    <small className="text-muted">RAM</small>
                  </div>
                  <div className="col-6 col-md-3 text-center">
                    <FaHdd size={24} className="text-warning mb-1" />
                    <h5 className="fw-bold mb-0">{agent.current_status.disk_percentage ?? '-'}%</h5>
                    <small className="text-muted">Disk</small>
                  </div>
                  <div className="col-6 col-md-3 text-center">
                    <FaThermometerHalf size={24} className="text-danger mb-1" />
                    <h5 className="fw-bold mb-0">{agent.current_status.cpu_temperature ?? '-'}°C</h5>
                    <small className="text-muted">CPU Temp</small>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        <div className="col-md-4">
          <div className="card border-0 shadow-sm mb-4" style={{ borderRadius: '12px' }}>
            <div className="card-header bg-transparent border-bottom-0 pt-3 pb-0 px-4">
              <h6 className="fw-bold mb-0" style={{ color: 'var(--text-body, #2d3748)' }}>
                <FaBell className="me-2" style={{ color: 'var(--primary-blue, #4e73df)' }} />
                Recent Alerts
              </h6>
            </div>
            <div className="card-body px-4">
              {alerts.length === 0 ? (
                <p className="text-muted small mb-0 text-center py-3">No alerts for this agent</p>
              ) : (
                alerts.slice(0, 10).map(alert => (
                  <div key={alert.id} className="d-flex align-items-start gap-2 mb-2 pb-2 border-bottom" style={{ fontSize: '0.8rem' }}>
                    <FaExclamationTriangle className="mt-1 flex-shrink-0"
                      style={{ color: alert.severity === 'critical' ? '#dc3545' : alert.severity === 'high' ? '#fd7e14' : '#ffc107' }}
                    />
                    <div>
                      <p className="mb-0 fw-semibold">{alert.title}</p>
                      <small className="text-muted">{formatDate(alert.created_at)}</small>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>

          <div className="card border-0 shadow-sm" style={{ borderRadius: '12px' }}>
            <div className="card-header bg-transparent border-bottom-0 pt-3 pb-0 px-4">
              <h6 className="fw-bold mb-0" style={{ color: 'var(--text-body, #2d3748)' }}>
                <FaShieldAlt className="me-2" style={{ color: 'var(--primary-blue, #4e73df)' }} />
                Agent Timeline
              </h6>
            </div>
            <div className="card-body px-4">
              <div className="timeline">
                {agent.activated_at && (
                  <div className="d-flex gap-2 mb-2 pb-2 border-bottom" style={{ fontSize: '0.8rem' }}>
                    <FaCheckCircle className="text-success mt-1 flex-shrink-0" />
                    <div>
                      <p className="mb-0 fw-semibold">Activated</p>
                      <small className="text-muted">{formatDate(agent.activated_at)}</small>
                    </div>
                  </div>
                )}
                <div className="d-flex gap-2 mb-2 pb-2 border-bottom" style={{ fontSize: '0.8rem' }}>
                  <FaInfoCircle className="text-info mt-1 flex-shrink-0" />
                  <div>
                    <p className="mb-0 fw-semibold">Registered</p>
                    <small className="text-muted">{formatDate(agent.created_at)}</small>
                  </div>
                </div>
                {agent.last_heartbeat_at && (
                  <div className="d-flex gap-2" style={{ fontSize: '0.8rem' }}>
                    <FaClock className="text-secondary mt-1 flex-shrink-0" />
                    <div>
                      <p className="mb-0 fw-semibold">Last Seen</p>
                      <small className="text-muted">{formatDate(agent.last_heartbeat_at)}</small>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AgentDetails;
