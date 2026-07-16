import { useState } from 'react';
import { FaPlus, FaFileExport, FaSync } from 'react-icons/fa';
import { useNavigate } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';

const spinStyles = `
  @keyframes spin { 100% { transform: rotate(360deg); } }
  .spin-icon { animation: spin 1s linear infinite; }
`;

const QuickActions = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [isRefreshing, setIsRefreshing] = useState(false);

  const handleRefresh = () => {
    setIsRefreshing(true);
    queryClient.invalidateQueries();
    setTimeout(() => {
      setIsRefreshing(false);
    }, 1000);
  };

  const actions = [
    { name: 'Add Machine', icon: <FaPlus />, color: 'var(--primary-blue)', onClick: () => navigate('/machines') },
    { name: 'Generate Report', icon: <FaFileExport />, color: '#22C55E', onClick: () => navigate('/reports') },
    { name: isRefreshing ? 'Refreshing...' : 'Refresh Data', icon: <FaSync className={isRefreshing ? 'spin-icon' : ''} />, color: '#F59E0B', onClick: handleRefresh },
  ];

  return (
    <div className="card h-100 p-4" style={{ borderRadius: '16px' }}>
      <style>{spinStyles}</style>
      <div className="d-flex justify-content-between align-items-center mb-3">
        <span className="text-muted fw-semibold">Quick Actions</span>
      </div>
      <div className="d-flex flex-column gap-3 mt-2">
        {actions.map((action, index) => (
          <button 
            key={index} 
            onClick={action.onClick}
            className="btn d-flex align-items-center w-100 text-start border-0 py-2 px-3"
            style={{ backgroundColor: 'var(--bg-input)', color: 'var(--text-body)', borderRadius: '8px', transition: 'all 0.2s' }}
            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = 'var(--pill-bg-hover)'}
            onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'var(--bg-input)'}
          >
            <span className="me-3 d-flex align-items-center" style={{ color: action.color }}>{action.icon}</span>
            <span className="fw-semibold" style={{ fontSize: '0.85rem' }}>{action.name}</span>
          </button>
        ))}
      </div>
    </div>
  );
};

export default QuickActions;
