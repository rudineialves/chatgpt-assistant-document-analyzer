# 🧠 ChatGPT Assistant Document Analyzer

Este projeto é uma ferramenta PHP que permite interpretar documentos em **imagem** ou **texto** e, com auxílio de um assistente baseado no ChatGPT (via API da OpenAI), **gerar automaticamente a seção "Dos Fatos" de uma petição inicial jurídica**.

## 🚀 Funcionalidades

- 🖼️ Reconhecimento de conteúdo em imagens (.jpg, .png, .gif, etc.) via OCR com o GPT-4-Turbo.
- 📄 Suporte a documentos de texto (.txt, .doc, .docx, .pdf).
- 📤 Upload automático dos arquivos para a API da OpenAI.
- 🤖 Criação de uma thread com assistente treinado para responder como assessor jurídico.
- 🧾 Retorno do conteúdo processado e formatado para uso jurídico.
- 🧹 Limpeza automática dos arquivos temporários após a execução.

## 📂 Estrutura do Projeto

```bash
├── ChatGptAssistant.class.php   # Classe principal com os métodos de integração
├── index.php                    # Script de execução da lógica principal
├── gpt_arqs/                    # Pasta para arquivos temporários (OCR)
```

## ⚙️ Requisitos

- PHP 7.4 ou superior
- cURL habilitado
- Conta na [OpenAI](https://platform.openai.com/)
- Um assistente previamente configurado com `assistant_id` treinado para atuar como assessor jurídico

## 🔧 Configuração

1. **Clone o repositório**
   ```bash
   git clone https://github.com/seu-usuario/chatgpt-assistant-juridico.git
   ```

2. **Configure a API Key da OpenAI**

   No arquivo `ChatGptAssistant.class.php`, substitua `'gpt_api_key'` pela sua chave da API:

   ```php
   private const API_KEY = 'sk-...'; // sua chave aqui
   ```

3. **Ajuste o ID do assistente jurídico**

   No `index.php`, edite a variável:

   ```php
   $assistant_id  = 'asst_xxxxxxxxxxxxxxxx';
   ```

4. **Adicione suas imagens ou documentos**
   
   No array `$files_list`, informe os caminhos dos arquivos a serem analisados:

   ```php
   $files_list = ['imagem01.jpg', 'laudo_medico.png', 'receita01.gif'];
   ```

5. **Execute**
   ```bash
   php index.php
   ```

## 📌 Observações

- O projeto é focado na geração de petições iniciais, mas pode ser adaptado para outras finalidades com modificações nas instruções e no treinamento do assistente.
- A API do GPT-4-Turbo precisa ter suporte a **upload de arquivos e threads com assistentes**, certifique-se de estar utilizando os endpoints corretos e de ter permissões habilitadas na sua conta OpenAI.

## 📃 Licença

MIT License. Livre para usar e modificar.
