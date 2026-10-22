<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantTableController extends Controller
{
    // GET /api/tables (List floor tables)
    public function index(): JsonResponse
    {
        $tables = DB::table('restaurant_tables')
            ->select('id', 'table_number', 'capacity', 'status')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($tables);
    }

    public function show(int $id): JsonResponse
    {
        $table = DB::table('restaurant_tables')->where('id', $id)->first();

        if ($table === null) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        return response()->json($table);
    }

    // POST /api/tables
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_number' => 'required|string|max:20|unique:restaurant_tables,table_number',
            'capacity' => 'required|integer|min:1',
            'status' => 'nullable|in:available,occupied,reserved',
        ]);

        $now = Carbon::now();

        $id = DB::table('restaurant_tables')->insertGetId([
            'table_number' => $validated['table_number'],
            'capacity' => $validated['capacity'],
            'status' => $validated['status'] ?? 'available',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $table = DB::table('restaurant_tables')->where('id', $id)->first();

        return response()->json(['message' => 'Table created', 'data' => $table], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $table = DB::table('restaurant_tables')->where('id', $id)->first();

        if ($table === null) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        $validated = $request->validate([
            'table_number' => ['sometimes', 'string', 'max:20', 'unique:restaurant_tables,table_number,'.$id],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:available,occupied,reserved'],
        ]);

        DB::table('restaurant_tables')
            ->where('id', $id)
            ->update([
                ...$validated,
                'updated_at' => Carbon::now(),
            ]);

        $updatedTable = DB::table('restaurant_tables')->where('id', $id)->first();

        return response()->json([
            'message' => 'Table updated',
            'data' => $updatedTable,
        ]);
    }

    // PATCH /api/tables/{id}/status (Toggle table status from the POS layout)
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved',
        ]);

        $affected = DB::table('restaurant_tables')
            ->where('id', $id)
            ->update([
                'status' => $validated['status'],
                'updated_at' => Carbon::now(),
            ]);

        if ($affected === 0) {
            return response()->json(['message' => 'Table not found or status identical'], 404);
        }

        return response()->json(['message' => 'Table status updated successfully']);
    }

    // DELETE /api/tables/{id}
    public function destroy(int $id): JsonResponse
    {
        $deleted = DB::table('restaurant_tables')->where('id', $id)->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        return response()->json(['message' => 'Table deleted']);
    }
}
