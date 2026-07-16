import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FaUserPlus } from 'react-icons/fa';
import { registerUser } from '../../services/auth';
import './Register.css';

const Register = () => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleRegister = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const result = await registerUser(name, email, password);
      if (result.token) {
        localStorage.setItem('auth_token', result.token);
        navigate('/');
      } else if (result.success) {
        navigate('/login');
      }
    } catch (err) {
      setError(err?.message || 'Registration failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="text-center mb-3 fs-5 fw-bold d-flex align-items-center justify-content-center" style={{ color: 'var(--primary-blue)' }}>
          <FaUserPlus className="me-2" />
          DeskGuard
        </div>
        
        <h5 className="fw-bold text-center mb-1" style={{ color: 'var(--text-body)' }}>Sign Up</h5>
        <p className="text-center text-muted mb-3" style={{ fontSize: '0.85rem' }}>Create a new account.</p>

        {error && <div className="alert alert-danger py-2 small text-center mb-2">{error}</div>}

        <form onSubmit={handleRegister}>
          <div className="mb-2">
            <label className="form-label fw-semibold mb-1" style={{ fontSize: '0.8rem', color: 'var(--text-body)' }}>Name</label>
            <input 
              type="text" 
              className="form-control form-control-sm py-2" 
              placeholder="John Doe"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
              style={{ backgroundColor: 'var(--bg-input)', borderColor: 'var(--border-color)' }}
            />
          </div>

          <div className="mb-2">
            <label className="form-label fw-semibold mb-1" style={{ fontSize: '0.8rem', color: 'var(--text-body)' }}>Email</label>
            <input 
              type="email" 
              className="form-control form-control-sm py-2" 
              placeholder="john@example.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              style={{ backgroundColor: 'var(--bg-input)', borderColor: 'var(--border-color)' }}
            />
          </div>

          <div className="mb-3">
            <label className="form-label fw-semibold mb-1" style={{ fontSize: '0.8rem', color: 'var(--text-body)' }}>Password</label>
            <input 
              type="password" 
              className="form-control form-control-sm py-2" 
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              style={{ backgroundColor: 'var(--bg-input)', borderColor: 'var(--border-color)' }}
            />
          </div>



          <button type="submit" className="btn btn-primary w-100 py-2 mb-3 fw-semibold text-white" disabled={loading}>
            {loading ? 'Creating Account...' : 'Sign Up'}
          </button>

          <div className="text-center small text-muted">
            Already have an account? <Link to="/login" className="text-decoration-none fw-semibold" style={{ color: 'var(--primary-blue)' }}>Log in</Link>
          </div>
        </form>
      </div>
    </div>
  );
};

export default Register;
