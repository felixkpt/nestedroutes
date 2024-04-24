<?php

namespace App\Models;

trait ExcludeSystemFillable
{
    /**
     * Get the fillable attributes, excluding hidden attributes.
     *
     * @return array
     */
    public function getFillable($from_search_repo = false)
    {
        $fillable = parent::getFillable(); // Get the original fillable attributes.

        if (!$from_search_repo) return $fillable;

        // Remove systemFillable attributes from the fillable array.
        $fillable = array_diff($fillable, $this->systemFillable ?? []);

        return $fillable;
    }
}
