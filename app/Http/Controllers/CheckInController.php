<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class CheckInController extends Controller
{
    public function store(Request $request, int $room): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $room = Room::query()->find($room);

        if (! $tenant instanceof Tenant || ! $room || $room->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Room not found for tenant.'], Response::HTTP_NOT_FOUND);
        }

        $validated = Validator::make($request->all(), [
            'child_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validated->fails()) {
            return response()->json([
                'message' => 'Invalid child ID.',
                'errors' => $validated->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $childId = (int) $validated->validated()['child_id'];

        try {
            $checkIn = DB::transaction(function () use ($tenant, $room, $childId) {
                $child = Child::query()
                    ->whereKey($childId)
                    ->lockForUpdate()
                    ->first();

                if (! $child || $child->tenant_id !== $tenant->id) {
                    throw new \RuntimeException('Invalid child ID.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $activeCheckIn = CheckIn::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('child_id', $child->id)
                    ->whereNull('checked_out_at')
                    ->lockForUpdate()
                    ->first();

                if ($activeCheckIn) {
                    throw new \RuntimeException('Child already has an active check-in.', Response::HTTP_CONFLICT);
                }

                return CheckIn::query()->create([
                    'tenant_id' => $tenant->id,
                    'room_id' => $room->id,
                    'child_id' => $child->id,
                    'checked_in_at' => Carbon::now(),
                ]);
            });
        } catch (\RuntimeException $exception) {
            $status = $exception->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY;
            $message = $exception->getMessage();

            if ($status === Response::HTTP_CONFLICT) {
                return response()->json(['message' => $message], Response::HTTP_CONFLICT);
            }

            return response()->json(['message' => $message], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'Child checked in successfully.',
            'data' => [
                'id' => $checkIn->id,
                'tenant_id' => $checkIn->tenant_id,
                'room_id' => $checkIn->room_id,
                'child_id' => $checkIn->child_id,
                'checked_in_at' => $checkIn->checked_in_at->toISOString(),
                'checked_out_at' => null,
            ],
        ], Response::HTTP_CREATED);
    }

    public function checkOut(Request $request, int $checkIn): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $checkIn = CheckIn::query()->find($checkIn);

        if (! $tenant || ! $checkIn || $checkIn->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Check-in not found for tenant.'], Response::HTTP_NOT_FOUND);
        }

        if (! is_null($checkIn->checked_out_at)) {
            return response()->json(['message' => 'Check-in already checked out.'], Response::HTTP_CONFLICT);
        }

        $checkIn->update([
            'checked_out_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Child checked out successfully.',
            'data' => [
                'id' => $checkIn->id,
                'tenant_id' => $checkIn->tenant_id,
                'room_id' => $checkIn->room_id,
                'child_id' => $checkIn->child_id,
                'checked_in_at' => $checkIn->checked_in_at->toISOString(),
                'checked_out_at' => $checkIn->checked_out_at->toISOString(),
            ],
        ], Response::HTTP_OK);
    }

    public function occupants(Request $request, int $room): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $room = Room::query()->find($room);

        if (! $tenant || ! $room || $room->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Room not found for tenant.'], Response::HTTP_NOT_FOUND);
        }

        $children = Child::query()
            ->join('check_ins', 'check_ins.child_id', '=', 'children.id')
            ->where('children.tenant_id', $tenant->id)
            ->where('check_ins.tenant_id', $tenant->id)
            ->where('check_ins.room_id', $room->id)
            ->whereNull('check_ins.checked_out_at')
            ->select('children.*')
            ->distinct()
            ->get();

        return response()->json([
            'data' => $children->map(fn (Child $child) => [
                'id' => $child->id,
                'tenant_id' => $child->tenant_id,
                'name' => $child->name,
            ])->values(),
        ], Response::HTTP_OK);
    }
}
