<?php

namespace Tests\Feature;

use App\DataTables\ProductDataTable;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Branch $branch;
    protected ProductCategory $category;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('adminPanelSetting', (object) [
            'system_name' => 'Salon Manager',
            'system_logo' => null,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Boss',
            'email' => 'admin_test@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);

        $permissions = [
            'products.index',
            'products.create',
            'products.edit',
            'products.destroy',
            'products.show',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm, 'guard_name' => 'web'],
                ['group' => 'products']
            );
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($permissions);
        $this->adminUser->assignRole('admin');

        $this->branch = Branch::create([
            'name' => 'Main Branch',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->category = ProductCategory::create([
            'name' => 'Hair Care',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->unit = Unit::create([
            'name' => 'Piece',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_product_image_can_be_uploaded_and_displayed(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('luxury_product.png', 400, 400);

        $response = $this->actingAs($this->adminUser)->post(route('products.store'), [
            'name' => 'Luxury Argan Oil Hair Mask',
            'code' => 99887766,
            'branch_id' => $this->branch->id,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'initial_quantity' => 10,
            'is_target' => 0,
            'price_can_change' => 1,
            'type' => 'sales',
            'status' => 'active',
            'image' => $file,
        ]);

        $response->assertSessionHasNoErrors();

        $product = Product::where('name', 'Luxury Argan Oil Hair Mask')->first();
        $this->assertNotNull($product, 'Product should be created in database');
        $this->assertNotNull($product->image, 'Product image path should be stored');

        // Verify storage on public disk
        Storage::disk('public')->assertExists($product->image);

        // Verify ProductDataTable endpoint returns rendered img tag with storage path
        $tableResponse = $this->actingAs($this->adminUser)->getJson(route('products.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $this->assertStringContainsString('storage/'.$product->image, $tableResponse->json('data.0.image'));
        $this->assertStringContainsString('<img', $tableResponse->json('data.0.image'));

        // Verify Edit View displays image instead of placeholder
        $editResponse = $this->actingAs($this->adminUser)->get(route('products.edit', $product->id));
        $editResponse->assertOk();
        $editResponse->assertSee('storage/'.$product->image, false);
    }
}
