import { useState, useEffect, useCallback } from 'react';
import { getAlerts, acknowledgeAlert, resolveAlert } from '../../services/alerts';
import { getMachines } from '../../services/machines';
import { PageLoading } from '../../components/ui/LoadingState';
import { ErrorState } from '../../components/ui/ErrorState';
import { EmptyState } from '../../components/ui/EmptyState';
import { StatusBadge, SeverityBadge } from '../../components/ui/StatusBadge';
import { Pagination } from '../../components/ui/Pagination';
import { FiBell, FiSearch, FiCheck, FiX } from 'react-icons/fi';
import { useNavigate } from 'react-router-dom';

export default function AlertsList() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [alerts, setAlerts] = useState([]);
  const [pagination, setPagination] = useState({ currentPage: 1, lastPage: 1, total: 0 });
  const [params, setParams] = useState({ page: 1, per_page: 20 });
  const [search, setSearch] = useState('');
  const [severity, setSeverity] = useState('');
  const [status, setStatus] = useState('');
  const [resolveModal, setResolveModal] = useState(null);
  const [resolveNote, setResolveNote] = useState('');
  const navigate = useNavigate();

  const fetchAlerts = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await getAlerts(params);
      const d = res?.data || res;
      const items = Array.isArray(d) ? d : (d?.data || []);
      const meta = d?.pagination || d?.meta || {};
      setAlerts(items);
      setPagination({
        currentPage: meta.current_page || meta.page || params.page,
        lastPage: meta.last_page || meta.total_pages || 1,
        total: meta.total || items.length,
      });
    } catch (err) {
      setError(err.message || 'Failed to load alerts');
    } finally {
      setLoading(false);
    }
  }, [params]);

  useEffect(() => { fetchAlerts(); }, [fetchAlerts]);

  const updateParams = (updates) => setParams(prev => ({ ...prev, page: 1, ...updates }));

  const handleSeverityFilter = (s) => {
    const next = s === severity ? '' : s;
    setSeverity(next);
    updateParams(next ? { severity: next, status: status || undefined } : { severity: undefined, status: status || undefined });
  };

  const handleStatusFilter = (s) => {
    const next = s === status ? '' : s;
    setStatus(next);
    updateParams(next ? { severity: severity || undefined, status: next } : { severity: severity || undefined, status: undefined });
  };

  const handleResolve = async (id) => {
    try {
      await resolveAlert(id, resolveNote);
      setResolveModal(null);
      setResolveNote('');
      fetchAlerts();
    } catch (err) {
      // ignore
    }
  };

  const handleAcknowledge = async (id) => {
    try {
      await acknowledgeAlert(id);
      fetchAlerts();
    } catch {
      // ignore
    }
  };

  if (loading && !alerts.length) return <PageLoading />;
  if (error && !alerts.length) return <ErrorState message={error} onRetry={fetchAlerts} />;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-bold text-gray-900">Alerts</h1>
        <p className="text-sm text-gray-500 mt-1">{pagination.total} alert{pagination.total !== 1 ? 's' : ''}</p>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-4">
        <div className="flex flex-wrap gap-3">
          <div className="relative flex-1 min-w-[200px]">
            <FiSearch className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input type="text" value={search} onChange={e => setSearch(e.target.value)} onKeyDown={e => { if (e.key === 'Enter') updateParams({ search: search || undefined }); }} placeholder="Search alerts..." className="pl-9 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none text-sm" />
          </div>
          <div className="flex gap-2 flex-wrap">
            {['critical', 'warning', 'info'].map(s => (
              <button key={s} onClick={() => handleSeverityFilter(s)} className={`px-3 py-2 rounded-lg text-xs font-medium transition-colors ${severity === s ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'}`}>{s}</button>
            ))}
            <div className="w-px bg-gray-200 mx-1" />
            {['open', 'acknowledged', 'resolved'].map(s => (
              <button key={s} onClick={() => handleStatusFilter(s)} className={`px-3 py-2 rounded-lg text-xs font-medium transition-colors ${status === s ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'}`}>{s}</button>
            ))}
          </div>
        </div>

        {alerts.length > 0 ? (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th className="px-4 py-3 w-20">Severity</th>
                    <th className="px-4 py-3">Title</th>
                    <th className="px-4 py-3">Machine</th>
                    <th className="px-4 py-3">Status</th>
                    <th className="px-4 py-3">Time</th>
                    <th className="px-4 py-3">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {alerts.map(a => (
                    <tr key={a.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-4 py-3"><SeverityBadge severity={a.severity} /></td>
                      <td className="px-4 py-3">
                        <p className="font-medium text-gray-900">{a.title}</p>
                        {a.description && <p className="text-xs text-gray-400 mt-0.5 line-clamp-1">{a.description}</p>}
                      </td>
                      <td className="px-4 py-3">
                        {a.machine ? (
                          <button onClick={() => navigate(`/machines/${a.machine.id}`)} className="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                            {a.machine.hostname || a.machine.device_name || a.machine.machine_uid}
                          </button>
                        ) : <span className="text-gray-400">—</span>}
                      </td>
                      <td className="px-4 py-3"><StatusBadge status={a.status} /></td>
                      <td className="px-4 py-3 text-gray-500 text-xs">{new Date(a.created_at).toLocaleString()}</td>
                      <td className="px-4 py-3">
                        <div className="flex gap-1">
                          {a.status === 'open' && <button onClick={() => handleAcknowledge(a.id)} className="p-1.5 rounded hover:bg-indigo-50 text-indigo-600" title="Acknowledge"><FiCheck className="w-4 h-4" /></button>}
                          {a.status !== 'resolved' && <button onClick={() => setResolveModal(a)} className="p-1.5 rounded hover:bg-emerald-50 text-emerald-600" title="Resolve"><FiX className="w-4 h-4" /></button>}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <Pagination currentPage={pagination.currentPage} lastPage={pagination.lastPage} onPageChange={(page) => setParams(prev => ({ ...prev, page }))} />
          </>
        ) : (
          <EmptyState icon={FiBell} title="No alerts" description={search ? 'No alerts match your search.' : 'No alerts have been generated yet.'} />
        )}
      </div>

      {resolveModal && (
        <div className="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4" onClick={() => setResolveModal(null)}>
          <div className="bg-white rounded-xl shadow-lg p-6 w-full max-w-md" onClick={e => e.stopPropagation()}>
            <h3 className="text-lg font-semibold text-gray-900 mb-2">Resolve Alert</h3>
            <p className="text-sm text-gray-500 mb-4">{resolveModal.title}</p>
            <textarea value={resolveNote} onChange={e => setResolveNote(e.target.value)} placeholder="Resolution note (optional)" className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none mb-4" rows={3} />
            <div className="flex gap-3 justify-end">
              <button onClick={() => setResolveModal(null)} className="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
              <button onClick={() => handleResolve(resolveModal.id)} className="px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">Resolve</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}