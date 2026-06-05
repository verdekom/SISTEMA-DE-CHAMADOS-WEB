<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

$arquivo_usuarios = "usuarios.csv";

// Garante a estrutura correta: login;senha;nivel;nome
if (!file_exists($arquivo_usuarios)) {
    $usuarios_iniciais = "professor;prof123;professor;Professor(a)\ncoordenador;coord123;coordenador;Coordenador do Curso\n";
    file_put_contents($arquivo_usuarios, $usuarios_iniciais);
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Usamos o trim para evitar problemas com espaços invisíveis digitados sem querer
    $user_digitado = trim($_POST['usuario']);
    $pass_digitado = trim($_POST['senha']);

    $linhas = file($arquivo_usuarios);
    $login_sucesso = false;

    foreach ($linhas as $linha) {
        if (!empty(trim($linha))) {
            $dados = explode(";", trim($linha));
            
            // Forçamos o PHP a limpar qualquer espaço invisível nas pontas das palavras
            $db_user  = trim($dados[0]);
            $db_senha = trim($dados[1]);
            $db_nivel = trim($dados[2]);
            $db_nome  = trim($dados[3]);
            
            if ($db_user === $user_digitado && $db_senha === $pass_digitado) {
                $_SESSION['usuario'] = $db_user;
                $_SESSION['nivel']   = $db_nivel;
                $_SESSION['nome']    = $db_nome;
                $login_sucesso = true;
                break;
            }
        }
    }

    if ($login_sucesso) {
        // Teste crucial de direcionamento
        if ($_SESSION['nivel'] === 'coordenador') {
            header("Location: painel_coordenador.php");
        } else {
            header("Location: formulario.php");
        }
        exit;
    } else {
        $erro = "Usuário ou senha incorretos!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Suporte Técnico - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Suporte Técnico Escolar</h1>
            <p class="text-slate-500 text-sm mt-1">Primeiro Módulo de Redes de Computadores</p>
        </div>

        <?php if(!empty($erro)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm font-semibold text-center"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Usuário / Login</label>
                <input type="text" name="usuario" required placeholder="Digite seu usuário" class="w-full p-2.5 border border-slate-300 rounded focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Senha</label>
                <input type="password" name="senha" required placeholder="••••••••" class="w-full p-2.5 border border-slate-300 rounded focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded transition">
                Entrar no Sistema
            </button>
        </form>
    </div>
</body>
</html>