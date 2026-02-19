<?php

namespace Taoshan98\LaravelApiWatcher\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;

class ApiRequestFactory extends Factory
{
    protected $model = ApiRequest::class;

    public function definition(): array
    {
        $method = $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);
        $statusCode = $this->faker->randomElement([200, 201, 204, 400, 401, 403, 404, 422, 500]);
        
        $duration = match(true) {
            $statusCode >= 500 => $this->faker->numberBetween(500, 2000),
            $statusCode >= 400 => $this->faker->numberBetween(50, 200),
            default => $this->faker->numberBetween(10, 500),
        };

        $exceptionInfo = $statusCode >= 500 ? json_encode([
            'message' => 'Server Error: ' . $this->faker->sentence,
            'file' => '/var/www/html/app/Http/Controllers/ApiController.php',
            'line' => $this->faker->numberBetween(10, 100),
            'trace' => [],
        ]) : null;

        return [
            'id' => (string) Str::uuid(),
            'method' => $method,
            'url' => 'http://localhost/api/' . $this->faker->randomElement(['users', 'posts', 'comments', 'products', 'orders']) . '/' . $this->faker->numberBetween(1, 100),
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'ip_address' => $this->faker->ipv4,
            'user_id' => $this->faker->optional(0.7)->numberBetween(1, 1000),
            'route_name' => 'api.' . $this->faker->word,
            'controller_action' => 'App\Http\Controllers\ApiController@index',
            'request_headers' => ['Accept' => 'application/json', 'User-Agent' => $this->faker->userAgent],
            'request_body' => $method !== 'GET' ? json_encode(['foo' => 'bar', 'key' => $this->faker->word]) : null,
            'response_headers' => ['Content-Type' => 'application/json'],
            'response_body' => json_encode(['status' => $statusCode >= 400 ? 'error' : 'success', 'data' => []]),
            'exception_info' => $exceptionInfo,
            'tags' => [],
            'memory_usage_kb' => $this->faker->numberBetween(1000, 8000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
