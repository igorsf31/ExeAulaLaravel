<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateContatoRequest;
use App\Http\Requests\UpdateContatoRequest;
use App\Models\Contato;
use App\Repositories\ContatoRepository;
use Flash;
use Illuminate\Http\Request;
use Prettus\Repository\Criteria\RequestCriteria;

class ContatoController extends AppBaseController
{
    public function contatoEnvia(Request $request) {
		$contato = Contato::create(['nome' => $request->name, 'email' => $request->email, 'mensagem' => $request->mensagem]);

		return redirect()->back();
	}

	public function contatoEnviaFront(){
		return view('frontend.contato');
    }
    
    public function index(){
        return view('frontend.contato');
    }
}
