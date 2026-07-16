import { Component } from 'react';

export default class ErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, info) {
    console.error('ErrorBoundary caught:', error, info);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '300px' }}>
          <div className="text-center">
            <div className="mb-3" style={{ fontSize: '48px' }}>⚠️</div>
            <h5 className="fw-bold mb-2" style={{ color: 'var(--text-body)' }}>Something went wrong</h5>
            <p className="text-muted mb-3 small">{this.state.error?.message || 'An unexpected error occurred'}</p>
            <button className="btn btn-primary btn-sm" onClick={() => this.setState({ hasError: false, error: null })}>
              Try Again
            </button>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}
