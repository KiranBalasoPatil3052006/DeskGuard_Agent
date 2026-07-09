import { useState, useEffect, useCallback } from 'react';
import { getEmailRecipients, addEmailRecipient, removeEmailRecipient, updateEmailRecipient, getNotificationSettings } from '../../services/settings';
import { useAuth } from '../../context/AuthContext';
import { PageLoading } from '../../components/ui/LoadingState';
import { ErrorState } from '../../components/ui/ErrorState';
import { EmptyState } from '../../components/ui/EmptyState';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { FiSettings, FiMail, FiUser, FiPlus, FiTrash2, FiSave } from 'react-icons/fi';

const TABS = [
  { id: 'email', label: 'Email Recipients', icon: FiMail },
  { id: 'profile', label: 'Profile', icon: FiUser },
];

export default function Settings() {
  const [activeTab, setActiveTab] = useState('email');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [recipients, setRecipients] = useState([]);
  const [newEmail, setNewEmail] = useState('');
  const [newName, setNewName] = useState('');
  const [adding, setAdding] = useState(false);
  const [message, setMessage] = useState(null);
  const { user, refreshUser } = useAuth();

  const fetchRecipients = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await getEmailRecipients();
      setRecipients(res?.data || []);
    } catch (err) {
      setError(err.message || 'Failed to load settings');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchRecipients(); }, [fetchRecipients]);

  const handleAdd = async () => {
    if (!newEmail) return;
    setAdding(true);
    try {
      const res = await addEmailRecipient({ email: newEmail, name: newName || undefined });
      setRecipients(prev => [...prev, res?.data || res]);
      setNewEmail('');
      setNewName('');
      setMessage({ type: 'success', text: 'Recipient added successfully' });
    } catch (err) {
      setMessage({ type: 'error', text: err.message || 'Failed to add recipient' });
    } finally {
      setAdding(false);
      setTimeout(() => setMessage(null), 3000);
    }
  };

  const handleRemove = async (id) => {
    try {
      await removeEmailRecipient(id);
      setRecipients(prev => prev.filter(r => r.id !== id));
      setMessage({ type: 'success', text: 'Recipient removed' });
    } catch (err) {
      setMessage({ type: 'error', text: err.message || 'Failed to remove' });
    } finally {
      setTimeout(() => setMessage(null), 3000);
    }
  };

  const handleToggle = async (recipient) => {
    try {
      await updateEmailRecipient(recipient.id, { is_active: !recipient.is_active });
      setRecipients(prev => prev.map(r => r.id === recipient.id ? { ...r, is_active: !r.is_active } : r));
    } catch {
      setMessage({ type: 'error', text: 'Failed to update status' });
      setTimeout(() => setMessage(null), 3000);
    }
  };

  if (loading) return <PageLoading />;
  if (error) return <ErrorState message={error} onRetry={fetchRecipients} />;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-bold text-gray-900">Settings</h1>
        <p className="text-sm text-gray-500 mt-1">Manage your account and notification preferences</p>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-1 flex overflow-x-auto">
        {TABS.map(tab => (
          <button key={tab.id} onClick={() => setActiveTab(tab.id)} className={`flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${activeTab === tab.id ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'}`}>
            <tab.icon className="w-4 h-4" />
            {tab.label}
          </button>
        ))}
      </div>

      {message && (
        <div className={`px-4 py-3 rounded-lg text-sm ${message.type === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'}`}>
          {message.text}
        </div>
      )}

      {activeTab === 'email' && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
          <h3 className="text-sm font-semibold text-gray-700">Email Notification Recipients</h3>
          <div className="flex gap-3">
            <input type="text" value={newName} onChange={e => setNewName(e.target.value)} placeholder="Name (optional)" className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
            <input type="email" value={newEmail} onChange={e => setNewEmail(e.target.value)} placeholder="Email address" className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" onKeyDown={e => e.key === 'Enter' && handleAdd()} />
            <button onClick={handleAdd} disabled={adding || !newEmail} className="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
              <FiPlus className="w-4 h-4" /> Add
            </button>
          </div>
          {recipients.length > 0 ? (
            <div className="space-y-2">
              {recipients.map(r => (
                <div key={r.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div className="flex items-center gap-3">
                    <div className="p-2 rounded-lg bg-indigo-50"><FiMail className="w-4 h-4 text-indigo-600" /></div>
                    <div>
                      <p className="text-sm font-medium text-gray-900">{r.name || r.email}</p>
                      <p className="text-xs text-gray-400">{r.email}</p>
                    </div>
                    <button onClick={() => handleToggle(r)} className="ml-2"><StatusBadge status={r.is_active ? 'active' : 'offline'} label={r.is_active ? 'Active' : 'Inactive'} /></button>
                  </div>
                  <button onClick={() => handleRemove(r.id)} className="p-2 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600"><FiTrash2 className="w-4 h-4" /></button>
                </div>
              ))}
            </div>
          ) : (
            <EmptyState icon={FiMail} title="No email recipients" description="Add email addresses to receive alert notifications." />
          )}
        </div>
      )}

      {activeTab === 'profile' && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
          <div className="flex items-center gap-4">
            <div className="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center">
              <FiUser className="w-8 h-8 text-indigo-600" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-gray-900">{user?.name || 'User'}</h3>
              <p className="text-sm text-gray-500">{user?.email || ''}</p>
            </div>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><span className="text-gray-500 block">Name</span><p className="font-medium text-gray-900">{user?.name || '—'}</p></div>
            <div><span className="text-gray-500 block">Email</span><p className="font-medium text-gray-900">{user?.email || '—'}</p></div>
            <div><span className="text-gray-500 block">Phone</span><p className="font-medium text-gray-900">{user?.phone || user?.mobile_number || '—'}</p></div>
            <div><span className="text-gray-500 block">Role</span><p className="font-medium text-gray-900">{user?.roles?.[0]?.name || user?.role || '—'}</p></div>
          </div>
        </div>
      )}
    </div>
  );
}