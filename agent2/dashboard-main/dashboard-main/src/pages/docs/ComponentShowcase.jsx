import React, { useState } from 'react';
import { Badge, ProgressBar, Skeleton, Timeline, Dialog } from '../../components/ui/CoreComponents';
import { useToast } from '../../components/ui/ToastContext';
import { FaServer, FaCheckCircle, FaExclamationTriangle } from 'react-icons/fa';

const ComponentShowcase = () => {
  const { showToast } = useToast();
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  const mockTimelineEvents = [
    { time: '10:00 AM', title: 'System Boot', description: 'Server started successfully.', icon: <FaServer className="text-primary"/> },
    { time: '11:15 AM', title: 'Warning', description: 'High memory usage detected.', icon: <FaExclamationTriangle className="text-warning"/> },
    { time: '12:30 PM', title: 'Task Completed', description: 'Backup finished.', icon: <FaCheckCircle className="text-success"/> }
  ];

  return (
    <div className="container-fluid p-0">
      <h3 className="fw-bold text-dark-blue mb-4">UI Components Showcase</h3>
      
      <div className="row g-4">
        {/* Badges & Progress */}
        <div className="col-12 col-xl-6">
          <div className="card border-0 glass-card p-4 h-100">
            <h5 className="fw-bold mb-4 border-bottom pb-2">Data Display</h5>
            
            <h6 className="fw-semibold text-muted mb-3">Badges</h6>
            <div className="d-flex gap-2 flex-wrap mb-4">
              <Badge variant="primary">Primary</Badge>
              <Badge variant="success">Success</Badge>
              <Badge variant="warning text-dark">Warning</Badge>
              <Badge variant="danger">Danger</Badge>
              <Badge variant="info">Info</Badge>
            </div>

            <h6 className="fw-semibold text-muted mb-3">Progress Bars</h6>
            <div className="d-flex flex-column gap-3 mb-4">
              <ProgressBar value={25} label="Storage Used" variant="primary" />
              <ProgressBar value={75} label="CPU Load" variant="warning" />
              <ProgressBar value={90} label="Memory Limit" variant="danger" />
            </div>

            <h6 className="fw-semibold text-muted mb-3">Loading Skeletons</h6>
            <div className="d-flex flex-column gap-2">
              <div className="d-flex gap-3 align-items-center">
                <Skeleton width="48px" height="48px" circle={true} />
                <div className="flex-grow-1">
                  <Skeleton width="40%" height="16px" className="mb-2" />
                  <Skeleton width="70%" height="12px" />
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Feedback & Interactivity */}
        <div className="col-12 col-xl-6">
          <div className="card border-0 glass-card p-4 h-100">
            <h5 className="fw-bold mb-4 border-bottom pb-2">Feedback & Interactions</h5>

            <h6 className="fw-semibold text-muted mb-3">Notification Toasts</h6>
            <div className="d-flex gap-2 flex-wrap mb-4">
              <button className="btn btn-outline-success" onClick={() => showToast('Operation saved successfully!', 'success')}>Success Toast</button>
              <button className="btn btn-outline-danger" onClick={() => showToast('Failed to connect to server.', 'error')}>Error Toast</button>
              <button className="btn btn-outline-warning" onClick={() => showToast('Disk space running low.', 'warning')}>Warning Toast</button>
              <button className="btn btn-outline-info" onClick={() => showToast('System update available.', 'info')}>Info Toast</button>
            </div>

            <h6 className="fw-semibold text-muted mb-3">Dialogs & Modals</h6>
            <div className="mb-4">
              <button className="btn btn-primary" onClick={() => setIsDialogOpen(true)}>Open Confirmation Dialog</button>
            </div>

            <h6 className="fw-semibold text-muted mb-3">Timeline</h6>
            <div className="bg-light p-3 rounded border">
              <Timeline events={mockTimelineEvents} />
            </div>
          </div>
        </div>
      </div>

      <Dialog 
        isOpen={isDialogOpen} 
        onClose={() => setIsDialogOpen(false)}
        onConfirm={() => {
          showToast('Action confirmed!', 'success');
          setIsDialogOpen(false);
        }}
        title="Confirm Deletion"
        message="Are you sure you want to delete this machine? This action cannot be undone."
        confirmText="Delete Machine"
        confirmVariant="danger"
      />
    </div>
  );
};

export default ComponentShowcase;
