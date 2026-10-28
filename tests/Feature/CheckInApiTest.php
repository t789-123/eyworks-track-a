<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckInApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_child_can_be_checked_in_to_a_room_for_the_same_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $room = Room::factory()->create(['tenant_id' => $tenant->id]);
        $child = Child::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/rooms/'.$room->id.'/check-ins', ['child_id' => $child->id]);

        $response->assertStatus(201)
            ->assertJsonPath('data.child_id', $child->id)
            ->assertJsonPath('data.room_id', $room->id)
            ->assertJsonPath('data.tenant_id', $tenant->id);

        $this->assertDatabaseHas('check_ins', [
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'child_id' => $child->id,
            'checked_out_at' => null,
        ]);
    }

    public function test_a_child_cannot_be_checked_in_twice_while_active(): void
    {
        $tenant = Tenant::factory()->create();
        $room = Room::factory()->create(['tenant_id' => $tenant->id]);
        $child = Child::factory()->create(['tenant_id' => $tenant->id]);

        CheckIn::factory()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'child_id' => $child->id,
            'checked_out_at' => null,
        ]);

        $response = $this->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/rooms/'.$room->id.'/check-ins', ['child_id' => $child->id]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Child already has an active check-in.');
    }

    public function test_a_child_cannot_be_checked_in_for_a_different_tenant_room(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $room = Room::factory()->create(['tenant_id' => $otherTenant->id]);
        $child = Child::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/rooms/'.$room->id.'/check-ins', ['child_id' => $child->id]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Room not found for tenant.');
    }

    public function test_an_invalid_tenant_header_is_rejected(): void
    {
        $response = $this->withHeader('X-Tenant-ID', 'bad-header')
            ->postJson('/api/rooms/1/check-ins', ['child_id' => 1]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Missing or invalid tenant header.');
    }

    public function test_unknown_tenant_is_rejected(): void
    {
        $response = $this->withHeader('X-Tenant-ID', '999999')
            ->postJson('/api/rooms/1/check-ins', ['child_id' => 1]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Unknown tenant.');
    }

    public function test_a_check_in_can_be_checked_out(): void
    {
        $tenant = Tenant::factory()->create();
        $room = Room::factory()->create(['tenant_id' => $tenant->id]);
        $child = Child::factory()->create(['tenant_id' => $tenant->id]);
        $checkIn = CheckIn::factory()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'child_id' => $child->id,
            'checked_in_at' => now()->subHour(),
            'checked_out_at' => null,
        ]);

        $response = $this->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/check-ins/'.$checkIn->id.'/check-out');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Child checked out successfully.');

        $this->assertNotNull(CheckIn::query()->find($checkIn->id)->checked_out_at);
    }

    public function test_check_out_fails_for_missing_record_or_wrong_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $checkIn = CheckIn::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/check-ins/'.$checkIn->id.'/check-out');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Check-in not found for tenant.');
    }

    public function test_active_occupants_are_returned_for_the_room_only(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $room = Room::factory()->create(['tenant_id' => $tenant->id]);
        $otherRoom = Room::factory()->create(['tenant_id' => $tenant->id]);
        $otherTenantRoom = Room::factory()->create(['tenant_id' => $otherTenant->id]);

        $activeChild = Child::factory()->create(['tenant_id' => $tenant->id]);
        $checkedOutChild = Child::factory()->create(['tenant_id' => $tenant->id]);
        $foreignChild = Child::factory()->create(['tenant_id' => $otherTenant->id]);

        CheckIn::factory()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'child_id' => $activeChild->id,
            'checked_out_at' => null,
        ]);
        CheckIn::factory()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'child_id' => $checkedOutChild->id,
            'checked_out_at' => now(),
        ]);
        CheckIn::factory()->create([
            'tenant_id' => $otherTenant->id,
            'room_id' => $otherTenantRoom->id,
            'child_id' => $foreignChild->id,
            'checked_out_at' => null,
        ]);

        $response = $this->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/rooms/'.$room->id.'/occupants');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $activeChild->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_the_room_occupants_endpoint_returns_empty_array_for_empty_room(): void
    {
        $tenant = Tenant::factory()->create();
        $room = Room::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/rooms/'.$room->id.'/occupants');

        $response->assertStatus(200)
            ->assertExactJson(['data' => []]);
    }
}
