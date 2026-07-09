import { NavLink } from 'react-router-dom';
import { FiGrid, FiMonitor, FiBell, FiSettings, FiUser, FiX, FiChevronLeft, FiChevronRight, FiMenu } from 'react-icons/fi';

const navItems = [
  { to: '/dashboard', label: 'Dashboard', icon: FiGrid },
  { to: '/machines', label: 'Machines', icon: FiMonitor },
  { to: '/alerts', label: 'Alerts', icon: FiBell },
  { to: '/settings', label: 'Settings', icon: FiSettings },
  { to: '/profile', label: 'Profile', icon: FiUser },
];

export default function Sidebar({ open, collapsed, onClose, onToggle }) {
  return (
    <>
      {open && !collapsed && <div className="fixed inset-0 bg-black/30 z-20 lg:hidden" onClick={onClose} />}
      <aside className={`fixed top-0 left-0 z-30 h-full bg-white border-r border-gray-200 transform transition-all duration-200 lg:translate-x-0 lg:static lg:z-auto ${collapsed ? 'w-16' : 'w-64'} ${open ? 'translate-x-0' : '-translate-x-full'}`}>
        <div className={`flex items-center h-16 border-b border-gray-200 ${collapsed ? 'justify-center px-0' : 'justify-between px-4'}`}>
          <div className="flex items-center gap-2 overflow-hidden">
            <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0">
              <FiMonitor className="w-4 h-4 text-white" />
            </div>
            {!collapsed && <span className="text-lg font-bold text-gray-900 whitespace-nowrap">DeskGuard</span>}
          </div>
          {!collapsed && (
            <div className="flex items-center gap-1">
              <button onClick={onToggle} className="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-indigo-600 transition-colors" title="Collapse sidebar">
                <FiChevronLeft className="w-4 h-4" />
              </button>
              <button onClick={onClose} className="lg:hidden p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-red-500 transition-colors">
                <FiX className="w-4 h-4" />
              </button>
            </div>
          )}
        </div>

        {collapsed && (
          <div className="flex justify-center pt-3 pb-1">
            <button onClick={onToggle} className="p-1.5 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition-colors" title="Expand sidebar">
              <FiChevronRight className="w-5 h-5" />
            </button>
          </div>
        )}

        <nav className={`p-2 space-y-1 ${collapsed ? 'flex flex-col items-center' : ''}`}>
          {navItems.map(item => (
            <NavLink
              key={item.to}
              to={item.to}
              onClick={onClose}
              className={({ isActive }) =>
                `flex items-center gap-3 rounded-lg text-sm font-medium transition-colors ${collapsed ? 'justify-center p-2.5 w-12 h-12' : 'px-3 py-2.5'} ${isActive ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'}`
              }
              title={collapsed ? item.label : undefined}
            >
              <item.icon className="w-5 h-5 flex-shrink-0" />
              {!collapsed && item.label}
            </NavLink>
          ))}
        </nav>

        <div className={`absolute bottom-4 ${collapsed ? 'left-0 right-0 flex justify-center' : 'left-0 right-0 flex justify-center'}`}>
          <button
            onClick={onToggle}
            className="p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-indigo-600 transition-colors"
            title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
          >
            {collapsed ? <FiChevronRight className="w-5 h-5" /> : <FiChevronLeft className="w-5 h-5" />}
          </button>
        </div>
      </aside>
    </>
  );
}