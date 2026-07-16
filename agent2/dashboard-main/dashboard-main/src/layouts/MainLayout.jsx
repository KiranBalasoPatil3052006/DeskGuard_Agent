import React, { useState, useEffect } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import Sidebar from '../components/layout/Sidebar';
import Navbar from '../components/layout/Navbar';

const MainLayout = () => {
  const [isSidebarOpen, setIsSidebarOpen] = useState(() => {
    const stored = localStorage.getItem('sidebar_open');
    return stored !== null ? stored === 'true' : true;
  });
  const location = useLocation();

  const toggleSidebar = () => {
    setIsSidebarOpen(prev => {
      const next = !prev;
      localStorage.setItem('sidebar_open', next);
      return next;
    });
  };

  // Close sidebar on mobile when navigating
  useEffect(() => {
    if (window.innerWidth < 768) {
      setIsSidebarOpen(false);
    }
  }, [location]);

  return (
    <div className="main-wrapper">
      {/* Mobile Overlay */}
      <div 
        className={`sidebar-overlay ${isSidebarOpen ? 'show' : ''}`}
        onClick={() => setIsSidebarOpen(false)}
      ></div>

      {/* Sidebar */}
      <div className={`sidebar-container ${isSidebarOpen ? 'open' : 'collapsed'}`}>
        <Sidebar isOpen={isSidebarOpen} onToggle={toggleSidebar} />
      </div>

      {/* Main Content Area */}
      <div className="main-content-area">
        <Navbar toggleSidebar={toggleSidebar} />
        
        <div className="dashboard-content">
          <Outlet />
        </div>
      </div>
    </div>
  );
};

export default MainLayout;
