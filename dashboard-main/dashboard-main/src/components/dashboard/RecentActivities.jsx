import React from 'react';
import { FaPlug, FaPowerOff, FaFileAlt, FaLaptopMedical, FaSignInAlt } from 'react-icons/fa';

const RecentActivities = () => {
  const activities = [
    { type: 'Machine Connected', detail: 'SRV-DB-01 came online.', time: '2 mins ago', icon: <FaPlug />, color: 'success' },
    { type: 'User Login', detail: 'Admin User logged in.', time: '15 mins ago', icon: <FaSignInAlt />, color: 'primary' },
    { type: 'Machine Disconnected', detail: 'WKST-DEV-44 went offline unexpectedly.', time: '1 hour ago', icon: <FaPowerOff />, color: 'danger' },
    { type: 'Report Generated', detail: 'Weekly usage report was generated.', time: '3 hours ago', icon: <FaFileAlt />, color: 'info' },
    { type: 'New Machine Added', detail: 'SRV-APP-03 registered to the network.', time: '1 day ago', icon: <FaLaptopMedical />, color: 'secondary' },
  ];

  return (
    <div className="card h-100">
      <div className="card-header">
        Recent Activities
      </div>
      <div className="card-body p-0">
        <ul className="list-group list-group-flush">
          {activities.map((activity, index) => (
            <li className="list-group-item px-4 py-3" key={index}>
              <div className="d-flex align-items-center">
                <div 
                  className={`bg-${activity.color} bg-opacity-10 text-${activity.color} rounded p-2 me-3`}
                  style={{ width: '40px', height: '40px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                >
                  {activity.icon}
                </div>
                <div className="flex-grow-1">
                  <h6 className="mb-1 text-dark fw-semibold" style={{ fontSize: '0.95rem' }}>{activity.type}</h6>
                  <p className="mb-0 text-muted" style={{ fontSize: '0.85rem' }}>{activity.detail}</p>
                </div>
                <div className="text-muted small">
                  {activity.time}
                </div>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
};

export default RecentActivities;
