
<?php
    require_once('ChatGptAssistant.class.php');
    $chatGptAssistant = new ChatGptAssistant();    

    $files_list  = [];
    $valid_files = []; 
    $message = '';  
    
    $content = '';

    //Documentos de imagens à serem analisados
    $files_list = [
        'imagem01.jpg',
        'laudo_medico.png',
        'receita01.gif'
    ];


    //Id do assistente previamente configurado e treinado
    //Neste caso, o assistente está configurado para agir como um acessor júridico          
    $assistant_id  = 'asst_6aioDc9ltT7hiJZ9PtetqFXD'; 
    //Instruções para o assistente
    $instructions  = "Crie a seção dos fatos de uma petição inicial baseado nas informações seguintes \n";


    //Organiza e processa os arquivos
    $valid_files = $chatGptAssistant->filesSort($files_list);    
    if(count($valid_files) < 1){ $chatGptAssistant->showMessage("<br>Não foram encontrados arquivos válidos!");} 
    else { 

        $attempts = 0;
        while($message == '' && $attempts < 5)
        {
            if($attempts > 0){$chatGptAssistant->showMessage("<br><br>Reiniciando a Consulta - Tentativa ".($attempts+1));}
            
            //Step 4: Envia os arquivos para o Assistente    
            $file_ids = $chatGptAssistant->uploadFiles($valid_files);
            if(count($file_ids) < 1){ $chatGptAssistant->showMessage("<br>Os arquivos não puderam ser enviados!");}
            else {

                //Cria a Thread para o Assistente    
                $thread_id = $chatGptAssistant->threadCreate($file_ids, $instructions, $assistant_id);
                if(empty($thread_id)){ $chatGptAssistant->showMessage("<br>A Thread não pode ser criada!"); }
                else {

                    //Verifica o status da Thread    
                    $thread_status = 'in_progress';
                    while($thread_status == 'in_progress'){
                        $thread_status = $chatGptAssistant->getThreadStatus($thread_id);
                    }

                    //Pega a mensagem
                    $message = $chatGptAssistant->assistantGetMessage($thread_id);
                    if(empty($message)){ 
                        $chatGptAssistant->showMessage("<br>A consulta não retornou uma mensagem!");
                        $attempts++;
                    }
                    else {
                        $chatGptAssistant->showMessage(nl2br("\n\n".$message));
                    }
                }
            }
        }
    }

    $chatGptAssistant->showMessage("<br><br>Excluindo os arquivos utilizados.");
    foreach($valid_files as $_file)
    {
        unlink($_file);
    }

    $chatGptAssistant->showMessage("<br>[DONE]");
