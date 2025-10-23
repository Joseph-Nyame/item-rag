<?php

namespace Tests\Feature;

use App\Models\Item;
use Tests\TestCase;
use Illuminate\Support\Facades\File;

class ItemCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupItems();
    }

    protected function tearDown(): void
    {
        $this->cleanupItems();
        parent::tearDown();
    }

    private function cleanupItems()
    {
        
        $contentPath = base_path('content/items');
        if (File::exists($contentPath)) {
            File::cleanDirectory($contentPath);
        }
    }

    /** @test */
    public function it_can_list_all_items()
    {
        // Create test items directly
        $item1 = Item::create(['name' => 'Item 1', 'description' => 'Description 1']);
        $item2 = Item::create(['name' => 'Item 2', 'description' => 'Description 2']);

        $response = $this->get('/items');

        $response->assertStatus(200);
        $response->assertViewIs('items.index');
        $response->assertViewHas('items');

        // Check if all items are in the view
        $response->assertSee('Item 1');
        $response->assertSee('Item 2');
    }

    /** @test */
    public function it_can_show_the_create_form()
    {
        $response = $this->get('/items/create');

        $response->assertStatus(200);
        $response->assertViewIs('items.create');
    }

    /** @test */
    public function it_can_create_an_item()
    {
        $itemData = [
            'name' => 'Test Item',
            'description' => 'This is a test item.',
        ];

        $response = $this->post('/items', $itemData);

        $response->assertStatus(302);
        $response->assertRedirect('/items');

        // Verify the item exists in Orbit
        $createdItem = Item::where('name', 'Test Item')->first();
        $this->assertNotNull($createdItem, 'Item should be created in Orbit');
        $this->assertEquals('Test Item', $createdItem->name);
        $this->assertEquals('This is a test item.', $createdItem->description);
    }

    /** @test */
    public function it_requires_name_when_creating_item()
    {
        $response = $this->post('/items', [
            'description' => 'Item without name',
        ]);

        $response->assertSessionHasErrors('name');

        // Verify no item was created
        $item = Item::where('description', 'Item without name')->first();
        $this->assertNull($item, 'No item should be created without a name');
    }

    /** @test */
    public function it_can_show_an_item()
    {
        $item = Item::create([
            'name' => 'Test Item',
            'description' => 'Test Description'
        ]);

        $response = $this->get("/items/{$item->id}");

        $response->assertStatus(200);
        $response->assertViewIs('items.show');
        $response->assertViewHas('item');
        $response->assertSee('Test Item');
        $response->assertSee('Test Description');
    }

    /** @test */
    public function it_can_show_the_edit_form()
    {
        $item = Item::create([
            'name' => 'Test Item',
            'description' => 'Test Description'
        ]);

        $response = $this->get("/items/{$item->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('items.edit');
        $response->assertViewHas('item');
        $response->assertSee('Test Item');
    }

    /** @test */
    public function it_can_update_an_item()
    {
        $item = Item::create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $updatedData = [
            'name' => 'Updated Item Name',
            'description' => 'Updated description',
        ];

        $response = $this->put("/items/{$item->id}", $updatedData);

        $response->assertStatus(302);
        $response->assertRedirect('/items');

        // Verify the item was updated
        $updatedItem = Item::find($item->id);
        $this->assertNotNull($updatedItem, 'Item should still exist after update');
        $this->assertEquals('Updated Item Name', $updatedItem->name);
        $this->assertEquals('Updated description', $updatedItem->description);
    }

    /** @test */
    public function it_requires_name_when_updating_item()
    {
        $item = Item::create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $originalName = $item->name;

        $response = $this->put("/items/{$item->id}", [
            'name' => '',
            'description' => 'Updated description',
        ]);

        $response->assertSessionHasErrors('name');

        // Verify the original data wasn't changed
        $unchangedItem = Item::find($item->id);
        $this->assertNotNull($unchangedItem);
        $this->assertEquals($originalName, $unchangedItem->name);
        $this->assertEquals('Original Description', $unchangedItem->description);
    }

    /** @test */
    public function it_can_delete_an_item()
    {
        $item = Item::create([
            'name' => 'Item to delete',
            'description' => 'Will be deleted'
        ]);

        $itemId = $item->id;

        $response = $this->delete("/items/{$itemId}");

        $response->assertStatus(302);
        $response->assertRedirect('/items');

        // Verify the item was deleted
        $deletedItem = Item::find($itemId);
        $this->assertNull($deletedItem, 'Item should be deleted from Orbit');
    }

    /** @test */
    public function it_handles_nonexistent_item_show()
    {
        $response = $this->get('/items/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_nonexistent_item_edit()
    {
        $response = $this->get('/items/999/edit');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_nonexistent_item_update()
    {
        $response = $this->put('/items/999', [
            'name' => 'Test',
            'description' => 'Test',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_nonexistent_item_delete()
    {
        $response = $this->delete('/items/999');

        $response->assertStatus(404);
    }
}
