import React from 'react';

const SystemHealth = ({ data }) => {
  const metrics = [
    { label: 'CPU Utilization', value: data?.cpu_percentage ?? 42, color: (data?.cpu_percentage ?? 0) > 90 ? 'danger' : (data?.cpu_percentage ?? 0) > 70 ? 'warning' : 'info' },
    { label: 'Memory Utilization', value: data?.ram_percentage ?? 68, color: (data?.ram_percentage ?? 0) > 90 ? 'danger' : (data?.ram_percentage ?? 0) > 70 ? 'warning' : 'warning' },
    { label: 'Disk Utilization', value: data?.disk_percentage ?? 85, color: (data?.disk_percentage ?? 0) > 95 ? 'danger' : (data?.disk_percentage ?? 0) > 80 ? 'warning' : 'danger' },
    { label: 'Network Utilization', value: data?.network_percentage ?? 25, color: (data?.network_percentage ?? 0) > 90 ? 'danger' : (data?.network_percentage ?? 0) > 70 ? 'warning' : 'success' },
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
