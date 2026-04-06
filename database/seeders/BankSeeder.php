<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            ['code' => '002', 'name' => 'PT. BANK RAKYAT INDONESIA (PERSERO), TBK (BRI)'],
            ['code' => '008', 'name' => 'PT. BANK MANDIRI (PERSERO), TBK'],
            ['code' => '009', 'name' => 'PT. BANK NEGARA INDONESIA (PERSERO), TBK (BNI)'],
            ['code' => '011', 'name' => 'PT. BANK DANAMON INDONESIA'],
            ['code' => '013', 'name' => 'PT. BANK PERMATA, TBK'],
            ['code' => '014', 'name' => 'PT. BANK CENTRAL ASIA, TBK - (BCA)'],
            ['code' => '016', 'name' => 'PT. BANK MAYBANK INDONESIA, TBK'],
            ['code' => '022', 'name' => 'PT. BANK CIMB NIAGA - (CIMB)'],
            ['code' => '028', 'name' => 'PT. BANK OCBC NISP, TBK'],
            ['code' => '046', 'name' => 'PT. BANK DBS INDONESIA'],
            ['code' => '054', 'name' => 'PT. BANK CAPITAL INDONESIA'],
            ['code' => '087', 'name' => 'PT. BANK HSBC INDONESIA'],
            ['code' => '095', 'name' => 'PT. BANK JTRUST INDONESIA, TBK'],
            ['code' => '111', 'name' => 'PT. BANK DKI'],
            ['code' => '137', 'name' => 'PT. BANK PEMBANGUNAN DAERAH BANTEN'],
            ['code' => '147', 'name' => 'PT. BANK MUAMALAT INDONESIA, TBK'],
            ['code' => '164', 'name' => 'PT. BANK ICBC INDONESIA'],
            ['code' => '200', 'name' => 'PT. BANK TABUNGAN NEGARA (PERSERO), TBK (BTN)'],
            ['code' => '212', 'name' => 'PT. BANK WOORI SAUDARA INDONESIA 1906, TBK (BWS)'],
            ['code' => '213', 'name' => 'PT. BANK TABUNGAN PENSIUNAN NASIONAL - (BTPN)'],
            ['code' => '422', 'name' => 'PT. BANK SYARIAH BRI - (BRI SYARIAH)'],
            ['code' => '425', 'name' => 'PT. BANK JABAR BANTEN SYARIAH'],
            ['code' => '426', 'name' => 'PT. BANK MEGA, TBK'],
            ['code' => '427', 'name' => 'PT. BNI SYARIAH'],
            ['code' => '441', 'name' => 'PT. BANK BUKOPIN'],
            ['code' => '451', 'name' => 'PT. BANK SYARIAH MANDIRI'],
            ['code' => '494', 'name' => 'PT. BANK RAKYAT INDONESIA AGRONIAGA, TBK'],
            ['code' => '521', 'name' => 'PT. BANK SYARIAH BUKOPIN'],
            ['code' => '536', 'name' => 'PT. BANK BCA SYARIAH'],
            ['code' => '547', 'name' => 'PT. BANK TABUNGAN PENSIUNAN NASIONAL SYARIAH - (BTPN Syariah)'],
            ['code' => '553', 'name' => 'PT. BANK MAYORA'],
            ['code' => '564', 'name' => 'PT. BANK MANDIRI TASPEN POS'],
            ['code' => '721', 'name' => 'PT. BANK PERMATA, TBK UNIT USAHA SYARIAH'],
            ['code' => '723', 'name' => 'PT. BANK TABUNGAN NEGARA (PERSERO), TBK UNIT USAHA SYARIAH'],
            ['code' => '724', 'name' => 'PT. BANK DKI UNIT USAHA SYARIAH'],
            ['code' => '730', 'name' => 'PT. BANK CIMB NIAGA UNIT USAHA SYARIAH - (CIMB SYARIAH)'],
            ['code' => '731', 'name' => 'PT. BANK OCBC NISP, TBK UNIT USAHA SYARIAH'],
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate(
                ['code' => $bank['code']],
                ['name' => $bank['name'], 'is_active' => true]
            );
        }
    }
}
