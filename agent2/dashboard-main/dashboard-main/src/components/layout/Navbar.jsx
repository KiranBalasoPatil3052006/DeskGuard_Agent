import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { FaSearch, FaBell, FaUserCircle, FaSignOutAlt, FaBars, FaCheckDouble } from 'react-icons/fa';
import { useAuth } from '../../context/AuthContext';
import { getUnreadNotificationCount, getNotifications, markAllNotificationsAsRead } from '../../services/notifications';

const Navbar = ({ toggleSidebar }) => {
  const navigate = useNavigate();
  const { user, logout } = useAuth();
  const [showSearch, setShowSearch] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [recentNotifications, setRecentNotifications] = useState([]);
  const [showDropdown, setShowDropdown] = useState(false);
  const dropdownRef = useRef(null);

  const fetchUnreadCount = useCallback(async () => {
    try {
      const res = await getUnreadNotificationCount();
      setUnreadCount(res.count ?? res.data?.count ?? 0);
    } catch {
      // silently fail
    }
  }, []);

  const fetchRecentNotifications = useCallback(async () => {
    try {
      const res = await getNotifications({ per_page: 5 });
      const list = res.data?.data ?? res.data ?? [];
      setRecentNotifications(list);
    } catch {
      // silently fail
    }
  }, []);

  useEffect(() => {
    fetchUnreadCount();
    const interval = setInterval(fetchUnreadCount, 30000);
    return () => clearInterval(interval);
  }, [fetchUnreadCount]);

  useEffect(() => {
    if (showDropdown) fetchRecentNotifications();
  }, [showDropdown, fetchRecentNotifications]);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
        setShowDropdown(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleBellClick = (e) => {
    e.stopPropagation();
    setShowDropdown((prev) => !prev);
  };

  const handleMarkAllRead = async () => {
    try {
      await markAllNotificationsAsRead();
      setUnreadCount(0);
      setRecentNotifications([]);
    } catch {
      // silently fail
    }
  };

  const handleNotificationClick = (notification) => {
    setShowDropdown(false);
    if (notification?.id) {
      navigate(`/alerts?id=${notification.id}`);
    } else {
      navigate('/alerts');
    }
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  const formatTime = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
  };

  return (
    <div className="px-4 pt-4">
      <nav className="top-navbar d-flex align-items-center justify-content-between px-4" style={{ borderRadius: '16px', height: '70px', zIndex: 900, position: 'relative', background: 'rgba(255, 255, 255, 0.85)', backdropFilter: 'blur(12px)', WebkitBackdropFilter: 'blur(12px)', border: '1px solid rgba(255, 255, 255, 0.8)' }}>
      <div className="d-flex align-items-center">
        <button 
          className="btn btn-link d-md-none me-3" 
          onClick={toggleSidebar}
          style={{ color: 'var(--text-body)' }}
        >
          <FaBars size={20} />
        </button>
      </div>

      <div className="d-flex align-items-center gap-4">
        {/* Search Icon */}
        <div className="d-flex align-items-center">
          {showSearch && (
            <input 
              type="text" 
              className="form-control form-control-sm border-secondary me-3" 
              placeholder="Search..." 
              style={{ width: '200px', backgroundColor: 'var(--bg-card)', color: 'var(--text-body)', transition: 'width 0.3s' }}
              autoFocus
              onBlur={(e) => {
                if(e.target.value === '') setShowSearch(false);
              }}
            />
          )}
          <div 
            className="cursor-pointer" 
            style={{ color: 'var(--text-body)' }}
            onClick={() => setShowSearch(!showSearch)}
            title="Search"
          >
            <FaSearch size={18} />
          </div>
        </div>

        {/* Notification Icon */}
        <div className="position-relative" ref={dropdownRef}>
          <div 
            className="cursor-pointer position-relative"
            style={{ color: 'var(--text-body)' }}
            onClick={handleBellClick}
            title="Notifications"
          >
            <FaBell size={20} />
            {unreadCount > 0 && (
              <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style={{ fontSize: '0.6rem', minWidth: '18px', padding: '2px 5px' }}>
                {unreadCount > 99 ? '99+' : unreadCount}
              </span>
            )}
          </div>

          {showDropdown && (
            <div className="position-absolute end-0 mt-2" style={{ width: '340px', zIndex: 1050, background: 'var(--bg-card, #fff)', borderRadius: '12px', boxShadow: '0 8px 30px rgba(0,0,0,0.12)', border: '1px solid rgba(0,0,0,0.06)', overflow: 'hidden' }}>
              <div className="d-flex align-items-center justify-content-between px-3 py-2" style={{ borderBottom: '1px solid rgba(0,0,0,0.06)' }}>
                <span className="fw-semibold" style={{ color: 'var(--text-body, #212529)', fontSize: '0.9rem' }}>Notifications</span>
                {unreadCount > 0 && (
                  <button className="btn btn-sm btn-link text-decoration-none p-0" onClick={handleMarkAllRead} style={{ color: 'var(--bs-success, #198754)', fontSize: '0.8rem' }}>
                    <FaCheckDouble className="me-1" />Mark all read
                  </button>
                )}
              </div>
              <div style={{ maxHeight: '350px', overflowY: 'auto' }}>
                {recentNotifications.length === 0 ? (
                  <div className="text-center py-4 text-muted" style={{ fontSize: '0.85rem' }}>
                    No notifications
                  </div>
                ) : (
                  recentNotifications.map((n) => (
                    <div
                      key={n.id}
                      className="px-3 py-2 cursor-pointer"
                      style={{ borderBottom: '1px solid rgba(0,0,0,0.04)', transition: 'background 0.15s', ...(n.is_read ? {} : { background: 'rgba(13, 110, 253, 0.04)' }) }}
                      onClick={() => handleNotificationClick(n)}
                      onMouseEnter={(e) => e.currentTarget.style.background = 'rgba(0,0,0,0.03)'}
                      onMouseLeave={(e) => e.currentTarget.style.background = n.is_read ? '' : 'rgba(13, 110, 253, 0.04)'}
                    >
                      <div className="fw-semibold" style={{ fontSize: '0.85rem', color: 'var(--text-body, #212529)' }}>{n.title}</div>
                      <div style={{ fontSize: '0.8rem', color: 'var(--text-muted, #6c757d)' }} className="text-truncate">{n.body}</div>
                      <div style={{ fontSize: '0.7rem', color: 'var(--text-muted, #adb5bd)' }}>{formatTime(n.created_at)}</div>
                    </div>
                  ))
                )}
              </div>
              {recentNotifications.length > 0 && (
                <div className="text-center py-2" style={{ borderTop: '1px solid rgba(0,0,0,0.06)' }}>
                  <button className="btn btn-sm btn-link text-decoration-none" onClick={() => { setShowDropdown(false); navigate('/alerts'); }} style={{ color: 'var(--bs-primary, #0d6efd)', fontSize: '0.85rem' }}>
                    View all alerts
                  </button>
                </div>
              )}
            </div>
          )}
        </div>

        {/* User Info */}
        <div 
          className="d-flex align-items-center" 
          style={{ gap: '10px' }}
        >
          <FaUserCircle size={32} className="text-success" />
          <div className="d-none d-md-block text-start">
            <div className="fw-semibold lh-1" style={{ color: 'var(--text-body)' }}>{user?.name || 'Admin User'}</div>
            {user?.roles?.length > 0 && (
              <small className="text-muted" style={{ fontSize: '0.75rem' }}>
                {typeof user.roles[0] === 'object' ? user.roles[0]?.name : user.roles[0]}
              </small>
            )}
          </div>
        </div>

        {/* Logout Button */}
        <div className="border-start border-secondary ps-4 ms-1">
          <button 
            className="btn btn-sm btn-outline-danger d-flex align-items-center rounded-pill px-3"
            onClick={handleLogout}
            title="Logout"
          >
            <FaSignOutAlt className="me-md-2" />
            <span className="d-none d-md-inline fw-semibold">Logout</span>
          </button>
        </div>
      </div>
      </nav>
    </div>
  );
};

export default Navbar;
