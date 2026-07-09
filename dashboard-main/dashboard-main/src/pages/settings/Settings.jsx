import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { FaCog, FaChartLine, FaBell, FaShieldAlt, FaSave, FaUser, FaCamera, FaPlus, FaTrash, FaSlidersH } from 'react-icons/fa';
import { getAlertRules, updateAlertRule } from '../../services/alerts';
import { getNotificationSettings, getEmailRecipients, addEmailRecipient, updateEmailRecipient, removeEmailRecipient } from '../../services/settings';
import { getUser } from '../../services/auth';
import api from '../../services/api';

const Settings = () => {
  const [activeTab, setActiveTab] = useState('profile');
  const [alertRules, setAlertRules] = useState([]);
  const [rulesLoading, setRulesLoading] = useState(false);
  const [rulesSaving, setRulesSaving] = useState(false);
  const [rulesMessage, setRulesMessage] = useState('');

  const getPref = (key, def) => {
    try { const v = localStorage.getItem(`settings_${key}`); return v !== null ? JSON.parse(v) : def; }
    catch { return def; }
  };
  const setPref = (key, val) => localStorage.setItem(`settings_${key}`, JSON.stringify(val));

  // --- Profile State ---
  const [user, setUser] = useState(null);
  const [profileLoading, setProfileLoading] = useState(false);
  const [profileMessage, setProfileMessage] = useState('');
  const [passwords, setPasswords] = useState({ current: '', new: '', confirm: '' });
  const [passwordMessage, setPasswordMessage] = useState('');

  // --- Notifications State ---
  const [recipients, setRecipients] = useState([]);
  const [recipientsLoading, setRecipientsLoading] = useState(false);
  const [newEmail, setNewEmail] = useState('');
  const [newName, setNewName] = useState('');
  const [notifMessage, setNotifMessage] = useState('');
  const [notifSuccess, setNotifSuccess] = useState(true);
  const [emailAlertsEnabled, setEmailAlertsEnabled] = useState(() => getPref('emailAlertsEnabled', true));
  const [inAppAlerts, setInAppAlerts] = useState(() => getPref('inAppAlerts', true));
  const [alertSeverity, setAlertSeverity] = useState({ critical: true, warning: true, info: false });

  // --- General Settings State (localStorage) ---
  const [theme, setTheme] = useState(() => getPref('theme', 'light'));
  const [language, setLanguage] = useState(() => getPref('language', 'en'));
  const [dateFormat, setDateFormat] = useState(() => getPref('dateFormat', '12h'));
  const [timezone, setTimezone] = useState(() => getPref('timezone', 'utc'));

  // --- Monitoring State (localStorage) ---
  const [refreshInterval, setRefreshInterval] = useState(() => getPref('refreshInterval', '5'));
  const [cpuThreshold, setCpuThreshold] = useState(() => getPref('cpuThreshold', 90));
  const [ramThreshold, setRamThreshold] = useState(() => getPref('ramThreshold', 85));
  const [diskThreshold, setDiskThreshold] = useState(() => getPref('diskThreshold', 95));
  const [alertFrequency, setAlertFrequency] = useState(() => getPref('alertFrequency', 'immediate'));

  useEffect(() => { setPref('theme', theme); }, [theme]);
  useEffect(() => { setPref('language', language); }, [language]);
  useEffect(() => { setPref('dateFormat', dateFormat); }, [dateFormat]);
  useEffect(() => { setPref('timezone', timezone); }, [timezone]);
  useEffect(() => { setPref('refreshInterval', refreshInterval); }, [refreshInterval]);
  useEffect(() => { setPref('cpuThreshold', cpuThreshold); }, [cpuThreshold]);
  useEffect(() => { setPref('ramThreshold', ramThreshold); }, [ramThreshold]);
  useEffect(() => { setPref('diskThreshold', diskThreshold); }, [diskThreshold]);
  useEffect(() => { setPref('alertFrequency', alertFrequency); }, [alertFrequency]);
  useEffect(() => { setPref('emailAlertsEnabled', emailAlertsEnabled); }, [emailAlertsEnabled]);
  useEffect(() => { setPref('inAppAlerts', inAppAlerts); }, [inAppAlerts]);

  useEffect(() => {
    if (activeTab === 'thresholds') fetchRules();
    if (activeTab === 'profile') fetchUser();
    if (activeTab === 'notifications') fetchRecipients();
  }, [activeTab]);

  // --- Profile ---
  const fetchUser = async () => {
    setProfileLoading(true);
    setProfileMessage('');
    try {
      const res = await getUser();
      setUser(res.data || res);
    } catch (err) {
      console.error('Failed to load user:', err);
      setProfileMessage('Failed to load user profile.');
    } finally {
      setProfileLoading(false);
    }
  };

  const handleChangePassword = async () => {
    if (!passwords.current || !passwords.new || !passwords.confirm) {
      setPasswordMessage('Please fill in all password fields.');
      return;
    }
    if (passwords.new !== passwords.confirm) {
      setPasswordMessage('New passwords do not match.');
      return;
    }
    try {
      await api.post('/auth/change-password', {
        current_password: passwords.current,
        new_password: passwords.new,
        new_password_confirmation: passwords.confirm,
      });
      setPasswordMessage('Password updated successfully.');
      setPasswords({ current: '', new: '', confirm: '' });
    } catch (err) {
      if (err.response && err.response.status === 404) {
        setPasswordMessage('Change password endpoint not yet implemented on the server.');
      } else {
        setPasswordMessage(err.response?.data?.message || 'Failed to update password.');
      }
    }
    setTimeout(() => setPasswordMessage(''), 5000);
  };

  // --- Notifications ---
  const fetchRecipients = async () => {
    setRecipientsLoading(true);
    setNotifMessage('');
    try {
      const res = await getEmailRecipients();
      setRecipients(res.data || []);
      try {
        const notifRes = await getNotificationSettings();
        const nd = notifRes.data || notifRes;
        if (nd.email_alerts_enabled !== undefined) setEmailAlertsEnabled(nd.email_alerts_enabled);
        if (nd.in_app_alerts !== undefined) setInAppAlerts(nd.in_app_alerts);
        if (nd.severity_triggers) setAlertSeverity(nd.severity_triggers);
      } catch (_) { /* notification settings endpoint is optional */ }
    } catch (err) {
      console.error('Failed to load recipients:', err);
      setNotifMessage('Failed to load email recipients.');
      setNotifSuccess(false);
    } finally {
      setRecipientsLoading(false);
    }
  };

  const handleAddEmail = async () => {
    if (!newEmail) return;
    setNotifMessage('');
    try {
      const res = await addEmailRecipient(newEmail, newName);
      const added = res.data || { id: Date.now(), email: newEmail, name: newName, is_active: true };
      setRecipients(prev => [...prev, added]);
      setNewEmail('');
      setNewName('');
      setNotifSuccess(true);
      setNotifMessage('Recipient added successfully.');
    } catch (err) {
      console.error('Failed to add recipient:', err);
      setNotifSuccess(false);
      setNotifMessage(err.response?.data?.message || 'Failed to add recipient.');
    }
    setTimeout(() => setNotifMessage(''), 3000);
  };

  const handleRemoveRecipient = async (id) => {
    setNotifMessage('');
    try {
      await removeEmailRecipient(id);
      setRecipients(prev => prev.filter(r => r.id !== id));
      setNotifSuccess(true);
      setNotifMessage('Recipient removed successfully.');
    } catch (err) {
      console.error('Failed to remove recipient:', err);
      setNotifSuccess(false);
      setNotifMessage(err.response?.data?.message || 'Failed to remove recipient.');
    }
    setTimeout(() => setNotifMessage(''), 3000);
  };

  const handleToggleRecipient = async (recip) => {
    try {
      await updateEmailRecipient(recip.id, { is_active: !recip.is_active });
      setRecipients(prev => prev.map(r => r.id === recip.id ? { ...r, is_active: !r.is_active } : r));
    } catch (err) {
      console.error('Failed to toggle recipient:', err);
    }
  };

  // --- Alert Rules ---
  const fetchRules = async () => {
    setRulesLoading(true);
    try {
      const res = await getAlertRules();
      setAlertRules(res.data || []);
    } catch (err) {
      console.error('Failed to load alert rules:', err);
    } finally {
      setRulesLoading(false);
    }
  };

  const handleRuleToggle = async (rule) => {
    try {
      await updateAlertRule(rule.id, { is_enabled: !rule.is_enabled });
      setAlertRules(prev => prev.map(r => r.id === rule.id ? { ...r, is_enabled: !r.is_enabled } : r));
    } catch (err) {
      console.error('Failed to toggle rule:', err);
    }
  };

  const handleRuleSave = async (rule) => {
    setRulesSaving(true);
    setRulesMessage('');
    try {
      await updateAlertRule(rule.id, {
        value: rule.value,
        severity: rule.severity,
        is_enabled: rule.is_enabled,
        consecutive_count: rule.consecutive_count,
        cooldown_minutes: rule.cooldown_minutes,
      });
      setRulesMessage('Rule updated successfully.');
      setTimeout(() => setRulesMessage(''), 3000);
    } catch (err) {
      setRulesMessage('Failed to update rule.');
      console.error('Failed to update rule:', err);
    } finally {
      setRulesSaving(false);
    }
  };

  const handleRuleChange = (id, field, value) => {
    setAlertRules(prev => prev.map(r => r.id === id ? { ...r, [field]: value } : r));
  };

  return (
    <div className="container-fluid p-0">
      {/* Page Header */}
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
          <h3 className="text-dark-blue fw-bold mb-1">System Settings</h3>
          <nav aria-label="breadcrumb">
            <ol className="breadcrumb mb-0 small">
              <li className="breadcrumb-item"><Link to="/dashboard" className="text-decoration-none">Home</Link></li>
              <li className="breadcrumb-item active" aria-current="page">Settings</li>
            </ol>
          </nav>
        </div>
        <div>
          <button className="btn btn-primary d-flex align-items-center">
            <FaSave className="me-2" /> Save All Changes
          </button>
        </div>
      </div>

      <div className="row g-4">
        {/* Settings Navigation Menu (Vertical Pills) */}
        <div className="col-12 col-md-4 col-xl-3">
          <div className="card glass-card border-0 mb-4">
            <div className="card-body p-3">
              <div className="nav flex-column nav-pills" role="tablist" aria-orientation="vertical">
                <button 
                  className={`nav-link text-start py-3 mb-2 fw-semibold d-flex align-items-center ${activeTab === 'profile' ? 'active' : 'text-dark'}`}
                  onClick={() => setActiveTab('profile')}
                >
                  <FaUser className="me-3" /> Profile & Account
                </button>
                <button 
                  className={`nav-link text-start py-3 mb-2 fw-semibold d-flex align-items-center ${activeTab === 'notifications' ? 'active' : 'text-dark'}`}
                  onClick={() => setActiveTab('notifications')}
                >
                  <FaBell className="me-3" /> Notification & Alerts
                </button>
                <button 
                  className={`nav-link text-start py-3 mb-2 fw-semibold d-flex align-items-center ${activeTab === 'general' ? 'active' : 'text-dark'}`}
                  onClick={() => setActiveTab('general')}
                >
                  <FaCog className="me-3" /> General Settings
                </button>
                <button 
                  className={`nav-link text-start py-3 mb-2 fw-semibold d-flex align-items-center ${activeTab === 'thresholds' ? 'active' : 'text-dark'}`}
                  onClick={() => setActiveTab('thresholds')}
                >
                  <FaSlidersH className="me-3" /> Alert Thresholds
                </button>
                <button 
                  className={`nav-link text-start py-3 mb-2 fw-semibold d-flex align-items-center ${activeTab === 'monitoring' ? 'active' : 'text-dark'}`}
                  onClick={() => setActiveTab('monitoring')}
                >
                  <FaChartLine className="me-3" /> Monitoring Preferences
                </button>
                <button 
                  className={`nav-link text-start py-3 fw-semibold d-flex align-items-center ${activeTab === 'security' ? 'active' : 'text-dark'}`}
                  onClick={() => setActiveTab('security')}
                >
                  <FaShieldAlt className="me-3" /> Security Settings
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Settings Content Area */}
        <div className="col-12 col-md-8 col-xl-9">
          <div className="card glass-card border-0 h-100">
            <div className="card-body p-4">
              
              {/* Profile & Account Settings */}
              {activeTab === 'profile' && (
                <div className="fade show active">
                  <h5 className="fw-bold text-dark-blue mb-4 border-bottom pb-3">Profile & Account</h5>
                  {profileMessage && (
                    <div className={`alert ${profileMessage.includes('Failed') ? 'alert-danger' : 'alert-info'} py-2 small`}>
                      {profileMessage}
                    </div>
                  )}
                  {profileLoading ? (
                    <div className="text-center py-4">
                      <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Loading...</span>
                      </div>
                    </div>
                  ) : (
                  <div className="row g-5">
                    <div className="col-12 col-xl-6">
                      <h6 className="fw-bold mb-3">Public Profile</h6>
                      <div className="d-flex align-items-center mb-4">
                        <div className="position-relative">
                          {user?.avatar ? (
                            <img src={user.avatar} alt="avatar" className="rounded-circle" style={{ width: '100px', height: '100px', objectFit: 'cover' }} />
                          ) : (
                            <div className="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center text-primary fs-1" style={{ width: '100px', height: '100px' }}>
                              <FaUser />
                            </div>
                          )}
                          <button className="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle" style={{ width: '32px', height: '32px', padding: '0' }}>
                            <FaCamera />
                          </button>
                        </div>
                        <div className="ms-4">
                          <button className="btn btn-outline-primary btn-sm mb-2">Upload New Photo</button>
                          <div className="small text-muted">JPG or PNG no larger than 2MB</div>
                        </div>
                      </div>
                      
                      <div className="mb-3">
                        <label className="form-label fw-semibold">Full Name</label>
                        <input type="text" className="form-control" value={user?.name || ''} onChange={e => setUser({...user, name: e.target.value})} />
                      </div>
                      <div className="mb-3">
                        <label className="form-label fw-semibold">Email Address</label>
                        <input type="email" className="form-control" value={user?.email || ''} onChange={e => setUser({...user, email: e.target.value})} />
                      </div>
                      <div className="mb-3">
                        <label className="form-label fw-semibold">Phone</label>
                        <input type="text" className="form-control" value={user?.phone || ''} onChange={e => setUser({...user, phone: e.target.value})} />
                      </div>
                      <div className="mb-3">
                        <label className="form-label fw-semibold">Mobile Number</label>
                        <input type="text" className="form-control" value={user?.mobile_number || ''} onChange={e => setUser({...user, mobile_number: e.target.value})} />
                      </div>
                    </div>
                    
                    <div className="col-12 col-xl-6">
                      <h6 className="fw-bold mb-3">Account Details</h6>
                      <div className="mb-3">
                        <label className="form-label fw-semibold">Role</label>
                        <input type="text" className="form-control" value={user?.roles?.[0]?.name || '—'} disabled />
                      </div>
                      <div className="mb-3">
                        <label className="form-label fw-semibold">Company</label>
                        <input type="text" className="form-control" value={user?.company_id || '—'} disabled />
                      </div>
                      <div className="mb-3">
                        <label className="form-label fw-semibold">Last Login</label>
                        <input type="text" className="form-control" value={user?.last_login_at ? new Date(user.last_login_at).toLocaleString() : '—'} disabled />
                      </div>

                      <h6 className="fw-bold mb-3 mt-4">Change Password</h6>
                      {passwordMessage && (
                        <div className={`alert ${passwordMessage.includes('successfully') ? 'alert-success' : 'alert-warning'} py-2 small`}>
                          {passwordMessage}
                        </div>
                      )}
                      <div className="mb-3">
                        <label className="form-label fw-semibold">Current Password</label>
                        <input type="password" className="form-control" value={passwords.current} onChange={e => setPasswords({...passwords, current: e.target.value})} />
                      </div>
                      <div className="mb-3">
                        <label className="form-label fw-semibold">New Password</label>
                        <input type="password" className="form-control" value={passwords.new} onChange={e => setPasswords({...passwords, new: e.target.value})} />
                      </div>
                      <div className="mb-3">
                        <label className="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" className="form-control" value={passwords.confirm} onChange={e => setPasswords({...passwords, confirm: e.target.value})} />
                      </div>
                      <button className="btn btn-secondary w-100 mt-2" onClick={handleChangePassword}>Update Password</button>
                    </div>
                  </div>
                  )}
                </div>
              )}

              {/* Notification Settings */}
              {activeTab === 'notifications' && (
                <div className="fade show active">
                  <h5 className="fw-bold text-dark-blue mb-4 border-bottom pb-3">Notification & Alerts</h5>
                  
                  {notifMessage && (
                    <div className={`alert ${notifSuccess ? 'alert-success' : 'alert-danger'} py-2 small`}>
                      {notifMessage}
                    </div>
                  )}

                  <div className="mb-5">
                    <div className="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                      <div>
                        <h6 className="mb-1 fw-bold">Email Alerts</h6>
                        <small className="text-muted">Send automated emails when alerts are triggered.</small>
                      </div>
                      <div className="form-check form-switch">
                        <input 
                          className="form-check-input" 
                          type="checkbox" 
                          role="switch" 
                          checked={emailAlertsEnabled} 
                          onChange={(e) => setEmailAlertsEnabled(e.target.checked)}
                          style={{ width: '40px', height: '20px' }} 
                        />
                      </div>
                    </div>

                    <div style={{ opacity: emailAlertsEnabled ? 1 : 0.5, pointerEvents: emailAlertsEnabled ? 'auto' : 'none' }}>
                      <h6 className="fw-bold mb-3">Email Recipients</h6>
                      <p className="text-muted small">These addresses will receive all configured alerts.</p>
                      
                      <div className="d-flex gap-2 mb-3">
                        <input 
                          type="text" 
                          className="form-control w-25" 
                          placeholder="Recipient name..." 
                          value={newName}
                          onChange={e => setNewName(e.target.value)}
                        />
                        <input 
                          type="email" 
                          className="form-control w-25" 
                          placeholder="Add new email address..." 
                          value={newEmail}
                          onChange={e => setNewEmail(e.target.value)}
                        />
                        <button className="btn btn-primary d-flex align-items-center" onClick={handleAddEmail} disabled={recipientsLoading}>
                          <FaPlus className="me-2" /> Add
                        </button>
                      </div>

                      {recipientsLoading ? (
                        <div className="text-center py-3">
                          <div className="spinner-border spinner-border-sm text-primary" role="status">
                            <span className="visually-hidden">Loading...</span>
                          </div>
                        </div>
                      ) : (
                      <div className="list-group w-75 mb-4">
                        {recipients.length === 0 ? (
                          <div className="text-muted small py-2">No email recipients configured.</div>
                        ) : (
                        recipients.map(recip => (
                          <div key={recip.id} className="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                            <div className="d-flex align-items-center gap-3">
                              <div className="form-check form-switch mb-0">
                                <input
                                  className="form-check-input"
                                  type="checkbox"
                                  role="switch"
                                  checked={recip.is_active}
                                  onChange={() => handleToggleRecipient(recip)}
                                  style={{ width: '36px', height: '18px' }}
                                />
                              </div>
                              <div>
                                <span className="fw-semibold text-dark-blue">{recip.email}</span>
                                {recip.name && <small className="text-muted ms-2">({recip.name})</small>}
                              </div>
                            </div>
                            <button className="btn btn-link text-danger p-0" onClick={() => handleRemoveRecipient(recip.id)}>
                              <FaTrash />
                            </button>
                          </div>
                        ))
                        )}
                      </div>
                      )}

                      <h6 className="fw-bold mb-3">Alert Severity Triggers</h6>
                      <p className="text-muted small">Select which alert severities should trigger an email.</p>
                      <div className="d-flex flex-column gap-2">
                        <div className="form-check">
                          <input className="form-check-input" type="checkbox" id="sevCritical" checked={alertSeverity.critical} onChange={e => setAlertSeverity({...alertSeverity, critical: e.target.checked})} />
                          <label className="form-check-label fw-semibold text-danger" htmlFor="sevCritical">Critical Alerts</label>
                        </div>
                        <div className="form-check">
                          <input className="form-check-input" type="checkbox" id="sevWarning" checked={alertSeverity.warning} onChange={e => setAlertSeverity({...alertSeverity, warning: e.target.checked})} />
                          <label className="form-check-label fw-semibold text-warning" htmlFor="sevWarning">Warning Alerts</label>
                        </div>
                        <div className="form-check">
                          <input className="form-check-input" type="checkbox" id="sevInfo" checked={alertSeverity.info} onChange={e => setAlertSeverity({...alertSeverity, info: e.target.checked})} />
                          <label className="form-check-label fw-semibold text-info" htmlFor="sevInfo">Informational Alerts</label>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <h5 className="fw-bold text-dark-blue mb-4 border-bottom pb-3 pt-3">Other Notifications</h5>
                  <div className="list-group list-group-flush">
                    <div className="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-0 bg-transparent">
                      <div>
                        <h6 className="mb-1 fw-bold">In-App System Alerts</h6>
                        <small className="text-muted">Show a popup banner when a new alert is generated.</small>
                      </div>
                      <div className="form-check form-switch">
                        <input 
                          className="form-check-input" 
                          type="checkbox" 
                          role="switch" 
                          checked={inAppAlerts}
                          onChange={(e) => setInAppAlerts(e.target.checked)}
                          style={{ width: '40px', height: '20px' }} 
                        />
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* General Settings */}
              {activeTab === 'general' && (
                <div className="fade show active">
                  <h5 className="fw-bold text-dark-blue mb-4 border-bottom pb-3">General Settings</h5>
                  <p className="text-muted small mb-3">These preferences are stored locally on your browser.</p>
                  <div className="row g-4">
                    <div className="col-12 col-lg-6">
                      <label className="form-label fw-semibold">Application Theme</label>
                      <select className="form-select" value={theme} onChange={e => setTheme(e.target.value)}>
                        <option value="light">Light Mode (Default)</option>
                        <option value="dark">Dark Mode</option>
                        <option value="system">System Default</option>
                      </select>
                    </div>
                    <div className="col-12 col-lg-6">
                      <label className="form-label fw-semibold">Language Selection</label>
                      <select className="form-select" value={language} onChange={e => setLanguage(e.target.value)}>
                        <option value="en">English (US)</option>
                        <option value="es">Español</option>
                        <option value="fr">Français</option>
                      </select>
                    </div>
                    <div className="col-12 col-lg-6">
                      <label className="form-label fw-semibold">Date & Time Format</label>
                      <select className="form-select" value={dateFormat} onChange={e => setDateFormat(e.target.value)}>
                        <option value="12h">MM/DD/YYYY - 12 Hour (AM/PM)</option>
                        <option value="24h">YYYY-MM-DD - 24 Hour</option>
                      </select>
                    </div>
                    <div className="col-12 col-lg-6">
                      <label className="form-label fw-semibold">Time Zone</label>
                      <select className="form-select" value={timezone} onChange={e => setTimezone(e.target.value)}>
                        <option value="utc">UTC (Universal Coordinated Time)</option>
                        <option value="est">EST (Eastern Standard Time)</option>
                        <option value="pst">PST (Pacific Standard Time)</option>
                      </select>
                    </div>
                  </div>
                </div>
              )}

              {/* Alert Thresholds */}
              {activeTab === 'thresholds' && (
                <div className="fade show active">
                  <h5 className="fw-bold text-dark-blue mb-4 border-bottom pb-3">Alert Thresholds</h5>
                  {rulesMessage && (
                    <div className={`alert ${rulesMessage.includes('success') ? 'alert-success' : 'alert-danger'} py-2 small`}>
                      {rulesMessage}
                    </div>
                  )}
                  {rulesLoading ? (
                    <div className="text-center py-4">
                      <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Loading...</span>
                      </div>
                    </div>
                  ) : alertRules.length === 0 ? (
                    <div className="text-muted text-center py-4">No alert rules configured.</div>
                  ) : (
                    <div className="table-responsive">
                      <table className="table table-borderless align-middle">
                        <thead className="table-light text-muted small">
                          <tr>
                            <th>Rule Name</th>
                            <th>Description</th>
                            <th>Metric</th>
                            <th>Operator</th>
                            <th>Threshold Value</th>
                            <th>Severity</th>
                            <th>Consecutive</th>
                            <th>Cooldown (min)</th>
                            <th className="text-center">Enabled</th>
                            <th className="text-end">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          {alertRules.map(rule => (
                            <tr key={rule.id}>
                              <td className="fw-semibold">{rule.name}</td>
                              <td className="text-muted small" style={{ maxWidth: '180px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={rule.description}>
                                {rule.description || '—'}
                              </td>
                              <td className="text-muted">{rule.metric}</td>
                              <td>{rule.operator}</td>
                              <td>
                                <input
                                  type="text"
                                  className="form-control form-control-sm"
                                  style={{ width: '90px' }}
                                  value={rule.value || ''}
                                  onChange={e => handleRuleChange(rule.id, 'value', e.target.value)}
                                />
                              </td>
                              <td>
                                <select
                                  className="form-select form-select-sm"
                                  style={{ width: '110px' }}
                                  value={rule.severity}
                                  onChange={e => handleRuleChange(rule.id, 'severity', e.target.value)}
                                >
                                  <option value="info">Info</option>
                                  <option value="warning">Warning</option>
                                  <option value="critical">Critical</option>
                                </select>
                              </td>
                              <td>
                                <input
                                  type="number"
                                  className="form-control form-control-sm"
                                  style={{ width: '65px' }}
                                  value={rule.consecutive_count ?? ''}
                                  onChange={e => handleRuleChange(rule.id, 'consecutive_count', e.target.value === '' ? '' : Number(e.target.value))}
                                  min="1"
                                />
                              </td>
                              <td>
                                <input
                                  type="number"
                                  className="form-control form-control-sm"
                                  style={{ width: '65px' }}
                                  value={rule.cooldown_minutes ?? ''}
                                  onChange={e => handleRuleChange(rule.id, 'cooldown_minutes', e.target.value === '' ? '' : Number(e.target.value))}
                                  min="0"
                                />
                              </td>
                              <td className="text-center">
                                <div className="form-check form-switch d-inline-block">
                                  <input
                                    className="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    checked={rule.is_enabled}
                                    onChange={() => handleRuleToggle(rule)}
                                    style={{ width: '36px', height: '18px' }}
                                  />
                                </div>
                              </td>
                              <td className="text-end">
                                <button
                                  className="btn btn-sm btn-primary"
                                  onClick={() => handleRuleSave(rule)}
                                  disabled={rulesSaving}
                                >
                                  {rulesSaving ? 'Saving...' : 'Save'}
                                </button>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              )}

              {/* Monitoring Settings */}
              {activeTab === 'monitoring' && (
                <div className="fade show active">
                  <h5 className="fw-bold text-dark-blue mb-4 border-bottom pb-3">Monitoring Preferences</h5>
                  <p className="text-muted small mb-3">These preferences are stored locally on your browser.</p>
                  <div className="row g-4">
                    <div className="col-12">
                      <label className="form-label fw-semibold">Data Refresh Interval</label>
                      <select className="form-select w-50" value={refreshInterval} onChange={e => setRefreshInterval(e.target.value)}>
                        <option value="5">Every 5 Seconds (Real-time)</option>
                        <option value="15">Every 15 Seconds</option>
                        <option value="60">Every 1 Minute</option>
                      </select>
                      <div className="form-text">How often the Live Monitoring charts pull new data.</div>
                    </div>
                    <div className="col-12 col-md-4">
                      <label className="form-label fw-semibold">CPU Alert Threshold (%)</label>
                      <input type="number" className="form-control" value={cpuThreshold} onChange={e => setCpuThreshold(Number(e.target.value))} min="50" max="100" />
                    </div>
                    <div className="col-12 col-md-4">
                      <label className="form-label fw-semibold">RAM Alert Threshold (%)</label>
                      <input type="number" className="form-control" value={ramThreshold} onChange={e => setRamThreshold(Number(e.target.value))} min="50" max="100" />
                    </div>
                    <div className="col-12 col-md-4">
                      <label className="form-label fw-semibold">Disk Alert Threshold (%)</label>
                      <input type="number" className="form-control" value={diskThreshold} onChange={e => setDiskThreshold(Number(e.target.value))} min="50" max="100" />
                    </div>
                    <div className="col-12">
                      <label className="form-label fw-semibold">Alert Frequency</label>
                      <select className="form-select w-50" value={alertFrequency} onChange={e => setAlertFrequency(e.target.value)}>
                        <option value="immediate">Immediate (On occurrence)</option>
                        <option value="5min">Batch every 5 minutes</option>
                        <option value="hourly">Hourly digest</option>
                      </select>
                    </div>
                  </div>
                </div>
              )}

              {/* Security Settings */}
              {activeTab === 'security' && (
                <div className="fade show active">
                  <h5 className="fw-bold text-dark-blue mb-4 border-bottom pb-3">Security Settings</h5>
                  <p className="text-muted small mb-3">This section shows example UI; local-only preferences.</p>
                  
                  <div className="mb-4">
                    <h6 className="fw-bold mb-3">Two-Factor Authentication (2FA)</h6>
                    <div className="p-3 bg-transparent border border-light rounded d-flex justify-content-between align-items-center">
                      <div>
                        <span className="badge bg-secondary mb-2">Disabled</span>
                        <p className="mb-0 small text-muted">Add an extra layer of security to your account by enabling 2FA.</p>
                      </div>
                      <button className="btn btn-outline-primary btn-sm">Enable 2FA</button>
                    </div>
                  </div>

                  <div className="mb-4">
                    <h6 className="fw-bold mb-3">Active Sessions</h6>
                    <ul className="list-group">
                      <li className="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                        <div>
                          <strong>Windows 11 - Chrome</strong>
                          <div className="text-muted small">IP: 192.168.1.10 • Current Session</div>
                        </div>
                      </li>
                      <li className="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                        <div>
                          <strong>iOS 16 - Safari</strong>
                          <div className="text-muted small">IP: 10.0.5.42 • Last active: 2 days ago</div>
                        </div>
                        <button className="btn btn-sm btn-outline-danger">Revoke</button>
                      </li>
                    </ul>
                  </div>

                  <div>
                    <h6 className="fw-bold mb-3">Recent Login Activity</h6>
                    <div className="table-responsive">
                      <table className="table table-sm table-borderless small">
                        <thead>
                          <tr className="text-muted border-bottom">
                            <th>Date & Time</th>
                            <th>IP Address</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td>Today, 08:45 AM</td>
                            <td>192.168.1.10</td>
                            <td className="text-success">Successful</td>
                          </tr>
                          <tr>
                            <td>Yesterday, 06:30 PM</td>
                            <td>192.168.1.55</td>
                            <td className="text-danger">Failed Password</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                </div>
              )}

            </div>
          </div>
        </div>
      </div>

    </div>
  );
};

export default Settings;
