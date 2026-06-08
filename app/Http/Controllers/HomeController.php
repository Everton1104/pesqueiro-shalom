<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Funcionários (não-admin) vão direto para as comandas
        if (!auth()->user()->is_admin) {
            return redirect()->route('comandas.index');
        }

        return view('home');
    }

    // Define a senha de autorização exigida de não-admins (cancelar/excluir)
    public function updateAuthPassword(Request $request)
    {
        abort_unless(auth()->user()->is_admin, 403);

        $data = $request->validate([
            'auth_password' => 'required|string|min:4|max:50',
        ]);

        Setting::set('cancel_auth_password', Hash::make($data['auth_password']));

        return redirect()->route('home')->with('status', 'Senha de autorização atualizada.');
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
