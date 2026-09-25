<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('adminPanelSetting', (object) [
            'system_name' => 'Salon Manager',
            'system_logo' => null,
            'block_insufficient_consumables' => false,
            'void_time_window_hours' => 24,
        ]);

        $role = Role::create(['name' => 'Admin']);
        $permission = Permission::create(['name' => 'home.index', 'group' => 'home']);
        $role->givePermissionTo($permission);

        $this->adminUser = User::factory()->create([
            'status' => 'active',
        ]);
        $this->adminUser->assignRole($role);
    }

    public function test_can_switch_language_to_arabic_via_route(): void
    {
        $response = $this->get(route('lang.switch', 'ar'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'ar');
    }

    public function test_can_switch_language_to_english_via_route(): void
    {
        $response = $this->withSession(['locale' => 'ar'])
            ->get(route('lang.switch', 'en'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    public function test_invalid_language_code_is_rejected(): void
    {
        $response = $this->withSession(['locale' => 'en'])
            ->get(route('lang.switch', 'invalid_locale'));

        $response->assertRedirect();
        $this->assertEquals('en', session('locale', 'en'));
    }

    public function test_set_locale_middleware_handles_query_parameter(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('home.index', ['lang' => 'ar']));

        $this->assertEquals('ar', app()->getLocale());
        $this->assertEquals('ar', session('locale'));
    }

    public function test_arabic_locale_renders_rtl_layout_and_arabic_assets(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['locale' => 'ar'])
            ->get(route('home.index'));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('bootstrap.rtl.min.css', false);
        $response->assertSee('rtl.css', false);
        $response->assertSee('Cairo', false);
        $response->assertSee('تسجيل الخروج');
    }

    public function test_english_locale_renders_ltr_layout_and_english_assets(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['locale' => 'en'])
            ->get(route('home.index'));

        $response->assertOk();
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('lang="en"', false);
        $response->assertSee('bootstrap.min.css', false);
        $response->assertDontSee('bootstrap.rtl.min.css', false);
        $response->assertDontSee('rtl.css', false);
        $response->assertSee('Sign Out');
    }

    public function test_login_page_renders_in_arabic_when_locale_set(): void
    {
        $response = $this->withSession(['locale' => 'ar'])
            ->get(route('login'));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee(__('Login to Your Account'));
    }
}
