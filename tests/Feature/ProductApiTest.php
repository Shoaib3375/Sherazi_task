<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_products_index_returns_paginated_data()
    {
        \App\Models\Category::factory()->create();
        \App\Models\Product::factory()->count(20)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'name', 'price', 'stock', 'category']
                     ],
                     'links',
                     'meta'
                 ])
                 ->assertJsonCount(15, 'data');
    }

    public function test_dashboard_is_cached()
    {
        \App\Models\Category::factory()->create();
        \App\Models\Product::factory()->count(5)->create();

        $response1 = $this->getJson('/api/products/dashboard');
        $response1->assertStatus(200);

        // Modify product name directly in DB to see if cache persists
        \Illuminate\Support\Facades\DB::table('products')->update(['name' => 'Cached Name']);

        $response2 = $this->getJson('/api/products/dashboard');
        // The categories are returned in dashboard, and they should be cached
        $this->assertEquals($response1->json(), $response2->json());
    }
}
