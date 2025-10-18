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
}
