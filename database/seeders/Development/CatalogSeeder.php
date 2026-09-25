<?php

namespace Database\Seeders\Development;

use App\Models\Employee;
use App\Models\Product;
use App\Models\Service;
use App\Models\Tool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $config = SimulationConfig::instance();

        // 1. Define Services Catalog (36 core services across 6 categories)
        $servicesCatalog = [
            // Category 1: Haircuts & Styling
            ['name' => "Women's Signature Haircut & Blowdry", 'cat_id' => 1, 'price' => 350.00, 'duration' => 60, 'branch_id' => 1],
            ['name' => "Men's Executive Haircut & Wash", 'cat_id' => 1, 'price' => 200.00, 'duration' => 45, 'branch_id' => 1],
            ['name' => "Children's Haircut (Under 12)", 'cat_id' => 1, 'price' => 150.00, 'duration' => 30, 'branch_id' => 1],
            ['name' => 'Classic Blowout & Thermal Styling', 'cat_id' => 1, 'price' => 220.00, 'duration' => 45, 'branch_id' => 1],
            ['name' => 'Hollywood Red Carpet Waves', 'cat_id' => 1, 'price' => 450.00, 'duration' => 75, 'branch_id' => 1],
            ['name' => 'Bridal Trial Hair Updo', 'cat_id' => 1, 'price' => 800.00, 'duration' => 90, 'branch_id' => 3],

            // Category 2: Hair Color & Balayage Treatments
            ['name' => 'Full Balayage & Toning Gloss', 'cat_id' => 2, 'price' => 1800.00, 'duration' => 180, 'branch_id' => 1],
            ['name' => 'Root Touch-Up & Color Refresh', 'cat_id' => 2, 'price' => 650.00, 'duration' => 90, 'branch_id' => 1],
            ['name' => 'Full Head Highlights (Foil)', 'cat_id' => 2, 'price' => 1400.00, 'duration' => 150, 'branch_id' => 1],
            ['name' => 'Brazilian Keratin Smoothing Treatment', 'cat_id' => 2, 'price' => 2200.00, 'duration' => 180, 'branch_id' => 1],
            ['name' => 'Olaplex Intensive Bond Repair Therapy', 'cat_id' => 2, 'price' => 550.00, 'duration' => 60, 'branch_id' => 1],
            ['name' => 'Caviar Scalp & Deep Hydration Treatment', 'cat_id' => 2, 'price' => 750.00, 'duration' => 60, 'branch_id' => 3],

            // Category 3: Nail Care & Aesthetics
            ['name' => 'Classic Spa Manicure', 'cat_id' => 3, 'price' => 180.00, 'duration' => 45, 'branch_id' => 1],
            ['name' => 'Luxury Paraffin Pedicure', 'cat_id' => 3, 'price' => 260.00, 'duration' => 60, 'branch_id' => 1],
            ['name' => 'Russian Dry Manicure + Gel Polish', 'cat_id' => 3, 'price' => 380.00, 'duration' => 75, 'branch_id' => 1],
            ['name' => 'Full Set Acrylic Nail Extensions', 'cat_id' => 3, 'price' => 650.00, 'duration' => 90, 'branch_id' => 2],
            ['name' => 'Gel Nail Refill & French Tip Art', 'cat_id' => 3, 'price' => 350.00, 'duration' => 60, 'branch_id' => 2],
            ['name' => 'Express Cut & File Polish Change', 'cat_id' => 3, 'price' => 120.00, 'duration' => 30, 'branch_id' => 2],

            // Category 4: Facial & Skincare Treatments
            ['name' => 'HydraFacial Deluxe Deep Cleansing', 'cat_id' => 4, 'price' => 1100.00, 'duration' => 75, 'branch_id' => 1],
            ['name' => 'Vitamin C Radiance & Glow Facial', 'cat_id' => 4, 'price' => 850.00, 'duration' => 60, 'branch_id' => 1],
            ['name' => 'Anti-Aging Collagen Boost Therapy', 'cat_id' => 4, 'price' => 1300.00, 'duration' => 90, 'branch_id' => 3],
            ['name' => 'Purifying Acne & Extraction Facial', 'cat_id' => 4, 'price' => 700.00, 'duration' => 60, 'branch_id' => 2],
            ['name' => 'Glycolic Chemical Skin Peel', 'cat_id' => 4, 'price' => 950.00, 'duration' => 60, 'branch_id' => 3],
            ['name' => 'LED Light Phototherapy Session', 'cat_id' => 4, 'price' => 400.00, 'duration' => 45, 'branch_id' => 1],

            // Category 5: Spa & Therapeutic Massages
            ['name' => 'Swedish Full-Body Relaxation Massage (60m)', 'cat_id' => 5, 'price' => 600.00, 'duration' => 60, 'branch_id' => 1],
            ['name' => 'Deep Tissue Tension Relief Massage (90m)', 'cat_id' => 5, 'price' => 850.00, 'duration' => 90, 'branch_id' => 1],
            ['name' => 'Heated Basalt Stone Massage Therapy (75m)', 'cat_id' => 5, 'price' => 950.00, 'duration' => 75, 'branch_id' => 3],
            ['name' => 'Moroccan Traditional Hammam & Body Scrub', 'cat_id' => 5, 'price' => 1200.00, 'duration' => 90, 'branch_id' => 1],
            ['name' => 'Aromatherapy Stress Relief Massage (60m)', 'cat_id' => 5, 'price' => 700.00, 'duration' => 60, 'branch_id' => 3],
            ['name' => 'Express Neck & Shoulder Tension Relief (30m)', 'cat_id' => 5, 'price' => 300.00, 'duration' => 30, 'branch_id' => 2],

            // Category 6: Men's Grooming & Beard Care
            ['name' => 'Royal Hot Towel Beard Trim & Sculpt', 'cat_id' => 6, 'price' => 180.00, 'duration' => 35, 'branch_id' => 1],
            ['name' => 'Straight Razor Traditional Wet Shave', 'cat_id' => 6, 'price' => 150.00, 'duration' => 30, 'branch_id' => 1],
            ['name' => "Gentleman's Scalp & Beard Detox Treatment", 'cat_id' => 6, 'price' => 320.00, 'duration' => 45, 'branch_id' => 1],
            ['name' => 'Charcoal Nose & Ear Wax Grooming', 'cat_id' => 6, 'price' => 90.00, 'duration' => 20, 'branch_id' => 1],
            ['name' => "Men's Executive Haircut + Beard Combo", 'cat_id' => 6, 'price' => 320.00, 'duration' => 60, 'branch_id' => 2],
            ['name' => "Men's Matte Charcoal Facial Cleansing", 'cat_id' => 6, 'price' => 450.00, 'duration' => 45, 'branch_id' => 2],
        ];

        $createdServices = [];
        foreach ($servicesCatalog as $index => $item) {
            $service = Service::updateOrCreate(
                ['name' => $item['name']],
                [
                    'service_category_id' => $item['cat_id'],
                    'price' => $item['price'],
                    'outside_price' => $item['price'] * 1.5,
                    'duration' => $item['duration'],
                    'branch_id' => $item['branch_id'],
                    'is_target' => ($item['price'] >= 1000),
                    'price_can_change' => true,
                    'status' => 'active',
                    'notes' => 'Premium service provided by certified specialists.',
                    'created_by' => 1,
                ]
            );
            $createdServices[$service->id] = $service;
        }

        // 2. Define Products Catalog (50 Retail Sales + 25 Backbar Operation Consumables)
        $productsCatalog = [
            // --- Retail Products (type: 'sales') ---
            ['name' => 'Kérastase Bain Force Architecte Shampoo 250ml', 'code' => '100001', 'cat_id' => 1, 'sup_id' => 3, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'Kérastase Elixir Ultime L’Huile Originale 100ml', 'code' => '100002', 'cat_id' => 2, 'sup_id' => 3, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'Kérastase Masque Thérapiste Hair Mask 200ml', 'code' => '100003', 'cat_id' => 1, 'sup_id' => 3, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'Kérastase Genesis Anti Hair-Fall Serum 90ml', 'code' => '100004', 'cat_id' => 2, 'sup_id' => 3, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => "L'Oréal Serie Expert Absolut Repair Mask 250ml", 'code' => '100005', 'cat_id' => 1, 'sup_id' => 1, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => "L'Oréal Serie Expert Metal Detox Shampoo 300ml", 'code' => '100006', 'cat_id' => 1, 'sup_id' => 1, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => "L'Oréal Tecni.Art Fix Design Hair Spray 200ml", 'code' => '100007', 'cat_id' => 2, 'sup_id' => 1, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'Schwarzkopf BC Bonacure Moisture Kick Spray Conditioner 200ml', 'code' => '100008', 'cat_id' => 1, 'sup_id' => 2, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'Schwarzkopf Osis+ Dust It Mattifying Powder 10g', 'code' => '100009', 'cat_id' => 2, 'sup_id' => 2, 'unit_id' => 1, 'type' => 'sales'],
            ['name' => 'OPI ProSpa Protective Hand & Cuticle Cream 50ml', 'code' => '100010', 'cat_id' => 4, 'sup_id' => 4, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'OPI Nail Enamel - Big Apple Red 15ml', 'code' => '100011', 'cat_id' => 4, 'sup_id' => 4, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'OPI Nail Enamel - Bubble Bath 15ml', 'code' => '100012', 'cat_id' => 4, 'sup_id' => 4, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'Dermalogica Daily Microfoliant Exfoliator 74g', 'code' => '100013', 'cat_id' => 3, 'sup_id' => 5, 'unit_id' => 1, 'type' => 'sales'],
            ['name' => 'Dermalogica Special Cleansing Gel 250ml', 'code' => '100014', 'cat_id' => 3, 'sup_id' => 5, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'Dermalogica BioLumin-C Vitamin C Serum 30ml', 'code' => '100015', 'cat_id' => 3, 'sup_id' => 5, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'Moroccanoil Treatment Original Hair Oil 100ml', 'code' => '100016', 'cat_id' => 2, 'sup_id' => 3, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'Moroccanoil Intense Hydrating Mask 250ml', 'code' => '100017', 'cat_id' => 1, 'sup_id' => 3, 'unit_id' => 2, 'type' => 'sales'],
            ['name' => 'Reuzel Blue Strong Hold Water Soluble Pomade 113g', 'code' => '100018', 'cat_id' => 2, 'sup_id' => 6, 'unit_id' => 1, 'type' => 'sales'],
            ['name' => 'Reuzel Refresh & Hydrate Beard Balm 35g', 'code' => '100019', 'cat_id' => 2, 'sup_id' => 6, 'unit_id' => 1, 'type' => 'sales'],
            ['name' => 'Proraso Pre-Shave Cream Refreshing Eucalyptus 100ml', 'code' => '100020', 'cat_id' => 2, 'sup_id' => 6, 'unit_id' => 2, 'type' => 'sales'],

            // --- Backbar Consumables (type: 'operation') ---
            ['name' => "L'Oréal Majirel Permanent Hair Color Tube 50ml", 'code' => '100050', 'cat_id' => 5, 'sup_id' => 1, 'unit_id' => 1, 'type' => 'operation'],
            ['name' => "L'Oréal Blond Studio Multi-Techniques Bleaching Powder 500g", 'code' => '100051', 'cat_id' => 5, 'sup_id' => 1, 'unit_id' => 1, 'type' => 'operation'],
            ['name' => "L'Oréal Oxydant Developer 20 Vol (6%) 1000ml", 'code' => '100052', 'cat_id' => 5, 'sup_id' => 1, 'unit_id' => 2, 'type' => 'operation'],
            ['name' => "L'Oréal Oxydant Developer 30 Vol (9%) 1000ml", 'code' => '100053', 'cat_id' => 5, 'sup_id' => 1, 'unit_id' => 2, 'type' => 'operation'],
            ['name' => 'Salon Professional Clarifying Backbar Shampoo 5000ml', 'code' => '100054', 'cat_id' => 5, 'sup_id' => 2, 'unit_id' => 2, 'type' => 'operation'],
            ['name' => 'Salon Professional Neutralizing Acid Rinse 5000ml', 'code' => '100055', 'cat_id' => 5, 'sup_id' => 2, 'unit_id' => 2, 'type' => 'operation'],
            ['name' => 'Sweet Almond & Lavender Therapeutic Massage Oil 1000ml', 'code' => '100056', 'cat_id' => 5, 'sup_id' => 6, 'unit_id' => 2, 'type' => 'operation'],
            ['name' => 'Organic Moroccan Black Soap Beldi 1000g', 'code' => '100057', 'cat_id' => 5, 'sup_id' => 6, 'unit_id' => 1, 'type' => 'operation'],
            ['name' => 'OPI Expert Touch Pure Acetone Lacquer Remover 960ml', 'code' => '100058', 'cat_id' => 5, 'sup_id' => 4, 'unit_id' => 2, 'type' => 'operation'],
            ['name' => 'OPI GelColor Base & Top Coat Professional Duo', 'code' => '100059', 'cat_id' => 5, 'sup_id' => 4, 'unit_id' => 6, 'type' => 'operation'],
            ['name' => 'Disposable Hygienic Salon Towels Pack (100 pcs)', 'code' => '100060', 'cat_id' => 5, 'sup_id' => 6, 'unit_id' => 3, 'type' => 'operation'],
            ['name' => 'Medical Grade Nitrile Gloves Box (100 pcs)', 'code' => '100061', 'cat_id' => 5, 'sup_id' => 6, 'unit_id' => 3, 'type' => 'operation'],
        ];

        $createdProducts = [];
        foreach ($productsCatalog as $item) {
            $product = Product::updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'category_id' => $item['cat_id'],
                    'supplier_id' => $item['sup_id'],
                    'unit_id' => $item['unit_id'],
                    'type' => $item['type'],
                    'status' => 'active',
                    'branch_id' => 1,
                    'is_target' => ($item['type'] === 'sales'),
                    'price_can_change' => true,
                    'initial_quantity' => 100,
                    'created_by' => 1,
                ]
            );
            $createdProducts[$product->id] = $product;
        }

        // 3. Link Services to Certified Employees (service_employees with commissions)
        // Group employees by specialty:
        // Stylists: Sara(5), Mohamed(6), Layla(7), Nourhan(12), Mostafa(17), Mahmoud(21), Ziad(26), Tamer(28)
        // Barbers: Omar(8), Sherif(19)
        // Nail Techs: Dina(9), Mariam(18), Zeina(29)
        // Skincare & Spa: Hoda(10), Ahmed(11), Aya(20), Farida(25), Inas(27)

        $serviceEmployeeMappings = [
            // Haircut & Blowdry services
            1 => [5 => 0.35, 6 => 0.30, 7 => 0.30, 12 => 0.20, 17 => 0.30, 21 => 0.20, 26 => 0.35, 28 => 0.30],
            2 => [8 => 0.35, 19 => 0.30, 6 => 0.30, 17 => 0.30],
            3 => [5 => 0.30, 6 => 0.30, 12 => 0.25, 17 => 0.30],
            4 => [5 => 0.35, 6 => 0.30, 7 => 0.35, 12 => 0.20, 17 => 0.30, 26 => 0.35],
            5 => [5 => 0.40, 7 => 0.35, 26 => 0.40],
            6 => [5 => 0.40, 7 => 0.35, 26 => 0.40],

            // Color & Chemical treatments
            7 => [5 => 0.40, 7 => 0.40, 26 => 0.40],
            8 => [5 => 0.35, 7 => 0.35, 6 => 0.30, 17 => 0.30, 26 => 0.35],
            9 => [5 => 0.35, 7 => 0.35, 17 => 0.30, 26 => 0.35],
            10 => [5 => 0.35, 6 => 0.30, 17 => 0.30, 26 => 0.35],
            11 => [5 => 0.30, 7 => 0.30, 12 => 0.25, 17 => 0.30],
            12 => [5 => 0.35, 7 => 0.35, 26 => 0.35],

            // Nails
            13 => [9 => 0.35, 18 => 0.30, 29 => 0.25],
            14 => [9 => 0.35, 18 => 0.30, 29 => 0.25],
            15 => [9 => 0.40, 18 => 0.35],
            16 => [9 => 0.40, 18 => 0.35],
            17 => [9 => 0.35, 18 => 0.30, 29 => 0.25],
            18 => [9 => 0.30, 18 => 0.25, 29 => 0.25],

            // Facial & Skincare
            19 => [10 => 0.35, 20 => 0.30, 25 => 0.40],
            20 => [10 => 0.35, 20 => 0.30, 25 => 0.40],
            21 => [10 => 0.40, 25 => 0.45],
            22 => [10 => 0.30, 20 => 0.30],
            23 => [10 => 0.35, 25 => 0.40],
            24 => [10 => 0.30, 20 => 0.25, 25 => 0.35],

            // Massage & Spa
            25 => [11 => 0.35, 27 => 0.35],
            26 => [11 => 0.40, 27 => 0.40],
            27 => [11 => 0.40, 27 => 0.40],
            28 => [11 => 0.35, 27 => 0.35],
            29 => [11 => 0.35, 27 => 0.35],
            30 => [11 => 0.30, 27 => 0.30],

            // Men's Grooming
            31 => [8 => 0.35, 19 => 0.30],
            32 => [8 => 0.35, 19 => 0.30],
            33 => [8 => 0.35, 19 => 0.30],
            34 => [8 => 0.30, 19 => 0.25],
            35 => [8 => 0.35, 19 => 0.30],
            36 => [8 => 0.30, 19 => 0.25, 20 => 0.25],
        ];

        DB::table('service_employees')->truncate();
        foreach ($serviceEmployeeMappings as $serviceId => $providers) {
            foreach ($providers as $empId => $commRate) {
                DB::table('service_employees')->insert([
                    'service_id' => $serviceId,
                    'employee_id' => $empId,
                    'commission_type' => 'percentage',
                    'commission_value' => $commRate * 100, // stored as 30.00, 35.00, etc.
                    'is_immediate_commission' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 4. Link Services to Required Tools (service_tools)
        DB::table('service_tools')->truncate();
        $toolLinks = [
            // Haircut -> Scissors (2), Hairdryer (1)
            1 => [1, 2], 2 => [1, 2], 3 => [1, 2], 4 => [1, 3], 5 => [1, 3], 6 => [1, 3],
            // Nails -> UV/LED Lamp (4), Sterilizer (6)
            13 => [6], 14 => [6], 15 => [4, 6], 16 => [4, 6], 17 => [4, 6],
            // Facial -> Steamer (5), Sterilizer (6)
            19 => [5, 6], 20 => [5, 6], 21 => [5, 6],
            // Stone massage -> Stone Warmer (8)
            27 => [8],
            // Barber -> Clipper (7), Scissors (2)
            31 => [2, 7], 32 => [2, 7], 35 => [2, 7],
        ];
        foreach ($toolLinks as $srvId => $toolIds) {
            foreach ($toolIds as $tId) {
                DB::table('service_tools')->insert([
                    'service_id' => $srvId,
                    'tool_id' => $tId,
                    'tool_quantity' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 5. Link Services to Consumables (service_products)
        // Find product IDs for backbar products:
        $colorCream = Product::where('code', '100050')->value('id');
        $bleachPowder = Product::where('code', '100051')->value('id');
        $dev20 = Product::where('code', '100052')->value('id');
        $massageOil = Product::where('code', '100056')->value('id');
        $moroccanSoap = Product::where('code', '100057')->value('id');
        $acetone = Product::where('code', '100058')->value('id');
        $gelDuo = Product::where('code', '100059')->value('id');

        DB::table('service_products')->truncate();
        $consumableLinks = [
            // Balayage (7) consumes 1 Bleach, 1 Dev
            7 => [[$bleachPowder, 1], [$dev20, 1]],
            // Root touchup (8) consumes 1 Color Cream, 1 Dev
            8 => [[$colorCream, 1], [$dev20, 1]],
            // Highlights (9) consumes 1 Bleach, 1 Dev
            9 => [[$bleachPowder, 1], [$dev20, 1]],
            // Russian Gel (15) consumes Gel Duo, Acetone
            15 => [[$gelDuo, 1], [$acetone, 1]],
            // Acrylic Extensions (16) consumes Gel Duo, Acetone
            16 => [[$gelDuo, 1], [$acetone, 1]],
            // Swedish Massage (25) consumes Massage Oil
            25 => [[$massageOil, 1]],
            // Deep Tissue (26) consumes Massage Oil
            26 => [[$massageOil, 1]],
            // Moroccan Hammam (28) consumes Moroccan Soap
            28 => [[$moroccanSoap, 1]],
        ];
        foreach ($consumableLinks as $srvId => $cItems) {
            foreach ($cItems as [$prodId, $qty]) {
                if ($prodId) {
                    DB::table('service_products')->insert([
                        'service_id' => $srvId,
                        'product_id' => $prodId,
                        'product_quantity' => $qty,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
