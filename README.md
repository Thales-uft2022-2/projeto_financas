# 💰 FinControl - Sistema de Controle Financeiro Pessoal

## 📋 Sobre o Projeto

**FinControl** é um sistema completo de controle financeiro pessoal desenvolvido em PHP com MySQL. O projeto foi criado para ajudar usuários a gerenciar suas receitas e despesas de forma simples, intuitiva e eficiente.

### 🎯 Objetivo

Oferecer uma ferramenta gratuita e acessível para que qualquer pessoa possa:
- Registrar transações financeiras (receitas e despesas)
- Visualizar relatórios e gráficos detalhados
- Organizar gastos por categorias personalizadas
- Acompanhar a evolução financeira mensal e anual
- Tomar decisões financeiras mais conscientes

---

## ✨ Funcionalidades

### 🔐 Autenticação
- ✅ Registro de novos usuários com validação
- ✅ Login seguro com hash de senha (password_hash)
- ✅ Sessão de usuário com logout
- ✅ Validação de senha em tempo real

### 📊 Dashboard
- ✅ Cards com resumo financeiro (saldo, receitas, despesas)
- ✅ Gráfico de evolução mensal (receitas vs despesas)
- ✅ Gráficos de pizza por categoria (receitas e despesas)
- ✅ Lista das últimas transações
- ✅ Filtros por mês e ano

### 💰 Transações
- ✅ Listagem completa com paginação (20 por página)
- ✅ Filtros por tipo, categoria, status, data e busca
- ✅ Adicionar transação com validação
- ✅ Editar transação existente
- ✅ Excluir transação com confirmação
- ✅ Máscara automática para valores (R$ 0,00)

### 🏷️ Categorias
- ✅ CRUD completo de categorias
- ✅ Seleção de cores personalizadas
- ✅ Seleção de ícones (FontAwesome)
- ✅ Filtro por tipo (receita/despesa)
- ✅ Verificação de transações vinculadas antes de excluir

### 📈 Relatórios
- ✅ Gráfico de evolução mensal
- ✅ Gráfico de top categorias (doughnut)
- ✅ Gráfico de formas de pagamento
- ✅ Gráfico comparativo anual
- ✅ Lista de transações do período
- ✅ Cards de resumo com médias e totais

### 📱 Responsividade
- ✅ Design adaptado para todos os dispositivos
- ✅ Menu responsivo
- ✅ Tabelas com scroll horizontal em dispositivos móveis

---

## 🛠️ Tecnologias Utilizadas

| Tecnologia | Versão | Descrição |
|------------|--------|-----------|
| **PHP** | 7.4+ | Backend e lógica de negócio |
| **MySQL** | 5.7+ | Banco de dados relacional |
| **HTML5** | - | Estrutura das páginas |
| **CSS3** | - | Estilização e layout |
| **JavaScript** | ES6+ | Interatividade e gráficos |
| **Chart.js** | 3.9+ | Biblioteca de gráficos |
| **FontAwesome** | 6.4+ | Ícones vetoriais |
| **XAMPP** | - | Ambiente de desenvolvimento |


### Scripts SQL

Os scripts para criação das tabelas estão no arquivo `criar_tabelas.php`.

## 🚀 Instalação

### Pré-requisitos

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP)
- Navegador Web (Chrome, Firefox, Edge, etc.)

### Passo a Passo

1. **Clone ou baixe o projeto**
   ```bash
   git clone https://github.com/seu-usuario/fincontrol.git

# Windows
C:\xampp\htdocs\projeto_financas\

# Linux
/opt/lampp/htdocs/projeto_financas/

Inicie o XAMPP

Inicie o Apache e o MySQL

Crie o banco de dados

Acesse: http://localhost/phpmyadmin

Crie um banco chamado projeto_financas

Execute o script SQL

Configure o banco

Edite config/database.php com suas credenciais

Execute a instalação

Acesse: http://localhost/projeto_financas/criar_tabelas.php

Acesse o sistema

http://localhost/projeto_financas/

Credenciais de Teste
Tipo	Email	Senha
Usuário Teste	teste@email.com	123456
Admin	admin@email.com	123456

🤝 Contribuição
Contribuições são bem-vindas! Siga os passos:

Fork o projeto

Crie sua branch (git checkout -b feature/nova-funcionalidade)

Commit suas mudanças (git commit -m 'Adiciona nova funcionalidade')

Push para a branch (git push origin feature/nova-funcionalidade)

Abra um Pull Request

📝 Licença
Este projeto está sob a licença MIT. Veja o arquivo LICENSE para mais detalhes.

👨‍💻 Autor
Seu Nome

GitHub: @seu-usuario

LinkedIn: Seu LinkedIn

🙏 Agradecimentos
Chart.js - Biblioteca de gráficos

FontAwesome - Ícones

Google Fonts - Fontes (Inter)

Comunidade PHP e Open Source

📞 Suporte
Para dúvidas ou sugestões:

📧 Email: contato@fincontrol.com

🐛 Issues: GitHub Issues

⭐ Avalie o Projeto
Se este projeto te ajudou, dê uma ⭐ no GitHub!

Feito com ❤️ por Nerd Thales Marques 

text

## 📋 Como usar o README.md

1. **Crie o arquivo** `README.md` na raiz do projeto:
C:\xampp\htdocs\projeto_financas\README.md

text

2. **Copie e cole** o texto acima

3. **Personalize** os dados:
- Substitua `seu-usuario` pelo seu GitHub
- Substitua `Seu Nome` pelo seu nome
- Substitua os links do LinkedIn e GitHub
- Adicione suas capturas de tela na pasta `screenshots/`

4. **Salve o arquivo**

---

## 📸 Para as Capturas de Tela

Crie uma pasta `screenshots/` e tire prints das páginas:

```bash
C:\xampp\htdocs\projeto_financas\screenshots\
├── index.png
├── dashboard.png
├── transactions.png
├── reports.png
└── categories.png
Pronto! Seu projeto agora tem uma documentação profissional! 🚀