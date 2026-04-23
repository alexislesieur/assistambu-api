<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    // Lister les articles du sac
    public function index(Request $request)
    {
        $items = Item::where('user_id', $request->user()->id)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json($items);
    }

    // Créer un article
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'required|in:oxygenotherapy,dressings,immobilization,medications,consumables',
            'quantity'     => 'required|integer|min:0',
            'max_quantity' => 'required|integer|min:0',
            'dlc'          => 'nullable|date',
        ]);

        $item = Item::create([
            'user_id'      => $request->user()->id,
            'name'         => $validated['name'],
            'category'     => $validated['category'],
            'quantity'     => $validated['quantity'],
            'max_quantity' => $validated['max_quantity'],
            'dlc'          => $validated['dlc'] ?? null,
        ]);

        return response()->json($item, 201);
    }

    // Afficher un article
    public function show(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return response()->json($item);
    }

    // Modifier un article
    public function update(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'category'     => 'sometimes|in:oxygenotherapy,dressings,immobilization,medications,consumables',
            'quantity'     => 'sometimes|integer|min:0',
            'max_quantity' => 'sometimes|integer|min:0',
            'dlc'          => 'nullable|date',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    // Supprimer un article
    public function destroy(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $item->delete();

        return response()->json(['message' => 'Article supprimé.']);
    }

    // Réassort — remettre la quantité à max_quantity
    public function restock(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $item->update(['quantity' => $item->max_quantity]);

        return response()->json($item);
    }

    // Alertes DLC et stock bas
    public function alerts(Request $request)
    {
        $items = Item::where('user_id', $request->user()->id)->get();

        $alerts = [
            'expired'      => $items->filter(fn($i) => $i->isExpired())->values(),
            'expiring_soon'=> $items->filter(fn($i) => $i->isExpiringSoon() && !$i->isExpired())->values(),
            'out_of_stock' => $items->filter(fn($i) => $i->isOutOfStock())->values(),
            'low_stock'    => $items->filter(fn($i) => $i->isLowStock())->values(),
        ];

        return response()->json($alerts);
    }
}