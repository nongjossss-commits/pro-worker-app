<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

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
}
