<?php

require_once "conexao.php";

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'salvarProduto':
        salvarProduto($pdo);
        break;

    default:
        echo json_encode([
            "status" => false,
            "msg" => "Ação inválida"
        ]);
}

/* =====================================================
SALVAR PRODUTO
===================================================== */

function salvarProduto($pdo)
{

    $produto_id = $_POST['produto_id'] ?? null;

    $dados = [
        "descricao" => $_POST['descricao'] ?? null,
        "sku" => $_POST['sku'] ?? null,
        "unidade" => $_POST['unidade'] ?? null,
        "codigo_barras" => $_POST['codigo_barras'] ?? null,
        "marca" => $_POST['marca'] ?? null,
        "modelo" => $_POST['modelo'] ?? null,

        "preco_custo" => $_POST['preco_custo'] ?? 0,
        "preco_venda" => $_POST['preco_venda'] ?? 0,

        "estoque_minimo" => $_POST['estoque_minimo'] ?? 0,
        "estoque_maximo" => $_POST['estoque_maximo'] ?? 0,
        "localizacao" => $_POST['localizacao'] ?? null,

        "observacoes" => $_POST['observacoes'] ?? null,
        "ativo" => isset($_POST['ativo']) ? 1 : 0
    ];

    try {

        $pdo->beginTransaction();

        /* =========================
        INSERT
        ========================= */

        if (!$produto_id) {

            $sql = "INSERT INTO produto
            (
                descricao,
                sku,
                unidade,
                codigo_barras,
                marca,
                modelo,
                preco_custo,
                preco_venda,
                estoque_minimo,
                estoque_maximo,
                localizacao,
                observacoes,
                ativo
            )
            VALUES
            (
                :descricao,
                :sku,
                :unidade,
                :codigo_barras,
                :marca,
                :modelo,
                :preco_custo,
                :preco_venda,
                :estoque_minimo,
                :estoque_maximo,
                :localizacao,
                :observacoes,
                :ativo
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($dados);

            $produto_id = $pdo->lastInsertId();

        } else {

            /* =========================
            UPDATE
            ========================= */

            $dados["produto_id"] = $produto_id;

            $sql = "UPDATE produto SET

                descricao = :descricao,
                sku = :sku,
                unidade = :unidade,
                codigo_barras = :codigo_barras,
                marca = :marca,
                modelo = :modelo,

                preco_custo = :preco_custo,
                preco_venda = :preco_venda,

                estoque_minimo = :estoque_minimo,
                estoque_maximo = :estoque_maximo,
                localizacao = :localizacao,

                observacoes = :observacoes,
                ativo = :ativo

            WHERE produto_id = :produto_id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($dados);
        }

        /* =====================================================
        CALIBRES
        ===================================================== */

        $pdo->prepare("
            DELETE FROM calibre_produto
            WHERE produto_id = ?
        ")->execute([$produto_id]);

        if (!empty($_POST['calibres'])) {

            $stmt = $pdo->prepare("
                INSERT INTO calibre_produto
                (produto_id, calibre_id)
                VALUES (?, ?)
            ");

            foreach ($_POST['calibres'] as $calibre_id) {

                if (!$calibre_id)
                    continue;

                $stmt->execute([
                    $produto_id,
                    $calibre_id
                ]);
            }
        }

        /* =====================================================
        VOLUMES
        ===================================================== */

        $pdo->prepare("
            DELETE FROM volume_produto
            WHERE produto_id = ?
        ")->execute([$produto_id]);

        if (!empty($_POST['volumes'])) {

            $stmt = $pdo->prepare("
                INSERT INTO volume_produto
                (produto_id, volume_id)
                VALUES (?, ?)
            ");

            foreach ($_POST['volumes'] as $volume_id) {

                if (!$volume_id)
                    continue;

                $stmt->execute([
                    $produto_id,
                    $volume_id
                ]);
            }
        }

        $pdo->commit();

        echo json_encode([
            "status" => true,
            "msg" => "Produto salvo com sucesso",
            "produto_id" => $produto_id
        ]);

    } catch (Exception $e) {

        $pdo->rollBack();

        echo json_encode([
            "status" => false,
            "msg" => $e->getMessage()
        ]);
    }
}