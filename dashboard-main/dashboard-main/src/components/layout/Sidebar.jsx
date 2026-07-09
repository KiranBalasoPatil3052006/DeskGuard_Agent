import React, { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { 
  FaTachometerAlt, 
  FaServer, 
  FaChartLine, 
  FaFileAlt, 
  FaBell, 
  FaCog, 
  FaUser 
} from 'react-icons/fa';

const Sidebar = ({ isOpen }) => {
  const location = useLocation();

  const menuItems = [
    { path: '/dashboard', name: 'Dashboard', icon: <FaTachometerAlt /> },
    { path: '/machines', name: 'Machines', icon: <FaServer /> },
    { path: '/monitoring', name: 'Live Monitoring', icon: <FaChartLine /> },
    { path: '/reports', name: 'Reports', icon: <FaFileAlt /> },
    { path: '/alerts', name: 'Alerts', icon: <FaBell /> },
    { path: '/settings', name: 'Settings', icon: <FaCog /> },
    { path: '/profile', name: 'User Profile', icon: <FaUser /> },
  ];

  return (
    <div className={`sidebar ${isOpen ? 'open' : ''}`}>
      <div className="sidebar-header d-flex align-items-center justify-content-center py-4">
        <h3 className="mb-0 fw-bold d-flex align-items-center" style={{ color: 'var(--primary-blue)' }}>
          <FaServer className="me-2 text-success" />
          <span>DeskGuard</span>
        </h3>
      </div>
      
      <div className="sidebar-menu mt-3">
        <ul className="nav flex-column">
          {menuItems.map((item) => (
            <li className="nav-item" key={item.name}>
              <Link 
                to={item.path} 
                className={`nav-link py-3 px-4 d-flex align-items-center ${location.pathname === item.path ? 'active-link' : ''}`}
              >
                <span className="me-3 fs-5">{item.icon}</span>
                <span className="fw-semibold">{item.name}</span>
              </Link>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
};

export default Sidebar;
