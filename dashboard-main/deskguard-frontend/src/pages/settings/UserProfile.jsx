import { useAuth } from '../../context/AuthContext';
import { FiUser, FiMail, FiPhone, FiShield } from 'react-icons/fi';
import { StatusBadge } from '../../components/ui/StatusBadge';

export default function UserProfile() {
  const { user } = useAuth();

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-bold text-gray-900">Profile</h1>
        <p className="text-sm text-gray-500 mt-1">Your account details</p>
      </div>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
          <div className="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center mx-auto mb-4">
            <FiUser className="w-10 h-10 text-indigo-600" />
          </div>
          <h2 className="text-lg font-semibold text-gray-900">{user?.name || 'User'}</h2>
          <p className="text-sm text-gray-500 mt-1">{user?.roles?.[0]?.name || user?.role || 'Administrator'}</p>
          <StatusBadge status="active" className="mt-3" />
        </div>
        <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
          <h3 className="text-sm font-semibold text-gray-700">Account Information</h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
              <FiUser className="w-5 h-5 text-gray-400" />
              <div><span className="text-gray-500 text-xs">Name</span><p className="font-medium text-gray-900">{user?.name || '—'}</p></div>
            </div>
            <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
              <FiMail className="w-5 h-5 text-gray-400" />
              <div><span className="text-gray-500 text-xs">Email</span><p className="font-medium text-gray-900">{user?.email || '—'}</p></div>
            </div>
            <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
              <FiPhone className="w-5 h-5 text-gray-400" />
              <div><span className="text-gray-500 text-xs">Phone</span><p className="font-medium text-gray-900">{user?.phone || user?.mobile_number || '—'}</p></div>
            </div>
            <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
              <FiShield className="w-5 h-5 text-gray-400" />
              <div><span className="text-gray-500 text-xs">Role</span><p className="font-medium text-gray-900">{user?.roles?.[0]?.name || user?.role || '—'}</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}