<?php

namespace Database\Seeders;

use App\Models\UserFee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['vip_level' => 'VIP 8', 'discount_purchase' => 10, 'transfer_fee_vn' => 10, 'min_accumulation' => 20000000000, 'max_accumulation' => 999999999999999, 'min_privilege' => 50],
            ['vip_level' => 'VIP 7', 'discount_purchase' => 0, 'transfer_fee_vn' => 0, 'min_accumulation' => 10000000000, 'max_accumulation' => 20000000000, 'min_privilege' => 70],
            ['vip_level' => 'VIP 6', 'discount_purchase' => 0, 'transfer_fee_vn' => 0, 'min_accumulation' => 5000000000, 'max_accumulation' => 10000000000, 'min_privilege' => 70],
            ['vip_level' => 'VIP 5', 'discount_purchase' => 0, 'transfer_fee_vn' => 0, 'min_accumulation' => 2500000000, 'max_accumulation' => 5000000000, 'min_privilege' => 70],
            ['vip_level' => 'VIP 4', 'discount_purchase' => 10, 'transfer_fee_vn' => 10, 'min_accumulation' => 1500000000, 'max_accumulation' => 2500000000, 'min_privilege' => 70],
            ['vip_level' => 'VIP 3', 'discount_purchase' => 0, 'transfer_fee_vn' => 0, 'min_accumulation' => 800000000, 'max_accumulation' => 1500000000, 'min_privilege' => 70],
            ['vip_level' => 'VIP 2', 'discount_purchase' => 0, 'transfer_fee_vn' => 0, 'min_accumulation' => 300000000, 'max_accumulation' => 800000000, 'min_privilege' => 70],
            ['vip_level' => 'VIP 1', 'discount_purchase' => 1, 'transfer_fee_vn' => 0, 'min_accumulation' => 100000000, 'max_accumulation' => 900000000, 'min_privilege' => 80],
            ['vip_level' => 'VIP 0', 'discount_purchase' => 0, 'transfer_fee_vn' => 0, 'min_accumulation' => 0, 'max_accumulation' => 11000000, 'min_privilege' => 90],
        ];
        foreach ($data as $item) {
            UserFee::create($item);
        }
    }
}
