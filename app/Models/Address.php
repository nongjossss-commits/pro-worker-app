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

    public function getFullAddressThAttribute()
    {
        $parts = [];
        if ($this->addrNo) $parts[] = 'เลขที่ ' . $this->addrNo;
        if ($this->addrMoo) $parts[] = 'หมู่ ' . $this->addrMoo;
        if ($this->addrSoi) $parts[] = 'ซอย ' . $this->addrSoi;
        if ($this->addrRoad) $parts[] = 'ถนน ' . $this->addrRoad;
        if ($this->addrSubDistrict) $parts[] = 'ต.' . $this->addrSubDistrict;
        if ($this->addrDistrict) $parts[] = 'อ.' . $this->addrDistrict;
        if ($this->addrProvince) $parts[] = 'จ.' . $this->addrProvince;
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
