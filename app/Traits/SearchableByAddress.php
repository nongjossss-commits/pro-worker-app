<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SearchableByAddress
{
    /**
     * Scope a query to search by address (Province and District).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByAddress(Builder $query, string $search): Builder
    {
        return $query->whereHas('addresses', function ($q) use ($search) {
            $q->where('addrProvince', 'like', "%{$search}%")
              ->orWhere('addrDistrict', 'like', "%{$search}%");
        });
    }
}
