<?php

use App\Http\Controllers\BalcaoController;
use App\Http\Controllers\CardapioController;
use App\Http\Controllers\ComandaController;
use App\Http\Controllers\CozinhaController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RelatorioController;
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

    // ── Comandas ──
    // 'scan' antes do resource para não ser capturado como {comanda}
    Route::get('comandas/scan', [ComandaController::class, 'scan'])->name('comandas.scan');
    Route::get('comandas-abertas', [ComandaController::class, 'abertasJson'])->name('comandas.abertas'); // polling
    Route::post('comandas/pad-order', [ComandaController::class, 'savePadOrder'])->name('comandas.pad-order');
    Route::resource('comandas', ComandaController::class)->only(['index', 'store', 'show']);
    Route::get('comandas/{comanda}/sig', [ComandaController::class, 'sig'])->name('comandas.sig'); // polling
    Route::post('comandas/{comanda}/itens',                 [ComandaController::class, 'addItem'])->name('comandas.itens.add');
    Route::patch('comandas/{comanda}/itens/{item}',         [ComandaController::class, 'updateItem'])->name('comandas.itens.update');
    Route::delete('comandas/{comanda}/itens/{item}',        [ComandaController::class, 'removeItem'])->name('comandas.itens.remove');
    Route::post('comandas/{comanda}/pagamentos',               [ComandaController::class, 'addPagamento'])->name('comandas.pagamentos.add');
    Route::delete('comandas/{comanda}/pagamentos/{pagamento}',  [ComandaController::class, 'removePagamento'])->name('comandas.pagamentos.remove');
    Route::post('comandas/{comanda}/fechar',                [ComandaController::class, 'fechar'])->name('comandas.fechar');
    Route::post('comandas/{comanda}/cancelar',              [ComandaController::class, 'cancelar'])->name('comandas.cancelar');
    Route::delete('comandas/{comanda}',                     [ComandaController::class, 'destroy'])->name('comandas.destroy');

    // ── Fichas (caixa pré-pago de evento) ──
    Route::resource('fichas', FichaController::class)->only(['index', 'store', 'show']);
    Route::post('fichas/{ficha}/cancelar', [FichaController::class, 'cancelar'])->name('fichas.cancelar');
    Route::delete('fichas/{ficha}',        [FichaController::class, 'destroy'])->name('fichas.destroy');

    // ── Balcão (retirada de prontos via QR) ──
    Route::get('balcao',                  [BalcaoController::class, 'index'])->name('balcao.index');
    Route::get('balcao/buscar',           [BalcaoController::class, 'buscar'])->name('balcao.buscar'); // JSON por código
    Route::post('balcao/{ficha}/entregar', [BalcaoController::class, 'entregar'])->name('balcao.entregar');

    // ── Cozinha (fila de preparo) ──
    Route::get('cozinha',                  [CozinhaController::class, 'index'])->name('cozinha.index');
    Route::get('cozinha-fila',             [CozinhaController::class, 'fila'])->name('cozinha.fila'); // polling
    Route::patch('cozinha/{ficha}/concluir', [CozinhaController::class, 'concluir'])->name('cozinha.concluir');

    // ── Relatórios ──
    Route::get('relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');

    // ── Painel: senha de autorização (admin) ──
    Route::post('painel/senha-autorizacao', [HomeController::class, 'updateAuthPassword'])->name('admin.auth-password');
});
