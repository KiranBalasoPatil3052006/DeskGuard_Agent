import React from 'react';

// --- Badge ---
export const Badge = ({ children, variant = 'primary', className = '' }) => (
  <span className={`badge bg-${variant} ${className}`}>
    {children}
  </span>
);

// --- ProgressBar ---
export const ProgressBar = ({ value = 0, variant = 'primary', label = '', className = '' }) => (
  <div className={`w-100 ${className}`}>
    {label && <div className="d-flex justify-content-between mb-1 small text-muted">
      <span>{label}</span>
      <span>{value}%</span>
    </div>}
    <div className="progress" style={{ height: '8px' }}>
      <div 
        className={`progress-bar bg-${variant}`} 
        role="progressbar" 
        style={{ width: `${value}%` }}
        aria-valuenow={value} 
        aria-valuemin="0" 
        aria-valuemax="100"
      ></div>
    </div>
  </div>
);

// --- Skeleton ---
export const Skeleton = ({ width = '100%', height = '20px', className = '', circle = false }) => (
  <div 
    className={`placeholder-glow ${className}`} 
    style={{ width, height, borderRadius: circle ? '50%' : '4px', opacity: 0.5 }}
  >
    <span className="placeholder w-100 h-100 d-inline-block bg-secondary rounded"></span>
  </div>
);

// --- Timeline ---
export const Timeline = ({ events = [] }) => (
  <div className="position-relative py-2">
    <div className="position-absolute top-0 bottom-0 start-0 border-start border-secondary border-opacity-25 ms-3" style={{ width: '1px' }}></div>
    {events.map((ev, idx) => (
      <div className="d-flex mb-4 position-relative" key={idx}>
        <div 
          className="bg-white border rounded-circle d-flex align-items-center justify-content-center shadow-sm"
          style={{ width: '32px', height: '32px', zIndex: 1, marginLeft: '11px', flexShrink: 0 }}
        >
          {ev.icon}
        </div>
        <div className="ms-4 flex-grow-1 border-bottom pb-3 border-secondary border-opacity-10">
          <div className="d-flex justify-content-between align-items-center mb-1">
            <h6 className="fw-semibold mb-0" style={{ color: 'var(--text-body)' }}>{ev.title}</h6>
            <span className="small text-muted">{ev.time}</span>
          </div>
          <div className="small text-muted">{ev.description}</div>
        </div>
      </div>
    ))}
  </div>
);

// --- Dialog Modal ---
export const Dialog = ({ isOpen, onClose, onConfirm, title, message, confirmText = 'Confirm', confirmVariant = 'primary' }) => {
  if (!isOpen) return null;
  return (
    <div className="modal fade show" style={{ display: 'block', backgroundColor: 'rgba(0,0,0,0.5)' }} tabIndex="-1">
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content border-0 shadow">
          <div className="modal-header">
            <h5 className="modal-title fw-bold text-dark-blue">{title}</h5>
            <button type="button" className="btn-close" onClick={onClose}></button>
          </div>
          <div className="modal-body">
            <p className="mb-0">{message}</p>
          </div>
          <div className="modal-footer border-0 bg-light">
            <button type="button" className="btn btn-outline-secondary" onClick={onClose}>Cancel</button>
            <button type="button" className={`btn btn-${confirmVariant}`} onClick={onConfirm}>{confirmText}</button>
          </div>
        </div>
      </div>
    </div>
  );
};
