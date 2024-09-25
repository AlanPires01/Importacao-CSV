<?php

namespace App\Http\Controllers;

use App\Jobs\ImportCsvJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(){
        $users = User::orderBy("id","desc")->paginate(40);
        return view("users.index",["users"=>$users]);
    }

    public function import(Request $request){
       
        //Validar o arquivo
        $request->validate([
            'file'=>'required|mimes:csv,txt|max:100000',
        ],[
            'file.required'=> 'O campo arquivo é obrigatório',
            'file.mimes'=> 'Arquivo inválido, necessário enviar arquivo .CSV',
            'file.max'=> 'Tamanho do arquivo excede :max Mb'
        ]);
       
        //gerar nome do arquivo conforme data e hora atual
        $timestamp= now()->format('Y-m-d-H-i-s');
        $filename = "import-{$timestamp}.csv";
     
        //receber o arquivo e movê-lo para um local temporário
        //$path = $request->file('file')->storeAs('uploads', $filename, 'local');
        if (!is_dir(storage_path('uploads'))) {
            mkdir(storage_path('uploads'), 0755, true);
        }
        
        //$path=$request->file('file')->storeAs('uploads', $filename);
        $path = $request->file('file')->storeAs('uploads', $filename, 'local');
        Log::info('Arquivo salvo');
        //Despachar o Job para processar o CSV
        ImportCsvJob::dispatch($path);

        //redirecionar o usuario para a pagina anterior com a mensagem de success
        return back()->with('success','Dados estão sendo importados');
    }
}
