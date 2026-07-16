import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FaLock } from 'react-icons/fa';
import { useAuth } from '../../context/AuthContext';
import './Register.css';

const Login = () => {
  const [email, setEmail] = useState('kiranbalasopatil33@gmail.com');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const { login, loading } = useAuth();
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();
    setError('');
    try {
      await login(email, password);
      navigate('/dashboard', { replace: true });
    } catch (err) {
      setError(err?.message || err?.data?.message || 'Login failed. Please check your credentials.');
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="text-center mb-4 fs-4 fw-bold d-flex align-items-center justify-content-center" style={{ color: 'var(--primary-blue)' }}>
          <FaLock className="me-2" />
          DeskGuard
        </div>

        <h4 className="fw-bold text-center" style={{ color: 'var(--text-body)' }}>Welcome Back</h4>
        <p className="text-center text-muted mb-4">Please enter your details to sign in.</p>

        {error && (
          <div className="alert alert-danger py-2 small" role="alert">
            {error}
          </div>
        )}

        <form onSubmit={handleLogin}>
          <div className="mb-3">
            <label className="form-label fw-semibold" style={{ color: 'var(--text-body)' }}>Email Address</label>
            <input 
              type="email" 
              className="form-control" 
              placeholder="Enter your email" 
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              style={{ backgroundColor: 'var(--bg-input)', borderColor: 'var(--border-color)' }}
            />
          </div>

          <div className="mb-4">
            <div className="d-flex justify-content-between">
              <label className="form-label fw-semibold" style={{ color: 'var(--text-body)' }}>Password</label>
              <Link to="/forgot-password" className="small text-decoration-none" style={{ color: 'var(--primary-blue)' }}>Forgot password?</Link>
            </div>
            <input 
              type="password" 
              className="form-control" 
              placeholder="Enter your password" 
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              style={{ backgroundColor: 'var(--bg-input)', borderColor: 'var(--border-color)' }}
            />
          </div>

          <div className="mb-4 form-check">
            <input type="checkbox" className="form-check-input border-secondary" id="rememberMe" />
            <label className="form-check-label small text-muted" htmlFor="rememberMe">Remember me</label>
          </div>

          <button type="submit" className="btn btn-primary w-100 py-2 mb-4 fw-semibold text-white" disabled={loading}>
            {loading ? 'Signing In...' : 'Sign In'}
          </button>

          <div className="text-center small text-muted">
            Don't have an account? <Link to="/register" className="text-decoration-none fw-semibold" style={{ color: 'var(--primary-blue)' }}>Sign up</Link>
          </div>
        </form>
      </div>
    </div>
  );
};

export default Login;
