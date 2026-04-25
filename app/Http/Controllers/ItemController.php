<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\LogService;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct(protected LogService $log) {}

    public function index(Request $request)
    {
        $items = Item::where('user_id', $request->user()->id)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'quantity'     => 'required|integer|min:0',
            'max_quantity' => 'nullable|integer|min:0',
            'dlc'          => 'nullable|date',
        ]);

        $item = Item::create([
            'user_id'      => $request->user()->id,
            ...$validated,
        ]);

        $this->log->itemCreate($request->user()->id, $item->id, $item->name);

        return response()->json($item, 201);
    }

    public function show(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
        return response()->json($item);
    }

    public function update(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'category'     => 'sometimes|string|max:100',
            'quantity'     => 'sometimes|integer|min:0',
            'max_quantity' => 'nullable|integer|min:0',
            'dlc'          => 'nullable|date',
        ]);

        $item->update($validated);
        return response()->json($item);
    }

    public function destroy(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $this->log->itemDelete($request->user()->id, $item->id, $item->name);

        $item->delete();
        return response()->json(['message' => 'Article supprimé.']);
    }

    public function restock(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $oldQty = $item->quantity;
        $item->update(['quantity' => $item->max_quantity ?? $item->quantity]);

        $this->log->itemRestock($request->user()->id, $item->id, $item->name, $oldQty, $item->quantity);

        return response()->json($item);
    }

    public function alerts(Request $request)
    {
        $items = Item::where('user_id', $request->user()->id)
            ->where(function ($q) {
                $q->where('quantity', 0)
                  ->orWhereRaw('quantity < max_quantity * 0.3')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('dlc')
                         ->where('dlc', '<=', now()->addDays(90));
                  });
            })
            ->get();

        return response()->json($items);
    }
}