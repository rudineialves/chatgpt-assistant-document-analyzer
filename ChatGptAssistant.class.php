<?php


class ChatGptAssistant {


    private const API_KEY = 'gpt_api_key';
    private const TMP_DIR = 'gpt_arqs/';
    
    
    /** ************************************************************
    * Interpreta a imagem e transforma o conteudo em texto
    * **************************************************************/
    public function imageInterpreter($file_path)
    {

        $content = '';
        $message_size_min = 900;
        $message_size     = 0;
        $attempts         = 0;

        $filetype = pathinfo($file_path, PATHINFO_EXTENSION);
        if(in_array(strtolower($filetype), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
        {            
            while($message_size < $message_size_min && $attempts < 5)
            {
                $this->showMessage("<br>Interpretando o documento ".str_replace('gpt_arqs/', '', $file_path).($attempts>0?" - Tentativa ".($attempts+1):''));

                $imageData = file_get_contents($file_path);
                $base64EncodedImage = mb_convert_encoding(base64_encode($imageData), 'ISO-8859-1', 'UTF-8');
                
                $ch = curl_init('https://api.openai.com/v1/chat/completions');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this::API_KEY,
                ]);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    "model" => "gpt-4-turbo",              
                    "messages" => [
                        [
                            "role" => "user", 
                            "content" => [
                                [
                                    "type" => "text",
                                    "text" => "Do que se trata esta imagem? Transcreva seu conteúdo conforme está na imagem. Se necessário substitua as informações pessoais por XXXX. Não retorne o conteúdo desta instrução."
                                ],
                                [
                                    "type" => "image_url",
                                    "image_url" => [
                                        "url" => "data:image/jpeg;base64,{$base64EncodedImage}"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "max_tokens" => 900         
                    
                ]));
                
                $ch_content = curl_exec($ch);
                $response = json_decode($ch_content);
                curl_close($ch);
                // var_dump($ch_content);
     
                $message_content = @$response->choices[0]->message->content;
                
                $message_size = strlen($message_content);
                $attempts++;

                if($message_size >= $message_size_min){
                    $content = $message_content;
                    $this->showMessage(" (OK)");
                }
                else {
                    $this->showMessage(" (Conteúdo não identificado)");
                }
            }
        }
        else {
            $this->showMessage("<br>O arquivo não é uma imagem válida!");
        }
        
        return $content;
    }


    /** ************************************************************
    * CLASSIFICA OS ARQUIVOS
    * se texto adiciona diretamente à lista
    * se imagem processa e transforma em texto antes de adicionar
    * **************************************************************/
    public function filesSort($files_list)
    {        
        $files_result = [];
        
        if(count($files_list) > 0){
            foreach($files_list as $file_path)
            {    
                $filetype = pathinfo($file_path, PATHINFO_EXTENSION); 
                if(in_array(strtolower($filetype), ['txt', 'doc', 'docx', 'pdf'])){
                    $files_result[] = $file_path;                   
                }
                else if(in_array(strtolower($filetype), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                {
                    $imgContent = $this->imageInterpreter($file_path);
                    if($imgContent != ''){
                        $temp_file = $this::TMP_DIR.md5(microtime()).'.txt';
                        if(file_put_contents($temp_file, $imgContent . PHP_EOL)){
                            $files_result[] = $temp_file;
                            @unlink($file_path);
                        }
                    }
                }
                else {                    
                    $this->showMessage("<br>Tipo de arquivo não suportado.");
                    @unlink($file_path);
                }
            }
        }

        return $files_result;
    }
    

    /** ***************************************************
     * Envia os arquivos e retorna seus ids
     * ****************************************************/
    public function uploadFiles($files_list)
    {
        $result_ids = [];

        foreach($files_list as $file_path)
        {        
            if(is_file($file_path))
            {
                $this->showMessage("<br>Processando o documento ".str_replace('gpt_arqs/', '', $file_path));

                $ch = curl_init('https://api.openai.com/v1/files');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $this::API_KEY));
                curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                    'purpose' => 'assistants',
                    'file' => new CURLFile($file_path),
                ));
                $upload_result = curl_exec($ch);
                $response_upload = json_decode($upload_result, true);
                curl_close($ch);

                if(isset($response_upload['id'])){
                    $result_ids[] = $response_upload['id'];
                }
                else {
                    $this->showMessage("<br>Error uploading file!");
                }
            }
            else {
                $this->showMessage("<br>File not found!");
            }
        }

        return $result_ids;
    }


    /** ***************************************************
     * Cria a Thread e retorna o id
     * ****************************************************/
    public function threadCreate($instructions, $assistant_id)
    {
        $thread_id = '';
  
            $attempts = 0;

            while(empty($thread_id) && $attempts < 5)
            {
                $this->showMessage("<br>Criando a Requisição".($attempts>0?" - Tentativa ".($attempts+1):''));

                $ch = curl_init("https://api.openai.com/v1/threads/runs");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this::API_KEY,
                    'OpenAI-Beta: assistants=v1'
                ]);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    "assistant_id" => $assistant_id,
                    "thread" => [
                        "messages" => [
                            [
                                "role" => "user", 
                                "content" => $instructions." com base no conteúdo do(s) arquivo(s) enviado(s)",
                            ]
                        ]          
                    ]
                ]));
                $ch_response = curl_exec($ch);
                $thread_creation_response = json_decode($ch_response, true);
                curl_close($ch);
                
                $thread_id = @$thread_creation_response['thread_id'];
                $attempts++;
            }
        

        return $thread_id;
    }


    /** ***************************************************
     * Verifica o status da Thread
     * ****************************************************/
    public function getThreadStatus($thread_id)
    {        
        sleep(10);

        $ch = curl_init("https://api.openai.com/v1/threads/".$thread_id."/runs");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this::API_KEY,
            'OpenAI-Beta: assistants=v1'
        ]);
        $thread_status_response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        $thread_status_data = $thread_status_response['data'];
        $thread_status = $thread_status_data[0]['status'];
        
        $this->showMessage("<br>Status da Requisição: ".$thread_status);

        return $thread_status;
    }



    public function assistantGetMessage($thread_id)
    {      
        $message_result   = '';
        $message_size_min = 1300;
        $message_size     = 0;
        $attempts         = 0;

        while($message_size < $message_size_min && $attempts < 1)
        {
            $this->showMessage("<br>Gerando a seção DOS FATOS".($attempts>0?" - Tentativa ".($attempts+1):''));

            $ch = curl_init("https://api.openai.com/v1/threads/".$thread_id."/messages");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this::API_KEY,
                'OpenAI-Beta: assistants=v1',
                'Content-Type: application/json',
                'stream' => true
            ]);
            $ch_result = curl_exec($ch);
            $thread_response = json_decode($ch_result, true);
            curl_close($ch);

            $thread_message = $thread_response['data'][0]['content'][0]['text']['value'];
            $message_size = strlen($thread_message);
            $attempts++;

            if($message_size >= $message_size_min){
                $message_result = $thread_message;
            }
            else {
                $this->showMessage("<br>(".$thread_message.")");
            }
        }

        return $message_result;
    }

    public function showMessage($pMessage){

        $msgSplited = str_split($pMessage);
        foreach($msgSplited as $char){
            echo $char;             
            flush();
            usleep(30000);
        }
    }

}