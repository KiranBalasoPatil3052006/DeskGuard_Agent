import React, { useState, useEffect, useCallback } from 'react';
import { 
  getAlertProfiles, getAlertProfile, createAlertProfile, 
  updateAlertProfile, deleteAlertProfile, duplicateAlertProfile,
  assignProfileToCompany, unassignProfileFromCompany
} from '../../services/alertProfiles';
import { FaPlus, FaEdit, FaCopy, FaTrash, FaChevronLeft, FaChevronRight, FaSearch, FaShieldAlt, FaExclamationTriangle } from 'react-icons/fa';

const DEFAULT_THRESHOLDS = {
  cpu_warning_percent: 80, cpu_critical_percent: 95, cpu_warning_duration_minutes: 5,
  ram_warning_percent: 80, ram_critical_percent: 95, ram_warning_duration_minutes: 5,
  cpu_temp_warning: 80, cpu_temp_critical: 90,
  disk_warning_percent: 85, disk_critical_percent: 95,
  disk_smart_warning_enabled: true, disk_smart_critical_enabled: true,
  offline_warning_minutes: 10, offline_critical_minutes: 30,
  failed_login_warning_count: 5, failed_login_critical_count: 15,
  network_disconnect_warning_count: 3,
};

const FIXED_RULES = [
  { name: 'RAM Changed', severity: 'critical', description: 'Triggers when RAM module is added, removed, or replaced.' },
  { name: 'SSD Changed', severity: 'critical', description: 'Triggers when an SSD is added or removed.' },
  { name: 'HDD Changed', severity: 'critical', description: 'Triggers when an HDD is added or removed.' },
  { name: 'CPU Changed', severity: 'critical', description: 'Triggers when the processor is replaced.' },
  { name: 'Motherboard Changed', severity: 'critical', description: 'Triggers when the motherboard is replaced.' },
  { name: 'BIOS Changed', severity: 'warning', description: 'Triggers when BIOS version changes.' },
  { name: 'Antivirus Removed', severity: 'critical', description: 'Triggers when antivirus software is uninstalled.' },
  { name: 'Firewall Disabled', severity: 'warning', description: 'Triggers when Windows Firewall is turned off.' },
];

export default function AlertThresholds() {
  const [profiles, setProfiles] = useState([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [perPage] = useState(20);
  const [totalPages, setTotalPages] = useState(1);
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // Selected profile for editing
  const [selectedId, setSelectedId] = useState(null);
  const [selectedProfile, setSelectedProfile] = useState(null);
  const [profileLoading, setProfileLoading] = useState(false);

  // Create modal
  const [showCreate, setShowCreate] = useState(false);
  const [createName, setCreateName] = useState('');
  const [createDesc, setCreateDesc] = useState('');
  const [createSaving, setCreateSaving] = useState(false);
  const [createError, setCreateError] = useState('');

  // Edit threshold form
  const [thresholdForm, setThresholdForm] = useState({});
  const [editName, setEditName] = useState('');
  const [editDesc, setEditDesc] = useState('');
  const [editSaving, setEditSaving] = useState(false);
  const [editError, setEditError] = useState('');

  // Assign companies
  const [showAssign, setShowAssign] = useState(false);
  const [assignCompanyId, setAssignCompanyId] = useState('');
  const [assignSaving, setAssignSaving] = useState(false);
  const [assignError, setAssignError] = useState('');

  // Delete confirmation
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleteSaving, setDeleteSaving] = useState(false);

  // Search debounce
  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(search), 300);
    return () => clearTimeout(t);
  }, [search]);

  useEffect(() => { setPage(1); }, [debouncedSearch]);

  // Fetch profiles
  const fetchProfiles = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const res = await getAlertProfiles({ search: debouncedSearch, page, per_page: perPage });
      const data = res?.data?.data || res?.data || [];
      setProfiles(Array.isArray(data) ? data : []);
      setTotal(res?.data?.total || 0);
      setTotalPages(res?.data?.total_pages || 1);
    } catch (err) {
      setError('Failed to load alert profiles.');
      setProfiles([]);
    } finally {
      setLoading(false);
    }
  }, [debouncedSearch, page, perPage]);

  useEffect(() => { fetchProfiles(); }, [fetchProfiles]);

  // Fetch single profile with thresholds
  const fetchProfile = useCallback(async (id) => {
    if (!id) { setSelectedProfile(null); return; }
    setProfileLoading(true);
    try {
      const res = await getAlertProfile(id);
      const profile = res?.data?.data || res?.data;
      setSelectedProfile(profile);
      setThresholdForm(profile?.thresholds || {});
      setEditName(profile?.name || '');
      setEditDesc(profile?.description || '');
      setEditError('');
    } catch {
      setEditError('Failed to load profile details.');
    } finally {
      setProfileLoading(false);
    }
  }, []);

  useEffect(() => { fetchProfile(selectedId); }, [selectedId, fetchProfile]);

  // Create profile
  const handleCreate = async (e) => {
    e.preventDefault();
    if (!createName.trim()) { setCreateError('Profile name is required.'); return; }
    setCreateSaving(true);
    setCreateError('');
    try {
      await createAlertProfile({ name: createName.trim(), description: createDesc.trim() || null });
      setShowCreate(false);
      setCreateName('');
      setCreateDesc('');
      fetchProfiles();
    } catch (err) {
      setCreateError(err?.response?.data?.message || 'Failed to create profile.');
    } finally {
      setCreateSaving(false);
    }
  };

  // Save thresholds
  const handleSaveThresholds = async () => {
    if (!selectedId) return;
    setEditSaving(true);
    setEditError('');
    try {
      const payload = { name: editName.trim(), description: editDesc.trim() || null, thresholds: thresholdForm };
      await updateAlertProfile(selectedId, payload);
      fetchProfiles();
      fetchProfile(selectedId);
    } catch (err) {
      setEditError(err?.response?.data?.message || 'Failed to save thresholds.');
    } finally {
      setEditSaving(false);
    }
  };

  // Duplicate profile
  const handleDuplicate = async (id) => {
    try {
      await duplicateAlertProfile(id);
      fetchProfiles();
    } catch (err) {
      setError(err?.response?.data?.message || 'Failed to duplicate profile.');
    }
  };

  // Delete profile
  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleteSaving(true);
    try {
      await deleteAlertProfile(deleteTarget.id);
      setDeleteTarget(null);
      if (selectedId === deleteTarget.id) setSelectedId(null);
      fetchProfiles();
    } catch (err) {
      setError(err?.response?.data?.message || 'Failed to delete profile.');
      setDeleteTarget(null);
    } finally {
      setDeleteSaving(false);
    }
  };

  // Assign company
  const handleAssignCompany = async () => {
    if (!selectedId || !assignCompanyId) return;
    setAssignSaving(true);
    setAssignError('');
    try {
      await assignProfileToCompany(selectedId, parseInt(assignCompanyId));
      setShowAssign(false);
      setAssignCompanyId('');
      fetchProfile(selectedId);
    } catch (err) {
      setAssignError(err?.response?.data?.message || 'Failed to assign company.');
    } finally {
      setAssignSaving(false);
    }
  };

  // Unassign company
  const handleUnassignCompany = async (companyId) => {
    if (!selectedId) return;
    try {
      await unassignProfileFromCompany(selectedId, companyId);
      fetchProfile(selectedId);
    } catch (err) {
      setEditError(err?.response?.data?.message || 'Failed to unassign company.');
    }
  };

  const handleSelect = (id) => { setSelectedId(id === selectedId ? null : id); };

  const updateThreshold = (field, value) => {
    setThresholdForm(prev => ({ ...prev, [field]: value === '' ? null : value }));
  };

  // Pagination
  const pages = [];
  for (let i = Math.max(1, page - 2); i <= Math.min(totalPages, page + 2); i++) pages.push(i);

  return (
    <div className="p-4" style={{ maxWidth: '1400px', margin: '0 auto' }}>
      <h4 className="fw-bold mb-1">Alert Thresholds</h4>
      <p className="text-muted mb-4" style={{ fontSize: '0.9rem' }}>Manage monitoring profiles that define when alerts are triggered for customer systems.</p>

      {error && (
        <div className="alert alert-danger d-flex align-items-center gap-2 py-2" style={{ borderRadius: '10px' }}>
          <FaExclamationTriangle /> {error}
          <button className="btn-close ms-auto" onClick={() => setError('')}></button>
        </div>
      )}

      <div className="row g-4">
        {/* ── LEFT: Profile List ── */}
        <div className="col-12 col-lg-5">
          <div className="card" style={{ borderRadius: '16px', border: '1px solid var(--border-color)', background: 'var(--bg-card)' }}>
            <div className="card-body p-3">
              <div className="d-flex align-items-center gap-2 mb-3">
                <div className="input-group" style={{ maxWidth: '280px' }}>
                  <span className="input-group-text bg-transparent border-end-0" style={{ borderRadius: '10px 0 0 10px' }}>
                    <FaSearch className="text-muted" style={{ fontSize: '0.8rem' }} />
                  </span>
                  <input 
                    className="form-control border-start-0 ps-0" style={{ borderRadius: '0 10px 10px 0' }}
                    placeholder="Search profiles..." value={search} onChange={e => setSearch(e.target.value)}
                  />
                </div>
                <button className="btn btn-success btn-sm ms-auto d-flex align-items-center gap-1" onClick={() => setShowCreate(true)}>
                  <FaPlus /> New Profile
                </button>
              </div>

              {loading ? (
                <div className="text-center py-4 text-muted">Loading...</div>
              ) : profiles.length === 0 ? (
                <div className="text-center py-4 text-muted">No alert profiles found.</div>
              ) : (
                <div className="table-responsive">
                  <table className="table table-hover align-middle mb-0" style={{ fontSize: '0.85rem' }}>
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th style={{ width: '60px' }}>Companies</th>
                        <th style={{ width: '60px' }}>Machines</th>
                        <th style={{ width: '100px' }}>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {profiles.map(p => (
                        <tr key={p.id} className={`cursor-pointer ${selectedId === p.id ? 'table-active' : ''}`} 
                            onClick={() => handleSelect(p.id)} style={{ cursor: 'pointer' }}>
                          <td>
                            <div className="fw-semibold">
                              {p.is_default && <span className="badge bg-success me-1" style={{ fontSize: '0.65rem' }}>DEFAULT</span>}
                              {p.name}
                            </div>
                            {p.description && <div className="text-muted" style={{ fontSize: '0.75rem' }}>{p.description}</div>}
                          </td>
                          <td>{p.assigned_companies_count || 0}</td>
                          <td>{p.assigned_machines_count || 0}</td>
                          <td>
                            <div className="d-flex gap-1" onClick={e => e.stopPropagation()}>
                              <button className="btn btn-sm btn-outline-secondary" title="Duplicate" onClick={() => handleDuplicate(p.id)}><FaCopy /></button>
                              <button className="btn btn-sm btn-outline-danger" title="Delete" onClick={() => setDeleteTarget(p)} 
                                disabled={p.assigned_companies_count > 0 || p.assigned_machines_count > 0}><FaTrash /></button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}

              {totalPages > 1 && (
                <div className="d-flex justify-content-center align-items-center gap-2 mt-3">
                  <button className="btn btn-sm btn-outline-secondary" disabled={page <= 1} onClick={() => setPage(p => p - 1)}><FaChevronLeft /></button>
                  {pages.map(p => (
                    <button key={p} className={`btn btn-sm ${p === page ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setPage(p)}>{p}</button>
                  ))}
                  <button className="btn btn-sm btn-outline-secondary" disabled={page >= totalPages} onClick={() => setPage(p => p + 1)}><FaChevronRight /></button>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* ── RIGHT: Profile Details ── */}
        <div className="col-12 col-lg-7">
          {!selectedId ? (
            <div className="card" style={{ borderRadius: '16px', border: '1px solid var(--border-color)', background: 'var(--bg-card)' }}>
              <div className="card-body text-center py-5 text-muted">
                <FaShieldAlt style={{ fontSize: '3rem', opacity: 0.3 }} className="mb-3" />
                <p>Select a profile to view and edit its thresholds.</p>
              </div>
            </div>
          ) : profileLoading ? (
            <div className="card" style={{ borderRadius: '16px', border: '1px solid var(--border-color)', background: 'var(--bg-card)' }}>
              <div className="card-body text-center py-4 text-muted">Loading profile...</div>
            </div>
          ) : selectedProfile ? (
            <>
              {/* ── Profile Header ── */}
              <div className="card mb-3" style={{ borderRadius: '16px', border: '1px solid var(--border-color)', background: 'var(--bg-card)' }}>
                <div className="card-body p-3">
                  <div className="d-flex align-items-start justify-content-between">
                    <div style={{ flex: 1 }}>
                      <div className="d-flex align-items-center gap-2 mb-2">
                        <input className="form-control form-control-sm fw-bold" style={{ maxWidth: '300px', fontSize: '1.1rem' }}
                          value={editName} onChange={e => setEditName(e.target.value)} />
                        {selectedProfile.is_default && <span className="badge bg-success">Default</span>}
                      </div>
                      <input className="form-control form-control-sm text-muted" style={{ maxWidth: '400px' }}
                        value={editDesc} onChange={e => setEditDesc(e.target.value)} placeholder="Description" />
                    </div>
                    <div className="d-flex gap-2">
                      <button className="btn btn-sm btn-outline-success" onClick={() => setShowAssign(true)}>
                        Assign Company
                      </button>
                    </div>
                  </div>

                  {selectedProfile.assigned_companies_count > 0 && (
                    <div className="mt-2 d-flex flex-wrap gap-1">
                      <small className="text-muted me-1">Companies:</small>
                      {Array.from({ length: selectedProfile.assigned_companies_count }, (_, i) => (
                        <span key={i} className="badge bg-light text-dark me-1" style={{ fontSize: '0.75rem' }}>
                          Company #{i + 1}
                          <button className="btn-close btn-close-white ms-1" style={{ fontSize: '0.5rem' }} 
                            onClick={() => handleUnassignCompany(i + 1)}></button>
                        </span>
                      ))}
                    </div>
                  )}
                </div>
              </div>

              {/* ── Threshold Editor ── */}
              <ThresholdEditor thresholds={thresholdForm} onChange={updateThreshold} />

              {/* ── Save / Error ── */}
              {editError && (
                <div className="alert alert-danger py-2 mt-2 d-flex align-items-center gap-2" style={{ borderRadius: '10px' }}>
                  <FaExclamationTriangle /> {editError}
                </div>
              )}
              <div className="d-flex justify-content-end mt-3">
                <button className="btn btn-primary px-4 d-flex align-items-center gap-2" onClick={handleSaveThresholds} disabled={editSaving}>
                  {editSaving ? 'Saving...' : 'Save Changes'}
                </button>
              </div>

              {/* ── Fixed Rules Panel ── */}
              <FixedRulesPanel />
            </>
          ) : null}
        </div>
      </div>

      {/* ── Create Modal ── */}
      {showCreate && (
        <div className="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
             style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }}
             onClick={() => { if (!createSaving) setShowCreate(false); }}>
          <div className="card p-4" style={{ width: '440px', borderRadius: '16px' }} onClick={e => e.stopPropagation()}>
            <h5 className="fw-bold mb-3">Create Alert Profile</h5>
            {createError && <div className="alert alert-danger py-2" style={{ borderRadius: '10px' }}>{createError}</div>}
            <form onSubmit={handleCreate}>
              <div className="mb-3">
                <label className="form-label fw-semibold">Profile Name</label>
                <input className="form-control" value={createName} onChange={e => setCreateName(e.target.value)} placeholder="e.g. Office Workstations" autoFocus />
              </div>
              <div className="mb-3">
                <label className="form-label fw-semibold">Description</label>
                <textarea className="form-control" rows={2} value={createDesc} onChange={e => setCreateDesc(e.target.value)} placeholder="Optional description" />
              </div>
              <div className="d-flex justify-content-end gap-2">
                <button type="button" className="btn btn-secondary" onClick={() => setShowCreate(false)} disabled={createSaving}>Cancel</button>
                <button type="submit" className="btn btn-success" disabled={createSaving}>{createSaving ? 'Creating...' : 'Create Profile'}</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ── Assign Company Modal ── */}
      {showAssign && (
        <div className="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
             style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }}
             onClick={() => { if (!assignSaving) setShowAssign(false); }}>
          <div className="card p-4" style={{ width: '400px', borderRadius: '16px' }} onClick={e => e.stopPropagation()}>
            <h5 className="fw-bold mb-3">Assign to Company</h5>
            {assignError && <div className="alert alert-danger py-2" style={{ borderRadius: '10px' }}>{assignError}</div>}
            <div className="mb-3">
              <label className="form-label fw-semibold">Company ID</label>
              <input className="form-control" type="number" value={assignCompanyId} onChange={e => setAssignCompanyId(e.target.value)} placeholder="Enter company ID" />
            </div>
            <div className="d-flex justify-content-end gap-2">
              <button className="btn btn-secondary" onClick={() => setShowAssign(false)} disabled={assignSaving}>Cancel</button>
              <button className="btn btn-success" onClick={handleAssignCompany} disabled={assignSaving || !assignCompanyId}>
                {assignSaving ? 'Assigning...' : 'Assign'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ── Delete Confirmation ── */}
      {deleteTarget && (
        <div className="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
             style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }}
             onClick={() => { if (!deleteSaving) setDeleteTarget(null); }}>
          <div className="card p-4" style={{ width: '400px', borderRadius: '16px' }} onClick={e => e.stopPropagation()}>
            <h5 className="fw-bold mb-3">Delete Profile</h5>
            <p>Are you sure you want to delete <strong>{deleteTarget.name}</strong>?</p>
            {deleteTarget.assigned_companies_count > 0 || deleteTarget.assigned_machines_count > 0 ? (
              <div className="alert alert-warning py-2" style={{ borderRadius: '10px' }}>
                This profile is currently assigned and cannot be deleted.
              </div>
            ) : null}
            <div className="d-flex justify-content-end gap-2">
              <button className="btn btn-secondary" onClick={() => setDeleteTarget(null)} disabled={deleteSaving}>Cancel</button>
              <button className="btn btn-danger" onClick={handleDelete} disabled={deleteSaving || deleteTarget.assigned_companies_count > 0 || deleteTarget.assigned_machines_count > 0}>
                {deleteSaving ? 'Deleting...' : 'Delete'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// ── ThresholdEditor Sub-component ──
function ThresholdEditor({ thresholds, onChange }) {
  const t = (field) => thresholds?.[field] ?? '';

  const renderField = (field, label, type = 'number', opts = {}) => {
    const value = t(field);
    const isBool = type === 'checkbox';
    return (
      <div className="mb-2" key={field}>
        <label className="form-label mb-0" style={{ fontSize: '0.82rem', fontWeight: 500 }}>{label}</label>
        {isBool ? (
          <div className="form-check form-switch mt-1">
            <input className="form-check-input" type="checkbox" checked={!!value} 
              onChange={e => onChange(field, e.target.checked)} />
          </div>
        ) : (
          <input className="form-control form-control-sm" type="number" min={opts.min ?? 0} max={opts.max ?? 100} step={opts.step ?? 'any'}
            value={value === null || value === undefined ? '' : value} 
            onChange={e => onChange(field, e.target.value === '' ? null : parseFloat(e.target.value))} 
            placeholder={opts.placeholder || ''} />
        )}
      </div>
    );
  };

  return (
    <div className="card" style={{ borderRadius: '16px', border: '1px solid var(--border-color)', background: 'var(--bg-card)' }}>
      <div className="card-body p-3">
        <h6 className="fw-bold mb-3">Threshold Configuration</h6>

        {/* Performance */}
        <div className="mb-3">
          <h6 className="text-success fw-semibold" style={{ fontSize: '0.85rem', borderBottom: '1px solid var(--border-color)', paddingBottom: '0.5rem' }}>
            Performance Thresholds
          </h6>
          <div className="row g-2">
            <div className="col-6 col-md-4">{renderField('cpu_warning_percent', 'CPU Warning %')}</div>
            <div className="col-6 col-md-4">{renderField('cpu_critical_percent', 'CPU Critical %')}</div>
            <div className="col-6 col-md-4">{renderField('cpu_warning_duration_minutes', 'CPU Warning Duration (min)')}</div>
            <div className="col-6 col-md-4">{renderField('ram_warning_percent', 'RAM Warning %')}</div>
            <div className="col-6 col-md-4">{renderField('ram_critical_percent', 'RAM Critical %')}</div>
            <div className="col-6 col-md-4">{renderField('ram_warning_duration_minutes', 'RAM Warning Duration (min)')}</div>
            <div className="col-6 col-md-4">{renderField('cpu_temp_warning', 'CPU Temp Warning °C')}</div>
            <div className="col-6 col-md-4">{renderField('cpu_temp_critical', 'CPU Temp Critical °C')}</div>
          </div>
        </div>

        {/* Storage */}
        <div className="mb-3">
          <h6 className="text-warning fw-semibold" style={{ fontSize: '0.85rem', borderBottom: '1px solid var(--border-color)', paddingBottom: '0.5rem' }}>
            Storage Thresholds
          </h6>
          <div className="row g-2">
            <div className="col-6 col-md-4">{renderField('disk_warning_percent', 'Disk Warning %')}</div>
            <div className="col-6 col-md-4">{renderField('disk_critical_percent', 'Disk Critical %')}</div>
            <div className="col-6 col-md-4">{renderField('disk_smart_warning_enabled', 'SMART Warning', 'checkbox')}</div>
            <div className="col-6 col-md-4">{renderField('disk_smart_critical_enabled', 'SMART Critical', 'checkbox')}</div>
          </div>
        </div>

        {/* Availability */}
        <div className="mb-3">
          <h6 className="text-info fw-semibold" style={{ fontSize: '0.85rem', borderBottom: '1px solid var(--border-color)', paddingBottom: '0.5rem' }}>
            Availability Thresholds
          </h6>
          <div className="row g-2">
            <div className="col-6 col-md-4">{renderField('offline_warning_minutes', 'Offline Warning (min)')}</div>
            <div className="col-6 col-md-4">{renderField('offline_critical_minutes', 'Offline Critical (min)')}</div>
          </div>
        </div>

        {/* Authentication */}
        <div className="mb-3">
          <h6 className="text-danger fw-semibold" style={{ fontSize: '0.85rem', borderBottom: '1px solid var(--border-color)', paddingBottom: '0.5rem' }}>
            Authentication Thresholds
          </h6>
          <div className="row g-2">
            <div className="col-6 col-md-4">{renderField('failed_login_warning_count', 'Failed Login Warning')}</div>
            <div className="col-6 col-md-4">{renderField('failed_login_critical_count', 'Failed Login Critical')}</div>
          </div>
        </div>

        {/* Network */}
        <div className="mb-0">
          <h6 className="text-secondary fw-semibold" style={{ fontSize: '0.85rem', borderBottom: '1px solid var(--border-color)', paddingBottom: '0.5rem' }}>
            Network Thresholds
          </h6>
          <div className="row g-2">
            <div className="col-6 col-md-4">{renderField('network_disconnect_warning_count', 'Network Disconnect Warning')}</div>
          </div>
        </div>
      </div>
    </div>
  );
}

// ── FixedRulesPanel Sub-component ──
function FixedRulesPanel() {
  return (
    <div className="card mt-3" style={{ borderRadius: '16px', border: '1px solid var(--border-color)', background: 'var(--bg-card)' }}>
      <div className="card-body p-3">
        <h6 className="fw-bold mb-1">System Critical Events</h6>
        <p className="text-muted mb-3" style={{ fontSize: '0.8rem' }}>
          These alerts are mandatory for security and AMC auditing purposes. They cannot be disabled.
        </p>
        <div className="table-responsive">
          <table className="table table-sm align-middle mb-0" style={{ fontSize: '0.82rem' }}>
            <thead>
              <tr>
                <th>Event</th>
                <th style={{ width: '100px' }}>Severity</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              {FIXED_RULES.map((rule, i) => (
                <tr key={i}>
                  <td className="fw-semibold">{rule.name}</td>
                  <td>
                    <span className={`badge ${rule.severity === 'critical' ? 'bg-danger' : 'bg-warning text-dark'}`}>
                      {rule.severity}
                    </span>
                  </td>
                  <td className="text-muted">{rule.description}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
