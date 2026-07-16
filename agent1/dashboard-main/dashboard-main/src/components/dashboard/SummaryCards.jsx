import React, { memo } from 'react';
import { FaCheckCircle, FaTimesCircle, FaMicrochip, FaMemory } from 'react-icons/fa';

const SummaryCards = memo(({ data }) => {
  const cards = [
    { title: 'Total Systems', value: data?.cards?.total_machines ?? '—', change: '', positive: true, icon: <FaMicrochip color="#3B82F6" /> },
    { title: 'Online Systems', value: data?.cards?.online_count ?? '—', change: '', positive: true, icon: <FaCheckCircle color="#22C55E" /> },
    { title: 'Offline Systems', value: data?.cards?.offline_count ?? '—', change: '', positive: false, icon: <FaTimesCircle color="#64748B" /> },
    { title: 'Critical Alerts', value: data?.cards?.critical_alerts ?? '—', change: '', positive: true, icon: <FaTimesCircle color="#EF4444" /> },
  ];

  return (
    <div className="card h-100 p-4" style={{ borderRadius: '16px' }}>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <span className="text-muted fw-semibold">Key Metrics</span>
      </div>

      <div className="row g-3">
        {cards.map((card, index) => (
          <div key={index} className="col-12 col-md-6">
            <div className="card h-100 border-0" style={{ backgroundColor: 'rgba(255, 255, 255, 0.4)', borderRadius: '12px' }}>
              <div className="card-body p-3">
                <h4 className="fw-bold mb-1">{card.value}</h4>
                <div className="d-flex justify-content-between align-items-center mt-auto pt-2 border-top border-secondary border-opacity-25">
                  <div className="text-muted small">{card.title}</div>
                  {card.icon}
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
});

export default SummaryCards;
