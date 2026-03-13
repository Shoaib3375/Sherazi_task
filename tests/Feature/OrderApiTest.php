<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_index_returns_paginated_data()
    {
        \App\Models\Category::factory()->create();
        $customer = \App\Models\Customer::factory()->create();
        \App\Models\Order::factory()->count(20)->create(['customer_id' => $customer->id]);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'links', 'meta'])
                 ->assertJsonCount(15, 'data');
    }

    public function test_order_creation_is_transactional()
    {
        \App\Models\Category::factory()->create();
        $customer = \App\Models\Customer::factory()->create();
        $product = \App\Models\Product::factory()->create(['stock' => 5, 'price' => 100]);

        $payload = [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
                ['product_id' => 9999, 'quantity' => 1] // Invalid product
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(500); // Should fail due to exception

        // Verify no order was created
        $this->assertEquals(0, \App\Models\Order::count());
        // Verify stock was NOT decremented due to rollback
        $this->assertEquals(5, $product->fresh()->stock);
    }
}
