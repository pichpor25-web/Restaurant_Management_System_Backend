<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_only_active_categories(): void
    {
        $this->authenticate();

        Category::create(['name' => 'Available', 'is_active' => true]);
        Category::create(['name' => 'Hidden', 'is_active' => false]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Available')
            ->assertJsonPath('0.is_active', true);
    }

    public function test_store_creates_category_and_returns_created_response(): void
    {
        $this->authenticate();

        $this->postJson('/api/v1/categories', [
            'name' => 'Desserts',
            'description' => 'Sweet dishes',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Category created')
            ->assertJsonPath('data.name', 'Desserts')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('categories', [
            'name' => 'Desserts',
            'description' => 'Sweet dishes',
            'is_active' => true,
        ]);
    }

    public function test_store_returns_validation_error_when_name_is_missing(): void
    {
        $this->authenticate();

        $this->postJson('/api/v1/categories', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_show_returns_category(): void
    {
        $this->authenticate();

        $category = Category::create(['name' => 'Drinks']);

        $this->getJson("/api/v1/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('name', 'Drinks');
    }

    public function test_update_changes_category_and_returns_updated_response(): void
    {
        $this->authenticate();

        $category = Category::create(['name' => 'Sides']);

        $this->patchJson("/api/v1/categories/{$category->id}", [
            'name' => 'Side Dishes',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Category updated')
            ->assertJsonPath('data.name', 'Side Dishes')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Side Dishes',
            'is_active' => false,
        ]);
    }

    public function test_update_returns_success_when_values_are_unchanged(): void
    {
        $this->authenticate();

        $category = Category::create(['name' => 'Salads']);

        $this->patchJson("/api/v1/categories/{$category->id}", [
            'name' => 'Salads',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Category updated');
    }

    public function test_destroy_deletes_category(): void
    {
        $this->authenticate();

        $category = Category::create(['name' => 'Soups']);

        $this->deleteJson("/api/v1/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Category deleted');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_show_returns_not_found_for_missing_category(): void
    {
        $this->authenticate();

        $this->getJson('/api/v1/categories/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Category not found');
    }

    private function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }
}
