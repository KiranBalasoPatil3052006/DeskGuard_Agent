import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import {
  FaFileAlt,
  FaCalendarDay,
  FaCalendarWeek,
  FaCalendarAlt,
  FaSearch,
  FaFilePdf,
  FaFileExcel,
  FaFileCsv,
  FaEye,
  FaTrash,
  FaDownload,
  FaTimes
} from 'react-icons/fa';
import { getReports, generateReport, downloadReport, deleteReport } from '../../services/reports';

const TYPE_OPTIONS = ['health', 'inventory', 'security', 'custom'];
const FORMAT_OPTIONS = ['pdf', 'excel', 'csv'];

const ReportsList = () => {
  const [reports, setReports] = useState([]);
  const [summary, setSummary] = useState({ total: 0, health: 0, inventory: 0, security: 0, custom: 0, pdf: 0, excel: 0, csv: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);

  const [typeFilter, setTypeFilter] = useState('');
  const [formatFilter, setFormatFilter] = useState('');
  const [searchFilter, setSearchFilter] = useState('');

  const [showGenerateModal, setShowGenerateModal] = useState(false);
  const [genType, setGenType] = useState('health');
  const [genFormat, setGenFormat] = useState('pdf');
  const [genFilters, setGenFilters] = useState('');
  const [generating, setGenerating] = useState(false);

  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const [downloading, setDownloading] = useState(null);

  useEffect(() => {
    setCurrentPage(1);
  }, [typeFilter, formatFilter, searchFilter]);

  const fetchReports = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = { page: currentPage, per_page: 10 };
      if (typeFilter) params.type = typeFilter;
      if (formatFilter) params.format = formatFilter;
      if (searchFilter) params.search = searchFilter;
      const res = await getReports(params);
      const d = res.data;
      const list = d.data || d || [];
      setReports(list);
      setLastPage(d.last_page || 1);
      const total = d.total || list.length;
      const byType = { health: 0, inventory: 0, security: 0, custom: 0 };
      const byFormat = { pdf: 0, excel: 0, csv: 0 };
      (d.data || d || []).forEach(r => {
        if (byType[r.type] !== undefined) byType[r.type]++;
        if (byFormat[r.format] !== undefined) byFormat[r.format]++;
      });
      setSummary({ total, ...byType, ...byFormat });
    } catch (err) {
      console.error('Failed to load reports:', err);
      setError('Failed to load reports. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [currentPage, typeFilter, formatFilter, searchFilter]);

  useEffect(() => {
    fetchReports();
  }, [fetchReports]);

  const handleGenerate = async () => {
    setGenerating(true);
    try {
      let filters = null;
      if (genFilters.trim()) {
        try { filters = JSON.parse(genFilters); } catch { filters = genFilters.trim(); }
      }
      await generateReport({ type: genType, format: genFormat, filters });
      setShowGenerateModal(false);
      setGenFilters('');
      setCurrentPage(1);
      await fetchReports();
    } catch (err) {
      console.error('Failed to generate report:', err);
    } finally {
      setGenerating(false);
    }
  };

  const handleDownload = async (id) => {
    setDownloading(id);
    try {
      const res = await downloadReport(id);
      const blob = res.data instanceof Blob ? res.data : new Blob([res.data]);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `report-${id}.${formatFilter || 'pdf'}`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      console.error('Failed to download report:', err);
    } finally {
      setDownloading(null);
    }
  };

  const handleDelete = async (id) => {
    setDeleting(true);
    try {
      await deleteReport(id);
      setDeleteConfirm(null);
      setReports(prev => prev.filter(r => r.id !== id));
    } catch (err) {
      console.error('Failed to delete report:', err);
    } finally {
      setDeleting(false);
    }
  };

  const summaryCards = [
    { title: 'Total Reports', value: summary.total, icon: <FaFileAlt />, color: 'primary', bg: 'e7f1ff' },
    { title: 'Health Reports', value: summary.health, icon: <FaCalendarDay />, color: 'success', bg: 'd1e7dd' },
    { title: 'Inventory Reports', value: summary.inventory, icon: <FaCalendarWeek />, color: 'warning', bg: 'fff3cd' },
    { title: 'Security Reports', value: summary.security, icon: <FaCalendarAlt />, color: 'danger', bg: 'f8d7da' },
  ];

  const formatIcon = (fmt) => {
    switch (fmt) {
      case 'pdf': return <FaFilePdf className="text-danger" />;
      case 'excel': return <FaFileExcel className="text-success" />;
      case 'csv': return <FaFileCsv className="text-primary" />;
      default: return <FaFileAlt className="text-secondary" />;
    }
  };

  const typeBadge = (type) => {
    const colors = {
      health: 'bg-success bg-opacity-10 text-success border-success',
      inventory: 'bg-warning bg-opacity-10 text-warning border-warning',
      security: 'bg-danger bg-opacity-10 text-danger border-danger',
      custom: 'bg-info bg-opacity-10 text-info border-info',
    };
    return <span className={`badge ${colors[type] || 'bg-secondary bg-opacity-10 text-secondary border-secondary'} border`}>{type}</span>;
  };

  return (
    <div className="container-fluid p-0">
      {/* Page Header */}
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
          <h3 className="text-dark-blue fw-bold mb-1">System Reports</h3>
          <nav aria-label="breadcrumb">
            <ol className="breadcrumb mb-0 small">
              <li className="breadcrumb-item"><Link to="/dashboard" className="text-decoration-none">Home</Link></li>
              <li className="breadcrumb-item active" aria-current="page">Reports</li>
            </ol>
          </nav>
        </div>
        <div>
          <button className="btn btn-primary d-flex align-items-center" onClick={() => setShowGenerateModal(true)}>
            <FaFileAlt className="me-2" /> Generate New Report
          </button>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="row g-4 mb-4">
        {summaryCards.map((card, idx) => (
          <div className="col-12 col-sm-6 col-xl-3" key={idx}>
            <div className="card border-0 glass-card h-100">
              <div className="card-body d-flex align-items-center">
                <div
                  className={`text-${card.color} me-3 d-flex align-items-center justify-content-center rounded`}
                  style={{ width: '48px', height: '48px', backgroundColor: `#${card.bg}`, fontSize: '1.25rem' }}
                >
                  {card.icon}
                </div>
                <div>
                  <h6 className="text-muted mb-0 small">{card.title}</h6>
                  <h4 className="mb-0 fw-bold text-dark-blue">{card.value}</h4>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card glass-card border-0 mb-4">
        {/* Filters */}
        <div className="card-body border-bottom border-light bg-transparent">
          <div className="row g-3">
            <div className="col-12 col-lg-4">
              <label className="form-label small text-muted mb-1">Report Type</label>
              <select className="form-select" value={typeFilter} onChange={e => setTypeFilter(e.target.value)}>
                <option value="">All Types</option>
                {TYPE_OPTIONS.map(t => (
                  <option key={t} value={t}>{t.charAt(0).toUpperCase() + t.slice(1)}</option>
                ))}
              </select>
            </div>
            <div className="col-12 col-lg-4">
              <label className="form-label small text-muted mb-1">Format</label>
              <select className="form-select" value={formatFilter} onChange={e => setFormatFilter(e.target.value)}>
                <option value="">All Formats</option>
                {FORMAT_OPTIONS.map(f => (
                  <option key={f} value={f}>{f.toUpperCase()}</option>
                ))}
              </select>
            </div>
            <div className="col-12 col-lg-4">
              <label className="form-label small text-muted mb-1">Generated By</label>
              <div className="input-group">
                <span className="input-group-text bg-white"><FaSearch className="text-muted" /></span>
                <input
                  type="text"
                  className="form-control border-start-0"
                  placeholder="Search by user..."
                  value={searchFilter}
                  onChange={e => setSearchFilter(e.target.value)}
                />
              </div>
            </div>
          </div>
        </div>

        {/* Reports Table */}
        <div className="card-body p-0">
          {loading ? (
            <div className="text-center py-5">
              <div className="spinner-border text-primary" role="status" />
            </div>
          ) : error ? (
            <div className="text-center py-5">
              <h6 className="text-danger mb-1">Error Loading Reports</h6>
              <p className="text-muted mb-0">{error}</p>
              <button className="btn btn-outline-primary btn-sm mt-3" onClick={fetchReports}>Retry</button>
            </div>
          ) : reports.length === 0 ? (
            <div className="text-center py-5">
              <FaFileAlt className="text-muted mb-3" style={{ fontSize: '48px', opacity: 0.4 }} />
              <h6 className="fw-bold mb-1 text-dark-blue">No Reports Found</h6>
              <p className="text-muted mb-0">No reports match the current filters.</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-hover align-middle mb-0">
                <thead className="table-light text-muted" style={{ fontSize: '0.85rem' }}>
                  <tr>
                    <th className="ps-4">Report ID</th>
                    <th>Type</th>
                    <th>Format</th>
                    <th>Generated By</th>
                    <th>Generated At</th>
                    <th className="pe-4 text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {reports.map((report) => (
                    <tr key={report.id} style={{ fontSize: '0.9rem' }}>
                      <td className="ps-4 text-muted font-monospace">#{report.id}</td>
                      <td>{typeBadge(report.type)}</td>
                      <td>
                        <span className="d-flex align-items-center gap-1">
                          {formatIcon(report.format)} {report.format.toUpperCase()}
                        </span>
                      </td>
                      <td>{report.generated_by || '—'}</td>
                      <td className="text-muted">
                        {report.generated_at ? new Date(report.generated_at).toLocaleString() : '—'}
                      </td>
                      <td className="pe-4 text-center">
                        <div className="btn-group shadow-sm">
                          <button
                            className="btn btn-sm btn-light border"
                            title="Download"
                            onClick={() => handleDownload(report.id)}
                            disabled={downloading === report.id}
                          >
                            {downloading === report.id ? (
                              <span className="spinner-border spinner-border-sm" />
                            ) : (
                              <FaDownload className="text-primary" />
                            )}
                          </button>
                          <button
                            className="btn btn-sm btn-light border"
                            title="Delete"
                            onClick={() => setDeleteConfirm(report.id)}
                          >
                            <FaTrash className="text-danger" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>

        {/* Pagination */}
        {lastPage > 1 && !loading && !error && (
          <div className="card-footer bg-transparent py-3 border-top border-light d-flex justify-content-between align-items-center">
            <span className="text-muted small">
              Page {currentPage} of {lastPage}
            </span>
            <div className="d-flex gap-2">
              <button
                className="btn btn-sm btn-outline-primary"
                disabled={currentPage === 1}
                onClick={() => setCurrentPage(p => p - 1)}
              >
                Previous
              </button>
              {Array.from({ length: lastPage }, (_, i) => i + 1).map(page => (
                <button
                  key={page}
                  className={`btn btn-sm ${currentPage === page ? 'btn-primary' : 'btn-outline-primary'}`}
                  onClick={() => setCurrentPage(page)}
                >
                  {page}
                </button>
              ))}
              <button
                className="btn btn-sm btn-outline-primary"
                disabled={currentPage === lastPage}
                onClick={() => setCurrentPage(p => p + 1)}
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Generate Report Modal */}
      {showGenerateModal && (
        <div
          className="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
          style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }}
          onClick={() => setShowGenerateModal(false)}
        >
          <div
            className="card p-4"
            style={{ width: '480px', borderRadius: '16px' }}
            onClick={e => e.stopPropagation()}
          >
            <div className="d-flex justify-content-between align-items-center mb-3">
              <h5 className="fw-bold mb-0 text-dark-blue">Generate New Report</h5>
              <button className="btn btn-sm btn-outline-secondary border-0" onClick={() => setShowGenerateModal(false)}>
                <FaTimes />
              </button>
            </div>
            <div className="mb-3">
              <label className="form-label small text-muted">Report Type</label>
              <select className="form-select" value={genType} onChange={e => setGenType(e.target.value)}>
                {TYPE_OPTIONS.map(t => (
                  <option key={t} value={t}>{t.charAt(0).toUpperCase() + t.slice(1)}</option>
                ))}
              </select>
            </div>
            <div className="mb-3">
              <label className="form-label small text-muted">Format</label>
              <select className="form-select" value={genFormat} onChange={e => setGenFormat(e.target.value)}>
                {FORMAT_OPTIONS.map(f => (
                  <option key={f} value={f}>{f.toUpperCase()}</option>
                ))}
              </select>
            </div>
            <div className="mb-3">
              <label className="form-label small text-muted">
                Filters <span className="text-muted">(optional JSON)</span>
              </label>
              <textarea
                className="form-control"
                rows={3}
                placeholder='e.g. {"date_from": "2024-01-01", "date_to": "2024-12-31"}'
                value={genFilters}
                onChange={e => setGenFilters(e.target.value)}
              />
            </div>
            <div className="d-flex justify-content-end gap-2">
              <button className="btn btn-secondary" onClick={() => setShowGenerateModal(false)}>Cancel</button>
              <button className="btn btn-primary" onClick={handleGenerate} disabled={generating}>
                {generating ? <><span className="spinner-border spinner-border-sm me-1" /> Generating...</> : 'Generate'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      {deleteConfirm && (
        <div
          className="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
          style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }}
          onClick={() => !deleting && setDeleteConfirm(null)}
        >
          <div
            className="card p-4"
            style={{ width: '400px', borderRadius: '16px' }}
            onClick={e => e.stopPropagation()}
          >
            <h5 className="fw-bold mb-3 text-dark-blue">Confirm Delete</h5>
            <p className="text-muted mb-4">Are you sure you want to delete this report? This action cannot be undone.</p>
            <div className="d-flex justify-content-end gap-2">
              <button className="btn btn-secondary" onClick={() => setDeleteConfirm(null)} disabled={deleting}>Cancel</button>
              <button className="btn btn-danger" onClick={() => handleDelete(deleteConfirm)} disabled={deleting}>
                {deleting ? 'Deleting...' : 'Delete'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ReportsList;
