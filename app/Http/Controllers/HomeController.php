<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('home');
    }

    public function uploadCardapio(Request $request)
    {
        $request->validate([
            'cardapio' => 'required|file|mimes:pdf|max:20480',
        ], [
            'cardapio.required' => 'Selecione um arquivo PDF.',
            'cardapio.mimes'    => 'O arquivo deve ser um PDF.',
            'cardapio.max'      => 'O arquivo não pode ultrapassar 20 MB.',
        ]);

        $request->file('cardapio')->move(public_path(), 'cardapio.pdf');

        return redirect()->route('home')->with('status', 'Cardápio atualizado com sucesso!');
    }
}
