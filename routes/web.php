<?php

use App\Http\Controllers\CardapioController;
use App\Http\Controllers\HomeController;
use App\Models\CardapioCategory;
use App\Models\CardapioItem;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $categoryOrder = CardapioCategory::orderBy('sort_order')->pluck('name');
    $grouped = CardapioItem::visible()->orderBy('sort_order')->get()->groupBy('category');

    $items = $categoryOrder
        ->filter(fn($cat) => $grouped->has($cat))
        ->mapWithKeys(fn($cat) => [$cat => $grouped[$cat]]);

    return view('welcome', compact('items'));
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('cardapio', CardapioController::class)->except(['show']);
    Route::patch('cardapio/{cardapio}/cycle',        [CardapioController::class, 'cycleStatus'])->name('cardapio.cycle');
    Route::post('cardapio/reorder',                  [CardapioController::class, 'reorder'])->name('cardapio.reorder');
    Route::post('cardapio-categories/reorder',       [CardapioController::class, 'reorderCategories'])->name('cardapio.categories.reorder');
});
