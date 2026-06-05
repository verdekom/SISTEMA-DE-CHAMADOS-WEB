<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario'])) { header("Location: index.php"); exit; }

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patrimonio = htmlspecialchars($_POST['patrimonio']);
    $local = htmlspecialchars($_POST['local']);
    $tipo = $_POST['tipo_dispositivo'];
    $urgencia = $_POST['urgencia'];
    $conexao = "Não se aplica"; 
    $lacre = $_POST['lacre'];
    $problemas = isset($_POST['problemas']) ? implode(", ", $_POST['problemas']) : "Nenhum sintoma marcado";
    $observacoes = !empty($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : "Nenhuma observação informada";
    
    $relator = $_SESSION['nome'];
    $data = date("d/m/Y H:i");
    $status = "Em Aberto";
    $feedback = "Aguardando análise"; 
    $id = time();

    $linha = "$id;$patrimonio;$local;$tipo;$urgencia;$conexao;$lacre;$problemas;$observacoes;$relator;$data;$status;$feedback\n";
    file_put_contents("chamados.csv", $linha, FILE_APPEND);
    $mensagem = "Chamado para a máquina Nº $patrimonio aberto com sucesso!";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Abrir Chamado Técnico</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-4 md:p-8">
    <div class="max-w-3xl mx-auto bg-white p-6 md:p-8 rounded-xl shadow-sm">
        
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 pb-4 border-b gap-4">
            <div>
                <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Painel do Professor</p>
                <h1 class="text-2xl font-bold text-slate-800">Relatar Problema Técnico</h1>
            </div>
            <div class="flex items-center gap-3 flex-wrap justify-center sm:justify-end">
                <span class="text-sm font-semibold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-full">👤 Olá, <?php echo $_SESSION['nome']; ?></span>
                <a href="status.php" class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-4 py-1.5 rounded text-sm font-semibold transition">Ver Status</a>
                <a href="logout.php" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-1.5 rounded text-sm font-semibold transition">Sair</a>
            </div>
        </div>

        <?php if(!empty($mensagem)): ?>
            <div class="bg-green-100 text-green-800 p-4 rounded mb-6 text-sm font-semibold">✓ <?php echo $mensagem; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4 p-4 bg-blue-50/50 rounded-lg border border-blue-100">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nº do Patrimônio / Máquina <span class="text-red-500">*</span></label>
                    <select name="patrimonio" required class="w-full p-2.5 bg-white border border-slate-300 rounded outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione o número...</option>
                        <?php for($i=1; $i<=99; $i++): ?>
                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>">PC - <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Localização <span class="text-red-500">*</span></label>
                    <select name="local" required class="w-full p-2.5 bg-white border border-slate-300 rounded outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione o local...</option>
                        <optgroup label="Salas de Aula">
                            <?php for($i=1; $i<=15; $i++): ?>
                                <option value="Sala <?php echo $i; ?>">Sala <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </optgroup>
                        <optgroup label="Laboratórios">
                            <option value="Laboratório 1">Laboratório 1</option>
                            <option value="Laboratório 2">Laboratório 2</option>
                        </optgroup>
                        <optgroup label="Outros Ambientes">
                            <option value="Biblioteca">Biblioteca</option>
                            <option value="Auditório">Auditório</option>
                            <option value="Secretaria">Secretaria</option>
                            <option value="Sala dos Professores">Sala dos Professores</option>
                            <option value="Recepção">Recepção</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <p class="text-xs text-slate-400 italic font-medium">* Os campos abaixo são opcionais para preenchimento.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-4 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de Dispositivo</label>
                    <select name="tipo_dispositivo" class="w-full p-2 bg-white border border-slate-300 rounded outline-none">
                        <option value="Não informado">Selecione...</option>
                        <option value="Desktop">Desktop (PC de Mesa)</option>
                        <option value="Notebook">Notebook</option>
                        <option value="Monitor">Monitor</option>
                        <option value="Cabo">Cabo</option>
                        <option value="Teclado">Teclado</option>
                        <option value="Mouse">Mouse</option>
                        <option value="Projetor">Projetor / Datashow</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nível de Urgência</label>
                    <select name="urgencia" class="w-full p-2 bg-white border border-slate-300 rounded font-semibold outline-none">
                        <option value="Não informada">Selecione...</option>
                        <option value="Baixa" class="text-green-600">🟢 Baixa (Uso parcial)</option>
                        <option value="Media" class="text-amber-600">🟡 Média (Atrapalha a aula)</option>
                        <option value="Alta" class="text-red-600">🔴 Alta (Máquina travada)</option>
                    </select>
                </div>
            </div>

            <div>
                <span class="block text-sm font-semibold text-slate-700 mb-3 text-base">Sintomas Observados</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <label class="flex items-center space-x-3 p-2.5 border border-slate-200 rounded hover:bg-slate-50 cursor-pointer"><input type="checkbox" name="problemas[]" value="Não liga" class="w-4 h-4"><span class="text-xs text-slate-700">Não liga</span></label>
                    <label class="flex items-center space-x-3 p-2.5 border border-slate-200 rounded hover:bg-slate-50 cursor-pointer"><input type="checkbox" name="problemas[]" value="Liga mas sem imagem" class="w-4 h-4"><span class="text-xs text-slate-700">Liga mas sem imagem</span></label>
                    <label class="flex items-center space-x-3 p-2.5 border border-slate-200 rounded hover:bg-slate-50 cursor-pointer"><input type="checkbox" name="problemas[]" value="Cabo com mau contato" class="w-4 h-4"><span class="text-xs text-slate-700">Cabo com mau contato</span></label>
                    <label class="flex items-center space-x-3 p-2.5 border border-slate-200 rounded hover:bg-slate-50 cursor-pointer"><input type="checkbox" name="problemas[]" value="Vírus" class="w-4 h-4"><span class="text-xs text-slate-700">Vírus</span></label>
                    <label class="flex items-center space-x-3 p-2.5 border border-slate-200 rounded hover:bg-slate-50 cursor-pointer"><input type="checkbox" name="problemas[]" value="Lentidão / Travando" class="w-4 h-4"><span class="text-xs text-slate-700">Lentidão / Travando</span></label>
                    <label class="flex items-center space-x-3 p-2.5 border border-slate-200 rounded hover:bg-slate-50 cursor-pointer"><input type="checkbox" name="problemas[]" value="Tela azul" class="w-4 h-4"><span class="text-xs text-slate-700">Tela azul</span></label>
                    <label class="flex items-center space-x-3 p-2.5 border border-slate-200 rounded hover:bg-slate-50 cursor-pointer"><input type="checkbox" name="problemas[]" value="Bateria não carrega" class="w-4 h-4"><span class="text-xs text-slate-700">Bateria não carrega</span></label>
                    <label class="flex items-center space-x-3 p-2.5 border border-slate-200 rounded hover:bg-slate-50 cursor-pointer"><input type="checkbox" name="problemas[]" value="Desligando sozinho" class="w-4 h-4"><span class="text-xs text-slate-700">Desligando sozinho</span></label>
                    <label class="flex items-center space-x-3 p-2.5 border border-slate-200 rounded hover:bg-slate-50 cursor-pointer"><input type="checkbox" name="problemas[]" value="Mau contato" class="w-4 h-4"><span class="text-xs text-slate-700">Mau contato</span></label>
                </div>
            </div>

            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <span class="block text-sm font-medium text-amber-900 mb-2 font-semibold">O lacre de garantia física está violado?</span>
                <div class="flex flex-wrap gap-6">
                    <label class="flex items-center space-x-2 cursor-pointer"><input type="radio" name="lacre" value="Intacto" checked><span class="text-sm text-slate-700">Não, está intacto</span></label>
                    <label class="flex items-center space-x-2 cursor-pointer text-red-600 font-semibold"><input type="radio" name="lacre" value="VIOLADO"><span class="text-sm">Sim, está rompido</span></label>
                    <label class="flex items-center space-x-2 cursor-pointer text-slate-600"><input type="radio" name="lacre" value="Não identificado (Professor não soube avaliar)"><span class="text-sm">Não sei informar</span></label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Observações adicionais (Opcional)</label>
                <textarea name="observacoes" rows="2" placeholder="Ex: Detalhes específicos..." class="w-full p-2 border border-slate-300 rounded outline-none focus:ring-2 focus:ring-blue-500 font-sans text-sm"></textarea>
            </div>

            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 rounded-lg transition">
                Enviar Relatório Técnico
            </button>
        </form>
    </div>
</body>
</html>