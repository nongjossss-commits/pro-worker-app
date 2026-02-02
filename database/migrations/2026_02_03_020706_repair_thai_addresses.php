<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $provinceMap = [
            'Bangkok' => 'กรุงเทพมหานคร',
            'Krabi' => 'กระบี่',
            'Kanchanaburi' => 'กาญจนบุรี',
            'Kalasin' => 'กาฬสินธุ์',
            'Kamphaeng Phet' => 'กำแพงเพชร',
            'Khon Kaen' => 'ขอนแก่น',
            'Chanthaburi' => 'จันทบุรี',
            'Chachoengsao' => 'ฉะเชิงเทรา',
            'Chon Buri' => 'ชลบุรี',
            'Chai Nat' => 'ชัยนาท',
            'Chaiyaphum' => 'ชัยภูมิ',
            'Chumphon' => 'ชุมพร',
            'Chiang Rai' => 'เชียงราย',
            'Chiang Mai' => 'เชียงใหม่',
            'Trang' => 'ตรัง',
            'Trat' => 'ตราด',
            'Tak' => 'ตาก',
            'Nakhon Nayok' => 'นครนายก',
            'Nakhon Pathom' => 'นครปฐม',
            'Nakhon Phanom' => 'นครพนม',
            'Nakhon Ratchasima' => 'นครราชสีมา',
            'Nakhon Si Thammarat' => 'นครศรีธรรมราช',
            'Nakhon Sawan' => 'นครสวรรค์',
            'Nonthaburi' => 'นนทบุรี',
            'Narathiwat' => 'นราธิวาส',
            'Nan' => 'น่าน',
            'Bueng Kan' => 'บึงกาฬ',
            'Buriram' => 'บุรีรัมย์',
            'Pathum Thani' => 'ปทุมธานี',
            'Prachuap Khiri Khan' => 'ประจวบคีรีขันธ์',
            'Prachin Buri' => 'ปราจีนบุรี',
            'Pattani' => 'ปัตตานี',
            'Phra Nakhon Si Ayutthaya' => 'พระนครศรีอยุธยา',
            'Phayao' => 'พะเยา',
            'Phangnga' => 'พังงา',
            'Phatthalung' => 'พัทลุง',
            'Phichit' => 'พิจิตร',
            'Phitsanulok' => 'พิษณุโลก',
            'Phetchaburi' => 'เพชรบุรี',
            'Phetchabun' => 'เพชรบูรณ์',
            'Phrae' => 'แพร่',
            'Phuket' => 'ภูเก็ต',
            'Maha Sarakham' => 'มหาสารคาม',
            'Mukdahan' => 'มุกดาหาร',
            'Mae Hong Son' => 'แม่ฮ่องสอน',
            'Yasothon' => 'ยโสธร',
            'Yala' => 'ยะลา',
            'Roi Et' => 'ร้อยเอ็ด',
            'Ranong' => 'ระนอง',
            'Rayong' => 'ระยอง',
            'Ratchaburi' => 'ราชบุรี',
            'Lopburi' => 'ลพบุรี',
            'Lampang' => 'ลำปาง',
            'Lamphun' => 'ลำพูน',
            'Loei' => 'เลย',
            'Si Sa Ket' => 'ศรีสะเกษ',
            'Sakon Nakhon' => 'สกลนคร',
            'Songkhla' => 'สงขลา',
            'Satun' => 'สตูล',
            'Samut Prakan' => 'สมุทรปราการ',
            'Samut Songkhram' => 'สมุทรสงคราม',
            'Samut Sakhon' => 'สมุทรสาคร',
            'Sa Kaeo' => 'สระแก้ว',
            'Saraburi' => 'สระบุรี',
            'Sing Buri' => 'สิงห์บุรี',
            'Sukhothai' => 'สุโขทัย',
            'Suphan Buri' => 'สุพรรณบุรี',
            'Surat Thani' => 'สุราษฎร์ธานี',
            'Surin' => 'สุรินทร์',
            'Nong Khai' => 'หนองคาย',
            'Nong Bua Lam Phu' => 'หนองบัวลำภู',
            'Ang Thong' => 'อ่างทอง',
            'Amnat Charoen' => 'อำนาจเจริญ',
            'Udon Thani' => 'อุดรธานี',
            'Uttaradit' => 'อุตรดิตถ์',
            'Uthai Thani' => 'อุทัยธานี',
            'Ubon Ratchathani' => 'อุบลราชธานี'
        ];

        $addresses = DB::table('addresses')
            ->where(function($q) {
                $q->whereNull('addrProvince')
                  ->orWhere('addrProvince', '')
                  ->orWhere('addrProvince', '-- ทุกจังหวัด --'); // Just in case
            })
            ->whereNotNull('addrProvinceEn')
            ->where('addrProvinceEn', '!=', '')
            ->get();

        foreach ($addresses as $address) {
            $en = trim($address->addrProvinceEn);
            if (isset($provinceMap[$en])) {
                DB::table('addresses')
                    ->where('id', $address->id)
                    ->update(['addrProvince' => $provinceMap[$en]]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation needed as this is a data repair
    }
};
