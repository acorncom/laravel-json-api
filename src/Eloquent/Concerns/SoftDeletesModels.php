<?php
/**
 * Copyright 2019 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi\Eloquent\Concerns;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Trait SoftDeletesModels
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait SoftDeletesModels
{

    /**
     * @param Model $record
     * @param EncodingParametersInterface $params
     * @return bool
     */
    public function delete($record, EncodingParametersInterface $params)
    {
        return (bool) $record->forceDelete();
    }

    /**
     * @param $resourceId
     * @return Builder
     */
    protected function findQuery($resourceId)
    {
        return $this->newQuery()->withTrashed()->where(
            $this->getQualifiedKeyName(),
            $resourceId
        );
    }

    /**
     * @param Model $record
     * @param Collection $attributes
     */
    protected function fillAttributes($record, Collection $attributes)
    {
        $field = $this->getSoftDeleteField($record);

        if ($attributes->has($field)) {
            $this->fillSoftDelete($record, $field, $attributes->get($field));
        }

        $record->fill(
            $this->deserializeAttributes($attributes->forget($field), $record)
        );
    }

    /**
     * Fill the soft delete value if it has been provided.
     *
     * @param Model $record
     * @param string $field
     * @param mixed $value
     */
    protected function fillSoftDelete(Model $record, $field, $value)
    {
        $value = $this->deserializeSoftDelete(
            $value,
            $field,
            $record
        );

        $record->forceFill([
            $this->getSoftDeleteKey($record) => $value,
        ]);
    }

    /**
     * Deserialize the provided value for the soft delete attribute.
     *
     * If a boolean is provided, we interpret the soft delete value as now.
     * We check for all the boolean values accepted by the Laravel boolean
     * validator.
     *
     * @param $value
     * @param $field
     * @param $record
     * @return Carbon|null
     */
    protected function deserializeSoftDelete($value, $field, $record)
    {
        if (collect([true, false, 1, 0, '1', '0'])->containsStrict($value)) {
            return $value ? Carbon::now() : null;
        }

        return $this->deserializeAttribute($value, $field, $record);
    }

    /**
     * The JSON API field name that is used for the soft delete value.
     *
     * If none is set, defaults to the dasherized version of the model's
     * deleted_at column.
     *
     * @return string|null
     */
    protected function softDeleteField()
    {
        return property_exists($this, 'softDeleteField') ? $this->softDeleteField : null;
    }

    /**
     * Get the JSON API field that is used for the soft-delete value.
     *
     * @param Model $record
     * @return string
     */
    protected function getSoftDeleteField(Model $record)
    {
        if ($field = $this->softDeleteField()) {
            return $field;
        }

        $key = $this->getSoftDeleteKey($record);

        return Str::dasherize($key);
    }

    /**
     * Get the model key that should be used for the soft-delete value.
     *
     * @param Model $record
     * @return string
     */
    protected function getSoftDeleteKey(Model $record)
    {
        return $record->getDeletedAtColumn();
    }

    /**
     * @param $record
     * @return void
     */
    protected function persist($record)
    {
        $this->saveOrRestore($record);
    }

    /**
     * @param Model $record
     */
    protected function saveOrRestore(Model $record)
    {
        if ($this->willRestore($record)) {
           $record->restore();
       } else {
            $record->save();
        }
    }

    /**
     * @param Model $record
     * @return bool
     */
    protected function willRestore(Model $record)
    {
        if (!$record->exists) {
            return false;
        }

        if (!$key = $this->getSoftDeleteKey($record)) {
            return false;
        }

        return $record->isDirty($key) && !$record->trashed();
    }

}
