<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(){
        return view("users.index");
    }

    public function import(Request $request){

        //Validar o arquivo
        $request->validate([
            'file'=>'required|mimes:csv,txt|max:8192',
        ],[
            'file.required'=> 'O campo arquivo é obrigatório',
            'file.mime'=> 'Arquivo inválido, necessário enviar arquivo .CSV',
            'file.max'=> 'Tamanho do arquivo excede :max Mb'
        ]);

        //gerar nome do arquivo conforme data e hora atual
        $timestamp= now()->format('Y-m-d-H-i-s');
        $filename = "import-{$timestamp}.csv";

        //receber o arquivo e movê-lo para um local temporário
        $path=$request->file('file')->storeAs('uploads', $filename,'');

        //Despachar o Job para processar o CSV
        ImportCsvJob::dispach($path);

        //redirecionar o usuario para a pagina anterior com a mensagem de success
        return back()->with('success','Dados estão sendo importados');
    }
}
