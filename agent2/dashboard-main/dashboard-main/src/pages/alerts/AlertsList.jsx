/**
 * Alerts List Page
 *
 * Displays all alerts with filtering by severity and status.
 * Provides acknowledge and resolve actions directly from the list.
 * Includes summary cards, search, and pagination.
 */
import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  FaSync, FaSearch, FaBell, FaExclamationCircle,
  FaExclamationTriangle, FaCheckCircle
} from 'react-icons/fa';
import { useQueryClient } from '@tanstack/react-query';
import { useAlerts, useAcknowledgeAlert, useResolveAlert } from '../../hooks/useQueries';

const AlertsList = () => {
  const queryClient = useQueryClient();
  const [selectedAlert, setSelectedAlert] = useState(null);
  const [searchFilter, setSearchFilter] = useState('');
  const [severityFilter, setSeverityFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [actionLoading, setActionLoading] = useState(null);
  const [resolveNote, setResolveNote] = useState('');
  const [showResolveModal, setShowResolveModal] = useState(null);

  useEffect(() => {
    setCurrentPage(1);
  }, [searchFilter, severityFilter, statusFilter]);

  const params = { page: currentPage, per_page: 10 };
  if (severityFilter) params.severity = severityFilter;
  if (statusFilter) params.status = statusFilter;
  if (searchFilter) params.search = searchFilter;

  const { data: alertsData, isLoading: loading, isFetching: isRefreshing } = useAlerts(params);
  const alerts = alertsData?.data || [];
  const meta = alertsData?.meta || {};
  const lastPage = meta?.last_page || 1;
  const summary = {
    total: meta?.total || alerts.length,
    critical: meta?.critical_count || alerts.filter(a => a.severity === 'critical').length,
    warning: meta?.warning_count || alerts.filter(a => a.severity === 'warning').length,
    resolved: meta?.resolved_count || alerts.filter(a => a.status === 'resolved').length,
  };

  const acknowledgeMutation = useAcknowledgeAlert();
  const resolveMutation = useResolveAlert();

  const handleAcknowledge = async (alertId) => {
    setActionLoading(alertId);
    try {
      await acknowledgeMutation.mutateAsync(alertId);
      setSelectedAlert(prev => prev?.id === alertId ? { ...prev, status: 'acknowledged' } : prev);
    } catch (err) {
      console.error('Failed to acknowledge alert:', err);
    } finally {
      setActionLoading(null);
    }
  };

  const handleResolve = async (alertId) => {
    setActionLoading(alertId);
    try {
      await resolveMutation.mutateAsync({ id: alertId, note: resolveNote });
      setSelectedAlert(prev => prev?.id === alertId ? { ...prev, status: 'resolved' } : prev);
      setShowResolveModal(null);
      setResolveNote('');
    } catch (err) {
      console.error('Failed to resolve alert:', err);
    } finally {
      setActionLoading(null);
    }
  };

  const getSeverityBadge = (severity) => {
    const colors = { critical: 'bg-danger', warning: 'bg-warning text-dark', info: 'bg-info' };
    return <span className={`badge ${colors[severity] || 'bg-secondary'}`}>{severity}</span>;
  };

  const getStatusBadge = (status) => {
    const colors = { open: 'bg-danger', acknowledged: 'bg-warning text-dark', resolved: 'bg-success' };
    return <span className={`badge ${colors[status] || 'bg-secondary'}`}>{status}</span>;
  };

  return (
    <div className="container-fluid p-0">
      {/* Page Header */}
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h3 className="fw-bold mb-0" style={{ color: 'var(--text-body)' }}>Alerts</h3>
        <button className="btn btn-outline-primary btn-sm" onClick={() => queryClient.invalidateQueries({ queryKey: ['alerts'] })} disabled={isRefreshing}>
          <FaSync className={isRefreshing ? 'spin-animation' : ''} /> Refresh
        </button>
      </div>

      {/* Summary Cards */}
      <div className="row g-3 mb-4">
        {[
          { label: 'Total Alerts', value: summary.total, icon: <FaBell />, color: 'var(--primary-color)' },
          { label: 'Critical', value: summary.critical, icon: <FaExclamationCircle />, color: '#EF4444' },
          { label: 'Warning', value: summary.warning, icon: <FaExclamationTriangle />, color: '#F59E0B' },
          { label: 'Resolved', value: summary.resolved, icon: <FaCheckCircle />, color: '#22C55E' },
        ].map((card, idx) => (
          <div className="col-6 col-lg-3" key={idx}>
            <div className="card p-3 border-0 h-100" style={{ borderRadius: '16px' }}>
              <div className="d-flex align-items-center gap-3">
                <div className="p-2 rounded-3" style={{ backgroundColor: `${card.color}20`, color: card.color, fontSize: '20px' }}>
                  {card.icon}
                </div>
                <div>
                  <div className="text-muted small">{card.label}</div>
                  <div className="fw-bold fs-4">{card.value}</div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Filters */}
      <div className="card p-3 mb-4" style={{ borderRadius: '16px' }}>
        <div className="row g-3 align-items-center">
          <div className="col-12 col-md-4">
            <div className="input-group">
              <span className="input-group-text bg-transparent border-end-0"><FaSearch className="text-muted" /></span>
              <input
                type="text"
                className="form-control border-start-0"
                placeholder="Search alerts..."
                value={searchFilter}
                onChange={e => setSearchFilter(e.target.value)}
                style={{ backgroundColor: 'var(--bg-input)' }}
              />
            </div>
          </div>
          <div className="col-6 col-md-3">
            <select
              className="form-select"
              value={severityFilter}
              onChange={e => setSeverityFilter(e.target.value)}
              style={{ backgroundColor: 'var(--bg-input)' }}
            >
              <option value="">All Severity</option>
              <option value="critical">Critical</option>
              <option value="warning">Warning</option>
              <option value="info">Info</option>
            </select>
          </div>
          <div className="col-6 col-md-3">
            <select
              className="form-select"
              value={statusFilter}
              onChange={e => setStatusFilter(e.target.value)}
              style={{ backgroundColor: 'var(--bg-input)' }}
            >
              <option value="">All Status</option>
              <option value="open">Open</option>
              <option value="acknowledged">Acknowledged</option>
              <option value="resolved">Resolved</option>
            </select>
          </div>
        </div>
      </div>

      {/* Alerts Table */}
      <div className="card" style={{ borderRadius: '16px' }}>
        {loading ? (
          <div className="text-center py-5">
            <div className="spinner-border text-primary" role="status" />
          </div>
        ) : alerts.length === 0 ? (
          <div className="text-center py-5">
            <FaCheckCircle className="text-success mb-3" style={{ fontSize: '48px' }} />
            <h6 className="fw-bold mb-1" style={{ color: 'var(--text-body)' }}>All Clear</h6>
            <p className="text-muted mb-0">No alerts match the current filters</p>
          </div>
        ) : (
          <div className="table-responsive">
            <table className="table table-borderless table-hover align-middle mb-0">
              <thead>
                <tr className="text-muted small">
                  <th className="ps-4">Severity</th>
                  <th>Alert</th>
                  <th>Machine</th>
                  <th>Status</th>
                  <th>Time</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {alerts.map(alert => (
                  <tr
                    key={alert.id}
                    onClick={() => setSelectedAlert(selectedAlert?.id === alert.id ? null : alert)}
                    style={{ cursor: 'pointer' }}
                    className={selectedAlert?.id === alert.id ? 'table-active' : ''}
                  >
                    <td className="ps-4">{getSeverityBadge(alert.severity)}</td>
                    <td>
                      <div className="fw-semibold">{alert.title}</div>
                      <div className="text-muted small text-truncate" style={{ maxWidth: '250px' }}>
                        {alert.description}
                      </div>
                    </td>
                    <td>
                      {alert.machine ? (
                        <Link
                          to={`/machines/${alert.machine_id || alert.machine?.id}`}
                          className="text-decoration-none fw-semibold"
                          onClick={e => e.stopPropagation()}
                        >
                          {alert.machine?.device_name || alert.machine?.hostname || 'View Machine'}
                        </Link>
                      ) : (
                        <span className="text-muted">—</span>
                      )}
                    </td>
                    <td>{getStatusBadge(alert.status)}</td>
                    <td className="text-muted small">
                      {alert.created_at ? new Date(alert.created_at).toLocaleString() : '—'}
                    </td>
                    <td onClick={e => e.stopPropagation()}>
                      {alert.status === 'open' && (
                        <button
                          className="btn btn-sm btn-outline-warning me-1"
                          onClick={() => handleAcknowledge(alert.id)}
                          disabled={actionLoading === alert.id}
                        >
                          {actionLoading === alert.id ? '...' : 'Acknowledge'}
                        </button>
                      )}
                      {alert.status !== 'resolved' && (
                        <button
                          className="btn btn-sm btn-outline-success"
                          onClick={() => { setShowResolveModal(alert.id); setResolveNote(''); }}
                          disabled={actionLoading === alert.id}
                        >
                          Resolve
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {lastPage > 1 && (
          <div className="d-flex justify-content-center gap-2 p-3">
            <button
              className="btn btn-sm btn-outline-primary"
              disabled={currentPage === 1}
              onClick={() => setCurrentPage(p => p - 1)}
            >
              Previous
            </button>
            <span className="d-flex align-items-center text-muted small">
              Page {currentPage} of {lastPage}
            </span>
            <button
              className="btn btn-sm btn-outline-primary"
              disabled={currentPage === lastPage}
              onClick={() => setCurrentPage(p => p + 1)}
            >
              Next
            </button>
          </div>
        )}
      </div>

      {/* Resolve Modal */}
      {showResolveModal && (
        <div
          className="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
          style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }}
          onClick={() => setShowResolveModal(null)}
        >
          <div
            className="card p-4"
            style={{ width: '440px', borderRadius: '16px' }}
            onClick={e => e.stopPropagation()}
          >
            <h5 className="fw-bold mb-3" style={{ color: 'var(--text-body)' }}>Resolve Alert</h5>
            <textarea
              className="form-control mb-3"
              rows={3}
              placeholder="Resolution note (optional)"
              value={resolveNote}
              onChange={e => setResolveNote(e.target.value)}
              style={{ backgroundColor: 'var(--bg-input)' }}
            />
            <div className="d-flex justify-content-end gap-2">
              <button className="btn btn-secondary" onClick={() => setShowResolveModal(null)}>Cancel</button>
              <button
                className="btn btn-success"
                onClick={() => handleResolve(showResolveModal)}
                disabled={actionLoading === showResolveModal}
              >
                {actionLoading === showResolveModal ? 'Resolving...' : 'Resolve'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Detail Panel */}
      {selectedAlert && (
        <div className="card p-4 mt-4" style={{ borderRadius: '16px' }}>
          <div className="d-flex justify-content-between align-items-center mb-3">
            <h5 className="fw-bold mb-0" style={{ color: 'var(--text-body)' }}>Alert Details</h5>
            <button className="btn btn-sm btn-outline-secondary" onClick={() => setSelectedAlert(null)}>✕</button>
          </div>
          <div className="row g-3">
            <div className="col-12 col-md-6">
              <div className="text-muted small">Title</div>
              <div className="fw-semibold">{selectedAlert.title}</div>
            </div>
            <div className="col-12 col-md-6">
              <div className="text-muted small">Severity</div>
              {getSeverityBadge(selectedAlert.severity)}
            </div>
            <div className="col-12">
              <div className="text-muted small">Description</div>
              <div>{selectedAlert.description || '—'}</div>
            </div>
            <div className="col-12 col-md-4">
              <div className="text-muted small">Status</div>
              {getStatusBadge(selectedAlert.status)}
            </div>
            <div className="col-12 col-md-4">
              <div className="text-muted small">Created</div>
              <div className="fw-semibold">{selectedAlert.created_at ? new Date(selectedAlert.created_at).toLocaleString() : '—'}</div>
            </div>
            <div className="col-12 col-md-4">
              <div className="text-muted small">Machine</div>
              {selectedAlert.machine ? (
                <Link to={`/machines/${selectedAlert.machine_id || selectedAlert.machine?.id}`} className="fw-semibold text-decoration-none">
                  {selectedAlert.machine?.device_name || selectedAlert.machine?.hostname || 'View Machine'}
                </Link>
              ) : <span>—</span>}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default AlertsList;
