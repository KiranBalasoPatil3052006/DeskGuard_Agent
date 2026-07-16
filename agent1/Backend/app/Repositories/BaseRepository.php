<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * Class BaseRepository
 *
 * Abstract base repository providing common CRUD operations
 * for all Eloquent models in the application.
 *
 * @package App\Repositories
 */
abstract class BaseRepository
{
    /**
     * The Eloquent model instance.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model The Eloquent model to bind to the repository.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Find a record by its primary key.
     *
     * @param int $id The primary key value.
     * @return Model|null The model instance if found, null otherwise.
     */
    public function findById(int $id): ?Model
    {
        try {
            return $this->model->find($id);
        } catch (\Throwable $e) {
            Log::error('BaseRepository::findById - Failed to find record by ID', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Retrieve all records from the associated model table.
     *
     * @param array $columns The columns to select.
     * @return Collection A collection of all records.
     */
    public function findAll(array $columns = ['*']): Collection
    {
        try {
            return $this->model->all($columns);
        } catch (\Throwable $e) {
            Log::error('BaseRepository::findAll - Failed to retrieve all records', [
                'error' => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Create a new record in the database.
     *
     * @param array $data The attribute key-value pairs for the new record.
     * @return Model The newly created model instance.
     */
    public function create(array $data): Model
    {
        try {
            return $this->model->create($data);
        } catch (\Throwable $e) {
            Log::error('BaseRepository::create - Failed to create record', [
                'data'  => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing record identified by its primary key.
     *
     * @param int   $id   The primary key of the record to update.
     * @param array $data The attribute key-value pairs to update.
     * @return bool True if the update was successful, false otherwise.
     */
    public function update(int $id, array $data): bool
    {
        try {
            $record = $this->model->find($id);

            if (!$record) {
                Log::warning('BaseRepository::update - Record not found', ['id' => $id]);
                return false;
            }

            return $record->update($data);
        } catch (\Throwable $e) {
            Log::error('BaseRepository::update - Failed to update record', [
                'id'    => $id,
                'data'  => $data,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete a record by its primary key.
     *
     * @param int $id The primary key of the record to delete.
     * @return bool True if the record was deleted, false otherwise.
     */
    public function delete(int $id): bool
    {
        try {
            $record = $this->model->find($id);

            if (!$record) {
                Log::warning('BaseRepository::delete - Record not found', ['id' => $id]);
                return false;
            }

            return (bool) $record->delete();
        } catch (\Throwable $e) {
            Log::error('BaseRepository::delete - Failed to delete record', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Paginate the results of the associated model query.
     *
     * @param int   $perPage The number of items per page.
     * @param array $columns The columns to select.
     * @return LengthAwarePaginator The paginated result set.
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        try {
            return $this->model->paginate($perPage, $columns);
        } catch (\Throwable $e) {
            Log::error('BaseRepository::paginate - Failed to paginate records', [
                'perPage' => $perPage,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Find records where a given field matches a value.
     *
     * @param string $field   The column name to search against.
     * @param mixed  $value   The value to match.
     * @param array  $columns The columns to select.
     * @return Collection A collection of matching records.
     */
    public function findByField(string $field, $value, array $columns = ['*']): Collection
    {
        try {
            return $this->model->where($field, '=', $value)->get($columns);
        } catch (\Throwable $e) {
            Log::error('BaseRepository::findByField - Failed to find records by field', [
                'field' => $field,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            return new Collection();
        }
    }

    /**
     * Find records matching an array of conditions.
     *
     * Each condition is an associative array with keys:
     * - 'field'    => string (required)
     * - 'operator' => string (optional, defaults to '=')
     * - 'value'    => mixed  (required)
     *
     * Alternately, a flat associative array [column => value] may be used,
     * in which case the operator defaults to '='.
     *
     * @param array $conditions An array of conditions.
     * @param array $columns    The columns to select.
     * @return Collection A collection of matching records.
     */
    public function findWhere(array $conditions, array $columns = ['*']): Collection
    {
        try {
            $query = $this->model->newQuery();

            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $field    = $value['field'];
                    $operator = $value['operator'] ?? '=';
                    $val      = $value['value'];
                    $query->where($field, $operator, $val);
                } else {
                    $query->where($field, '=', $value);
                }
            }

            return $query->get($columns);
        } catch (\Throwable $e) {
            Log::error('BaseRepository::findWhere - Failed to find records by conditions', [
                'conditions' => $conditions,
                'error'      => $e->getMessage(),
            ]);
            return new Collection();
        }
    }
}
