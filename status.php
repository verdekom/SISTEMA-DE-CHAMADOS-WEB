<?php
// Início da sessão e configurações de segurança do servidor
session_start();
date_default_timezone_set('America/Sao_Paulo');

// O usuário deve estar obrigatoriamente autenticado para ver esta página
if (!isset($_SESSION['usuario'])) { 
    header("Location: index.php"); 
    exit; 
}

// Proteção extra: Garante que a sessão possui um nome válido antes de prosseguir
$nome_usuario_ativo = isset($_SESSION['nome']) ? trim($_SESSION['nome']) : "Usuário Anônimo";

$arquivo_chamados = "chamados.csv";

// --- LÓGICA DE INTERAÇÃO DO USUÁRIO NO CHAT BILATERAL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar_msg_professor'])) {
    $id_alvo = $_POST['id_chamado'];
    $nova_msg = trim(htmlspecialchars($_POST['mensagem_chat']));
    $linhas_atualizadas = [];

    // Bloqueia envios vazios ou se a sessão estiver de alguma forma corrompida
    if (!empty($nova_msg) && $nome_usuario_ativo !== "Usuário Anônimo" && file_exists($arquivo_chamados)) {
        $linhas = file($arquivo_chamados);
        foreach ($linhas as $linha) {
            if (empty(trim($linha))) continue;
            $dados = explode(";", trim($linha));
            if ($dados[0] == $id_alvo) {
                // Descriptografa e decodifica o histórico de mensagens existente na coluna 12 (Base64 + JSON)
                $historico = (isset($dados[12]) && !empty(trim($dados[12]))) ? json_decode(base64_decode(trim($dados[12])), true) : [];
                
                // Adiciona o novo registro de conversa ao arquivo usando a variável de segurança tratada
                $historico[] = [
                    'autor' => $nome_usuario_ativo, 
                    'texto' => $nova_msg, 
                    'hora' => date("d/M H:i")
                ];
                
                // Codifica novamente para salvar com segurança sem quebrar a estrutura CSV
                $dados[12] = base64_encode(json_encode($historico));
            }
            $linhas_atualizadas[] = implode(";", $dados) . (strpos(end($dados), "\n") === false ? "\n" : "");
        }
        file_put_contents($arquivo_chamados, implode("", $linhas_atualizadas));
    }
    header("Location: status.php"); 
    exit;
}

// --- FILTRAGEM E CARREGAMENTO DOS CHAMADOS ---
$chamados = [];
if (file_exists($arquivo_chamados)) {
    $linhas = file($arquivo_chamados);
    foreach ($linhas as $linha) {
        if (!empty(trim($linha))) {
            $ch = explode(";", trim($linha));
            
            // Regra de segurança: O professor comum só consegue visualizar os chamados que ele próprio abriu.
            // O coordenador tem autorização para visualizar a totalidade da lista de chamados.
            if ($_SESSION['nivel'] === 'professor' && trim($ch[9]) !== trim($nome_usuario_ativo)) {
                continue; 
            }
            
            $chamados[] = $ch;
        }
    }
}
// Ordenação cronológica inversa: Os chamados mais recentes aparecem no topo
$chamados = array_reverse($chamados);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Acompanhamento de Chamados</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 p-4 md:p-8">
    <div class="max-w-3xl mx-auto">
        
        <!-- CABEÇALHO DO PAINEL -->
        <div class="bg-white p-6 rounded-xl shadow-sm mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Meus Chamados</h1>
                <p class="text-xs text-slate-500">Histórico de assistência técnica ativa na escola</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-full hidden sm:inline">👤 Usuário: <?php echo $nome_usuario_ativo; ?></span>
                <a href="<?php echo $_SESSION['nivel'] === 'coordenador' ? 'painel_coordenador.php' : 'formulario.php'; ?>" class="text-sm bg-slate-800 text-white px-4 py-2 rounded font-bold hover:bg-slate-900 transition">
                    Voltar
                </a>
            </div>
        </div>

        <!-- LISTAGEM DETALHADA E CHAT -->
        <div class="space-y-6">
            <?php if(empty($chamados)): ?>
                <div class="bg-white p-8 text-center rounded-xl border border-dashed text-slate-400 shadow-sm">
                    Nenhum chamado aberto encontrado associado ao seu perfil.
                </div>
            <?php else: ?>
                <?php foreach($chamados as $ch): 
                    // Carrega as mensagens da conversa bilateral
                    $chat = (isset($ch[12]) && !empty(trim($ch[12]))) ? json_decode(base64_decode(trim($ch[12])), true) : [];
                ?>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                        
                        <!-- IDENTIFICAÇÃO DO EQUIPAMENTO -->
                        <div class="flex justify-between items-start border-b pb-3 gap-2">
                            <div>
                                <span class="bg-blue-100 text-blue-800 text-xs font-mono font-bold px-2 py-0.5 rounded">Código de Patrimônio: PC-<?php echo $ch[1]; ?></span>
                                <h3 class="text-base font-bold text-slate-800 mt-1">📍 Local: <?php echo $ch[2]; ?></h3>
                            </div>
                            <span class="text-xs font-bold px-3 py-1 rounded-full <?php echo $ch[11] === 'Resolvido' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'; ?>">
                                <?php echo strtoupper($ch[11]); ?>
                            </span>
                        </div>

                        <!-- DADOS DO DIAGNÓSTICO DO HARDWARE -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-slate-50 p-3 rounded border">
                            <div><b>📦 Equipamento Danificado:</b> <?php echo $ch[3]; ?></div>
                            <div><b>🚨 Urgência Indicada:</b> <?php echo $ch[4]; ?></div>
                            <div class="sm:col-span-2"><b>⚠️ Sintomas Detectados:</b> <span class="text-slate-700"><?php echo $ch[7]; ?></span></div>
                            <div class="sm:col-span-2"><b>🔒 Estado do Lacre de Garantia:</b> <span class="<?php echo $ch[6]==='VIOLADO'?'text-red-600 font-bold':'text-slate-600';?>"><?php echo $ch[6]; ?></span></div>
                            <div class="sm:col-span-2 border-t pt-2 mt-1 text-slate-600"><b>📝 Observações Adicionais do Professor:</b> <br><span class="italic">"<?php echo $ch[8]; ?>"</span></div>
                        </div>

                        <!-- CHAT BILATERAL DE ACORDO DE MANUTENÇÃO -->
                        <div class="bg-slate-100 p-4 rounded-lg space-y-3">
                            <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wide">Mensagens e Instruções do Técnico</p>
                            
                            <!-- ÁREA DOS BALÕES DE CONVERSA -->
                            <div class="space-y-2 max-h-52 overflow-y-auto bg-white p-3 rounded border shadow-inner">
                                <?php if(empty($chat)): ?>
                                    <p class="text-xs italic text-slate-400 p-1">Aguardando a primeira mensagem do coordenador técnico.</p>
                                <?php else: ?>
                                    <?php foreach($chat as $m): 
                                        $ehAutorLogado = ($m['autor'] === $nome_usuario_ativo);
                                    ?>
                                        <div class="flex flex-col <?php echo $ehAutorLogado ? 'items-end' : 'items-start'; ?>">
                                            <div class="max-w-[85%] p-2 rounded-lg text-xs shadow-sm <?php echo $ehAutorLogado ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-800 border'; ?>">
                                                <b class="block text-[9px] <?php echo $ehAutorLogado ? 'text-blue-200' : 'text-slate-500'; ?> mb-0.5"><?php echo $m['autor']; ?></b>
                                                <p class="leading-relaxed"><?php echo $m['texto']; ?></p>
                                                <span class="block text-[8px] text-right mt-1 opacity-70"><?php echo $m['hora']; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- ENVIO DE RESPOSTA DO PROFESSOR (BLOQUEADO APÓS A RESOLUÇÃO) -->
                            <?php if($ch[11] !== 'Resolvido'): ?>
                                <form method="POST" action="" class="flex gap-2 bg-white p-1 rounded border shadow-sm">
                                    <input type="hidden" name="id_chamado" value="<?php echo $ch[0]; ?>">
                                    <input type="text" name="mensagem_chat" required placeholder="Responder à equipe de suporte..." class="flex-1 p-2 text-xs outline-none bg-transparent">
                                    <button type="submit" name="enviar_msg_professor" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-4 py-2 rounded transition">Responder</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- METADADOS DE IDENTIFICAÇÃO -->
                        <div class="text-[10px] text-slate-400 text-right">
                            Registrado por <b><?php echo $ch[9]; ?></b> em <b><?php echo $ch[10]; ?></b>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>