import React from 'react';

const SystemHealth = () => {
  const metrics = [
    { label: 'CPU Utilization', value: 42, color: 'info' },
    { label: 'Memory Utilization', value: 68, color: 'warning' },
    { label: 'Disk Utilization', value: 85, color: 'danger' },
    { label: 'Network Utilization', value: 25, color: 'success' },
  ];

  return (
    <div className="card h-100">
      <div className="card-header">
        System Health Summary
      </div>
      <div className="card-body">
        {metrics.map((metric, index) => (
          <div className="mb-4" key={index}>
            <div className="d-flex justify-content-between mb-1">
              <span className="fw-semibold text-muted" style={{ fontSize: '0.9rem' }}>{metric.label}</span>
              <span className="fw-bold">{metric.value}%</span>
            </div>
            <div className="progress" style={{ height: '8px' }}>
              <div 
                className={`progress-bar bg-${metric.color}`} 
                role="progressbar" 
                style={{ width: `${metric.value}%` }} 
                aria-valuenow={metric.value} 
                aria-valuemin="0" 
                aria-valuemax="100"
              ></div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default SystemHealth;
