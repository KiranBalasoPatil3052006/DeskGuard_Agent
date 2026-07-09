import { FiAlertTriangle, FiRefreshCw } from 'react-icons/fi';

export function ErrorState({ message = 'Something went wrong', onRetry }) {
  return (
    <div className="flex flex-col items-center justify-center py-16 text-center">
      <div className="bg-red-50 p-4 rounded-full mb-4">
        <FiAlertTriangle className="w-8 h-8 text-red-500" />
      </div>
      <h3 className="text-lg font-semibold text-gray-800 mb-1">Error</h3>
      <p className="text-gray-500 text-sm mb-4 max-w-md">{message}</p>
      {onRetry && (
        <button onClick={onRetry} className="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors text-sm font-medium">
          <FiRefreshCw className="w-4 h-4" />
          Retry
        </button>
      )}
    </div>
  );
}

export function InlineError({ message }) {
  return (
    <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
      <FiAlertTriangle className="w-4 h-4 flex-shrink-0" />
      {message}
    </div>
  );
}