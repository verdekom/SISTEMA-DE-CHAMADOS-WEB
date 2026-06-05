<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

// SEGURANÇA: Só o coordenador logado pode ver e usar esta página
if (!isset($_SESSION['usuario']) || $_SESSION['nivel'] !== 'coordenador') { 
    header("Location: index.php"); 
    exit; 
}

$mensagem = "";
$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Captura os dados removendo espaços extras nas pontas
    $novo_usuario = trim(htmlspecialchars($_POST['usuario']));
    $nova_senha = trim(htmlspecialchars($_POST['senha']));
    $nome_completo = trim(htmlspecialchars($_POST['nome']));
    $nivel = $_POST['nivel'];

    $arquivo_usuarios = "usuarios.csv";

    // VALIDAÇÃO EM PHP: Bloqueia caso o usuário digite apenas espaços em branco
    if (empty($novo_usuario) || empty($nova_senha) || empty($nome_completo) || empty($nivel)) {
        $erro = "Todos os campos são obrigatórios! Preencha as informações corretamente.";
    } else {
        $usuario_ja_existe = false;
        
        if (file_exists($arquivo_usuarios)) {
            $linhas = file($arquivo_usuarios);
            foreach ($linhas as $linha) {
                if (!empty(trim($linha))) {
                    $dados = explode(";", trim($linha));
                    if ($dados[0] === $novo_usuario) {
                        $usuario_ja_existe = true;
                        break;
                    }
                }
            }
        }

        if ($usuario_ja_existe) {
            $erro = "Este nome de usuário já existe! Escolha outro login.";
        } else {
            // Se tudo estiver preenchido e correto, salva a nova linha no CSV
            $nova_linha = "$novo_usuario;$nova_senha;$nivel;$nome_completo\n";
            file_put_contents($arquivo_usuarios, $nova_linha, FILE_APPEND);
            $mensagem = "Conta criada com sucesso para: $nome_completo!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Coordenador - Cadastrar Usuário</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 p-4 md:p-8">
    <div class="max-w-md mx-auto bg-white p-6 md:p-8 rounded-xl shadow-sm mt-10">
        <div class="text-center mb-6">
            <span class="text-xs font-bold text-blue-600 uppercase">Área Administrativa</span>
            <h1 class="text-2xl font-bold text-slate-800">Cadastrar Novo Usuário</h1>
            <p class="text-xs text-slate-400 mt-1">* Todos os campos abaixo são obrigatórios</p>
        </div>

        <?php if(!empty($erro)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm font-semibold text-center"><?php echo $erro; ?></div>
        <?php endif; ?>

        <?php if(!empty($mensagem)): ?>
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4 text-sm font-semibold text-center">✓ <?php echo $mensagem; ?></div>
        <?php endif; ?>

        <!-- Formulário com o atributo 'required' em todos os campos obrigatórios -->
        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Nome Completo <span class="text-red-500">*</span></label>
                <input type="text" name="nome" required placeholder="Ex: Prof. Ricardo Redes" class="w-full p-2.5 bg-white border border-slate-300 rounded focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Usuário / Login (Sem espaços) <span class="text-red-500">*</span></label>
                <input type="text" name="usuario" required placeholder="Ex: ricardo_redes" class="w-full p-2.5 bg-white border border-slate-300 rounded focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Senha de Acesso <span class="text-red-500">*</span></label>
                <input type="password" name="senha" required placeholder="Digite a senha para a conta" class="w-full p-2.5 bg-white border border-slate-300 rounded focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Tipo de Nível / Cargo <span class="text-red-500">*</span></label>
                <select name="nivel" required class="w-full p-2.5 bg-white border border-slate-300 rounded focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="professor">Professor(a)</option>
                    <option value="coordenador">Coordenador(a) / Técnico</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded transition">
                Registrar Usuário
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="painel_coordenador.php" class="text-sm text-slate-600 hover:underline">← Voltar ao Painel Principal</a>
        </div>
    </div>
</body>
</html>