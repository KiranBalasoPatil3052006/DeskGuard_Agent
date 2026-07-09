import React, { useState, useEffect, useRef } from 'react';
import { FaChevronDown } from 'react-icons/fa';

const SystemHealthOverview = ({ data }) => {
  const [timeframe, setTimeframe] = useState('24H');
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef(null);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const total = data?.cards?.total_machines || 0;
  const online = data?.cards?.online_count || data?.cards?.online_machines || 0;
  const uptimePct = total > 0 ? Math.round((online / total) * 100) : 0;

  const dataMap = {
    '12H': { value: `${uptimePct}%`, trend: '—' },
    '24H': { value: `${uptimePct}%`, trend: '—' },
    '7D':  { value: `${uptimePct}%`, trend: '—' },
    '30D': { value: `${uptimePct}%`, trend: '—' },
  };

  const handleSelect = (tf) => {
    setTimeframe(tf);
    setIsOpen(false);
  };

  return (
    <div className="card h-100 p-4 position-relative" style={{ borderRadius: '16px' }}>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <span className="text-muted fw-semibold">Global System Health</span>
        <div className="position-relative" ref={dropdownRef}>
          <button 
            className="pill-btn d-flex align-items-center"
            onClick={() => setIsOpen(!isOpen)}
          >
            {timeframe} <FaChevronDown className="ms-2" size={10} />
          </button>

          {isOpen && (
            <div className="position-absolute end-0 mt-2 shadow-lg" style={{ backgroundColor: 'var(--bg-card)', border: '1px solid var(--border-color)', borderRadius: '8px', zIndex: 50, minWidth: '90px', overflow: 'hidden' }}>
              {['12H', '24H', '7D', '30D'].map(tf => (
                <div 
                  key={tf} 
                  className="px-3 py-2"
                  onClick={() => handleSelect(tf)}
                  style={{ 
                    fontSize: '0.85rem', 
                    color: timeframe === tf ? '#ffffff' : 'var(--text-muted-color)',
                    backgroundColor: timeframe === tf ? 'var(--primary-blue)' : 'transparent',
                    cursor: 'pointer',
                    transition: 'all 0.2s'
                  }}
                  onMouseOver={(e) => { if(timeframe !== tf) { e.target.style.backgroundColor = 'var(--pill-bg-hover)'; e.target.style.color = 'var(--text-body)'; } }}
                  onMouseOut={(e) => { if(timeframe !== tf) { e.target.style.backgroundColor = 'transparent'; e.target.style.color = 'var(--text-muted-color)'; } }}
                >
                  {tf}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      <div className="mt-2">
        <div className="metric-value-large mb-2">{dataMap[timeframe].value}</div>
        <div className="d-flex align-items-center">
          <span className="text-muted me-2 small">{online}/{total} machines online</span>
        </div>
      </div>
    </div>
  );
};

export default SystemHealthOverview;
