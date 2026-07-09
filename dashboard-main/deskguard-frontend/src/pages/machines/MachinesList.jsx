import { useState } from 'react';
import { getMachines } from '../../services/machines';
import { usePagination } from '../../hooks/useApi';
import { PageLoading } from '../../components/ui/LoadingState';
import { ErrorState } from '../../components/ui/ErrorState';
import { EmptyState } from '../../components/ui/EmptyState';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { Pagination } from '../../components/ui/Pagination';
import { FiMonitor, FiSearch, FiFilter, FiChevronDown } from 'react-icons/fi';
import { useNavigate } from 'react-router-dom';

export default function MachinesList() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [showFilters, setShowFilters] = useState(false);
  const navigate = useNavigate();

  const { data: machines, loading, error, currentPage, lastPage, total, onPageChange, updateParams, refresh } = usePagination(getMachines, { per_page: 20 });

  const handleSearch = () => {
    updateParams({ search, page: 1 });
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter') handleSearch();
  };

  const handleStatusFilter = (status) => {
    const next = status === statusFilter ? '' : status;
    setStatusFilter(next);
    updateParams({ status: next || undefined, page: 1, search: search || undefined });
  };

  if (error && !machines.length) return <ErrorState message={error} onRetry={refresh} />;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-bold text-gray-900">Machines</h1>
          <p className="text-sm text-gray-500 mt-1">{total} machine{total !== 1 ? 's' : ''} registered</p>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-4">
        <div className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <FiSearch className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input type="text" value={search} onChange={e => setSearch(e.target.value)} onKeyDown={handleKeyDown} placeholder="Search by name or mobile..." className="pl-9 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none text-sm" />
          </div>
          <div className="flex gap-2">
            {['online', 'offline', 'alert'].map(s => (
              <button key={s} onClick={() => handleStatusFilter(s)} className={`px-3 py-2 rounded-lg text-xs font-medium transition-colors ${statusFilter === s ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'}`}>
                {s.charAt(0).toUpperCase() + s.slice(1)}
              </button>
            ))}
          </div>
        </div>

        {loading ? (
          <div className="space-y-3 py-8"><PageLoading /></div>
        ) : machines.length > 0 ? (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th className="px-4 py-3">Computer Name</th>
                    <th className="px-4 py-3">Mobile</th>
                    <th className="px-4 py-3">Status</th>
                    <th className="px-4 py-3">Last Seen</th>
                    <th className="px-4 py-3">Alerts</th>
                    <th className="px-4 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {machines.map(m => (
                    <tr key={m.id} className="hover:bg-gray-50 transition-colors cursor-pointer" onClick={() => navigate(`/machines/${m.id}`)}>
                      <td className="px-4 py-3 font-medium text-gray-900">{m.hostname || m.device_name || m.machine_uid}</td>
                      <td className="px-4 py-3 text-gray-500">{m.employee_mobile_number || '—'}</td>
                      <td className="px-4 py-3"><StatusBadge status={m.is_online ? 'online' : 'offline'} /></td>
                      <td className="px-4 py-3 text-gray-500">{m.last_heartbeat_at ? new Date(m.last_heartbeat_at).toLocaleString() : '—'}</td>
                      <td className="px-4 py-3">{m.alerts_count ?? 0}</td>
                      <td className="px-4 py-3 text-right">
                        <button onClick={(e) => { e.stopPropagation(); navigate(`/machines/${m.id}`); }} className="text-indigo-600 hover:text-indigo-800 text-xs font-medium">View Details</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <Pagination currentPage={currentPage} lastPage={lastPage} onPageChange={onPageChange} />
          </>
        ) : (
          <EmptyState icon={FiMonitor} title="No machines found" description={search ? 'Try a different search term.' : 'No machines registered yet.'} />
        )}
      </div>
    </div>
  );
}