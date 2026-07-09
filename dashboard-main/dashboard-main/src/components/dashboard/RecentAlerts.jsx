import React, { useState } from 'react';

const RecentAlerts = ({ alerts }) => {
  const [filter, setFilter] = useState('All');

  const items = Array.isArray(alerts) ? alerts : [];

  const filteredAlerts = filter === 'All'
    ? items
    : items.filter(a => a.severity === filter);

  return (
    <div className="card h-100 p-4" style={{ borderRadius: '16px' }}>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <span className="text-muted fw-semibold">Recent Alerts</span>
        <div className="pill-group">
          {['All', 'Critical', 'Warning', 'Info'].slice(0, 2).map(f => (
            <button
              key={f}
              className={`pill-btn ${filter === f ? 'active' : ''}`}
              onClick={() => setFilter(f)}
            >
              {f}
            </button>
          ))}
        </div>
      </div>

      <div className="d-flex flex-column gap-3">
        {filteredAlerts.length > 0 ? (
          filteredAlerts.slice(0, 5).map((alert, index) => (
            <div key={alert.id || index} className="d-flex justify-content-between align-items-center pb-3" style={{ borderBottom: index < Math.min(filteredAlerts.length, 5) - 1 ? '1px solid var(--border-color)' : 'none' }}>
              <div className="d-flex align-items-center gap-3">
                <div style={{ width: '8px', height: '8px', borderRadius: '50%', backgroundColor: alert.severity === 'Critical' ? '#EF4444' : alert.severity === 'Warning' ? '#F59E0B' : '#22C55E' }}></div>
                <div>
                  <div className="fw-semibold" style={{ fontSize: '0.9rem', color: 'var(--text-body)' }}>{alert.title || alert.alert_type || alert.machine?.device_name || alert.machine_name}</div>
                  <div className="text-muted small" style={{ fontSize: '0.75rem' }}>{alert.description}</div>
                </div>
              </div>
              <div className="text-end">
                <div className="fw-semibold" style={{ fontSize: '0.9rem', color: 'var(--text-body)' }}>{alert.severity}</div>
              </div>
            </div>
          ))
        ) : (
          <div className="text-muted text-center py-4">No alerts found</div>
        )}
      </div>
    </div>
  );
};

export default RecentAlerts;
