<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';

$usuarioLogado = $_SESSION['usuario_id'];
$stmtUser = $pdo->prepare("SELECT * FROM usuarios WHERE usuario_id = ?");
$stmtUser->execute([$usuarioLogado]);
$usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);
$nomeusuario = $usuario['nome'] ?? '';
$perfilUsuario = $usuario['perfil'] ?? '';
$emailUsuario = $usuario['email'] ?? '';
$fotoUsuario = $usuario['foto'] ?? 'padrao.png';

// Preferências do usuário
$stmtPref = $pdo->prepare("SELECT dark_mode, font_size, color_theme FROM preferencias_usuario WHERE usuario_id = ?");
$stmtPref->execute([$usuarioLogado]);
$preferencias = $stmtPref->fetch(PDO::FETCH_ASSOC);

if (!$preferencias) {
    $stmtInsert = $pdo->prepare("INSERT INTO preferencias_usuario (usuario_id) VALUES (?)");
    $stmtInsert->execute([$usuarioLogado]);
    $preferencias = [
        'dark_mode' => 'disabled',
        'font_size' => 'default',
        'color_theme' => 'default'
    ];
}

// Trocar senha do usuário logado
if (isset($_POST['trocar_senha'])) {

    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if (!$senhaAtual || !$novaSenha || !$confirmarSenha) {
        $error = "Preencha todos os campos.";
    } elseif ($novaSenha !== $confirmarSenha) {
        $error = "A nova senha não confere.";
    } else {

        // Buscar hash atual
        $stmt = $pdo->prepare(
            "SELECT senha FROM usuarios WHERE usuario_id = :usuario_id LIMIT 1"
        );
        $stmt->execute([
            ':usuario_id' => $_SESSION['usuario_id']
        ]);

        $hashBanco = $stmt->fetchColumn();

        if (!$hashBanco || !password_verify($senhaAtual, $hashBanco)) {
            $error = "Senha atual incorreta.";
        } else {

            // Atualizar senha
            $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "UPDATE usuarios SET senha = :senha WHERE usuario_id = :usuario_id"
            );
            $stmt->execute([
                ':senha' => $novoHash,
                ':usuario_id' => $_SESSION['usuario_id']
            ]);

            $success = "Senha alterada com sucesso.";
        }
    }
}

// Telas disponíveis para busca
$telas = [
    ['nome' => 'Cadastro de Produtos', 'tituloAba' => 'Produtos', 'url' => '../pages/produtos/produtos_content.php'],
    ['nome' => 'Cadastro de parceiros', 'tituloAba' => 'Parceiros', 'url' => '../pages/parceiros/parceiros_content.php'],
    ['nome' => 'Categorias', 'tituloAba' => 'Categorias', 'url' => '../pages/categorias/categorias_content.php'],
    ['nome' => 'Calibres e variações de produtos', 'tituloAba' => 'Calibres', 'url' => '../pages/calibre/calibre_content.php'],
    ['nome' => 'Vinculos de calibres', 'tituloAba' => 'Vinculo calibres', 'url' => '../pages/calibre/calibre_produto_content.php'],
    ['nome' => 'Volumes', 'tituloAba' => 'Volumes', 'url' => '../pages/volumes/volumes_content.php'],
    ['nome' => 'Entradas de mercadorias', 'tituloAba' => 'Entradas', 'url' => '../pages/entradas/entradas_content.php'],
    ['nome' => 'Saídas de mercadorias', 'tituloAba' => 'Saídas', 'url' => '../pages/saida_mercadoria/saida_mercadoria.php'],
    ['nome' => 'Entradas(Relatórios)', 'tituloAba' => 'R.Entradas', 'url' => '../pages/relatorios/relatorio_entradas.php'],
    ['nome' => 'Cadastros de usuários', 'tituloAba' => 'Usuário', 'url' => '../pages/gerenciamento/usuarios_content.php'],
    ['nome' => 'Mapeamento de telas e ações', 'tituloAba' => 'Mapeamento', 'url' => '../pages/gerenciamento/mapeamento_content.php'],
    ['nome' => 'Permissões de usuário', 'tituloAba' => 'Permissões', 'url' => '../pages/gerenciamento/permissoes_content.php'],
    ['nome' => 'Criação de chaves', 'tituloAba' => 'chaves', 'url' => '../pages/gerenciamento/chaves_content.php'],
    ['nome' => 'Produção de mercadorias', 'tituloAba' => 'Produções', 'url' => '../pages/producao/producao_content.php'],
    ['nome' => 'Classificação', 'tituloAba' => 'Classificação', 'url' => ''],
    ['nome' => 'Transferencias', 'tituloAba' => 'Transferencias', 'url' => ''],
    ['nome' => 'Devoluções de entradas', 'tituloAba' => 'Devoluções', 'url' => ''],
    ['nome' => 'Avarias', 'tituloAba' => 'Avarias', 'url' => ''],
    ['nome' => 'Doações', 'tituloAba' => 'Doações', 'url' => ''],
];

$caminhoFoto = file_exists($_SERVER['DOCUMENT_ROOT'] . '/lemyx/uploads/usuarios/' . $fotoUsuario)
    ? '/lemyx/uploads/usuarios/' . $fotoUsuario
    : '/lemyx/uploads/usuarios/';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lemyx</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="manifest" href="../assets/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/icons/lemyx_sf-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="../assets/icons/lemyx_sf-192.png">
    <link rel="icon" type="image/png" sizes="500x500" href="../assets/icons/lemyx_sf-512.png">
    <link rel="apple-touch-icon" href="../assets/icons/lemyx_sf-192.png">

</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow theme-navbar">
        <div class="container-fluid d-flex align-items-center justify-content-between">

            <div class="d-flex align-items-center me-4">
                <a href="#" id="btnHomeDashboard" class="fw-bold text-white fs-5 text-decoration-none user-select-none">
                    Lemyx
                </a>
            </div>

            <div class="tab-wrapper me-3 flex-grow-1">
                <ul class="nav nav-tabs" id="dynamicTabs"></ul>
            </div>

            <div class="d-flex align-items-center">
                <div class="search-container me-4">
                    <input type="text" id="search-input" placeholder="Buscar telas..." autocomplete="on" />
                    <ul id="suggestions" style="display:none;"></ul>
                </div>

                <button
                    class="btn btn-outline-light rounded-circle me-2 d-flex align-items-center justify-content-center"
                    data-bs-toggle="offcanvas" data-bs-target="#usuarioOffcanvas" title="Usuário" aria-label="Usuário"
                    style="width: 40px; height: 40px;">
                    <img id="fotoUsuario_1" src="/lemyx/uploads/usuarios/usuario_<?= $_SESSION['usuario_id'] ?>.jpg"
                        class="rounded-circle d-none" width="40" height="40" alt="Foto do usuário"
                        onload="this.classList.remove('d-none'); document.getElementById('iconeUsuario_1').classList.add('d-none');"
                        onerror="this.classList.add('d-none'); document.getElementById('iconeUsuario_1').classList.remove('d-none');">
                    <i id="iconeUsuario_1" class="fa-solid fa-user text-light" style="font-size: 18px;"></i>
                </button>

            </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="usuarioOffcanvas" aria-labelledby="usuarioOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="usuarioOffcanvasLabel">Conta</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column p-0">

            <!-- Cabeçalho do usuário -->
            <div class="text-center p-4 border-bottom">

                <!-- Avatar clicável -->
                <div class="mb-2 d-flex justify-content-center">
                    <button type="button" class="btn p-0 border-0 bg-transparent position-relative"
                        onclick="document.getElementById('inputFoto').click();" style="width: 120px; height: 120px;"
                        aria-label="Alterar foto do usuário" title="Alterar foto">

                        <!-- Foto -->
                        <img id="fotoUsuario_2" src="/lemyx/uploads/usuarios/usuario_<?= $_SESSION['usuario_id'] ?>.jpg"
                            class="rounded-circle d-none" width="120" height="120" alt="Foto do usuário"
                            onload="this.classList.remove('d-none'); document.getElementById('iconeUsuario_2').classList.add('d-none');"
                            onerror="this.classList.add('d-none'); document.getElementById('iconeUsuario_2').classList.remove('d-none');">

                        <!-- Ícone fallback -->
                        <i id="iconeUsuario_2"
                            class="fa-solid fa-user d-flex align-items-center justify-content-center rounded-circle bg-secondary text-white"
                            style="font-size: 80px; width: 120px; height: 120px;">
                        </i>

                        <!-- Ícone câmera (overlay) -->
                        <span
                            class="position-absolute bottom-0 end-0 bg-dark rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="fa-solid fa-camera text-light" style="font-size: 10px;"></i>
                        </span>

                    </button>
                </div>

                <!-- Input file oculto -->
                <input type="file" id="inputFoto" accept="image/*" class="d-none">

                <!-- Nome e perfil -->
                <h6 class="mb-1">
                    Bem-vindo,
                    <strong><?= htmlspecialchars($nomeusuario) ?></strong>
                    (<?= htmlspecialchars($perfilUsuario) ?>)
                </h6>

                <!-- Email -->
                <small class="text-muted">
                    <strong><?= htmlspecialchars($emailUsuario) ?></strong>
                </small>

            </div>


            <!-- Menu -->
            <div class="list-group list-group-flush flex-grow-1">

                <button class="list-group-item list-group-item-action" id="btnTrocarSenha" data-bs-toggle="modal"
                    data-bs-target="#modalTrocarSenha">
                    🔒 Trocar senha
                </button>

                <button class="list-group-item list-group-item-action" data-bs-toggle="modal"
                    data-bs-target="#configModal">
                    ⚙️ Preferências
                </button>
            </div>

            <!-- Rodapé -->
            <div class="p-3 border-top">

                <button class="list-group-item list-group-item-action p-3" id="btnInstalarApp" onclick="instalarApp()">
                    Instalar Lemyx
                </button>

                <a href="../logout.php" class="btn btn-outline-danger w-100">
                    🚪 Sair
                </a>
            </div>

        </div>

    </div>

    <div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="configModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="configModalLabel">⚙️ Configurações da Tela</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="toggleDarkMode">
                        <label class="form-check-label" for="toggleDarkMode">🌗 Modo Escuro</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="fontSizeSelect">🔠 Tamanho da Fonte</label>
                        <select class="form-select" id="fontSizeSelect">
                            <option value="small">Pequeno</option>
                            <option value="default">Normal</option>
                            <option value="large">Grande</option>
                        </select>
                    </div>

                    <div class="theme-picker">
                        <label for="themeColor">🎨 Selecione a cor do tema</label>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <div class="d-flex align-items-center gap-2 mt-2"><input type="color" id="themeColor"
                                    value="#3f4750">
                            </div>
                            <button type="button" id="saveThemeColor" class="btn btn-primary btn-sm">
                                Salvar
                            </button>
                        </div>
                    </div>

                    <!-- Mensagem abaixo do seletor -->
                    <div class="mt-2">
                        <small id="themeSavedMsg" class="text-success d-none">
                            🎨 Cor do tema salva com sucesso
                        </small>
                    </div>

                </div>


            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>

        </div>

    </div>

    </div>

    <div class="d-flex">
        <div class="sidebar p-3" id="sidebar">
            <h3 class="text-white">___________________</h3>
            <ul class="nav flex-column mt-4">
                <li class="nav-item mb-2">
                    <a href="#submenuCadastros" class="nav-link toggle-submenu">Cadastros<span
                            class="arrow float-end">▼</span></a>
                    <ul class="submenu list-unstyled" id="submenuCadastros">
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/parceiros/parceiros_content.php">Parceiros</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/produtos/produtos_content.php">Produtos</a></li>
                    </ul>
                </li>

                <li class="nav-item mb-2">
                    <a href="#submenuCadastros" class="nav-link toggle-submenu">Controle<span
                            class="arrow float-end">▼</span></a>
                    <ul class="submenu list-unstyled" id="submenuCadastros">
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/categorias/categorias_content.php">Categorias</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/calibre/calibre_content.php">Calibres</a>
                        </li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/calibre/calibre_produto_content.php">Calibres/Produtos</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/volumes/volumes_content.php">Volumes</a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item mb-2">
                    <a href="#submenuEntradas" class="nav-link toggle-submenu">Compras<span
                            class="arrow float-end">▼</span></a>
                    <ul class="submenu list-unstyled" id="submenuEntradas">
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/entradas/entradas_content.php">Entradas</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/entradas/entradas_content.php">Devoluções</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/relatorios/relatorio_entradas.php">Relatórios</a></li>
                    </ul>
                </li>

                <li class="nav-item mb-2">
                    <a href="#submenuEntradas" class="nav-link toggle-submenu">Perdas<span
                            class="arrow float-end">▼</span></a>
                    <ul class="submenu list-unstyled" id="submenuEntradas">
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/entradas/entradas_content.php">Avarias</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/entradas/entradas_content.php">Doações</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/relatorios/relatorios.php">Relatórios</a></li>
                    </ul>
                </li>

                <li class="nav-item mb-2">
                    <a href="#submenuEntradas" class="nav-link toggle-submenu">Transformações<span
                            class="arrow float-end">▼</span></a>
                    <ul class="submenu list-unstyled" id="submenuEntradas">
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/producao/producao_content.php">Produções</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/producao/producao_content.php">Classificações</a></li>
                    </ul>
                </li>

                <li class="nav-item mb-2">
                    <a href="#submenuEntradas" class="nav-link toggle-submenu">Transferencias<span
                            class="arrow float-end">▼</span></a>
                    <ul class="submenu list-unstyled" id="submenuEntradas">
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/entradas/entradas_content.php">Filiais</a></li>
                    </ul>
                </li>

                <li class="nav-item mb-2">
                    <a href="#submenuSaidas" class="nav-link toggle-submenu">Saídas<span
                            class="arrow float-end">▼</span></a>
                    <ul class="submenu list-unstyled" id="submenuSaidas">
                        <li><a href="#" class="nav-link load-page"
                                data-url="pages/saida_mercadoria/saida_mercadoria.php">Saídas</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/relatorios/relatorios.php">Relatórios</a></li>
                    </ul>
                </li>

                <li class="nav-item mb-2">
                    <a href="#submenuSaidas" class="nav-link toggle-submenu">Gereciamento<span
                            class="arrow float-end">▼</span></a>
                    <ul class="submenu list-unstyled" id="submenuSaidas">
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/gerenciamento/usuarios_content.php">Usuario</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/gerenciamento/permissoes_content.php">Permissões</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/gerenciamento/mapeamento_content.php">Mapeamento</a></li>
                        <li><a href="#" class="nav-link load-page"
                                data-url="../pages/gerenciamento/chaves_content.php">Chaves</a></li>
                    </ul>
                </li>

                <li class="nav-item mt-4"><a href="../logout.php" class="nav-link text-danger">Sair</a></li>

            </ul>

            <button id="toggleSidebar" class="sidebar-toggle">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

        </div>

        <div class="container-fluid p-5 main-content">

            <div class="row">
                <div id="conteudo">

                    <!-- DASHBOARD (HOME) -->
                    <div id="dashboardHome" class="mb-4"></div>

                    <!-- CONTEÚDO DAS ABAS -->
                    <div class="tab-content" id="dynamicTabContent"></div>

                </div>
            </div>

        </div>

    </div>

    <div class="modal fade" id="modalTrocarSenha" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">

                    <div class="modal-header">
                        <h5 class="modal-title">Trocar senha</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <!-- Campos -->
                        <div class="mb-3">
                            <input type="password" name="senha_atual" class="form-control" placeholder="Senha atual"
                                required>
                        </div>

                        <div class="mb-3">
                            <input type="password" name="nova_senha" class="form-control" placeholder="Nova senha"
                                required>
                        </div>

                        <div class="mb-3">
                            <input type="password" name="confirmar_senha" class="form-control"
                                placeholder="Confirmar nova senha" required>
                        </div>

                        <!-- Feedback -->
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger d-flex align-items-center mt-2 mb-0 fade show" role="alert">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                <div class="flex-grow-1">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success d-flex align-items-center mt-2 mb-0 fade show" role="alert">
                                <i class="fa-solid fa-check-circle me-2"></i>
                                <div class="flex-grow-1">
                                    <?= htmlspecialchars($success) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>


                    <div class="modal-footer">
                        <button type="submit" name="trocar_senha" class="btn btn-primary">
                            Salvar
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        document.addEventListener('DOMContentLoaded', aplicarPreferencias);
        const toggleDark = document.getElementById('toggleDarkMode');
        const fontSizeSelect = document.getElementById('fontSizeSelect');
        const telas = <?= json_encode($telas) ?>;
        const searchInput = document.getElementById('search-input');
        const suggestions = document.getElementById('suggestions');
        const saveThemeBtn = document.getElementById('saveThemeColor');
        const themeSavedMsg = document.getElementById('themeSavedMsg');
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const content = document.querySelector('.main-content');
        let tabCount = 0;

        function aplicarPreferencias() {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'enabled') document.body.classList.add('dark-mode'); else document.body.classList.remove('dark-mode');
            toggleDark.checked = (darkMode === 'enabled');

            const fontSize = localStorage.getItem('fontSize');
            document.body.classList.remove('font-small', 'font-large');
            if (fontSize === 'small') document.body.classList.add('font-small');
            else if (fontSize === 'large') document.body.classList.add('font-large');
            fontSizeSelect.value = fontSize || 'default';

        }

        toggleDark.addEventListener('change', function () {
            document.body.classList.toggle('dark-mode', this.checked);
            localStorage.setItem('darkMode', this.checked ? 'enabled' : 'disabled');
        });

        fontSizeSelect.addEventListener('change', function () {
            document.body.classList.remove('font-small', 'font-large');
            if (this.value === 'small') document.body.classList.add('font-small');
            else if (this.value === 'large') document.body.classList.add('font-large');
            localStorage.setItem('fontSize', this.value);
        });

        searchInput.addEventListener('input', () => {
            const val = searchInput.value.toLowerCase().trim();

            if (!val) { suggestions.style.display = 'none'; suggestions.innerHTML = ''; return; }
            const matches = telas.filter(t => t.nome.toLowerCase().includes(val));

            if (!matches.length) { suggestions.style.display = 'none'; suggestions.innerHTML = ''; return; }
            suggestions.innerHTML = matches.map(m => `
                <li 
                    data-url="${m.url}" 
                    data-titulo="${m.tituloAba ?? m.nome}">
                    ${m.nome}
                </li>
            `).join('');

            suggestions.style.display = 'block';
        });

        suggestions.addEventListener('click', e => {
            const item = e.target.closest('li');
            if (!item) return;

            const url = item.dataset.url;
            const tituloAba = e.target.dataset.titulo;

            if (!url) return;

            abrirAba(url, tituloAba);

            // limpa busca
            suggestions.style.display = 'none';
            suggestions.innerHTML = '';
            searchInput.value = '';
        });

        document.addEventListener('click', e => {
            if (!searchInput.contains(e.target) && !suggestions.contains(e.target)) {
                suggestions.style.display = 'none';
            }
        });

        function abrirAba(url, nomeTela) {

            $('#dashboardHome').hide().empty();

            const tabId = "tab" + tabCount;
            let existingTab = $(`#dynamicTabs a[data-url="${url}"]`);
            if (existingTab.length) {
                $('#dashboardHome').hide().empty();   // ESCONDE O DASHBOARD
                new bootstrap.Tab(existingTab[0]).show();
                return;
            }

            $('#dynamicTabs').append(`
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="${tabId}-tab" data-url="${url}" data-bs-toggle="tab" href="#${tabId}" role="tab">
                        ${nomeTela} <span class="close-tab" style="cursor:pointer;">&times;</span>
                    </a>
                </li>
            `);

            $('#dynamicTabContent').append(`
                <div class="tab-pane fade show active" id="${tabId}">Você não possui permissão para visualizar esta tela.</div>
            `);

            $('#dynamicTabs a').not(`#${tabId}-tab`).removeClass('active');

            $('#dynamicTabContent .tab-pane').not(`#${tabId}`).removeClass('show active');

            // CARREGA O CONTEÚDO e inicializa funções específicas
            $('#' + tabId).load(url, function () {
                // Parceiros
                if (url.includes('parceiros_content.php')) {
                    if (typeof initParceiros === 'function') {
                        initParceiros(tabId);
                    }
                }

                // Categorias
                if (url.includes('categorias_content.php')) {
                    if (typeof initCategorias === 'function') {
                        initCategorias(tabId);
                    }
                }

            });

            $(`#${tabId}-tab .close-tab`).click(function (e) {
                e.stopPropagation();
                const tabElement = $(this).closest('a');
                const contentId = tabElement.attr('href');
                tabElement.parent().remove();
                $(contentId).remove();
                const lastTab = $('#dynamicTabs a').last();

                if (lastTab.length) {
                    new bootstrap.Tab(lastTab[0]).show();
                } else {
                    carregarDashboard();
                }

            });

            new bootstrap.Tab(document.getElementById(`${tabId}-tab`)).show();
            tabCount++;
        }

        document.getElementById('dynamicTabs').addEventListener('shown.bs.tab', () => {
            $('#dashboardHome').hide().empty();
        });

        function carregarDashboard() {
            // remove seleção de abas
            $('#dynamicTabs a').removeClass('active');
            $('#dynamicTabContent .tab-pane').removeClass('show active');

            // carrega dashboard
            $('#dashboardHome').load('../pages/dashboard/dashboard.php').show();
        }

        document.getElementById('btnHomeDashboard').addEventListener('click', function (e) {
            e.preventDefault();
            carregarDashboard();
        });

        $(document).ready(function () {
            $('.toggle-submenu').click(function (e) {
                e.preventDefault();
                const submenu = $(this).next('.submenu');
                $('.submenu').not(submenu).slideUp();
                $('.arrow').not($(this).find('.arrow')).removeClass('down');
                submenu.slideToggle();
                $(this).find('.arrow').toggleClass('down');
            });

            $('.load-page').off('click').on('click', function (e) {
                e.preventDefault();
                abrirAba($(this).data('url'), $(this).text().trim());
            });
        });

        document.getElementById('inputFoto').addEventListener('change', function () {
            const arquivo = this.files[0];
            if (!arquivo) return;

            const formData = new FormData();
            formData.append('foto', arquivo);

            fetch('/lemyx/uploads/upload_foto.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // força reload da imagem (cache bust)
                        document.getElementById('fotoUsuario_1').src =
                            data.foto + '?t=' + new Date().getTime();
                        document.getElementById('fotoUsuario_2').src =
                            data.foto + '?t=' + new Date().getTime();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(() => alert('Erro na comunicação com o servidor'));
        });

        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(a => {
                a.classList.remove('show');
            });
        }, 6000);

        function applyThemeColor(color) {
            document.documentElement.style.setProperty('--sidebar-bg', color);
            document.documentElement.style.setProperty(
                '--sidebar-hover',
                adjustColor(color, 20)
            );
        }

        function adjustColor(hex, amount) {
            let col = hex.replace('#', '');
            let num = parseInt(col, 16);

            let r = Math.min(255, Math.max(0, (num >> 16) + amount));
            let g = Math.min(255, Math.max(0, ((num >> 8) & 0x00FF) + amount));
            let b = Math.min(255, Math.max(0, (num & 0x0000FF) + amount));

            return '#' + (r << 16 | g << 8 | b).toString(16).padStart(6, '0');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const colorInput = document.getElementById('themeColor');
            const saveBtn = document.getElementById('saveThemeColor');

            if (!colorInput || !saveBtn) return;

            let savedColor = localStorage.getItem('themeColor') || '#3f4750';
            let tempColor = null;

            /* ===== APLICA COR SALVA AO INICIAR ===== */
            applyThemeColor(savedColor);
            colorInput.value = savedColor;

            /* ===== PRÉ-VISUALIZAÇÃO ===== */
            colorInput.addEventListener('input', (e) => {
                tempColor = e.target.value;
                applyThemeColor(tempColor);
            });

            /* ===== SALVAR ===== */
            saveBtn.addEventListener('click', () => {
                if (!tempColor) return;

                savedColor = tempColor;
                localStorage.setItem('themeColor', savedColor);
                tempColor = null;
            });

            /* ===== FECHOU SEM SALVAR ===== */
            document.addEventListener('hidden.bs.modal', () => {
                if (tempColor !== null) {
                    applyThemeColor(savedColor);
                    tempColor = null;
                    colorInput.value = savedColor;
                }
            });

            /* ===== FUNÇÕES ===== */
            function applyThemeColor(color) {
                document.documentElement.style.setProperty('--sidebar-bg', color);
                document.documentElement.style.setProperty(
                    '--sidebar-hover',
                    adjustColor(color, 20)
                );
            }

            function adjustColor(hex, amount) {
                let col = hex.replace('#', '');
                let num = parseInt(col, 16);

                let r = Math.min(255, Math.max(0, (num >> 16) + amount));
                let g = Math.min(255, Math.max(0, ((num >> 8) & 0x00FF) + amount));
                let b = Math.min(255, Math.max(0, (num & 0x0000FF) + amount));

                return '#' + (r << 16 | g << 8 | b)
                    .toString(16)
                    .padStart(6, '0');
            }
        });

        saveThemeBtn.addEventListener('click', () => {
            // mostra mensagem
            themeSavedMsg.classList.remove('d-none');

            // esconde após 3 segundos
            setTimeout(() => {
                themeSavedMsg.classList.add('d-none');
            }, 3000);
        });

        $(document).ready(function () {
            carregarDashboard();
        });

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            content.classList.toggle('full');

            toggleBtn.classList.toggle(
                'rotated',
                sidebar.classList.contains('hidden')
            );
        });


        //------------- INSTALAR APP -----------------

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/lemyx/assets/sw.js')
                .then(function (reg) {
                    console.log('Service Worker registrado', reg);
                })
                .catch(function (err) {
                    console.log('Erro ao registrar Service Worker', err);
                });
        }

        let deferredPrompt = null;

        const btnInstalar = document.getElementById('btnInstalarApp');

        window.addEventListener('beforeinstallprompt', (e) => {

            console.log('Evento de instalação disponível');

            e.preventDefault();

            deferredPrompt = e;

        });

        if (btnInstalar) {

            btnInstalar.addEventListener('click', async () => {

                if (!deferredPrompt) {
                    console.log('Instalação ainda não disponível');
                    return;
                }

                deferredPrompt.prompt();

                const result = await deferredPrompt.userChoice;

                console.log('Resultado:', result.outcome);

                if (result.outcome === 'accepted') {

                    Swal.fire({
                        icon: 'success',
                        title: 'Instalação iniciada',
                        text: 'O navegador está instalando o Lemyx.'
                    });

                }

                deferredPrompt = null;

            });

        }

        window.addEventListener('appinstalled', () => {

            console.log('Aplicativo instalado');

            Swal.fire({
                icon: 'success',
                title: 'Lemyx instalado',
                text: 'O aplicativo foi instalado com sucesso.'
            });

        });

    </script>

    <?php if (!empty($error)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('modalTrocarSenha')).show();
            });
        </script>
    <?php endif; ?>

</body>

</html>