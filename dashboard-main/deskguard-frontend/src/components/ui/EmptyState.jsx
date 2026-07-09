import { FiInbox } from 'react-icons/fi';

export function EmptyState({ icon: Icon = FiInbox, title = 'No data', description = 'There is nothing to display yet.' }) {
  return (
    <div className="flex flex-col items-center justify-center py-16 text-center">
      <div className="bg-gray-50 p-4 rounded-full mb-4">
        <Icon className="w-8 h-8 text-gray-400" />
      </div>
      <h3 className="text-lg font-semibold text-gray-600 mb-1">{title}</h3>
      <p className="text-gray-400 text-sm max-w-md">{description}</p>
    </div>
  );
}