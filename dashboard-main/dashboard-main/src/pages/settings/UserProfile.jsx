import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { FaUserCircle, FaEnvelope, FaPhone, FaShieldAlt, FaKey, FaSave, FaSpinner } from 'react-icons/fa';
import { getUser } from '../../services/auth';
import api from '../../services/api';

const UserProfile = () => {
  const [user, setUser] = useState(null);
  const [companyName, setCompanyName] = useState('');
  const [loading, setLoading] = useState(true);
  const [profileForm, setProfileForm] = useState({ name: '', email: '', phone: '', mobile_number: '' });
  const [profileSaving, setProfileSaving] = useState(false);
  const [profileMessage, setProfileMessage] = useState(null);
  const [passwordForm, setPasswordForm] = useState({ current_password: '', new_password: '', new_password_confirmation: '' });
  const [passwordSaving, setPasswordSaving] = useState(false);
  const [passwordMessage, setPasswordMessage] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const userData = await getUser();
        setUser(userData);
        setProfileForm({
          name: userData.name || '',
          email: userData.email || '',
          phone: userData.phone || '',
          mobile_number: userData.mobile_number || '',
        });
        if (userData.company_id) {
          try {
            const company = await api.get('/companies/' + userData.company_id);
            setCompanyName(company.name || '');
          } catch {
            setCompanyName('Unknown');
          }
        }
      } catch (err) {
        console.error('Failed to load user:', err);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, []);

  const handleProfileChange = (e) => {
    setProfileForm({ ...profileForm, [e.target.name]: e.target.value });
  };

  const handleProfileSubmit = async (e) => {
    e.preventDefault();
    setProfileSaving(true);
    setProfileMessage(null);
    try {
      await api.put('/auth/profile', profileForm);
      setProfileMessage({ type: 'success', text: 'Profile updated successfully.' });
      const userData = await getUser();
      setUser(userData);
    } catch (err) {
      setProfileMessage({ type: 'danger', text: err?.message || 'Failed to update profile.' });
    } finally {
      setProfileSaving(false);
    }
  };

  const handlePasswordChange = (e) => {
    setPasswordForm({ ...passwordForm, [e.target.name]: e.target.value });
  };

  const handlePasswordSubmit = async (e) => {
    e.preventDefault();
    setPasswordSaving(true);
    setPasswordMessage(null);
    try {
      await api.post('/auth/change-password', passwordForm);
      setPasswordMessage({ type: 'success', text: 'Password changed successfully.' });
      setPasswordForm({ current_password: '', new_password: '', new_password_confirmation: '' });
    } catch (err) {
      setPasswordMessage({ type: 'danger', text: err?.message || 'Failed to change password.' });
    } finally {
      setPasswordSaving(false);
    }
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleString(undefined, {
      year: 'numeric', month: 'short', day: 'numeric',
      hour: '2-digit', minute: '2-digit',
    });
  };

  if (loading) {
    return (
      <div className="container-fluid p-0 d-flex justify-content-center align-items-center" style={{ minHeight: '300px' }}>
        <FaSpinner className="text-primary fa-spin" size={40} />
      </div>
    );
  }

  const roleName = user?.roles?.[0]?.name || 'User';

  return (
    <div className="container-fluid p-0">
      {/* Page Header */}
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
          <h3 className="text-dark-blue fw-bold mb-1">User Profile</h3>
          <nav aria-label="breadcrumb">
            <ol className="breadcrumb mb-0 small">
              <li className="breadcrumb-item"><Link to="/dashboard" className="text-decoration-none">Home</Link></li>
              <li className="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
          </nav>
        </div>
      </div>

      <div className="row g-4">
        {/* Left Column: Profile Summary */}
        <div className="col-12 col-xl-4">
          <div className="card glass-card border-0 mb-4">
            <div className="card-body text-center p-4">
              <div className="position-relative d-inline-block mb-3">
                <FaUserCircle className="text-primary" style={{ fontSize: '100px' }} />
                <span className="position-absolute bottom-0 end-0 p-2 bg-success border border-light rounded-circle"></span>
              </div>
              <h4 className="fw-bold text-dark-blue mb-1">{user?.name || 'User'}</h4>
              <p className="text-muted mb-2">{user?.email || ''}</p>
              <span className="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2 rounded-pill">
                <FaShieldAlt className="me-1" /> {roleName}
              </span>
            </div>
            <div className="card-footer bg-transparent border-top border-light p-4">
              <ul className="list-unstyled mb-0">
                <li className="d-flex align-items-center mb-3">
                  <FaEnvelope className="text-muted me-3" />
                  <span className="text-dark">{user?.email || 'N/A'}</span>
                </li>
                <li className="d-flex align-items-center mb-3">
                  <FaPhone className="text-muted me-3" />
                  <span className="text-dark">{user?.phone || user?.mobile_number || 'N/A'}</span>
                </li>
                <li className="d-flex align-items-center">
                  <div className="text-muted small w-100 text-center border-top pt-3 mt-1">
                    Last Login: {formatDate(user?.last_login_at)}
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>

        {/* Right Column: Account Settings Form */}
        <div className="col-12 col-xl-8">
          <div className="card glass-card border-0 mb-4">
            <div className="card-header bg-transparent border-bottom border-light fw-bold text-dark-blue py-3">
              Edit Profile Information
            </div>
            <div className="card-body p-4">
              {profileMessage && (
                <div className={`alert alert-${profileMessage.type} alert-dismissible fade show`} role="alert">
                  {profileMessage.text}
                  <button type="button" className="btn-close" onClick={() => setProfileMessage(null)}></button>
                </div>
              )}
              <form onSubmit={handleProfileSubmit}>
                <div className="row g-3 mb-4">
                  <div className="col-md-6">
                    <label className="form-label text-muted small fw-semibold">Full Name</label>
                    <input type="text" className="form-control" name="name" value={profileForm.name} onChange={handleProfileChange} />
                  </div>
                  <div className="col-md-6">
                    <label className="form-label text-muted small fw-semibold">Username</label>
                    <input type="text" className="form-control" value={user?.email?.split('@')[0] || ''} disabled />
                    <div className="form-text">Username cannot be changed.</div>
                  </div>
                  <div className="col-md-6">
                    <label className="form-label text-muted small fw-semibold">Email Address</label>
                    <input type="email" className="form-control" name="email" value={profileForm.email} onChange={handleProfileChange} />
                  </div>
                  <div className="col-md-6">
                    <label className="form-label text-muted small fw-semibold">Phone Number</label>
                    <input type="tel" className="form-control" name="phone" value={profileForm.phone} onChange={handleProfileChange} />
                  </div>
                  <div className="col-12">
                    <label className="form-label text-muted small fw-semibold">Company / Department</label>
                    <input type="text" className="form-control" value={companyName} disabled />
                  </div>
                </div>
                <div className="d-flex justify-content-end">
                  <button type="submit" className="btn btn-primary d-flex align-items-center" disabled={profileSaving}>
                    {profileSaving ? <FaSpinner className="fa-spin me-2" /> : <FaSave className="me-2" />} Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>

          {/* Change Password Section */}
          <div className="card glass-card border-0">
            <div className="card-header bg-transparent border-bottom border-light fw-bold text-dark-blue py-3 d-flex align-items-center">
              <FaKey className="text-muted me-2" /> Change Password
            </div>
            <div className="card-body p-4">
              {passwordMessage && (
                <div className={`alert alert-${passwordMessage.type} alert-dismissible fade show`} role="alert">
                  {passwordMessage.text}
                  <button type="button" className="btn-close" onClick={() => setPasswordMessage(null)}></button>
                </div>
              )}
              <form onSubmit={handlePasswordSubmit}>
                <div className="row g-3 mb-4">
                  <div className="col-12">
                    <label className="form-label text-muted small fw-semibold">Current Password</label>
                    <input type="password" className="form-control" name="current_password" value={passwordForm.current_password} onChange={handlePasswordChange} placeholder="Enter current password" required />
                  </div>
                  <div className="col-md-6">
                    <label className="form-label text-muted small fw-semibold">New Password</label>
                    <input type="password" className="form-control" name="new_password" value={passwordForm.new_password} onChange={handlePasswordChange} placeholder="Enter new password" required />
                  </div>
                  <div className="col-md-6">
                    <label className="form-label text-muted small fw-semibold">Confirm New Password</label>
                    <input type="password" className="form-control" name="new_password_confirmation" value={passwordForm.new_password_confirmation} onChange={handlePasswordChange} placeholder="Confirm new password" required />
                  </div>
                </div>
                <div className="d-flex justify-content-end">
                  <button type="submit" className="btn btn-danger d-flex align-items-center" disabled={passwordSaving}>
                    {passwordSaving ? <FaSpinner className="fa-spin me-2" /> : <FaKey className="me-2" />} Update Password
                  </button>
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>
    </div>
  );
};

export default UserProfile;
