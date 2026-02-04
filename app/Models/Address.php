<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'addrNo',
        'addrMoo',
        'addrSoi',
        'addrRoad',
        'addrProvince',
        'addrDistrict',
        'addrSubDistrict',
        'addrZipCode',
        'addrNoEn',
        'addrMooEn',
        'addrSoiEn',
        'addrRoadEn',
        'addrSubDistrictEn',
        'addrDistrictEn',
        'addrProvinceEn',
        'addrZipCodeEn',
        'addressable_id',
        'addressable_type',
    ];

    public function addressable()
    {
        return $this->morphTo();
    }

    public function getFullAddressAttribute()
    {
        if (app()->getLocale() === 'th') {
            return $this->full_address_th;
        }
        // Fallback to English fields if not Thai,
        // OR if you want to support other languages, handle them here.
        // For now, default to English structure for non-Thai.
        return $this->full_address_en;
    }

    public function getFullAddressThAttribute()
    {
        $parts = [];
        if ($this->addrNo) $parts[] = 'เลขที่ ' . $this->addrNo;
        if ($this->addrMoo) $parts[] = 'หมู่ ' . $this->addrMoo;
        if ($this->addrSoi) $parts[] = 'ซอย ' . $this->addrSoi;
        if ($this->addrRoad) $parts[] = 'ถนน ' . $this->addrRoad;

        $isBangkok = $this->addrProvince && (str_contains($this->addrProvince, 'กรุงเทพ') || str_contains($this->addrProvince, 'Bangkok'));

        if ($this->addrSubDistrict) {
            $val = trim($this->addrSubDistrict);
            $prefix = $isBangkok ? 'แขวง' : 'ต.';
            // Only add prefix if not already present
            if (!str_starts_with($val, $prefix)) {
                $parts[] = $prefix . $val;
            } else {
                $parts[] = $val;
            }
        }

        if ($this->addrDistrict) {
            $val = trim($this->addrDistrict);
            $prefix = $isBangkok ? 'เขต' : 'อ.';
            if (!str_starts_with($val, $prefix)) {
                $parts[] = $prefix . $val;
            } else {
                $parts[] = $val;
            }
        }

        if ($this->addrProvince) {
            $val = trim($this->addrProvince);
            // For Bangkok, typically no prefix or just the name. For others, use 'จ.'
            $prefix = $isBangkok ? '' : 'จ.';
            if ($prefix && !str_starts_with($val, $prefix)) {
                $parts[] = $prefix . $val;
            } else {
                $parts[] = $val;
            }
        }

        if ($this->addrZipCode) $parts[] = $this->addrZipCode;

        return implode(' ', $parts);
    }

    public function getFullAddressEnAttribute()
    {
        $parts = [];
        if ($this->addrNoEn) $parts[] = 'No. ' . $this->addrNoEn;
        if ($this->addrMooEn) $parts[] = 'Moo ' . $this->addrMooEn;
        if ($this->addrSoiEn) $parts[] = 'Soi ' . $this->addrSoiEn;
        if ($this->addrRoadEn) $parts[] = 'Rd. ' . $this->addrRoadEn;
        if ($this->addrSubDistrictEn) $parts[] = $this->addrSubDistrictEn;
        if ($this->addrDistrictEn) $parts[] = $this->addrDistrictEn;
        if ($this->addrProvinceEn) $parts[] = $this->addrProvinceEn;
        if ($this->addrZipCodeEn) $parts[] = $this->addrZipCodeEn;

        return implode(', ', $parts);
    }
}
