<?php
session_start();

header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada.']);
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

/* ================= PERMISSÕES ================= */

if ($action === 'add' && !pode($usuarioId, 'fornecedores', 'criar')) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sem permissão para criar.']);
    exit;
}

if ($action === 'edit' && !pode($usuarioId, 'fornecedores', 'editar')) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sem permissão para editar.']);
    exit;
}

if ($action === 'delete' && !pode($usuarioId, 'fornecedores', 'excluir')) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sem permissão para excluir.']);
    exit;
}

/* ================= CAMPOS ================= */

$campos = [
    'nome',
    'razao_social',
    'tipo_pessoa',
    'cpf_cnpj',
    'inscricao_estadual',
    'tipo_parceiro',
    'classificacao',
    'selecao',
    'cep',
    'endereco',
    'numero',
    'complemento',
    'bairro',
    'cidade',
    'estado',
    'pais',
    'contato_nome',
    'telefone',
    'email',
    'observacoes',
    'ativo'
];

$dados = [];

foreach ($campos as $campo) {
    if ($campo === 'ativo') {
        $dados[$campo] = isset($_POST[$campo]) ? 1 : 0;
    } else {
        $dados[$campo] = $_POST[$campo] ?? null;
    }
}

/* ================= ADD ================= */

if ($action === 'add') {
    function apenasNumeros($v)
    {
        return preg_replace('/\D/', '', $v);
    }

    function validarCPF($cpf)
    {
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf))
            return false;
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d)
                return false;
        }
        return true;
    }

    function validarCNPJ($cnpj)
    {
        if (strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj))
            return false;
        $t = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0, $s = 0; $i < 12; $i++)
            $s += $cnpj[$i] * $t[$i];
        $d1 = $s % 11 < 2 ? 0 : 11 - ($s % 11);

        $t = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0, $s = 0; $i < 13; $i++)
            $s += $cnpj[$i] * $t[$i];
        $d2 = $s % 11 < 2 ? 0 : 11 - ($s % 11);

        return $cnpj[12] == $d1 && $cnpj[13] == $d2;
    }
    
    $cpfCnpj = apenasNumeros($_POST['cpf_cnpj'] ?? '');

    if (
        (strlen($cpfCnpj) === 11 && !validarCPF($cpfCnpj)) ||
        (strlen($cpfCnpj) === 14 && !validarCNPJ($cpfCnpj))
    ) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'CPF/CNPJ inválido.']);
        exit;
    }

    $sql = "
        INSERT INTO parceiros (
            " . implode(', ', array_keys($dados)) . "
        ) VALUES (
            :" . implode(', :', array_keys($dados)) . "
        )
    ";

    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($dados)) {
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Parceiro cadastrado com sucesso.'
        ]);
    } else {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao cadastrar parceiro.'
        ]);
    }

    exit;
}

/* ================= EDIT ================= */

if ($action === 'edit') {

    $parceiroId = $_POST['parceiro_id'] ?? null;

    if (!$parceiroId) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID não informado.']);
        exit;
    }

    $sets = [];
    foreach ($dados as $campo => $valor) {
        $sets[] = "$campo = :$campo";
    }

    function apenasNumeros($v)
    {
        return preg_replace('/\D/', '', $v);
    }

    function validarCPF($cpf)
    {
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf))
            return false;
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d)
                return false;
        }
        return true;
    }

    function validarCNPJ($cnpj)
    {
        if (strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj))
            return false;
        $t = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0, $s = 0; $i < 12; $i++)
            $s += $cnpj[$i] * $t[$i];
        $d1 = $s % 11 < 2 ? 0 : 11 - ($s % 11);

        $t = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0, $s = 0; $i < 13; $i++)
            $s += $cnpj[$i] * $t[$i];
        $d2 = $s % 11 < 2 ? 0 : 11 - ($s % 11);

        return $cnpj[12] == $d1 && $cnpj[13] == $d2;
    }

    $cpfCnpj = apenasNumeros($_POST['cpf_cnpj'] ?? '');

    if (
        (strlen($cpfCnpj) === 11 && !validarCPF($cpfCnpj)) ||
        (strlen($cpfCnpj) === 14 && !validarCNPJ($cpfCnpj))
    ) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'CPF/CNPJ inválido.']);
        exit;
    }

    $sql = "
        UPDATE parceiros SET
            " . implode(', ', $sets) . "
        WHERE parceiro_id = :parceiro_id
    ";

    $stmt = $pdo->prepare($sql);
    $dados['parceiro_id'] = $parceiroId;

    if ($stmt->execute($dados)) {
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Parceiro atualizado com sucesso.'
        ]);
    } else {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao atualizar parceiro.'
        ]);
    }

    exit;
}

/* ================= DELETE ================= */

if ($action === 'delete') {

    $parceiroId = $_POST['parceiro_id'] ?? null;

    if (!$parceiroId) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID não informado.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM parceiros WHERE parceiro_id = :id");

    if ($stmt->execute([':id' => $parceiroId])) {
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Parceiro excluído com sucesso.'
        ]);
    } else {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao excluir parceiro.'
        ]);
    }

    exit;
}

/* ================= FALLBACK ================= */

echo json_encode([
    'sucesso' => false,
    'mensagem' => 'Ação inválida.'
]);