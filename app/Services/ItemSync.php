<?php

namespace App\Services;

use OpenAI;
use App\Models\Item;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ItemSync
{
    private $client;
    protected $qdrantUrl;
    protected string $collectionName = 'items';
    protected int $vectorSize = 1536;

    public function __construct(protected OpenAI $openai)
    {
        $this->qdrantUrl = 'http://' . env('VECTORDB_HOST', 'localhost') . ':' . env('QDRANT_PORT', '6333');
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
        
        // Verify Qdrant connection
        try {
            $response = Http::get("{$this->qdrantUrl}/readyz");
            if (!$response->successful()) {
                throw new \Exception("Qdrant not available at {$this->qdrantUrl}");
            }
        } catch (\Exception $e) {
            Log::error("Qdrant connection failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Full sync of all items
     */
    public function fullSync(): int
    {
        try {
            $this->ensureCollectionExists();

            $items = Item::all();
            if ($items->isEmpty()) {
                Log::info("No items to sync.");
                return 0;
            }

            // Batch embed items for efficiency
            $texts = $items->map(fn($item) => implode(' ', array_filter([
                $item->name,
                $item->description,
            ])) ?: json_encode($item->toArray()))->toArray();

            $embeddings = $this->embedTextBatch($texts);

            // Prepare points with proper structure
            $points = [];
            foreach ($items as $index => $item) {
                if (!isset($embeddings[$index]) || count($embeddings[$index]) !== $this->vectorSize) {
                    Log::error("Invalid embedding for item {$item->id}");
                    continue;
                }

                $points[] = [
                    'id' => Str::uuid(), // Ensure ID is string
                    'vector' => $embeddings[$index],
                    'payload' => [
                        'id' => $item->id,
                        'name' => $item->name ?? '',
                        'description' => $item->description ?? '',
                        'created_at' => $item->created_at?->toDateTimeString() ?? now()->toDateTimeString(),
                        'updated_at' => $item->updated_at?->toDateTimeString() ?? now()->toDateTimeString(),
                    ],
                ];
            }

            if (empty($points)) {
                throw new \Exception('No valid points to sync.');
            }

            $payload = ['points' => $points];
            Log::debug("Qdrant fullSync payload: " . json_encode($payload, JSON_PRETTY_PRINT));

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->put("{$this->qdrantUrl}/collections/{$this->collectionName}/points", $payload);

            if ($response->failed()) {
                Log::error("Qdrant fullSync failed: Status {$response->status()}, Body: {$response->body()}");
                throw new \Exception('Qdrant sync failed: ' . $response->body());
            }

            return count($points);
        } catch (\Exception $e) {
            Log::error("ItemSync fullSync failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Sync a single item
     */
    public function syncSingle(Item $item): bool
    {
        try {
            $this->ensureCollectionExists();

            $point = $this->preparePoint($item);
            $payload = ['points' => [$point]];
            
            Log::debug("Qdrant syncSingle payload: " . json_encode($payload, JSON_PRETTY_PRINT));

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->put("{$this->qdrantUrl}/collections/{$this->collectionName}/points", $payload);

            if ($response->failed()) {
                Log::error("Qdrant syncSingle failed for item {$item->id}: Status {$response->status()}, Body: {$response->body()}");
                throw new \Exception('Qdrant upsert failed: ' . $response->body());
            }

            return true;
        } catch (\Exception $e) {
            Log::error("ItemSync syncSingle failed for item {$item->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Delete a single item
     */
    public function deleteSingle(int $itemId): bool
    {
        try {
            // First find the UUID associated with this item
            $item = Item::find($itemId);
            if (!$item) {
                Log::debug("Item {$itemId} not found, skipping delete");
                return true;
            }

            // Get the point ID from Qdrant (you might need to implement this)
            $pointId = $this->getPointIdForItem($itemId);
            
            if (!$pointId) {
                Log::debug("Point for item {$itemId} not found in Qdrant, skipping delete");
                return true;
            }

            $response = Http::post(
                "{$this->qdrantUrl}/collections/{$this->collectionName}/points/delete",
                ['points' => [$pointId]]
            );

            if ($response->failed() && $response->status() !== 404) {
                Log::error("Qdrant delete failed for item {$itemId}: Status {$response->status()}, Body: {$response->body()}");
                throw new \Exception('Qdrant delete failed: ' . $response->body());
            }

            return true;
        } catch (\Exception $e) {
            Log::error("ItemSync deleteSingle failed for item {$itemId}: {$e->getMessage()}");
            throw $e;
        }
    }


    protected function getPointIdForItem(int $itemId): ?string
    {
        // Query Qdrant to find the point ID for this item
        $response = Http::post(
            "{$this->qdrantUrl}/collections/{$this->collectionName}/points/scroll",
            [
                'filter' => [
                    'must' => [
                        [
                            'key' => 'original_id',
                            'match' => ['value' => $itemId]
                        ]
                    ]
                ],
                'limit' => 1
            ]
        );

        if ($response->successful()) {
            $data = $response->json();
            return $data['result']['points'][0]['id'] ?? null;
        }

        return null;
    }
    /**
     * Ensure the Qdrant collection exists
     */
    protected function ensureCollectionExists(): void
    {
        $response = Http::get("{$this->qdrantUrl}/collections/{$this->collectionName}");

        if ($response->status() === 404) {
            $response = Http::put("{$this->qdrantUrl}/collections/{$this->collectionName}", [
                'vectors' => [
                    'size' => $this->vectorSize,
                    'distance' => 'Cosine',
                ],
                'optimizers_config' => [
                    'default_segment_number' => 2,
                    'indexing_threshold' => 100,
                ],
            ]);

            if ($response->failed()) {
                Log::error("Collection creation failed: Status {$response->status()}, Body: {$response->body()}");
                throw new \Exception('Collection creation failed: ' . $response->body());
            }
            
            Log::info("Collection {$this->collectionName} created successfully");
        } elseif ($response->failed()) {
            Log::error("Collection check failed: Status {$response->status()}, Body: {$response->body()}");
            throw new \Exception('Collection check failed: ' . $response->body());
        }
    }

    /**
     * Prepare a single point for an item
     */
    protected function preparePoint(Item $item): array
{
    $text = implode(' ', array_filter([
        $item->name,
        $item->description,
    ])) ?: json_encode($item->toArray());

    $vector = $this->embedText($text);

    return [
        'id' => (string) Str::uuid(), // Generate new UUID for each point
        'vector' => $vector,
        'payload' => [
            'original_id' => $item->id, 
            'name' => $item->name ?? '',
            'description' => $item->description ?? '',
            'created_at' => $item->created_at?->toDateTimeString() ?? now()->toDateTimeString(),
            'updated_at' => $item->updated_at?->toDateTimeString() ?? now()->toDateTimeString(),
        ],
    ];
}

    /**
     * Embed a single text using OpenAI
     */
    protected function embedText(string $text): array
    {
        try {
            Log::debug("Generating embedding for text: " . substr($text, 0, 50) . "...");
            
            $response = $this->client->embeddings()->create([
                'model' => 'text-embedding-ada-002',
                'input' => $text,
            ]);

            $embedding = $response->embeddings[0]->embedding;
            
            Log::debug("Embedding generated. Length: " . count($embedding));

            if (count($embedding) !== $this->vectorSize) {
                throw new \Exception("Invalid embedding size: expected {$this->vectorSize}, got " . count($embedding));
            }

            return $embedding;
        } catch (\Exception $e) {
            Log::error("Failed to embed text: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Embed multiple texts in batch
     */
    protected function embedTextBatch(array $texts): array
    {
        try {
            $texts = array_filter($texts, 'strlen');
            if (empty($texts)) {
                throw new \Exception('No valid texts provided for embedding');
            }

            $response = $this->client->embeddings()->create([
                'model' => 'text-embedding-ada-002',
                'input' => $texts,
            ]);

            $embeddings = array_map(fn($embedding) => $embedding->embedding, $response->embeddings);
            
            foreach ($embeddings as $index => $embedding) {
                if (count($embedding) !== $this->vectorSize) {
                    Log::error("Invalid batch embedding size at index {$index}: expected {$this->vectorSize}, got " . count($embedding));
                    throw new \Exception("Invalid batch embedding size at index {$index}");
                }
            }

            Log::debug("Batch embeddings generated: " . count($embeddings) . " vectors");
            return $embeddings;
        } catch (\Exception $e) {
            Log::error("Failed to embed text batch: {$e->getMessage()}");
            throw $e;
        }
    }
}