<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\Employer;

trait AddressFilterTrait
{
    /**
     * Apply address filters to the query.
     */
    public function applyAddressFilters(Builder $query, Request $request, $employerPath = null)
    {
        $province = $request->input('addrProvince');
        $district = $request->input('addrDistrict');
        $subDistrict = $request->input('addrSubDistrict');

        if (!$province && !$district && !$subDistrict) {
            return $query;
        }

        // Correctly navigate to addresses relation.
        // If $employerPath is 'employer', we check 'employer.addresses'
        $relation = $employerPath ? $employerPath . '.addresses' : 'addresses';

        $query->whereHas($relation, function ($q) use ($province, $district, $subDistrict) {
            if ($province) {
                $q->where('addrProvince', $province);
            }
            if ($district) {
                $q->where('addrDistrict', $district);
            }
            if ($subDistrict) {
                $q->where('addrSubDistrict', $subDistrict);
            }
        });

        return $query;
    }

    /**
     * Get dynamic address options based on the current query results (before address filtering).
     */
    public function getAddressOptions(Builder $query, $employerIdField = 'id', $joinEmployees = false)
    {
        // Clone to avoid modifying the original query
        $subQuery = (clone $query);

        // Remove orders and pagination for the subquery
        $subQuery->getQuery()->orders = null;
        $subQuery->getQuery()->limit = null;
        $subQuery->getQuery()->offset = null;

        // Ensure we only get the relevant ID column
        if ($joinEmployees) {
            $table = $subQuery->getModel()->getTable();
            $subQuery->join('employees', $table . '.employee_id', '=', 'employees.id');
            // Select the employer_id and use it as the join key
            $subQuery->select('employees.employer_id');
            $joinColumn = 'employer_id';
        } else {
            $subQuery->select($employerIdField);
            // Extract the simple column name (alias) for the join condition
            // e.g. "employers.id" -> "id", "employer_id" -> "employer_id"
            $joinColumn = str_contains($employerIdField, '.') ? last(explode('.', $employerIdField)) : $employerIdField;
        }

        // Get the correct morph class for Employer to ensure matching regardless of MorphMap
        $morphClass = (new Employer)->getMorphClass();
        // Also include the short class name to handle legacy data or direct saves
        $possibleMorphs = array_unique([$morphClass, class_basename($morphClass)]);

        // Use a joinSub to robustly filter addresses belonging to the filtered employers
        // This avoids limitations of whereIn with subqueries and handles bindings correctly
        $baseAddressQuery = Address::query()
            ->whereIn('addressable_type', $possibleMorphs)
            ->joinSub($subQuery, 'filtered_source', function ($join) use ($joinColumn) {
                $join->on('addresses.addressable_id', '=', 'filtered_source.' . $joinColumn);
            });

        $provinces = (clone $baseAddressQuery)
            ->distinct()
            ->pluck('addrProvince')
            ->filter()
            ->sort()
            ->values();

        $districts = collect();
        $selectedProvince = request('addrProvince');
        if ($selectedProvince) {
            $districts = (clone $baseAddressQuery)
                ->where('addrProvince', $selectedProvince)
                ->distinct()
                ->pluck('addrDistrict')
                ->filter()
                ->sort()
                ->values();
        }

        $subDistricts = collect();
        $selectedDistrict = request('addrDistrict');
        if ($selectedProvince && $selectedDistrict) {
            $subDistricts = (clone $baseAddressQuery)
                ->where('addrProvince', $selectedProvince)
                ->where('addrDistrict', $selectedDistrict)
                ->distinct()
                ->pluck('addrSubDistrict')
                ->filter()
                ->sort()
                ->values();
        }

        return [
            'provinces' => $provinces,
            'districts' => $districts,
            'subDistricts' => $subDistricts,
        ];
    }

    /**
     * Get labels for matched addresses to display in brackets.
     */
    public function getMatchedAddressLabels($employer, $province, $district = null, $subDistrict = null)
    {
        if (!$province) {
            return [];
        }

        if (!$employer || !$employer->relationLoaded('addresses')) {
            return [];
        }

        $labels = [];
        foreach ($employer->addresses as $address) {
            if ($address->addrProvince === $province) {
                if ($district && $address->addrDistrict !== $district) {
                    continue;
                }
                if ($subDistrict && $address->addrSubDistrict !== $subDistrict) {
                    continue;
                }

                $labels[] = ($address->type === 'registered') ? 'ที่อยู่ตามทะเบียนบ้าน' : 'ที่อยู่สถานที่ทำงาน';
            }
        }

        return array_unique($labels);
    }
}
