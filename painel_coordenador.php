<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario']) || $_SESSION['nivel'] !== 'coordenador') { header("Location: index.php"); exit; }

$arquivo_chamados = "chamados.csv";
$arquivo_usuarios = "usuarios.csv";

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'abertos';

// --- LÓGICA DO CHAT (ENVIAR MENSAGEM) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar_mensagem'])) {
    $id_alvo = $_POST['id_chamado'];
    $nova_msg = trim(htmlspecialchars($_POST['mensagem_chat']));
    $linhas_atualizadas = [];

    if (!empty($nova_msg) && file_exists($arquivo_chamados)) {
        $linhas = file($arquivo_chamados);
        foreach ($linhas as $linha) {
            if (empty(trim($linha))) continue;
            $dados = explode(";", trim($linha));
            if ($dados[0] == $id_alvo) {
                // A coluna 12 armazena o histórico do chat criptografado em Base64
                $historico = (isset($dados[12]) && !empty(trim($dados[12]))) ? json_decode(base64_decode(trim($dados[12])), true) : [];
                $historico[] = [
                    'autor' => $_SESSION['nome'],
                    'texto' => $nova_msg,
                    'hora' => date("d/M H:i")
                ];
                $dados[12] = base64_encode(json_encode($historico));
            }
            $linhas_atualizadas[] = implode(";", $dados) . (strpos(end($dados), "\n") === false ? "\n" : "");
        }
        file_put_contents($arquivo_chamados, implode("", $linhas_atualizadas));
    }
    header("Location: painel_coordenador.php?tab=" . $tab);
    exit;
}

// --- MUDAR STATUS (RESOLVER OU REABRIR) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id_alvo = $_GET['id'];
    $linhas_atualizadas = [];
    if (file_exists($arquivo_chamados)) {
        $linhas = file($arquivo_chamados);
        foreach ($linhas as $linha) {
            if (empty(trim($linha))) continue;
            $dados = explode(";", trim($linha));
            if ($dados[0] == $id_alvo) {
                if ($action == 'resolver') {
                    $dados[11] = "Resolvido";
                } elseif ($action == 'reabrir') {
                    $dados[11] = "Em Aberto";
                }
            }
            $linhas_atualizadas[] = implode(";", $dados) . (strpos(end($dados), "\n") === false ? "\n" : "");
        }
        file_put_contents($arquivo_chamados, implode("", $linhas_atualizadas));
    }
    header("Location: painel_coordenador.php?tab=" . $tab);
    exit;
}

// --- APAGAR CHAMADO ---
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id_deletar = $_GET['id'];
    $linhas_restantes = [];
    if (file_exists($arquivo_chamados)) {
        $linhas = file($arquivo_chamados);
        foreach ($linhas as $linha) {
            $dados = explode(";", trim($linha));
            if ($dados[0] != $id_deletar) { $linhas_restantes[] = $linha; }
        }
        file_put_contents($arquivo_chamados, implode("", $linhas_restantes));
    }
    header("Location: painel_coordenador.php?tab=" . $tab);
    exit;
}

// --- CONTAGEM DE SEGURANÇA DE COORDENADORES ---
$qtd_coordenadores = 0;
if (file_exists($arquivo_usuarios)) {
    $linhas_contagem = file($arquivo_usuarios);
    foreach ($linhas_contagem as $l_conf) {
        if (!empty(trim($l_conf))) {
            $d_conf = explode(";", trim($l_conf));
            if ($d_conf[2] === 'coordenador') { $qtd_coordenadores++; }
        }
    }
}

// --- APAGAR UTILIZADOR ---
if (isset($_GET['delete_user'])) {
    $user_deletar = $_GET['delete_user'];
    $linhas_restantes = [];
    if (file_exists($arquivo_usuarios)) {
        $linhas = file($arquivo_usuarios);
        foreach ($linhas as $linha) {
            $dados = explode(";", trim($linha));
            if ($dados[0] != $user_deletar || $user_deletar === $_SESSION['usuario']) { $linhas_restantes[] = $linha; }
        }
        file_put_contents($arquivo_usuarios, implode("", $linhas_restantes));
    }
    header("Location: painel_coordenador.php?tab=usuarios");
    exit;
}

// --- EDITAR UTILIZADOR ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_usuario'])) {
    $user_alvo = $_POST['user_original'];
    $novo_nome = trim(htmlspecialchars($_POST['nome_editado']));
    $nova_senha = trim(htmlspecialchars($_POST['senha_editada']));
    $novo_nivel = $_POST['nivel_editado'];
    
    if ($user_alvo === $_SESSION['usuario'] && $novo_nivel === 'professor' && $qtd_coordenadores <= 1) {
        $novo_nivel = 'coordenador';
        echo "<script>alert('Ação bloqueada! Você é o único coordenador no sistema.');</script>";
    }

    $linhas_atualizadas = [];
    if (file_exists($arquivo_usuarios)) {
        $linhas = file($arquivo_usuarios);
        foreach ($linhas as $linha) {
            $dados = explode(";", trim($linha));
            if ($dados[0] === $user_alvo) {
                $dados[1] = $nova_senha;
                $dados[2] = $novo_nivel;
                $dados[3] = $novo_nome;
                if ($user_alvo === $_SESSION['usuario']) {
                    $_SESSION['nome'] = $novo_nome;
                    $_SESSION['nivel'] = $novo_nivel;
                }
            }
            $linhas_atualizadas[] = implode(";", $dados) . "\n";
        }
        file_put_contents($arquivo_usuarios, implode("", $linhas_atualizadas));
    }
    if ($_SESSION['nivel'] === 'professor') { header("Location: formulario.php"); } 
    else { header("Location: painel_coordenador.php?tab=usuarios"); }
    exit;
}

// --- PROCESSAMENTO DA LISTAGEM ---
$chamados_abertos = [];
$chamados_resolvidos = [];
if (file_exists($arquivo_chamados)) {
    $linhas = file($arquivo_chamados);
    foreach ($linhas as $linha) {
        if (!empty(trim($linha))) { 
            $ch = explode(";", trim($linha));
            if ($ch[11] === 'Resolvido') { $chamados_resolvidos[] = $ch; } 
            else { $chamados_abertos[] = $ch; }
        }
    }
}
$chamados_abertos = array_reverse($chamados_abertos);
$chamados_resolvidos = array_reverse($chamados_resolvidos);

$lista_usuarios = [];
if (file_exists($arquivo_usuarios)) {
    $linhas = file($arquivo_usuarios);
    foreach ($linhas as $linha) {
        if (!empty(trim($linha))) { $lista_usuarios[] = explode(";", trim($linha)); }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Coordenador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 p-4 md:p-8">
    <div class="max-w-4xl mx-auto">
        
        <div class="bg-white p-6 rounded-xl shadow-sm mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <p class="text-xs text-blue-600 font-bold uppercase">Gestão de Laboratórios</p>
                <h1 class="text-2xl font-bold text-slate-800">Painel do Coordenador</h1>
            </div>
            <div class="flex items-center gap-3 flex-wrap justify-center sm:justify-end w-full sm:w-auto">
                <span class="text-sm font-semibold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-full w-full sm:w-auto text-center">👤 Olá, <?php echo $_SESSION['nome']; ?></span>
                <a href="cadastro.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded text-sm font-semibold transition">➕ Usuário</a>
                <a href="status.php" class="bg-slate-100 text-slate-700 hover:bg-slate-200 px-4 py-1.5 rounded text-sm font-semibold transition">Ver Status</a>
                <a href="logout.php" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-1.5 rounded text-sm font-semibold transition">Sair</a>
            </div>
        </div>

        <div class="flex border-b border-slate-200 mb-4 bg-white rounded-t-lg overflow-hidden shadow-sm">
            <a href="painel_coordenador.php?tab=abertos" class="flex-1 text-center py-3 font-bold text-xs sm:text-sm transition <?php echo $tab === 'abertos' ? 'bg-amber-500 text-white' : 'text-slate-600 hover:bg-slate-50'; ?>">📂 Abertos (<?php echo count($chamados_abertos); ?>)</a>
            <a href="painel_coordenador.php?tab=resolvidos" class="flex-1 text-center py-3 font-bold text-xs sm:text-sm transition <?php echo $tab === 'resolvidos' ? 'bg-green-600 text-white' : 'text-slate-600 hover:bg-slate-50'; ?>">✓ Finalizados (<?php echo count($chamados_resolvidos); ?>)</a>
            <a href="painel_coordenador.php?tab=usuarios" class="flex-1 text-center py-3 font-bold text-xs sm:text-sm transition <?php echo $tab === 'usuarios' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-50'; ?>">👤 Contas/Professores (<?php echo count($lista_usuarios); ?>)</a>
        </div>

        <?php if ($tab !== 'usuarios'): ?>
            <div class="space-y-2">
                <?php $lista_exibicao = ($tab === 'resolvidos') ? $chamados_resolvidos : $chamados_abertos; ?>
                <?php if(empty($lista_exibicao)): ?>
                    <div class="bg-white p-8 text-center rounded-xl border border-dashed italic text-slate-400 shadow-sm">Nenhum chamado nesta aba.</div>
                <?php else: ?>
                    <?php foreach($lista_exibicao as $ch): 
                        $chat = (isset($ch[12]) && !empty(trim($ch[12]))) ? json_decode(base64_decode(trim($ch[12])), true) : [];
                    ?>
                        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                            <div onclick="toggleElemento('card_<?php echo $ch[0]; ?>')" class="p-3 flex items-center justify-between cursor-pointer hover:bg-slate-50 select-none transition">
                                <div class="flex items-center space-x-3 truncate">
                                    <span class="font-mono text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">PC: <?php echo $ch[1]; ?></span>
                                    <span class="text-sm font-bold text-slate-800 truncate"><?php echo $ch[2]; ?></span>
                                    <span class="text-xs text-slate-400 hidden sm:inline">👤 Relator: <?php echo $ch[9]; ?></span>
                                </div>
                                <div class="flex items-center space-x-2 shrink-0">
                                    <span class="text-xs text-slate-400 font-mono"><?php echo explode(" ", $ch[10])[0]; ?></span>
                                    <span class="text-slate-400 text-xs font-bold" id="seta_card_<?php echo $ch[0]; ?>">▼</span>
                                </div>
                            </div>
                            
                            <div id="card_<?php echo $ch[0]; ?>" class="hidden border-t p-4 bg-slate-50/60 space-y-4">
                                <div class="grid grid-cols-1 gap-2.5">
                                    <div class="flex items-center bg-white border rounded overflow-hidden">
                                        <span class="bg-slate-100 text-slate-600 text-[11px] font-bold px-2 py-1.5 w-24 shrink-0 border-r">Patrimônio</span>
                                        <input type="text" readonly value="<?php echo $ch[1]; ?>" id="patr_<?php echo $ch[0]; ?>" class="px-2 py-1 text-xs flex-1 bg-transparent font-mono font-bold outline-none">
                                        <button onclick="copiarTexto('patr_<?php echo $ch[0]; ?>')" class="bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold px-3 py-1.5">Copiar</button>
                                    </div>
                                    <div class="flex items-center bg-white border rounded overflow-hidden">
                                        <span class="bg-slate-100 text-slate-600 text-[11px] font-bold px-2 py-1.5 w-24 shrink-0 border-r">Localização</span>
                                        <input type="text" readonly value="<?php echo $ch[2]; ?>" id="loc_<?php echo $ch[0]; ?>" class="px-2 py-1 text-xs flex-1 bg-transparent outline-none">
                                        <button onclick="copiarTexto('loc_<?php echo $ch[0]; ?>')" class="bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold px-3 py-1.5">Copiar</button>
                                    </div>
                                    <div class="flex items-center bg-white border rounded overflow-hidden">
                                        <span class="bg-slate-100 text-slate-600 text-[11px] font-bold px-2 py-1.5 w-24 shrink-0 border-r">Sintomas</span>
                                        <input type="text" readonly value="<?php echo $ch[7]; ?>" id="sint_<?php echo $ch[0]; ?>" class="px-2 py-1 text-xs flex-1 bg-transparent outline-none">
                                        <button onclick="copiarTexto('sint_<?php echo $ch[0]; ?>')" class="bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold px-3 py-1.5">Copiar</button>
                                    </div>
                                    <div class="flex items-center bg-white border rounded overflow-hidden">
                                        <span class="bg-slate-100 text-slate-600 text-[11px] font-bold px-2 py-1.5 w-24 shrink-0 border-r">Obs. Adicionais</span>
                                        <input type="text" readonly value="<?php echo $ch[8]; ?>" id="obs_<?php echo $ch[0]; ?>" class="px-2 py-1 text-xs text-slate-600 flex-1 bg-transparent outline-none">
                                        <button onclick="copiarTexto('obs_<?php echo $ch[0]; ?>')" class="bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold px-3 py-1.5">Copiar</button>
                                    </div>
                                </div>
                                
                                <div class="text-[11px] text-slate-500 flex flex-wrap gap-x-4 gap-y-1 bg-white p-2 rounded border border-dashed">
                                    <span>📦 <b>Dispositivo:</b> <?php echo $ch[3]; ?></span>
                                    <span>🚨 <b>Urgência:</b> <?php echo $ch[4]; ?></span>
                                    <span>🔒 <b>Lacre Físico:</b> <?php echo $ch[6]; ?></span>
                                    <span class="ml-auto">🕒 <b>Aberto em:</b> <?php echo $ch[10]; ?></span>
                                </div>

                                <div class="bg-white p-3 rounded-lg border space-y-2">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Histórico de Alinhamento Técnico</p>
                                    <div class="space-y-1.5 max-h-44 overflow-y-auto bg-slate-50 p-2 rounded border inner-chat shadow-inner">
                                        <?php if(empty($chat)): ?>
                                            <p class="text-xs italic text-slate-400 p-1">Nenhuma mensagem trocada. Inicie a conversa abaixo.</p>
                                        <?php else: ?>
                                            <?php foreach($chat as $msg): ?>
                                                <div class="text-xs bg-white p-2 rounded border shadow-sm">
                                                    <span class="font-bold text-blue-600"><?php echo $msg['autor']; ?>:</span>
                                                    <span class="text-slate-700"><?php echo $msg['texto']; ?></span>
                                                    <span class="text-[9px] text-slate-400 float-right mt-0.5"><?php echo $msg['hora']; ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <form method="POST" action="" class="flex gap-2">
                                        <input type="hidden" name="id_chamado" value="<?php echo $ch[0]; ?>">
                                        <input type="text" name="mensagem_chat" required placeholder="Digite uma orientação ou pergunta para o professor..." class="flex-1 p-2 text-xs border rounded outline-none focus:ring-1 focus:ring-blue-500">
                                        <button type="submit" name="enviar_mensagem" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 rounded transition">Enviar</button>
                                    </form>
                                </div>
                                
                                <div class="flex flex-col sm:flex-row justify-between items-center border-t pt-3 gap-2">
                                    <a href="painel_coordenador.php?tab=<?php echo $tab; ?>&delete=true&id=<?php echo $ch[0]; ?>" onclick="return confirm('Pretende apagar definitivamente este registo do sistema?')" class="text-xs text-red-500 hover:underline">🗑️ Remover Chamado</a>
                                    
                                    <?php if ($ch[11] !== 'Resolvido'): ?>
                                        <a href="painel_coordenador.php?tab=<?php echo $tab; ?>&action=resolver&id=<?php echo $ch[0]; ?>" class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white text-center text-xs font-bold px-4 py-2 rounded shadow transition">✓ Marcar como Resolvido</a>
                                    <?php else: ?>
                                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                                            <span class="text-xs text-green-700 font-bold bg-green-50 px-2 py-1 rounded border border-green-200">Finalizado</span>
                                            <a href="painel_coordenador.php?tab=<?php echo $tab; ?>&action=reabrir&id=<?php echo $ch[0]; ?>" class="bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold px-3 py-1.5 rounded transition">↩ Reabrir</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="space-y-2">
                <?php foreach($lista_usuarios as $usr): ?>
                    <div class="bg-white rounded-lg border border-slate-200 shadow-sm">
                        <div onclick="toggleElemento('user_<?php echo $usr[0]; ?>')" class="p-3 flex items-center justify-between cursor-pointer hover:bg-slate-50 select-none">
                            <div class="flex items-center space-x-3">
                                <span class="text-sm font-bold text-slate-800">👤 <?php echo $usr[3]; ?></span>
                                <span class="text-xs font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-500">login: <?php echo $usr[0]; ?></span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded <?php echo $usr[2] === 'coordenador' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>"><?php echo ucfirst($usr[2]); ?></span>
                                <span class="text-slate-400 text-xs font-bold" id="seta_user_<?php echo $usr[0]; ?>">▼</span>
                            </div>
                        </div>

                        <div id="user_<?php echo $usr[0]; ?>" class="hidden border-t p-4 bg-slate-50/70">
                            <form method="POST" action="" class="space-y-3">
                                <input type="hidden" name="user_original" value="<?php echo $usr[0]; ?>">
                                <input type="hidden" name="editar_usuario" value="1">
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Nome Completo</label>
                                        <input type="text" name="nome_editado" required value="<?php echo $usr[3]; ?>" class="w-full p-1.5 text-xs bg-white border rounded outline-none focus:ring-1 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Senha (Oculta)</label>
                                        <input type="password" name="senha_editada" required value="<?php echo $usr[1]; ?>" class="w-full p-1.5 text-xs bg-white border rounded font-mono outline-none focus:ring-1 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Nível de Acesso</label>
                                        <?php if ($usr[0] === $_SESSION['usuario'] && $qtd_coordenadores <= 1): ?>
                                            <select name="nivel_editado" class="w-full p-1.5 text-xs bg-slate-100 border rounded cursor-not-allowed text-slate-500" readonly>
                                                <option value="coordenador" selected>Coordenador (Mínimo de 1 ativo)</option>
                                            </select>
                                        <?php else: ?>
                                            <select name="nivel_editado" class="w-full p-1.5 text-xs bg-white border rounded outline-none focus:ring-1 focus:ring-blue-500">
                                                <option value="professor" <?php echo $usr[2] === 'professor' ? 'selected' : ''; ?>>Professor(a)</option>
                                                <option value="coordenador" <?php echo $usr[2] === 'coordenador' ? 'selected' : ''; ?>>Coordenador / Técnico</option>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center border-t pt-2 mt-2">
                                    <?php if ($usr[0] !== $_SESSION['usuario']): ?>
                                        <a href="painel_coordenador.php?tab=usuarios&delete_user=<?php echo $usr[0]; ?>" onclick="return confirm('Remover permanentemente os acessos de <?php echo $usr[3]; ?>?')" class="text-xs text-red-500 hover:underline">🗑️ Remover Conta</a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">Sua Conta Ativa</span>
                                    <?php endif; ?>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1.5 rounded transition">💾 Salvar Alterações</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleElemento(id) {
            const card = document.getElementById(id);
            const seta = document.getElementById("seta_" + id);
            if(card.classList.contains('hidden')){ card.classList.remove('hidden'); seta.innerText="▲"; }
            else{ card.classList.add('hidden'); seta.innerText="▼"; }
        }
        function copiarTexto(id) {
            var el = document.getElementById(id);
            navigator.clipboard.writeText(el.value).then(() => {
                const btn = event.target;
                const txt = btn.innerText;
                btn.innerText = "OK"; btn.classList.replace('bg-blue-600','bg-green-600');
                setTimeout(() => { btn.innerText = txt; btn.classList.replace('bg-green-600','bg-blue-600'); }, 1000);
            });
        }
    </script>
</body>
</html>