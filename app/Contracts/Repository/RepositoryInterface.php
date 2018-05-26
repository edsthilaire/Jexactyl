<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    /**
     * Return an identifier or Model object to be used by the repository.
     *
     * @return string|\Closure|object
     */
    public function model();

    /**
     * Return the model being used for this repository instance.
     *
     * @return mixed
     */
    public function getModel();

    /**
     * Returns an instance of a query builder.
     *
     * @return mixed
     */
    public function getBuilder();

    /**
     * Returns the columns to be selected or returned by the query.
     *
     * @return mixed
     */
    public function getColumns();

    /**
     * An array of columns to filter the response by.
     *
     * @param array|string $columns
     * @return $this
     */
    public function setColumns($columns = ['*']);

    /**
     * Stop repository update functions from returning a fresh
     * model when changes are committed.
     *
     * @return $this
     */
    public function withoutFreshModel();

    /**
     * Return a fresh model with a repository updates a model.
     *
     * @return $this
     */
    public function withFreshModel();

    /**
     * Set whether or not the repository should return a fresh model
     * when changes are committed.
     *
     * @param bool $fresh
     * @return $this
     */
    public function setFreshModel(bool $fresh = true);

    /**
     * Create a new model instance and persist it to the database.
     *
     * @param array $fields
     * @param bool  $validate
     * @param bool  $force
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create(array $fields, bool $validate = true, bool $force = false);

    /**
     * Find a model that has the specific ID passed.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function find(int $id);

    /**
     * Find a model matching an array of where clauses.
     *
     * @param array $fields
     * @return \Illuminate\Support\Collection
     */
    public function findWhere(array $fields): Collection;

    /**
     * Find and return the first matching instance for the given fields.
     *
     * @param array $fields
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function findFirstWhere(array $fields);

    /**
     * Return a count of records matching the passed arguments.
     *
     * @param array $fields
     * @return int
     */
    public function findCountWhere(array $fields): int;

    /**
     * Delete a given record from the database.
     *
     * @param int $id
     * @return int
     */
    public function delete(int $id): int;

    /**
     * Delete records matching the given attributes.
     *
     * @param array $attributes
     * @return int
     */
    public function deleteWhere(array $attributes): int;

    /**
     * Update a given ID with the passed array of fields.
     *
     * @param int   $id
     * @param array $fields
     * @param bool  $validate
     * @param bool  $force
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update($id, array $fields, bool $validate = true, bool $force = false);

    /**
     * Perform a mass update where matching records are updated using whereIn.
     * This does not perform any model data validation.
     *
     * @param string $column
     * @param array  $values
     * @param array  $fields
     * @return int
     */
    public function updateWhereIn(string $column, array $values, array $fields): int;

    /**
     * Update a record if it exists in the database, otherwise create it.
     *
     * @param array $where
     * @param array $fields
     * @param bool  $validate
     * @param bool  $force
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function updateOrCreate(array $where, array $fields, bool $validate = true, bool $force = false);

    /**
     * Return all records associated with the given model.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Return a paginated result set using a search term if set on the repository.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginated(int $perPage): LengthAwarePaginator;

    /**
     * Insert a single or multiple records into the database at once skipping
     * validation and mass assignment checking.
     *
     * @param array $data
     * @return bool
     */
    public function insert(array $data): bool;

    /**
     * Insert multiple records into the database and ignore duplicates.
     *
     * @param array $values
     * @return bool
     */
    public function insertIgnore(array $values): bool;
}
