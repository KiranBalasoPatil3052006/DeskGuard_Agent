import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { FaCodeBranch, FaSync, FaHdd, FaCube, FaShieldAlt, FaWifi, FaTools, FaServer, FaFlag, FaSearch as FaSearchIcon, FaThumbsUp, FaCheck, FaSlash, FaFilter } from 'react-icons/fa';
import { useChanges, useUpdateChangeStatus } from '../../hooks/useQueries';

const CATEGORIES = ['', 'hardware', 'software', 'security', 'network', 'peripheral', 'configuration'];
const SEVERITIES = ['', 'critical', 'important', 'warning', 'information'];
const STATUSES = ['', 'pending_review', 'investigating', 'approved', 'resolved', 'false_positive'];

function getSeverityColor(severity) {
  switch (severity) {
    case 'critical': return { border: '#EF4444', bg: '#FEF2F2', badge: 'bg-danger', text: '#DC2626' };
    case 'important': return { border: '#F97316', bg: '#FFF7ED', badge: 'bg-warning', text: '#EA580C' };
    case 'warning': return { border: '#F59E0B', bg: '#FFFBEB', badge: 'bg-warning', text: '#D97706' };
    default: return { border: '#3B82F6', bg: '#EFF6FF', badge: 'bg-info', text: '#2563EB' };
  }
}

function getCategoryIcon(category) {
  switch (category) {
    case 'hardware': return <FaHdd className="text-danger" />;
    case 'software': return <FaCube className="text-primary" />;
    case 'security': return <FaShieldAlt className="text-warning" />;
    case 'network': return <FaWifi className="text-purple" />;
    case 'peripheral': return <FaTools className="text-teal" />;
    case 'configuration': return <FaServer className="text-secondary" />;
    default: return <FaCodeBranch className="text-indigo" />;
  }
}

function getChangeTypeStyle(type) {
  if (['added', 'connected', 'enabled'].includes(type)) return 'bg-success-subtle text-success-emphasis';
  if (['removed', 'disconnected', 'disabled'].includes(type)) return 'bg-danger-subtle text-danger-emphasis';
  return 'bg-warning-subtle text-warning-emphasis';
}

function getStatusConfig(status) {
  switch (status) {
    case 'investigating': return { badge: 'bg-purple-subtle text-purple-emphasis', icon: FaSearchIcon, label: 'Investigating' };
    case 'approved': return { badge: 'bg-success-subtle text-success-emphasis', icon: FaThumbsUp, label: 'Approved' };
    case 'resolved': return { badge: 'bg-info-subtle text-info-emphasis', icon: FaCheck, label: 'Resolved' };
    case 'false_positive': return { badge: 'bg-secondary-subtle text-secondary-emphasis', icon: FaSlash, label: 'False Positive' };
    default: return { badge: 'bg-warning-subtle text-warning-emphasis', icon: FaFlag, label: 'Pending Review' };
  }
}

export default function ChangesList() {
  const navigate = useNavigate();
  const [category, setCategory] = useState('hardware');
  const [severity, setSeverity] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [selectedChange, setSelectedChange] = useState(null);

  const qp = { page, per_page: 30 };
  if (category) qp.category = category;
  if (severity) qp.severity = severity;
  if (status) qp.status = status;

  const { data, isLoading: loading } = useChanges(qp);
  const changes = data?.data || [];
  const meta = data?.meta || { total: 0 };

  const statusMutation = useUpdateChangeStatus();
  const [updatingId, setUpdatingId] = useState(null);

  const handleFilterChange = (type, value) => {
    if (type === 'category') setCategory(value);
    if (type === 'severity') setSeverity(value);
    if (type === 'status') setStatus(value);
    setPage(1);
  };

  const handleStatusUpdate = async (changeId, newStatus) => {
    setUpdatingId(changeId);
    try {
      await statusMutation.mutateAsync({ changeId, status: newStatus });
      setSelectedChange(prev => prev?.id === changeId ? { ...prev, status: newStatus } : prev);
    } catch (err) {
      alert('Failed to update status: ' + (err.message || 'Unknown error'));
    } finally {
      setUpdatingId(null);
    }
  };

  return (
    <div className="container-fluid p-0">
      <div className="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h4 className="fw-bold mb-1" style={{ color: 'var(--text-body)' }}>Hardware Change Monitor</h4>
          <p className="text-muted mb-0 small">Track unauthorized hardware changes detected by the AMC agent across all machines</p>
        </div>
        <button onClick={() => fetchChanges(page)} className="btn btn-outline-secondary btn-sm rounded-pill px-3 d-flex align-items-center gap-2">
          <FaSync /> Refresh
        </button>
      </div>

      <div className="card p-3 mb-4" style={{ borderRadius: '16px' }}>
        <div className="d-flex flex-wrap align-items-center gap-3">
          <span className="text-muted small fw-semibold d-flex align-items-center gap-1"><FaFilter /> Hardware Type:</span>
          <div className="pill-group flex-wrap">
            {['All', 'hardware', 'storage', 'memory', 'cpu', 'motherboard', 'peripheral'].map(cat => (
              <button key={cat} onClick={() => handleFilterChange('category', cat === 'All' ? '' : cat)}
                className={`pill-btn ${category === (cat === 'All' ? '' : cat) ? 'active' : ''}`}>
                {cat === 'All' ? 'All' : cat.charAt(0).toUpperCase() + cat.slice(1)}
              </button>
            ))}
          </div>
        </div>
        <div className="d-flex flex-wrap align-items-center gap-3 mt-2">
          <span className="text-muted small fw-semibold d-flex align-items-center gap-1"><FaFilter /> Severity:</span>
          <div className="pill-group flex-wrap">
            {['All', ...SEVERITIES.filter(Boolean)].map(sev => (
              <button key={sev} onClick={() => handleFilterChange('severity', sev === 'All' ? '' : sev)}
                className={`pill-btn ${severity === (sev === 'All' ? '' : sev) ? 'active' : ''}`}>
                {sev === 'All' ? 'All' : sev.charAt(0).toUpperCase() + sev.slice(1)}
              </button>
            ))}
          </div>
        </div>
        <div className="d-flex flex-wrap align-items-center gap-3 mt-2">
          <span className="text-muted small fw-semibold d-flex align-items-center gap-1"><FaFlag /> Status:</span>
          <div className="pill-group flex-wrap">
            {['All', ...STATUSES.filter(Boolean)].map(st => (
              <button key={st} onClick={() => handleFilterChange('status', st === 'All' ? '' : st)}
                className={`pill-btn ${status === (st === 'All' ? '' : st) ? 'active' : ''}`}>
                {st === 'All' ? 'All' : st.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
              </button>
            ))}
          </div>
        </div>
      </div>

      {loading ? (
        <div className="text-center py-5">
          <div className="spinner-border text-primary mb-3" role="status"><span className="visually-hidden">Loading...</span></div>
          <p className="text-muted">Loading changes...</p>
        </div>
      ) : changes.length > 0 ? (
        <>
          <div className="row g-3">
            {changes.map((c, i) => {
              const sev = getSeverityColor(c.severity);
              const statusCfg = getStatusConfig(c.status);
              const StatusIcon = statusCfg.icon;
              return (
                <div key={c.id || i} className="col-12 col-md-6 col-lg-4">
                  <div className="card h-100 p-3 cursor-pointer" style={{ borderLeft: `4px solid ${sev.border}`, borderRadius: '12px' }}
                    onClick={() => setSelectedChange(c)}>
                    <div className="d-flex align-items-start justify-content-between mb-2">
                      <div className="d-flex align-items-center gap-2">
                        <span className="fs-5">{getCategoryIcon(c.category)}</span>
                        <div>
                          <span className={`badge rounded-pill ${sev.badge} bg-opacity-10 text-dark me-1`}>{c.severity || 'info'}</span>
                          {c.machine && <small className="text-muted d-block">{c.machine.hostname || c.machine.device_name || ''}</small>}
                        </div>
                      </div>
                      <span className={`badge rounded-pill d-flex align-items-center gap-1 ${statusCfg.badge}`}>
                        <StatusIcon className="me-1" style={{ fontSize: '0.65rem' }} />{statusCfg.label}
                      </span>
                    </div>
                    <h6 className="fw-semibold mb-1 text-truncate" style={{ color: 'var(--text-body)' }}>{c.item_label || c.item_identifier || 'Change'}</h6>
                    <p className="small text-muted mb-2" style={{ WebkitLineClamp: 2, overflow: 'hidden', display: '-webkit-box', WebkitBoxOrient: 'vertical' }}>{c.description || 'No description'}</p>
                    <div className="d-flex align-items-center justify-content-between small">
                      <span className={`badge ${getChangeTypeStyle(c.change_type)}`}>{c.change_type}</span>
                      <span className="text-muted">{c.detected_at ? new Date(c.detected_at).toLocaleDateString() : '—'}</span>
                    </div>
                    {c.previous_value && c.new_value && (
                      <div className="mt-2 pt-2 small" style={{ borderTop: '1px solid var(--border-color)' }}>
                        <span className="text-muted text-decoration-line-through">{typeof c.previous_value === 'string' && c.previous_value.length > 30 ? c.previous_value.substring(0, 30) + '...' : c.previous_value}</span>
                        <span className="mx-1 text-muted">→</span>
                        <span className="fw-semibold" style={{ color: 'var(--text-body)' }}>{typeof c.new_value === 'string' && c.new_value.length > 30 ? c.new_value.substring(0, 30) + '...' : c.new_value}</span>
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>

          {meta && (
            <div className="card p-3 mt-4 d-flex flex-row align-items-center justify-content-between" style={{ borderRadius: '12px' }}>
              <small className="text-muted">Page {meta.current_page || page} of {meta.last_page || 1} ({meta.total || changes.length} total)</small>
              <div className="d-flex gap-2">
                <button disabled={page <= 1} onClick={() => setPage(p => p - 1)} className="btn btn-outline-secondary btn-sm">Previous</button>
                <button disabled={page >= (meta.last_page || 1)} onClick={() => setPage(p => p + 1)} className="btn btn-outline-secondary btn-sm">Next</button>
              </div>
            </div>
          )}
        </>
      ) : (
        <div className="card p-5 text-center" style={{ borderRadius: '16px' }}>
          <div className="mb-3" style={{ fontSize: '48px', opacity: 0.3 }}><FaCodeBranch /></div>
          <h6 className="fw-bold mb-2" style={{ color: 'var(--text-body)' }}>No Hardware Changes Detected</h6>
          <p className="text-muted mb-0 small">All monitored machines have consistent hardware configurations. Any unauthorized modifications will appear here automatically.</p>
        </div>
      )}

      {selectedChange && (
        <div className="modal fade show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }} onClick={() => setSelectedChange(null)}>
          <div className="modal-dialog modal-lg modal-dialog-centered" onClick={e => e.stopPropagation()}>
            <div className="modal-content" style={{ borderRadius: '16px', border: '1px solid var(--border-color)', background: 'var(--bg-card)' }}>
              <div className="modal-header border-0 pb-0">
                <div className="d-flex align-items-center gap-3">
                  <span className="fs-4">{getCategoryIcon(selectedChange.category)}</span>
                  <div>
                    <h5 className="fw-bold mb-1" style={{ color: 'var(--text-body)' }}>Change Details</h5>
                    <span className={`badge rounded-pill ${getSeverityColor(selectedChange.severity).badge} bg-opacity-10 text-dark`}>
                      {selectedChange.severity || 'information'}
                    </span>
                  </div>
                </div>
                <button type="button" className="btn-close" onClick={() => setSelectedChange(null)}></button>
              </div>
              <div className="modal-body">
                <h6 className="fw-semibold mb-2" style={{ color: 'var(--text-body)' }}>{selectedChange.item_label || selectedChange.item_identifier || 'Change Item'}</h6>
                <p className="text-muted small mb-4">{selectedChange.description || 'No description available.'}</p>

                <div className="row g-2 mb-4">
                  <div className="col-6">
                    <div className="p-3 rounded-3" style={{ background: 'var(--bg-table-striped)' }}>
                      <small className="text-muted d-block">Category</small>
                      <span className="fw-semibold" style={{ color: 'var(--text-body)' }}>{selectedChange.category || '—'}</span>
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="p-3 rounded-3" style={{ background: 'var(--bg-table-striped)' }}>
                      <small className="text-muted d-block">Change Type</small>
                      <span className={`badge ${getChangeTypeStyle(selectedChange.change_type)}`}>{selectedChange.change_type || '—'}</span>
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="p-3 rounded-3" style={{ background: 'var(--bg-table-striped)' }}>
                      <small className="text-muted d-block">Severity</small>
                      <span className={`badge rounded-pill ${getSeverityColor(selectedChange.severity).badge} bg-opacity-10 text-dark`}>{selectedChange.severity || 'information'}</span>
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="p-3 rounded-3" style={{ background: 'var(--bg-table-striped)' }}>
                      <small className="text-muted d-block">Detected At</small>
                      <span className="fw-semibold" style={{ color: 'var(--text-body)' }}>{selectedChange.detected_at ? new Date(selectedChange.detected_at).toLocaleString() : '—'}</span>
                    </div>
                  </div>
                  {selectedChange.status && (
                    <div className="col-12">
                      <div className="p-3 rounded-3" style={{ background: 'var(--bg-table-striped)' }}>
                        <small className="text-muted d-block">Investigation Status</small>
                        <span className={`badge rounded-pill d-inline-flex align-items-center gap-1 ${getStatusConfig(selectedChange.status).badge}`}>
                          {(() => { const S = getStatusConfig(selectedChange.status); return <><S.icon /><span className="ms-1">{S.label}</span></>; })()}
                        </span>
                      </div>
                    </div>
                  )}
                </div>

                {(selectedChange.previous_value || selectedChange.new_value) && (
                  <div className="mb-4">
                    <h6 className="fw-semibold mb-2" style={{ color: 'var(--text-body)' }}>Value Comparison</h6>
                    <div className="row g-2">
                      <div className="col-6">
                        <div className="p-3 rounded-3" style={{ background: '#FEF2F2', border: '1px solid #FECACA' }}>
                          <small className="fw-semibold text-danger mb-1 d-block">Previous State</small>
                          <small className="text-dark">{selectedChange.previous_value || '(none)'}</small>
                        </div>
                      </div>
                      <div className="col-6">
                        <div className="p-3 rounded-3" style={{ background: '#F0FDF4', border: '1px solid #BBF7D0' }}>
                          <small className="fw-semibold text-success mb-1 d-block">Current State</small>
                          <small className="text-dark">{selectedChange.new_value || '(none)'}</small>
                        </div>
                      </div>
                    </div>
                  </div>
                )}

                <div className="p-3 rounded-3 mb-3" style={{ background: '#FEF2F2', border: '1px solid #FECACA' }}>
                  <small className="fw-semibold text-danger d-block mb-1">Possible Impact</small>
                  <small className="text-danger">Unauthorized hardware changes may violate AMC service terms. Verify with the customer whether this replacement was authorized.</small>
                </div>

                <div className="p-3 rounded-3 mb-3" style={{ background: '#FFFBEB', border: '1px solid #FDE68A' }}>
                  <small className="fw-semibold text-warning-emphasis d-block mb-1">Recommended Action</small>
                  <small className="text-dark">{selectedChange.recommendation || 'Investigate the hardware change and verify with the customer if this component replacement was authorized under the AMC agreement.'}</small>
                </div>

                <div>
                  <small className="fw-semibold text-muted d-block mb-2">Actions</small>
                  <div className="d-flex flex-wrap gap-2">
                    {(!['approved', 'resolved', 'false_positive'].includes(selectedChange.status)) && (
                      <>
                        <button onClick={() => handleStatusUpdate(selectedChange.id, 'approved')} disabled={updatingId === selectedChange.id}
                          className="btn btn-success btn-sm rounded-pill px-3 d-flex align-items-center gap-1">
                          <FaThumbsUp /> Approve
                        </button>
                        <button onClick={() => handleStatusUpdate(selectedChange.id, 'investigating')} disabled={updatingId === selectedChange.id}
                          className="btn btn-warning btn-sm rounded-pill px-3 d-flex align-items-center gap-1 text-white">
                          <FaSearchIcon /> Investigate
                        </button>
                      </>
                    )}
                    {!['resolved', 'false_positive'].includes(selectedChange.status) && (
                      <button onClick={() => handleStatusUpdate(selectedChange.id, 'resolved')} disabled={updatingId === selectedChange.id}
                        className="btn btn-info btn-sm rounded-pill px-3 d-flex align-items-center gap-1 text-white">
                        <FaCheck /> Resolve
                      </button>
                    )}
                    {!['false_positive', 'resolved', 'approved'].includes(selectedChange.status) && (
                      <button onClick={() => handleStatusUpdate(selectedChange.id, 'false_positive')} disabled={updatingId === selectedChange.id}
                        className="btn btn-outline-secondary btn-sm rounded-pill px-3 d-flex align-items-center gap-1">
                        <FaSlash /> False Positive
                      </button>
                    )}
                    {updatingId === selectedChange.id && <span className="small text-muted align-self-center">Updating...</span>}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
