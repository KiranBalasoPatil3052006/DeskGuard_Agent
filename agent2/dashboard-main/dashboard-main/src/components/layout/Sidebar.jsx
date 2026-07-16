import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { 
  FaTachometerAlt, 
  FaServer, 
  FaFileAlt, 
  FaBell, 
  FaCog, 
  FaUserPlus,
  FaBars,
  FaShieldAlt
} from 'react-icons/fa';
import { useAuth } from '../../context/AuthContext';

const Sidebar = ({ isOpen, onToggle }) => {
  const location = useLocation();
  const { user } = useAuth();
  const role = user?.role || '';

  const menuItems = [
    { path: '/dashboard', name: 'Dashboard', icon: <FaTachometerAlt /> },
    { path: '/machines', name: 'Machines', icon: <FaServer /> },
    { path: '/reports', name: 'Reports', icon: <FaFileAlt /> },
    { path: '/alerts', name: 'Alerts', icon: <FaBell /> },
    ...(role === 'Super Admin' ? [{ path: '/accounts', name: 'Create Account', icon: <FaUserPlus /> }] : []),
    { path: '/settings', name: 'Settings', icon: <FaCog /> },
  ];

  return (
    <div className={`sidebar ${isOpen ? 'open' : 'collapsed'}`}>
      {/* Toggle button at top, logo below */}
      <div className="sidebar-toggle-wrapper">
        <button 
          className="sidebar-hamburger"
          onClick={onToggle}
          title={isOpen ? 'Collapse sidebar' : 'Expand sidebar'}
        >
          <FaBars />
        </button>
        {isOpen ? (
          <div className="sidebar-logo">
            <FaShieldAlt className="text-success" />
            <span className="ms-2 fw-bold">DeskGuard</span>
          </div>
        ) : (
          <div className="sidebar-logo-collapsed">
            <FaShieldAlt className="text-success" />
          </div>
        )}
      </div>
      
      <div className="sidebar-menu mt-2">
        <ul className="nav flex-column">
          {menuItems.map((item) => (
            <li className="nav-item" key={item.name}>
              <Link 
                to={item.path} 
                className={`nav-link py-3 d-flex align-items-center ${location.pathname === item.path ? 'active-link' : ''}`}
                title={!isOpen ? item.name : ''}
              >
                <span className="sidebar-icon">{item.icon}</span>
                {isOpen && <span className="sidebar-label ms-3">{item.name}</span>}
              </Link>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
};

export default Sidebar;
