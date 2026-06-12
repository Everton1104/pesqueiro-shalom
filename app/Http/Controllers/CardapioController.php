<?php

namespace App\Http\Controllers;

use App\Models\CardapioCategory;
use App\Models\CardapioItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CardapioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->user()->is_admin, 403);
            return $next($request);
        });
    }

    public function index()
    {
        $categoryMeta = CardapioCategory::orderBy('sort_order')->get();
        $itemsByCategory = CardapioItem::orderBy('sort_order')->get()->groupBy('category');

        // Ordena conforme a tabela de categorias; coloca categorias extras no fim
        $items = $categoryMeta
            ->filter(fn($c) => $itemsByCategory->has($c->name))
            ->mapWithKeys(fn($c) => [$c->name => $itemsByCategory[$c->name]]);

        $extra = $itemsByCategory->filter(fn($_, $name) => !$categoryMeta->pluck('name')->contains($name));
        $items = $items->merge($extra);

        return view('cardapio.index', [
            'items'        => $items,
            'categoryMeta' => $categoryMeta->keyBy('name'),
            'categories'   => CardapioItem::CATEGORIES,
            'statuses'     => CardapioItem::STATUSES,
        ]);
    }

    public function create()
    {
        return view('cardapio.create', [
            'categories' => CardapioItem::CATEGORIES,
            'statuses'   => CardapioItem::STATUSES,
            'nextOrder'  => CardapioItem::max('sort_order') + 1,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category'    => 'required|string|max:100',
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:12288',
            'price'       => 'required|numeric|min:0',
            'sort_order'  => 'required|integer|min:0',
            'status'      => 'required|in:active,hidden,unavailable,coming_soon',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cardapio', 'public');
        }

        $data['active'] = $data['status'] === 'active';
        CardapioItem::create($data);

        return redirect()->route('cardapio.index')->with('status', 'Item adicionado com sucesso!');
    }

    public function edit(CardapioItem $cardapio)
    {
        return view('cardapio.edit', [
            'cardapio'   => $cardapio,
            'categories' => CardapioItem::CATEGORIES,
            'statuses'   => CardapioItem::STATUSES,
        ]);
    }

    public function update(Request $request, CardapioItem $cardapio)
    {
        $data = $request->validate([
            'category'     => 'required|string|max:100',
            'name'         => 'required|string|max:150',
            'description'  => 'nullable|string|max:255',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:12288',
            'price'        => 'required|numeric|min:0',
            'sort_order'   => 'required|integer|min:0',
            'status'       => 'required|in:active,hidden,unavailable,coming_soon',
            'remove_image' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($cardapio->image) Storage::disk('public')->delete($cardapio->image);
            $data['image'] = $request->file('image')->store('cardapio', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($cardapio->image) Storage::disk('public')->delete($cardapio->image);
            $data['image'] = null;
        }

        unset($data['remove_image']);
        $data['active'] = $data['status'] === 'active';
        $cardapio->update($data);

        return redirect()->route('cardapio.index')->with('status', 'Item atualizado com sucesso!');
    }

    public function destroy(CardapioItem $cardapio)
    {
        if ($cardapio->image) Storage::disk('public')->delete($cardapio->image);
        $cardapio->delete();
        return redirect()->route('cardapio.index')->with('status', 'Item removido.');
    }

    // Cicla entre: active → hidden → unavailable → coming_soon → active
    public function cycleStatus(CardapioItem $cardapio)
    {
        $cycle = ['active' => 'hidden', 'hidden' => 'unavailable', 'unavailable' => 'coming_soon', 'coming_soon' => 'active'];
        $next = $cycle[$cardapio->status] ?? 'active';
        $cardapio->update(['status' => $next, 'active' => $next === 'active']);
        return redirect()->route('cardapio.index')
            ->with('status', '"' . $cardapio->name . '" → ' . CardapioItem::STATUSES[$next]['label']);
    }

    // Reordena itens dentro de uma categoria
    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:cardapio_items,id']);

        foreach ($request->ids as $order => $id) {
            CardapioItem::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['ok' => true]);
    }

    // Reordena os cards de categoria
    public function reorderCategories(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:cardapio_categories,id']);

        foreach ($request->ids as $order => $id) {
            CardapioCategory::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['ok' => true]);
    }
}
