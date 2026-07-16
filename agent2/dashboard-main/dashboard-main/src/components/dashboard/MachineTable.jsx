import { memo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { FaSearch, FaEllipsisV } from 'react-icons/fa';

const MachineTable = memo(({ machines }) => {
  const navigate = useNavigate();
  const [filter, setFilter] = useState('All');
  const [search, setSearch] = useState('');

  const items = Array.isArray(machines) ? machines : [];

  const filteredMachines = items.filter(m => {
    const matchesFilter = filter === 'All' || m.status === filter;
    const nameStr = m.device_name || m.hostname || m.name || '';
    const mobileStr = m.employee_mobile_number || m.mobile || '';
    const matchesSearch = nameStr.toLowerCase().includes(search.toLowerCase()) || mobileStr.toString().includes(search);
    return matchesFilter && matchesSearch;
  });

  const statusClass = (status) => {
    switch (status) {
      case 'Critical': return 'bg-danger';
      case 'Warning': return 'bg-warning';
      case 'Online': case 'online': return 'bg-success';
      case 'Offline': case 'offline': return 'bg-secondary';
      default: return 'bg-secondary';
    }
  };

  return (
    <div className="card h-100 p-4" style={{ borderRadius: '16px' }}>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <span className="text-muted fw-semibold">Registered Systems</span>
        <div className="d-flex align-items-center gap-3">
          <div className="position-relative">
            <FaSearch className="position-absolute text-muted" style={{ top: '50%', left: '10px', transform: 'translateY(-50%)' }} />
            <input 
              type="text" 
              className="form-control form-control-sm ps-4" 
              placeholder="Quick search..." 
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              style={{ backgroundColor: 'var(--bg-card)', color: 'var(--text-body)', border: '1px solid var(--border-color)', borderRadius: '20px' }}
            />
          </div>
          <div className="pill-group d-none d-md-flex">
            {['All', 'Critical', 'Warning', 'Online', 'Offline'].slice(0, 3).map(f => (
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
      </div>
      <div className="table-responsive">
        <table className="table table-borderless align-middle mb-0" style={{ color: 'var(--text-body)' }}>
          <thead>
            <tr style={{ borderBottom: '1px solid var(--border-color)' }}>
              <th className="text-muted fw-normal pb-3 ps-0" style={{ fontSize: '0.8rem' }}>Computer Name</th>
              <th className="text-muted fw-normal pb-3" style={{ fontSize: '0.8rem' }}>IP Address</th>
              <th className="text-muted fw-normal pb-3" style={{ fontSize: '0.8rem' }}>Operating System</th>
              <th className="text-muted fw-normal pb-3" style={{ fontSize: '0.8rem' }}>Status</th>
              <th className="text-muted fw-normal pb-3 text-end pe-0" style={{ fontSize: '0.8rem' }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {filteredMachines.length > 0 ? (
              filteredMachines.slice(0, 5).map((m, index) => (
              <tr key={m.id || index} style={{ borderBottom: '1px solid var(--border-color)' }}>
                <td className="ps-0 py-3 fw-semibold">{m.device_name || m.hostname || m.machine_uid || '—'}</td>
                <td className="py-3" style={{ color: 'var(--text-body)' }}>{m.current_status?.network_interfaces?.[0]?.ip_address || m.hostname || '—'}</td>
                <td className="py-3" style={{ color: 'var(--text-body)' }}>{m.operating_system || '—'}</td>
                <td className="py-3">
                  <span className={`badge ${statusClass(m.status || (m.is_online ? 'Online' : 'Offline'))}`}>
                    {m.status || (m.is_online ? 'Online' : 'Offline') || 'Unknown'}
                  </span>
                </td>
                <td className="pe-0 py-3 text-end">
                  <button 
                    className="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 me-2" 
                    style={{ fontSize: '0.75rem' }}
                    onClick={() => navigate(`/machines/${m.id}`)}
                  >
                    View Details
                  </button>
                  <button className="btn btn-sm btn-link text-muted p-0"><FaEllipsisV /></button>
                </td>
              </tr>
            ))) : (
              <tr>
                <td colSpan="5" className="text-center py-4 text-muted">No machines found</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
});

export default MachineTable;
