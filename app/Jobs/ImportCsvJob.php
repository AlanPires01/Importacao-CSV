<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use Illuminate\Support\Str;
use League\Csv\Statement;

class ImportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    /**
     * Create a new job instance.
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {   
        Log::info('Job iniciado');
        Log::info('Caminho do arquivo:', ['path' => $this->filePath]);

        //ler o conteudo do arquivo com o metodo createFromPath sem precisar abrir o arquivo
        $csv=Reader::createFromPath(storage_path('app/'.$this->filePath),'r');
        //definir o delimitador compo ponto e vírgula
        $csv->setDelimiter(';');
        Log::info('Arqwuivo csv carregado');

        //Definir a primeira linha como cabeçalho
        $csv->setHeaderOffset(0);
        Log::info('Cabeçalho do CSV configurado');
        // Inicializar o offset para começar do início do arquivo
        $offset = 0;
        $limit = 100;

        //COntinuar processando até que todos os registros sejam lidos

        while(true){
            //Definir o início e o fim das linhas que devem ser lidas
            $stmt = (new Statement())->offset($offset)->limit( $limit );

            //Retorna uma coleção de arrays associativos, cada array representa uma linha do arquivo CSV com base no offset e limit definidos.
            $records = $stmt->process($csv);

            //Se não houver mais registros, sair do loop
            if(count($records) ===0){
                break;
            }

            //Percorrer as linhas do arquivo

            foreach($records as $record){
                //criar oo array de informações do novo registro

                $userData = [
                    'name'=>$record['name'],
                    'email'=>$record['email'],
                    'password'=>Hash::make(Str::random(7),['rounds'=>12]),
                ];

                //verificar se o e-mail já está cadastrado

                if(User::where('email',$userData['email'])->exists()){
                    //Salvar o log indicando que o e-mail já está cadastrado
                    Log::info('Erro, usuário '.$userData['email'].' já cadastrado');
                    continue;
                }

                // Inserir os dados no banco de dados
                User::create($userData);
            
            }

            $offset +=$limit;
        }
    
    }
}
