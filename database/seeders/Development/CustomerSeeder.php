<?php

namespace Database\Seeders\Development;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $config = SimulationConfig::instance();
        $startDate = $config->startDate->copy();

        $maleFirstNames = [
            'Ahmed', 'Mohamed', 'Mahmoud', 'Tarek', 'Karim', 'Hassan', 'Youssef', 'Omar', 'Ali', 'Sherif',
            'Amr', 'Mostafa', 'Khaled', 'Hesham', 'Sameh', 'Adel', 'Ibrahim', 'Ziad', 'Nader', 'Ramy',
            'Hany', 'Walid', 'Hazem', 'Ayman', 'Bassam', 'Fady', 'Ehab', 'Bahaa', 'Mina', 'George',
        ];

        $femaleFirstNames = [
            'Sara', 'Mona', 'Nour', 'Layla', 'Dina', 'Yasmin', 'Mariam', 'Hoda', 'Salma', 'Reem',
            'Noha', 'Aya', 'Farida', 'Inas', 'Zeina', 'Rania', 'Jana', 'Heba', 'Mai', 'Nada',
            'Nourhan', 'Fatma', 'Donia', 'Dalia', 'Engy', 'Hend', 'Shereen', 'Lina', 'Mirna', 'Sandy',
        ];

        $familyNames = [
            'Hassan', 'Ali', 'Mansour', 'El-Sayed', 'Ibrahim', 'Kamel', 'Radwan', 'Zaki', 'Nour', 'Shaker',
            'Abdelrahman', 'Fouad', 'Farag', 'Ghoneim', 'El-Masry', 'Salem', 'Wahba', 'Sami', 'Lotfy', 'Nabil',
            'Gaber', 'Khattab', 'El-Naggar', 'Sabry', 'Metwally', 'Tawfik', 'Fahmy', 'Sadek', 'El-Husseini', 'Diab',
        ];

        $neighborhoods = [
            '15 Brazil St, Zamalek, Cairo',
            '28 Road 9, Maadi, Cairo',
            'Plot 72, South Academy, New Cairo',
            '14 Beirut St, Heliopolis, Cairo',
            '5 Abbas El-Akkad St, Nasr City, Cairo',
            '9 Mossaddak St, Dokki, Giza',
            '23 Shehab St, Mohandessin, Giza',
            'Beverly Hills, Gate 2, Sheikh Zayed',
            'Al-Rehab City, Group 112, New Cairo',
            'Palm Hills October, Compound Entrance',
        ];

        $totalCustomers = 1200;
        $customersToInsert = [];
        $depositsToInsert = [];

        for ($i = 1; $i <= $totalCustomers; $i++) {
            $isFemale = ($i % 3 !== 0); // 66% female, 34% male (realistic salon mix)
            $gender = $isFemale ? 'female' : 'male';
            $firstName = $isFemale ? $femaleFirstNames[($i * 7) % count($femaleFirstNames)] : $maleFirstNames[($i * 11) % count($maleFirstNames)];
            $lastName = $familyNames[($i * 13) % count($familyNames)];
            $fullName = "{$firstName} {$lastName}";

            $salutation = match (true) {
                ($i % 25 === 0) => 'Dr',
                ($i % 35 === 0) => 'Eng',
                $isFemale && ($i % 4 === 0) => 'Mrs',
                $isFemale => 'Ms',
                default => 'Mr',
            };

            // Cohort allocation
            // 1-90: VIP
            // 91-390: Regulars
            // 391-750: Occasional
            // 751-970: Churned
            // 971-1120: New
            // 1121-1200: Problematic / Edge cases
            $isVip = ($i <= 90);
            $isNew = ($i >= 971 && $i <= 1120);
            $isChurned = ($i >= 751 && $i <= 970);

            // Registration date
            $createdAt = match (true) {
                $isNew => $config->anchorDate->copy()->subDays(mt_rand(3, 50)),
                $isChurned => $startDate->copy()->addDays(mt_rand(5, 90)),
                default => $startDate->copy()->addDays(mt_rand(1, 280)),
            };

            $uniquePhone = '01'.($i % 4 === 0 ? '0' : ($i % 4 === 1 ? '1' : ($i % 4 === 2 ? '2' : '5'))).str_pad((string) $i, 8, '0', STR_PAD_LEFT);
            $uniqueEmail = 'client.'.strtolower(str_replace(' ', '.', $fullName)).".{$i}@example.test";
            $address = $neighborhoods[$i % count($neighborhoods)];

            $source = match ($i % 5) {
                0 => 'referral',
                1 => 'online',
                2 => 'walk_in',
                3 => 'advertisement',
                default => 'direct',
            };

            $customersToInsert[] = [
                'id' => $i,
                'name' => $fullName,
                'email' => $uniqueEmail,
                'phone' => $uniquePhone,
                'address' => $address,
                'salutation' => $salutation,
                'status' => 'active',
                'dob' => Carbon::createFromDate(1975 + ($i % 28), ($i % 12) + 1, ($i % 27) + 1)->toDateString(),
                'notes' => $isVip ? 'VIP client. Prefers Senior Stylist and sparkling water.' : null,
                'is_vip' => $isVip,
                'gender' => $gender,
                'last_service' => null, // will be dynamically established in appointment/sales simulation
                'added_from' => $source,
                'created_by' => 1,
                'updated_by' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            // 40 VIP clients receive initial pre-paid deposit balances ($300 to $1,500)
            if ($isVip && $i <= 40) {
                $depositAmount = match ($i % 4) {
                    0 => 500.00,
                    1 => 750.00,
                    2 => 1000.00,
                    default => 1500.00,
                };

                $depositsToInsert[] = [
                    'customer_id' => $i,
                    'reference_type' => 'deposit',
                    'reference_id' => 0,
                    'amount' => $depositAmount,
                    'notes' => "Initial prepaid salon deposit credit (EGP {$depositAmount})",
                    'status' => 'available',
                    'used_in_transaction_id' => null,
                    'created_by' => 1,
                    'updated_by' => null,
                    'created_at' => $createdAt->copy()->addDays(2),
                    'updated_at' => $createdAt->copy()->addDays(2),
                ];
            }
        }

        // Fast chunked insert for customers
        foreach (array_chunk($customersToInsert, 250) as $chunk) {
            DB::table('customers')->insert($chunk);
        }

        // Insert initial deposits
        if (! empty($depositsToInsert)) {
            DB::table('customer_transactions')->insert($depositsToInsert);
        }
    }
}
