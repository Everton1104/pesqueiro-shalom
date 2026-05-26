<?php

namespace Database\Seeders;

use App\Models\CardapioCategory;
use App\Models\CardapioItem;
use Illuminate\Database\Seeder;

class CardapioSeeder extends Seeder
{
    public function run(): void
    {
        CardapioCategory::truncate();
        foreach (CardapioItem::CATEGORIES as $i => $name) {
            CardapioCategory::create(['name' => $name, 'sort_order' => $i]);
        }

        CardapioItem::truncate();

        $items = [
            // PORÇÕES
            ['category' => 'PORÇÕES', 'name' => 'Camarão',                  'description' => '500g',                                           'price' => 85.00, 'sort_order' => 1],
            ['category' => 'PORÇÕES', 'name' => 'Tilápia',                  'description' => '400g',                                           'price' => 60.00, 'sort_order' => 2],
            ['category' => 'PORÇÕES', 'name' => 'Posta de Tilápia',         'description' => '800g',                                           'price' => 60.00, 'sort_order' => 3],
            ['category' => 'PORÇÕES', 'name' => 'Lambari',                  'description' => '500g',                                           'price' => 60.00, 'sort_order' => 4],
            ['category' => 'PORÇÕES', 'name' => 'Frango Frito',             'description' => null,                                             'price' => 50.00, 'sort_order' => 5],
            ['category' => 'PORÇÕES', 'name' => 'Fritas com Cheddar e Bacon','description' => '500g',                                          'price' => 50.00, 'sort_order' => 6],
            ['category' => 'PORÇÕES', 'name' => 'Calabresa',                'description' => '500g',                                           'price' => 35.00, 'sort_order' => 7],
            ['category' => 'PORÇÕES', 'name' => 'Fritas',                   'description' => '500g',                                           'price' => 35.00, 'sort_order' => 8],
            ['category' => 'PORÇÕES', 'name' => 'Polenta',                  'description' => '500g',                                           'price' => 35.00, 'sort_order' => 9],
            ['category' => 'PORÇÕES', 'name' => 'Mandioca',                 'description' => '500g',                                           'price' => 35.00, 'sort_order' => 10],
            ['category' => 'PORÇÕES', 'name' => 'Anéis de Cebola',          'description' => '400g',                                           'price' => 35.00, 'sort_order' => 11],

            // COMIDA
            ['category' => 'COMIDA', 'name' => 'Feijão',                    'description' => '600ml',                                          'price' => 12.00, 'sort_order' => 1],
            ['category' => 'COMIDA', 'name' => 'Arroz',                     'description' => '600ml',                                          'price' => 12.00, 'sort_order' => 2],
            ['category' => 'COMIDA', 'name' => 'Salada',                    'description' => null,                                             'price' => 10.00, 'sort_order' => 3],

            // SALGADOS
            ['category' => 'SALGADOS', 'name' => 'Salgado Frito',           'description' => null,                                             'price' =>  8.00, 'sort_order' => 1],
            ['category' => 'SALGADOS', 'name' => 'Salgado Assado',          'description' => null,                                             'price' => 10.00, 'sort_order' => 2],

            // CERVEJAS
            ['category' => 'CERVEJAS', 'name' => 'Heineken',                'description' => '600ml',                                          'price' => 20.00, 'sort_order' => 1],
            ['category' => 'CERVEJAS', 'name' => 'Original',                'description' => '600ml',                                          'price' => 18.00, 'sort_order' => 2],
            ['category' => 'CERVEJAS', 'name' => 'Skol',                    'description' => '600ml',                                          'price' => 15.00, 'sort_order' => 3],
            ['category' => 'CERVEJAS', 'name' => 'Antártica',               'description' => '600ml',                                          'price' => 15.00, 'sort_order' => 4],
            ['category' => 'CERVEJAS', 'name' => 'Império',                 'description' => '330ml',                                          'price' => 13.00, 'sort_order' => 5],
            ['category' => 'CERVEJAS', 'name' => 'Heineken Long Neck',      'description' => null,                                             'price' => 12.00, 'sort_order' => 6],
            ['category' => 'CERVEJAS', 'name' => 'Império Long Neck',       'description' => null,                                             'price' => 12.00, 'sort_order' => 7],
            ['category' => 'CERVEJAS', 'name' => 'Cerveja Lata',            'description' => '350ml',                                          'price' =>  8.00, 'sort_order' => 8],

            // BEBIDAS
            ['category' => 'BEBIDAS', 'name' => 'Refrigerante Lata',        'description' => null,                                             'price' =>  7.00, 'sort_order' => 1],
            ['category' => 'BEBIDAS', 'name' => 'Suco Lata',                'description' => null,                                             'price' =>  7.00, 'sort_order' => 2],
            ['category' => 'BEBIDAS', 'name' => 'Refrigerante KS',          'description' => null,                                             'price' =>  7.00, 'sort_order' => 3],
            ['category' => 'BEBIDAS', 'name' => 'Água com Gás',             'description' => null,                                             'price' =>  5.00, 'sort_order' => 4],
            ['category' => 'BEBIDAS', 'name' => 'Água Mineral',             'description' => null,                                             'price' =>  5.00, 'sort_order' => 5],
            ['category' => 'BEBIDAS', 'name' => 'Tônica',                   'description' => null,                                             'price' =>  8.00, 'sort_order' => 6],
            ['category' => 'BEBIDAS', 'name' => 'Redbull',                  'description' => null,                                             'price' => 15.00, 'sort_order' => 7],
            ['category' => 'BEBIDAS', 'name' => 'Monster',                  'description' => null,                                             'price' => 15.00, 'sort_order' => 8],
            ['category' => 'BEBIDAS', 'name' => 'Suco Natural',             'description' => '300ml',                                          'price' => 10.00, 'sort_order' => 9],

            // BEBIDAS QUENTES
            ['category' => 'BEBIDAS QUENTES', 'name' => 'Cachaça',          'description' => null,                                             'price' =>  5.00, 'sort_order' => 1],
            ['category' => 'BEBIDAS QUENTES', 'name' => 'Pingas Raízes',    'description' => 'Abacaxi, Amburana, Carqueja, Cambuci, Canela',   'price' =>  5.00, 'sort_order' => 2],
            ['category' => 'BEBIDAS QUENTES', 'name' => 'Misturadas',       'description' => 'Dreher, Cynar, Paratudo, Catuaba, Menta, Canelinha', 'price' => 8.00, 'sort_order' => 3],

            // BORBONS
            ['category' => 'BORBONS', 'name' => 'Red Label',                'description' => null,                                             'price' => 25.00, 'sort_order' => 1],
            ['category' => 'BORBONS', 'name' => 'Chivas Regal',             'description' => null,                                             'price' => 25.00, 'sort_order' => 2],
            ['category' => 'BORBONS', 'name' => 'Black Label',              'description' => null,                                             'price' => 30.00, 'sort_order' => 3],
            ['category' => 'BORBONS', 'name' => 'Jack Daniel\'s',           'description' => null,                                             'price' => 30.00, 'sort_order' => 4],
            ['category' => 'BORBONS', 'name' => 'Buchana\'s',               'description' => null,                                             'price' => 30.00, 'sort_order' => 5],
            ['category' => 'BORBONS', 'name' => 'Gold Label',               'description' => null,                                             'price' => 35.00, 'sort_order' => 6],

            // CAIPIRINHAS
            ['category' => 'CAIPIRINHAS', 'name' => 'Sake',                 'description' => 'Morango, Limão, Maracujá, Abacaxi',              'price' => 25.00, 'sort_order' => 1],
            ['category' => 'CAIPIRINHAS', 'name' => 'Cachaça',              'description' => 'Morango, Limão, Maracujá, Abacaxi',              'price' => 25.00, 'sort_order' => 2],
            ['category' => 'CAIPIRINHAS', 'name' => 'Vodka',                'description' => 'Morango, Limão, Maracujá, Abacaxi',              'price' => 25.00, 'sort_order' => 3],
            ['category' => 'CAIPIRINHAS', 'name' => 'Vinho',                'description' => 'Limão',                                          'price' => 25.00, 'sort_order' => 4],
            ['category' => 'CAIPIRINHAS', 'name' => 'Batidas',              'description' => 'Leite Condensado',                               'price' => 30.00, 'sort_order' => 5],

            // DRINKS
            ['category' => 'DRINKS', 'name' => 'Beefeater Gin',             'description' => null,                                             'price' => 25.00, 'sort_order' => 1],
            ['category' => 'DRINKS', 'name' => 'Tanqueray',                 'description' => null,                                             'price' => 25.00, 'sort_order' => 2],
            ['category' => 'DRINKS', 'name' => 'Smirnoff Vodka',            'description' => null,                                             'price' => 10.00, 'sort_order' => 3],
            ['category' => 'DRINKS', 'name' => 'Absolut Vodka',             'description' => null,                                             'price' => 15.00, 'sort_order' => 4],
            ['category' => 'DRINKS', 'name' => 'Underberg',                 'description' => null,                                             'price' => 20.00, 'sort_order' => 5],
            ['category' => 'DRINKS', 'name' => 'Campari',                   'description' => null,                                             'price' => 20.00, 'sort_order' => 6],
        ];

        foreach ($items as $item) {
            CardapioItem::create($item);
        }
    }
}
