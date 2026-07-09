import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { FaKey } from 'react-icons/fa';
import './Register.css';

const ForgotPassword = () => {
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);

  const handleReset = (e) => {
    e.preventDefault();
    setSubmitted(true);
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="text-center mb-4 fs-4 fw-bold d-flex align-items-center justify-content-center" style={{ color: 'var(--primary-blue)' }}>
          <FaKey className="me-2" />
          DeskGuard
        </div>

        <h4 className="fw-bold text-center" style={{ color: 'var(--text-body)' }}>Reset Password</h4>
        <p className="text-center text-muted mb-4">
          Enter your email and we'll send you a link to reset your password.
        </p>

        {!submitted ? (
          <form onSubmit={handleReset}>
            <div className="mb-4">
              <label className="form-label fw-semibold" style={{ color: 'var(--text-body)' }}>Email Address</label>
              <input 
                type="email" 
                className="form-control" 
                placeholder="eg. johnfrans@gmail.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                style={{ backgroundColor: 'var(--bg-input)', borderColor: 'var(--border-color)' }}
              />
            </div>

            <button type="submit" className="btn btn-primary w-100 py-2 mb-4 fw-semibold text-white">
              Send Reset Link
            </button>
          </form>
        ) : (
          <div className="alert bg-success bg-opacity-25 text-success border border-success border-opacity-50 text-center mb-4 p-4 rounded-3" role="alert">
            Password reset link sent to <strong>{email}</strong>.<br/>Please check your inbox.
          </div>
        )}

        <div className="text-center small text-muted">
          Remember your password? <Link to="/login" className="text-decoration-none fw-semibold" style={{ color: 'var(--primary-blue)' }}>Back to Login</Link>
        </div>
      </div>
    </div>
  );
};

export default ForgotPassword;
