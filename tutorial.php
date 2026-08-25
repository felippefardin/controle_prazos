<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
exigirLogin();
$titulo = 'Tutorial do sistema';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head tutorial-heading">
    <div>
        <h1>Tutorial do sistema</h1>
        <p>Guia completo para cadastrar procuradores, controlar processos e acompanhar vencimentos.</p>
    </div>
    <a class="btn btn-primary" href="prazo_form.php">+ Cadastrar processo</a>
</section>

<section class="tutorial-layout">
    <aside class="panel tutorial-index" aria-label="Índice do tutorial">
        <strong>Conteúdo</strong>
        <nav>
            <a href="#visao-geral">1. Visão geral</a>
            <a href="#primeiro-acesso">2. Primeiro acesso</a>
            <a href="#dashboard">3. Dashboard e alertas</a>
            <a href="#procuradores">4. Cadastrar procuradores</a>
            <a href="#processos">5. Cadastrar processos</a>
            <a href="#acompanhar">6. Acompanhar e pesquisar</a>
            <a href="#observacoes">7. Observações e edição</a>
            <a href="#concluir">8. Concluir ou excluir</a>
            <a href="#exportar">9. Exportar relatórios</a>
            <a href="#perfil">10. Perfil e senha</a>
            <a href="#recuperacao">11. Recuperar acesso</a>
            <a href="#boas-praticas">12. Boas práticas</a>
        </nav>
    </aside>

    <div class="tutorial-content">
        <article class="panel tutorial-step" id="visao-geral">
            <span class="step-number">1</span><div><h2>Visão geral</h2>
            <p>O Controle de Prazos organiza processos, procuradores responsáveis e datas de vencimento. O menu superior dá acesso às áreas principais:</p>
            <ul><li><strong>Dashboard:</strong> resumo e próximos vencimentos.</li><li><strong>Prazos:</strong> lista completa, busca, filtros e ações.</li><li><strong>Procuradores:</strong> cadastro das pessoas que serão vinculadas aos processos.</li><li><strong>Concluídos:</strong> histórico dos processos finalizados.</li><li><strong>Tutorial:</strong> esta página de ajuda.</li></ul></div>
        </article>

        <article class="panel tutorial-step" id="primeiro-acesso">
            <span class="step-number">2</span><div><h2>Primeiro acesso e criação de usuário</h2>
            <ol><li>Na tela de login, clique em <strong>Criar novo usuário</strong>.</li><li>Informe nome completo, e-mail, CPF, nome de usuário e matrícula.</li><li>Crie uma senha com pelo menos oito caracteres e confirme-a.</li><li>Clique em <strong>Criar usuário</strong>.</li><li>Consulte seu e-mail e digite o código de seis números recebido.</li><li>Aguarde a aprovação do administrador.</li><li>Depois da aprovação, volte ao login e entre usando o <strong>nome de usuário</strong> e a senha.</li></ol>
            <div class="tutorial-tip"><strong>Importante:</strong> o acesso é feito pelo nome de usuário, e não pelo e-mail.</div></div>
        </article>

        <article class="panel tutorial-step" id="dashboard">
            <span class="step-number">3</span><div><h2>Entendendo o Dashboard</h2>
            <p>O Dashboard mostra os totais de processos concluídos, em andamento e próximos a vencer. A tabela exibe somente processos abertos que vencem entre 1 e 30 dias, numerados pelo vencimento mais próximo.</p>
            <div class="tutorial-colors"><span class="guide-green">21 a 30 dias</span><span class="guide-yellow">11 a 20 dias</span><span class="guide-red">1 a 10 dias</span></div>
            <p>O alerta vermelho de <strong>1 a 5 dias</strong> mostra quantos processos estão nessa faixa. Clique nele para abrir apenas esses processos, ordenados do mais urgente ao menos urgente.</p></div>
        </article>

        <article class="panel tutorial-step" id="procuradores">
            <span class="step-number">4</span><div><h2>Cadastrar um procurador</h2>
            <ol><li>No menu, clique em <strong>Procuradores</strong>.</li><li>Clique em <strong>+ Cadastrar procurador</strong>.</li><li>Informe obrigatoriamente o nome completo.</li><li>Se disponíveis, informe OAB, telefone e e-mail.</li><li>Mantenha <strong>Procurador ativo</strong> marcado para que ele apareça no cadastro de processos.</li><li>Clique em <strong>Salvar procurador</strong>.</li></ol>
            <p>Use <strong>Editar</strong> na listagem para corrigir os dados ou deixar o procurador inativo. Os vínculos já existentes são preservados.</p></div>
        </article>

        <article class="panel tutorial-step" id="processos">
            <span class="step-number">5</span><div><h2>Cadastrar um novo processo</h2>
            <ol><li>Clique em <strong>+ Novo processo</strong> no menu ou no Dashboard.</li><li>Informe o número do processo e o assunto/descrição.</li><li>Escolha o status: <strong>Novo</strong>, <strong>Em andamento</strong> ou <strong>Concluído</strong>.</li><li>Marque um ou vários procuradores. Pelo menos um procurador deve ser selecionado.</li><li>Informe as datas de entrada e vencimento.</li><li>Adicione observações, se necessário.</li><li>Clique em <strong>Salvar processo</strong>.</li></ol>
            <div class="tutorial-tip"><strong>Dica:</strong> cadastre os procuradores antes de criar o processo. Procuradores inativos não aparecem para novos vínculos.</div></div>
        </article>

        <article class="panel tutorial-step" id="acompanhar">
            <span class="step-number">6</span><div><h2>Acompanhar, pesquisar e filtrar</h2>
            <p>Abra <strong>Prazos</strong> para consultar todos os registros. A busca aceita número do processo, assunto ou nome de procurador.</p>
            <p>Os filtros permitem visualizar todos, novos, vencidos, os que vencem hoje, próximos cinco dias, períodos até 30 dias, em dia e concluídos. Clique em <strong>Limpar</strong> para voltar à lista completa.</p>
            <p>A coluna <strong>Situação</strong> calcula automaticamente quantos dias faltam, se vence hoje ou há quanto tempo está vencido.</p></div>
        </article>

        <article class="panel tutorial-step" id="observacoes">
            <span class="step-number">7</span><div><h2>Editar e registrar observações</h2>
            <ul><li>Clique em <strong>Editar</strong> para alterar dados, datas, status ou procuradores vinculados.</li><li>Clique em <strong>Observações</strong> para registrar rapidamente uma informação sem abrir o formulário completo.</li><li>Revise a data de vencimento antes de salvar, pois ela define todos os alertas e cores do Dashboard.</li></ul></div>
        </article>

        <article class="panel tutorial-step" id="concluir">
            <span class="step-number">8</span><div><h2>Concluir ou excluir um processo</h2>
            <p>Quando o trabalho terminar, clique em <strong>Concluir</strong>. O processo sairá das listas de pendências e será enviado para a página <strong>Concluídos</strong>.</p>
            <p>Use <strong>Excluir</strong> somente para registros criados por engano. A exclusão pede confirmação e não deve ser usada como substituta da conclusão.</p>
            <div class="tutorial-warning"><strong>Atenção:</strong> excluir remove o processo e seus vínculos. Para manter o histórico, prefira concluir.</div></div>
        </article>

        <article class="panel tutorial-step" id="exportar">
            <span class="step-number">9</span><div><h2>Exportar PDF ou Excel</h2>
            <ol><li>No Dashboard, em Prazos ou em Concluídos, clique em <strong>Exportar PDF</strong> ou <strong>Exportar Excel</strong>.</li><li>Se desejar, informe a data inicial e a data final do relatório.</li><li>Confirme a exportação.</li><li>O arquivo será salvo na pasta de downloads do navegador.</li></ol>
            <p>Os relatórios incluem processo, assunto, datas, procuradores vinculados, status, situação e observações.</p></div>
        </article>

        <article class="panel tutorial-step" id="perfil">
            <span class="step-number">10</span><div><h2>Perfil, segurança e saída</h2>
            <p>Clique no seu nome, no canto superior direito, para abrir o perfil. Nessa área você pode conferir os dados pessoais e alterar a senha.</p>
            <p>Ao terminar o trabalho, clique em <strong>Sair</strong>. Isso encerra a sessão e impede que outra pessoa use sua conta naquele computador.</p></div>
        </article>

        <article class="panel tutorial-step" id="recuperacao">
            <span class="step-number">11</span><div><h2>Esqueci minha senha</h2>
            <ol><li>Na tela de login, clique em <strong>Esqueci minha senha</strong>.</li><li>Informe o e-mail cadastrado.</li><li>Digite o código de seis números recebido por e-mail.</li><li>Crie e confirme a nova senha.</li><li>Volte ao login e entre com seu nome de usuário.</li></ol>
            <p>O código expira em 15 minutos, funciona uma única vez e possui limite de tentativas.</p></div>
        </article>

        <article class="panel tutorial-step" id="boas-praticas">
            <span class="step-number">12</span><div><h2>Boas práticas de uso</h2>
            <ul><li>Confira o Dashboard no início de cada dia.</li><li>Mantenha datas e procuradores sempre atualizados.</li><li>Registre decisões importantes nas observações.</li><li>Conclua processos finalizados para manter o painel limpo.</li><li>Exporte relatórios periodicamente como apoio ao acompanhamento.</li><li>Não compartilhe senhas ou códigos recebidos por e-mail.</li><li>Sempre use o botão <strong>Sair</strong> em computadores compartilhados.</li></ul>
            <p><a class="btn btn-primary" href="dashboard.php">Voltar ao Dashboard</a></p></div>
        </article>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
