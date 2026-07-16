import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { FaUserPlus, FaEdit, FaTrash, FaEye, FaToggleOn, FaToggleOff, FaSearch, FaTimes, FaSave, FaSpinner, FaChevronLeft, FaChevronRight } from 'react-icons/fa';
import { getAccounts, createAccount, updateAccount, deleteAccount, disableAccount, enableAccount, generateEmployeeId } from '../../services/accounts';

const AccountsList = () => {
  const [accounts, setAccounts] = useState([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [perPage] = useState(10);
  const [totalPages, setTotalPages] = useState(0);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [form, setForm] = useState({ full_name: '', email: '', password: '', confirm_password: '', employee_id: '' });
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState(null);
  const [isEditing, setIsEditing] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [showViewModal, setShowViewModal] = useState(false);
  const [viewTarget, setViewTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchAccounts = useCallback(async () => {
    setLoading(true);
    try {
      const params = { page, per_page: perPage };
      if (search) params.search = search;
      if (statusFilter !== 'all') params.status = statusFilter;
      const res = await getAccounts(params);
      const d = res.data || res;
      setAccounts(d.data || []);
      setTotal(d.total || 0);
      setTotalPages(d.total_pages || 0);
    } catch (err) {
      setAccounts([]);
    } finally {
      setLoading(false);
    }
  }, [page, perPage, search, statusFilter]);

  useEffect(() => { fetchAccounts(); }, [fetchAccounts]);

  useEffect(() => {
    if (!isEditing) {
      generateEmployeeId().then(res => {
        const d = res.data || res;
        setForm(prev => ({ ...prev, employee_id: d.employee_id || '' }));
      }).catch(() => {});
    }
  }, [isEditing]);

  const handleFormChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const resetForm = () => {
    setForm({ full_name: '', email: '', password: '', confirm_password: '', employee_id: '' });
    setIsEditing(false);
    setEditingId(null);
    setMessage(null);
    generateEmployeeId().then(res => {
      const d = res.data || res;
      setForm(prev => ({ ...prev, employee_id: d.employee_id || '' }));
    }).catch(() => {});
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setMessage(null);
    try {
      if (isEditing && editingId) {
        const payload = { full_name: form.full_name, email: form.email, employee_id: form.employee_id };
        await updateAccount(editingId, payload);
        setMessage({ type: 'success', text: 'Account updated successfully.' });
      } else {
        await createAccount(form);
        setMessage({ type: 'success', text: 'Account created successfully.' });
      }
      resetForm();
      if (page !== 1) setPage(1); else fetchAccounts();
    } catch (err) {
      const errData = err?.errors || err?.data?.errors || {};
      const firstKey = Object.keys(errData)[0];
      setMessage({ type: 'danger', text: firstKey ? `${errData[firstKey]?.join(', ') || err.message || 'Validation failed.'}` : (err?.message || 'Failed to save account.') });
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = async (account) => {
    setIsEditing(true);
    setEditingId(account.id);
    setForm({
      full_name: account.full_name || '',
      email: account.email || '',
      password: '',
      confirm_password: '',
      employee_id: account.employee_id || '',
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleView = (account) => {
    setViewTarget(account);
    setShowViewModal(true);
  };

  const handleDeleteClick = (account) => {
    setDeleteTarget(account);
    setShowDeleteModal(true);
  };

  const handleDeleteConfirm = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await deleteAccount(deleteTarget.id);
      setShowDeleteModal(false);
      setDeleteTarget(null);
      setMessage({ type: 'success', text: 'Account deleted successfully.' });
      fetchAccounts();
    } catch (err) {
      setMessage({ type: 'danger', text: err?.message || 'Failed to delete account.' });
    } finally {
      setDeleting(false);
    }
  };

  const handleToggleStatus = async (account) => {
    try {
      if (account.is_active) {
        await disableAccount(account.id);
      } else {
        await enableAccount(account.id);
      }
      fetchAccounts();
    } catch (err) {
      setMessage({ type: 'danger', text: err?.message || 'Failed to change account status.' });
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    setSearch(searchInput);
    setPage(1);
  };

  const handleSearchClear = () => {
    setSearchInput('');
    setSearch('');
    setPage(1);
  };

  const handleFilterChange = (val) => {
    setStatusFilter(val);
    setPage(1);
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
  };

  const formatDateTime = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
  };

  return (
    <div className="container-fluid p-0">
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
          <h3 className="text-dark-blue fw-bold mb-1">Create Account</h3>
          <nav aria-label="breadcrumb">
            <ol className="breadcrumb mb-0 small">
              <li className="breadcrumb-item"><Link to="/dashboard" className="text-decoration-none">Home</Link></li>
              <li className="breadcrumb-item active" aria-current="page">Accounts</li>
            </ol>
          </nav>
        </div>
      </div>

      {message && (
        <div className={`alert alert-${message.type} alert-dismissible fade show d-flex align-items-center`} role="alert">
          <span className="flex-grow-1">{message.text}</span>
          <button type="button" className="btn-close" onClick={() => setMessage(null)}></button>
        </div>
      )}

      <div className="row g-4">
        <div className="col-12 col-xl-5">
          <div className="card glass-card border-0">
            <div className="card-header bg-transparent border-bottom border-light fw-bold text-dark-blue py-3 d-flex align-items-center">
              <FaUserPlus className="text-success me-2" /> {isEditing ? 'Edit Account' : 'Create New Account'}
            </div>
            <div className="card-body p-4">
              <form onSubmit={handleSubmit}>
                <div className="row g-3 mb-4">
                  <div className="col-12">
                    <label className="form-label text-muted small fw-semibold">Full Name <span className="text-danger">*</span></label>
                    <input type="text" className="form-control" name="full_name" value={form.full_name} onChange={handleFormChange} placeholder="Enter full name" required />
                  </div>
                  <div className="col-12">
                    <label className="form-label text-muted small fw-semibold">Email Address <span className="text-danger">*</span></label>
                    <input type="email" className="form-control" name="email" value={form.email} onChange={handleFormChange} placeholder="Enter email address" required />
                  </div>
                  {!isEditing && (
                    <>
                      <div className="col-md-6">
                        <label className="form-label text-muted small fw-semibold">Password <span className="text-danger">*</span></label>
                        <input type="password" className="form-control" name="password" value={form.password} onChange={handleFormChange} placeholder="Min 6 characters" required={!isEditing} minLength={6} />
                      </div>
                      <div className="col-md-6">
                        <label className="form-label text-muted small fw-semibold">Confirm Password <span className="text-danger">*</span></label>
                        <input type="password" className="form-control" name="confirm_password" value={form.confirm_password} onChange={handleFormChange} placeholder="Confirm password" required={!isEditing} />
                      </div>
                    </>
                  )}
                  <div className="col-12">
                    <label className="form-label text-muted small fw-semibold">Employee ID <span className="text-danger">*</span></label>
                    <div className="input-group">
                      <input type="text" className="form-control" name="employee_id" value={form.employee_id} onChange={handleFormChange} placeholder="EMP-0001" required />
                      <button type="button" className="btn btn-outline-secondary" onClick={() => generateEmployeeId().then(res => { const d = res.data || res; setForm(prev => ({ ...prev, employee_id: d.employee_id || '' })); })} title="Generate new ID">
                        <FaSpinner className={saving ? 'fa-spin' : ''} />
                      </button>
                    </div>
                    <div className="form-text">Auto-generated. You can edit if needed.</div>
                  </div>
                </div>
                <div className="d-flex gap-2 justify-content-end">
                  {isEditing && (
                    <button type="button" className="btn btn-outline-secondary d-flex align-items-center" onClick={resetForm}>
                      <FaTimes className="me-2" /> Cancel
                    </button>
                  )}
                  <button type="submit" className="btn btn-success d-flex align-items-center" disabled={saving}>
                    {saving ? <FaSpinner className="fa-spin me-2" /> : <FaSave className="me-2" />} {isEditing ? 'Update Account' : 'Create Account'}
                  </button>
                  {!isEditing && (
                    <button type="button" className="btn btn-outline-secondary d-flex align-items-center" onClick={resetForm}>
                      <FaTimes className="me-2" /> Reset Form
                    </button>
                  )}
                </div>
              </form>
            </div>
          </div>
        </div>

        <div className="col-12 col-xl-7">
          <div className="card glass-card border-0">
            <div className="card-header bg-transparent border-bottom border-light py-3">
              <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h6 className="fw-bold text-dark-blue mb-0 d-flex align-items-center">
                  Administrator Accounts <span className="badge bg-primary bg-opacity-10 text-primary ms-2 rounded-pill">{total}</span>
                </h6>
                <div className="d-flex gap-2 flex-wrap">
                  <form onSubmit={handleSearch} className="d-flex" style={{ maxWidth: '220px' }}>
                    <div className="input-group input-group-sm">
                      <input type="text" className="form-control" placeholder="Search name, email, ID..." value={searchInput} onChange={(e) => setSearchInput(e.target.value)} />
                      {search && (
                        <button className="btn btn-outline-secondary" type="button" onClick={handleSearchClear}><FaTimes /></button>
                      )}
                      <button className="btn btn-primary" type="submit"><FaSearch /></button>
                    </div>
                  </form>
                  <div className="btn-group btn-group-sm">
                    {['all', 'active', 'disabled'].map(f => (
                      <button key={f} className={`btn ${statusFilter === f ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => handleFilterChange(f)}>
                        {f === 'all' ? 'All' : f === 'active' ? 'Active' : 'Disabled'}
                      </button>
                    ))}
                  </div>
                </div>
              </div>
            </div>
            <div className="card-body p-0">
              {loading ? (
                <div className="text-center py-5"><FaSpinner className="text-primary fa-spin" size={32} /></div>
              ) : accounts.length === 0 ? (
                <div className="text-center py-5 text-muted">
                  <FaUserPlus size={48} className="mb-3 text-muted opacity-50" />
                  <p className="mb-0">No administrator accounts found.</p>
                  {search || statusFilter !== 'all' ? <p className="small">Try adjusting your search or filters.</p> : <p className="small">Create your first account using the form.</p>}
                </div>
              ) : (
                <div className="table-responsive">
                  <table className="table table-hover align-middle mb-0">
                    <thead className="table-light small">
                      <tr>
                        <th className="ps-4">Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Login</th>
                        <th className="pe-4 text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {accounts.map(acc => (
                        <tr key={acc.id}>
                          <td className="ps-4 fw-medium"><code>{acc.employee_id}</code></td>
                          <td className="fw-medium">{acc.full_name}</td>
                          <td className="small text-muted">{acc.email}</td>
                          <td><span className="badge bg-info bg-opacity-10 text-info border border-info">{acc.role}</span></td>
                          <td>
                            <span className={`badge rounded-pill ${acc.is_active ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary'}`}>
                              {acc.is_active ? 'Active' : 'Disabled'}
                            </span>
                          </td>
                          <td className="small text-muted">{formatDate(acc.created_at)}</td>
                          <td className="small text-muted">{formatDateTime(acc.last_login)}</td>
                          <td className="pe-4 text-end">
                            <div className="d-flex gap-1 justify-content-end">
                              <button className="btn btn-sm btn-outline-info" title="View" onClick={() => handleView(acc)}><FaEye /></button>
                              <button className="btn btn-sm btn-outline-primary" title="Edit" onClick={() => handleEdit(acc)}><FaEdit /></button>
                              <button className={`btn btn-sm ${acc.is_active ? 'btn-outline-warning' : 'btn-outline-success'}`} title={acc.is_active ? 'Disable' : 'Enable'} onClick={() => handleToggleStatus(acc)}>
                                {acc.is_active ? <FaToggleOff /> : <FaToggleOn />}
                              </button>
                              <button className="btn btn-sm btn-outline-danger" title="Delete" onClick={() => handleDeleteClick(acc)}><FaTrash /></button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
            {totalPages > 1 && (
              <div className="card-footer bg-transparent border-top border-light d-flex justify-content-between align-items-center py-3">
                <small className="text-muted">Showing {(page - 1) * perPage + 1}-{Math.min(page * perPage, total)} of {total}</small>
                <div className="d-flex gap-1">
                  <button className="btn btn-sm btn-outline-secondary" disabled={page <= 1} onClick={() => setPage(p => Math.max(1, p - 1))}>
                    <FaChevronLeft />
                  </button>
                  {Array.from({ length: totalPages }, (_, i) => i + 1).map(p => (
                    <button key={p} className={`btn btn-sm ${p === page ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setPage(p)}>{p}</button>
                  ))}
                  <button className="btn btn-sm btn-outline-secondary" disabled={page >= totalPages} onClick={() => setPage(p => Math.min(totalPages, p + 1))}>
                    <FaChevronRight />
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {showViewModal && viewTarget && (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header border-bottom border-light">
                <h6 className="modal-title fw-bold">Account Details</h6>
                <button type="button" className="btn-close" onClick={() => setShowViewModal(false)}></button>
              </div>
              <div className="modal-body">
                <dl className="row mb-0">
                  <dt className="col-sm-4 text-muted small">Employee ID</dt>
                  <dd className="col-sm-8"><code>{viewTarget.employee_id}</code></dd>
                  <dt className="col-sm-4 text-muted small">Full Name</dt>
                  <dd className="col-sm-8 fw-medium">{viewTarget.full_name}</dd>
                  <dt className="col-sm-4 text-muted small">Email</dt>
                  <dd className="col-sm-8">{viewTarget.email}</dd>
                  <dt className="col-sm-4 text-muted small">Role</dt>
                  <dd className="col-sm-8"><span className="badge bg-info bg-opacity-10 text-info">{viewTarget.role}</span></dd>
                  <dt className="col-sm-4 text-muted small">Status</dt>
                  <dd className="col-sm-8"><span className={`badge rounded-pill ${viewTarget.is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary'}`}>{viewTarget.is_active ? 'Active' : 'Disabled'}</span></dd>
                  <dt className="col-sm-4 text-muted small">Created</dt>
                  <dd className="col-sm-8">{formatDateTime(viewTarget.created_at)}</dd>
                  <dt className="col-sm-4 text-muted small">Last Login</dt>
                  <dd className="col-sm-8">{formatDateTime(viewTarget.last_login)}</dd>
                  <dt className="col-sm-4 text-muted small">Created By</dt>
                  <dd className="col-sm-8">{viewTarget.created_by || '—'}</dd>
                </dl>
              </div>
              <div className="modal-footer border-top border-light">
                <button type="button" className="btn btn-secondary" onClick={() => setShowViewModal(false)}>Close</button>
              </div>
            </div>
          </div>
        </div>
      )}

      {showDeleteModal && deleteTarget && (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog modal-dialog-centered modal-sm">
            <div className="modal-content">
              <div className="modal-body text-center py-4">
                <FaTrash size={36} className="text-danger mb-3" />
                <h6 className="fw-bold mb-2">Are you sure?</h6>
                <p className="small text-muted mb-3">You are about to delete the account for <strong>{deleteTarget.full_name}</strong> ({deleteTarget.employee_id}). This action cannot be undone.</p>
                <div className="d-flex gap-2 justify-content-center">
                  <button type="button" className="btn btn-outline-secondary" onClick={() => { setShowDeleteModal(false); setDeleteTarget(null); }}>Cancel</button>
                  <button type="button" className="btn btn-danger d-flex align-items-center" disabled={deleting} onClick={handleDeleteConfirm}>
                    {deleting ? <FaSpinner className="fa-spin me-2" /> : <FaTrash className="me-2" />} Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default AccountsList;
