-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 04/03/2026 às 00:43
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `controller`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `calibre`
--

CREATE TABLE `calibre` (
  `calibre_id` int(11) NOT NULL,
  `calibre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `calibre`
--

INSERT INTO `calibre` (`calibre_id`, `calibre`) VALUES
(5, 'CT1CL120'),
(7, 'CT1CL125'),
(1, 'CT1CL135'),
(9, 'CT1CL140'),
(3, 'CT1CL150'),
(4, 'CT1CL185'),
(2, 'VITORIA');

-- --------------------------------------------------------

--
-- Estrutura para tabela `calibre_produto`
--

CREATE TABLE `calibre_produto` (
  `calibre_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `calibre_produto`
--

INSERT INTO `calibre_produto` (`calibre_id`, `produto_id`) VALUES
(1, 7),
(1, 8),
(2, 6),
(3, 7),
(4, 7),
(9, 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `categoria_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`categoria_id`, `nome`) VALUES
(2, 'EXOTICOS'),
(5, 'FLORES'),
(1, 'FLV'),
(4, 'HORTALIÇAS'),
(3, 'LEGUMES'),
(7, 'PROCESSADOS');

-- --------------------------------------------------------

--
-- Estrutura para tabela `chave`
--

CREATE TABLE `chave` (
  `chave_id` int(11) NOT NULL,
  `chave` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `chave`
--

INSERT INTO `chave` (`chave_id`, `chave`) VALUES
(8, 'criar'),
(9, 'editar'),
(10, 'excluir'),
(11, 'adicionar'),
(12, 'concluir'),
(13, '*'),
(14, 'visualizar');

-- --------------------------------------------------------

--
-- Estrutura para tabela `conferencia`
--

CREATE TABLE `conferencia` (
  `conferencia_id` int(11) NOT NULL,
  `entrada_produtos_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `peso_liquido` float DEFAULT NULL,
  `peso_medio` float DEFAULT NULL,
  `pallet` int(11) DEFAULT NULL,
  `quantidade_cx` int(11) DEFAULT NULL,
  `peso_bruto` float DEFAULT NULL,
  `peso_caixa` float DEFAULT NULL,
  `peso_operacional` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `conferencia`
--

INSERT INTO `conferencia` (`conferencia_id`, `entrada_produtos_id`, `usuario_id`, `peso_liquido`, `peso_medio`, `pallet`, `quantidade_cx`, `peso_bruto`, `peso_caixa`, `peso_operacional`) VALUES
(2, 5, 6, 100, 10, 1, 10, 140, 2, 20),
(4, 15, 1, 420, 8.4, 2, 50, 500, 1.2, 20),
(5, 15, 1, 332, 8.3, 3, 40, 400, 1.2, 20),
(13, 15, 1, 420, 8.4, 4, 50, 500, 1.2, 20),
(14, 14, 1, 124.8, 8.91429, 1, 14, 180, 1.8, 30),
(15, 14, 1, 216, 8.64, 2, 25, 280, 1.8, 19),
(16, 15, 1, 416, 8.32, 5, 50, 500, 1.2, 24),
(17, 14, 1, 330, 8.25, 3, 40, 424, 1.8, 22),
(18, 14, 1, 307.8, 9.05294, 4, 34, 390, 1.8, 21),
(19, 16, 1, 655, 13.1, 1, 50, 740, 1.2, 25),
(20, 18, 1, 108.8, 3.50968, 1, 31, 242, 1.2, 96),
(21, 19, 1, 804, 16.08, 1, 50, 1000, 2, 96),
(23, 23, 1, 404, 8.08, 1, 50, 600, 2, 96),
(24, 23, 1, 814, 13.5667, 2, 60, 1000, 1.5, 96),
(25, 25, 1, 608, 12.16, 1, 50, 800, 1.8, 102),
(26, 25, 1, 614, 10.2333, 2, 60, 800, 1.5, 96),
(29, 29, 1, 343, 4.2875, 1, 80, 500, 1.4, 45),
(30, 28, 1, 650, 13, 1, 50, 800, 1.5, 75),
(31, 30, 1, 715.8, 9.67297, 1, 74, 924, 1.8, 75),
(32, 31, 1, 504.4, 8.13548, 1, 62, 635, 1.3, 50),
(33, 33, 1, 562, 9.36667, 1, 60, 800, 1.9, 124),
(34, 34, 1, 776, 15.52, 1, 50, 1000, 1.8, 134),
(35, 36, 6, 326, 8.15, 1, 40, 470, 1.2, 96),
(36, 35, 6, 265, 10.6, 1, 25, 324, 1.4, 24),
(37, 37, 6, 1132, 14.15, 1, 80, 1400, 1.8, 124);

-- --------------------------------------------------------

--
-- Estrutura para tabela `devolução`
--

CREATE TABLE `devolução` (
  `devolucao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `entrada_produtos_id` int(11) NOT NULL,
  `quantidade_caixa` int(11) NOT NULL,
  `autorização` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `entradas`
--

CREATE TABLE `entradas` (
  `entrada_id` int(11) NOT NULL,
  `data_entrada` date NOT NULL,
  `chegada` datetime DEFAULT NULL,
  `parceiro_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'PENDENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `entradas`
--

INSERT INTO `entradas` (`entrada_id`, `data_entrada`, `chegada`, `parceiro_id`, `status`) VALUES
(1, '2026-02-19', NULL, 2, 'CONCLUIDO'),
(2, '2026-02-19', '2026-02-19 22:11:11', 3, 'CONCLUIDO'),
(4, '2026-02-12', '2026-02-19 21:01:30', 4, 'CONCLUIDO'),
(5, '2026-02-12', NULL, 2, 'CONCLUIDO'),
(7, '2026-02-19', NULL, 5, 'CONCLUIDO'),
(8, '2026-02-20', NULL, 6, 'CONCLUIDO'),
(10, '2026-02-21', NULL, 3, 'CONCLUIDO'),
(11, '2026-02-21', NULL, 6, 'CONCLUIDO'),
(15, '2026-02-24', NULL, 4, 'CONCLUIDO'),
(16, '2026-02-25', NULL, 3, 'PENDENTE'),
(17, '2026-03-02', NULL, 8, 'PENDENTE'),
(18, '2026-03-02', NULL, 5, 'PENDENTE');

-- --------------------------------------------------------

--
-- Estrutura para tabela `entrada_produtos`
--

CREATE TABLE `entrada_produtos` (
  `entrada_produtos_id` int(11) NOT NULL,
  `entradas_id` int(11) NOT NULL,
  `produtos_id` int(11) NOT NULL,
  `volume_id` int(11) DEFAULT NULL,
  `calibre_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `entrada_produtos`
--

INSERT INTO `entrada_produtos` (`entrada_produtos_id`, `entradas_id`, `produtos_id`, `volume_id`, `calibre_id`, `status`) VALUES
(5, 1, 2, NULL, NULL, 'CONCLUIDO'),
(9, 2, 2, NULL, NULL, 'PENDENTE'),
(10, 1, 1, NULL, NULL, 'CONCLUIDO'),
(11, 2, 1, NULL, NULL, 'PENDENTE'),
(14, 4, 5, NULL, NULL, 'PENDENTE'),
(15, 4, 4, NULL, NULL, 'PENDENTE'),
(16, 5, 1, NULL, NULL, 'PENDENTE'),
(17, 5, 7, NULL, 3, 'PENDENTE'),
(18, 5, 10, NULL, NULL, 'RECEBIDO'),
(19, 2, 13, NULL, NULL, 'CONCLUIDO'),
(23, 8, 18, NULL, NULL, 'CONCLUIDO'),
(25, 8, 7, NULL, 1, 'CONCLUIDO'),
(26, 8, 2, NULL, NULL, 'CONCLUIDO'),
(27, 8, 8, NULL, NULL, 'CONCLUIDO'),
(28, 8, 13, NULL, NULL, 'CONCLUIDO'),
(29, 8, 11, NULL, NULL, 'CONCLUIDO'),
(30, 8, 17, NULL, NULL, 'CONCLUIDO'),
(31, 8, 19, NULL, NULL, 'CONCLUIDO'),
(33, 10, 16, NULL, NULL, 'CONCLUIDO'),
(34, 11, 5, NULL, NULL, 'CONCLUIDO'),
(35, 15, 8, NULL, NULL, 'CONCLUIDO'),
(36, 15, 11, NULL, NULL, 'CONCLUIDO'),
(37, 15, 7, NULL, 1, 'CONCLUIDO'),
(38, 16, 2, NULL, 1, 'PENDENTE');

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque`
--

CREATE TABLE `estoque` (
  `produto_id` int(11) NOT NULL,
  `calibre_id` int(11) NOT NULL,
  `quantidade_cx` int(11) NOT NULL,
  `quantidade_kg` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `parceiros`
--

CREATE TABLE `parceiros` (
  `parceiro_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `tipo_pessoa` enum('F','J') NOT NULL DEFAULT 'J',
  `inscricao_estadual` varchar(30) DEFAULT NULL,
  `cpf_cnpj` varchar(20) NOT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `pais` varchar(2) NOT NULL,
  `contato_nome` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `tipo_parceiro` enum('fornecedor','cliente','motorista','transportadora','colaborador','produtor') NOT NULL DEFAULT 'fornecedor',
  `classificacao` enum('produtor_rural','empresa') NOT NULL DEFAULT 'empresa',
  `selecao` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `parceiros`
--

INSERT INTO `parceiros` (`parceiro_id`, `nome`, `razao_social`, `tipo_pessoa`, `inscricao_estadual`, `cpf_cnpj`, `cep`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `pais`, `contato_nome`, `telefone`, `email`, `tipo_parceiro`, `classificacao`, `selecao`, `ativo`, `observacoes`, `created_at`, `updated_at`) VALUES
(2, 'GERALDO GERIN', NULL, 'J', '123456', '123456', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, 'fornecedor', 'empresa', '', 1, NULL, '2026-02-27 23:41:23', '2026-02-28 00:57:49'),
(3, 'ANTONIO GERALDO GOBBI', 'ANTONIO GERALDO GOBBI', 'F', '114195404', '900.767.887-68', '29600970', 'Rua Marechal Deodoro', '10', '', 'Centro', 'Afonso Cláudio', 'ES', 'BR', 'ANTONIO', '28996547325', 'anotonio@gmail.com', 'fornecedor', 'produtor_rural', '', 1, '', '2026-02-27 23:41:23', '2026-03-02 12:11:49'),
(4, 'BRUNO CONTI', NULL, 'J', '123456', '123456', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, 'fornecedor', 'empresa', '', 1, NULL, '2026-02-27 23:41:23', '2026-02-28 00:57:49'),
(5, 'DEOLINO JASTROW', NULL, 'J', '123456', '123456', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, 'fornecedor', 'empresa', '', 1, NULL, '2026-02-27 23:41:23', '2026-02-28 00:57:49'),
(6, 'BEMFRUTI', NULL, 'J', '123456', '123456', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, 'fornecedor', 'empresa', '', 1, NULL, '0000-00-00 00:00:00', '2026-02-28 00:59:56'),
(7, 'FERNANDO ZANOTTI', NULL, 'J', '123456', '123456', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, 'fornecedor', 'empresa', '', 1, NULL, '2026-02-27 23:41:23', '2026-02-28 00:59:08'),
(8, 'BATISTA PEDRO', NULL, 'J', '123456', '123456', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, 'fornecedor', 'empresa', '', 1, NULL, '2026-02-27 23:41:23', '2026-02-28 00:59:08'),
(10, 'AILTON OTT', 'AILTON OTT', 'F', '082231788', '28129260000857', '29100010', 'Avenida Champagnat', '946', 'TESTE', 'Centro de Vila Velha', 'Vila Velha', 'ES', 'BR', 'AILTON', '(27) 99867-4214', 'driftnfe@carone.com.br', 'fornecedor', 'produtor_rural', '', 1, 'TESTE', '2026-03-02 00:48:50', NULL),
(11, 'EDNALDO DEGASPERI', 'EDNALDO DEGASPERI', 'F', '114184658', '034.540.057-79', '29900970', 'Praça Nestor Gomes', '42', 'EM FRETE A PRAÇA', 'Centro', 'Linhares', 'ES', 'BR', 'ADNALDO', '(27) 9914-5234', 'ednaldo@gmail.com', 'fornecedor', 'produtor_rural', 'Terceiros', 1, '', '2026-03-02 01:00:32', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `perdas`
--

CREATE TABLE `perdas` (
  `perda_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `volume_id` int(11) NOT NULL,
  `quantidade_caixa` int(11) NOT NULL,
  `peso` float NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `destino` varchar(255) NOT NULL,
  `obs` varchar(1000) DEFAULT NULL,
  `autorização` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `permissoes`
--

CREATE TABLE `permissoes` (
  `permissao_id` int(11) NOT NULL,
  `tela_id` int(11) NOT NULL,
  `chave_id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `permissoes`
--

INSERT INTO `permissoes` (`permissao_id`, `tela_id`, `chave_id`, `descricao`, `ativo`) VALUES
(1, 1, 8, 'Criar Entradas', 1),
(2, 1, 10, 'Excluir Entradas', 1),
(3, 1, 9, 'Editar Entradas', 1),
(5, 1, 14, 'Visualizar Entradas', 1),
(6, 1, 12, 'Concluir Entradas', 1),
(7, 7, 14, 'Visualizar Volumes', 1),
(8, 5, 8, 'Criar Produtos', 1),
(9, 5, 9, 'Editar Produtos', 1),
(10, 5, 10, 'Excluir Produtos', 1),
(11, 5, 14, 'Visualizar Produtos', 1),
(12, 6, 8, 'Criar Calibre', 1),
(13, 6, 9, 'Editar Calibre', 1),
(14, 6, 10, 'Excluir Calibre', 1),
(15, 4, 8, 'Criar Fornecedores', 1),
(16, 4, 9, 'Editar Fornecedores', 1),
(17, 4, 10, 'Excluir Fornecedores', 1),
(18, 4, 14, 'Visualizar Fornecedores', 1),
(19, 4, 13, 'Controle total de Fornecedores', 1),
(20, 8, 14, 'Visualizar Categorias', 1),
(21, 8, 8, 'Criar Categorias', 1),
(22, 8, 9, 'Editar Categorias', 1),
(23, 8, 10, 'Excluir Categorias', 1),
(24, 8, 13, '* Categorias', 1),
(25, 6, 14, 'Visualizar Calibre', 1),
(26, 9, 14, 'Visualizar Calibre_Produto', 1),
(27, 9, 10, 'Excluir Calibre_Produto', 1),
(28, 9, 8, 'Criar Calibre_Produto', 1),
(30, 7, 8, 'Criar Volumes', 1),
(31, 7, 13, '* Volumes', 1),
(32, 7, 10, 'Excluir Volumes', 1),
(33, 7, 9, 'Editar Volumes', 1),
(34, 2, 11, 'Adicionar Entrada_Produtos', 1),
(35, 2, 12, 'Concluir Entrada_Produtos', 1),
(37, 2, 9, 'Editar Entrada_Produtos', 1),
(38, 2, 10, 'Excluir Entrada_Produtos', 1),
(39, 2, 14, 'Visualizar Entrada_Produtos', 1),
(40, 11, 11, 'Adicionar Conferencia', 1),
(41, 11, 9, 'Editar Conferencia', 1),
(42, 11, 10, 'Excluir Conferencia', 1),
(43, 11, 14, 'Visualizar Conferencia', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `preferencias_usuario`
--

CREATE TABLE `preferencias_usuario` (
  `usuario_id` int(11) NOT NULL,
  `color_theme` varchar(50) DEFAULT 'default',
  `dark_mode` enum('enabled','disabled') DEFAULT 'disabled',
  `font_size` enum('small','default','large') DEFAULT 'default',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `preferencias_usuario`
--

INSERT INTO `preferencias_usuario` (`usuario_id`, `color_theme`, `dark_mode`, `font_size`, `criado_em`) VALUES
(1, 'default', 'disabled', 'default', '2026-01-19 02:34:46'),
(6, 'default', 'disabled', 'default', '2026-01-29 01:44:06');

-- --------------------------------------------------------

--
-- Estrutura para tabela `producao`
--

CREATE TABLE `producao` (
  `producao_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `producao`
--

INSERT INTO `producao` (`producao_id`) VALUES
(1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `producao_mp`
--

CREATE TABLE `producao_mp` (
  `producao_mp_id` int(11) NOT NULL,
  `producao_pa_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `volume_id` int(11) NOT NULL,
  `quantidade_caixa` int(11) NOT NULL,
  `peso_liquido` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `producao_mp`
--

INSERT INTO `producao_mp` (`producao_mp_id`, `producao_pa_id`, `produto_id`, `volume_id`, `quantidade_caixa`, `peso_liquido`) VALUES
(11, 8, 2, 5, 15, 0),
(12, 9, 5, 5, 15, 0),
(14, 11, 2, 5, 7, 0),
(17, 14, 15, 5, 25, 0),
(27, 22, 2, 5, 30, 0),
(29, 12, 2, 5, 8, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `producao_pa`
--

CREATE TABLE `producao_pa` (
  `producao_pa_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade_caixa` int(11) NOT NULL,
  `quantidade` float NOT NULL,
  `peso_medio` float NOT NULL,
  `media_final` float NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `producao_pa`
--

INSERT INTO `producao_pa` (`producao_pa_id`, `produto_id`, `quantidade_caixa`, `quantidade`, `peso_medio`, `media_final`, `status`) VALUES
(8, 2, 20, 200, 15, 0, 'PENDENTE'),
(9, 5, 10, 100, 30, 0, 'PENDENTE'),
(11, 2, 10, 100, 10, 14, 'PENDENTE'),
(12, 2, 10, 100, 10, 16, 'PENDENTE'),
(14, 14, 20, 400, 20, 25, 'FINALIZADA'),
(22, 2, 20, 400, 20, 30, 'FINALIZADA');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `produto_id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `unidade` varchar(50) NOT NULL,
  `comprador` varchar(255) DEFAULT NULL,
  `conferente` varchar(255) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`produto_id`, `descricao`, `unidade`, `comprador`, `conferente`, `categoria_id`, `ativo`) VALUES
(1, 'TOMATE', 'KG', NULL, NULL, 1, 1),
(2, 'ABACATE 4A', 'KG', NULL, NULL, 1, 1),
(4, 'PITAYA', 'KG', NULL, NULL, 2, 1),
(5, 'AIPIM', 'KG', NULL, NULL, 1, 1),
(6, 'UVA VERMELHA', 'UN', NULL, NULL, 1, 1),
(7, 'MAÇA GALA', 'KG', NULL, NULL, 1, 1),
(8, 'ACEROLA', 'UN', NULL, NULL, 2, 1),
(9, 'PEPINO', 'KG', NULL, NULL, 3, 1),
(10, 'ALFACE', 'UN', NULL, NULL, 4, 1),
(11, 'CALANDIVA', 'UN', NULL, NULL, 5, 1),
(12, 'CEBOLA PACOTE 1KG', 'UN', NULL, NULL, 1, 1),
(13, 'CEBOLA 2A', 'KG', NULL, NULL, 1, 1),
(14, 'MELANCIA CX', 'KG', NULL, NULL, 1, 1),
(15, 'MELANCIA BIM', 'KG', NULL, NULL, 1, 1),
(16, 'CEBOLA 3A', 'KG', NULL, NULL, 1, 1),
(17, 'MAMÃO HAVAI', '', NULL, NULL, 1, 1),
(18, 'BATATA INGLESA 4A', '', NULL, NULL, 1, 1),
(19, 'MARACUJA AZEDO 3A', '', NULL, NULL, 1, 1),
(20, 'GOIABA VERMELHA 4A', '', NULL, NULL, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `saidas`
--

CREATE TABLE `saidas` (
  `saidas_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `tipo_saida_id` int(11) NOT NULL,
  `data_saida` date NOT NULL,
  `quantidade` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `telas`
--

CREATE TABLE `telas` (
  `tela_id` int(11) NOT NULL,
  `chave` varchar(100) NOT NULL COMMENT 'Identificador técnico da tela (ex: entradas)',
  `nome` varchar(255) NOT NULL COMMENT 'Nome exibido da tela',
  `ordem` int(11) DEFAULT 0 COMMENT 'Ordem de exibição no menu',
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `telas`
--

INSERT INTO `telas` (`tela_id`, `chave`, `nome`, `ordem`, `ativo`) VALUES
(1, 'entradas', 'Entradas', 1, 1),
(2, 'entrada_produtos', 'Produtos da Entrada', 2, 1),
(3, 'usuarios', 'Usuários', 99, 1),
(4, 'fornecedores', 'Fornecedores', 4, 1),
(5, 'produtos', 'Produtos', 5, 1),
(6, 'calibre', 'Calibre', 0, 1),
(7, 'volumes', 'Volumes', 0, 1),
(8, 'categorias', 'Categorias', 0, 1),
(9, 'calibre_produto', 'Calibres/Produtos', 0, 1),
(10, 'producao', 'Produção', 0, 1),
(11, 'conferencia', 'Conferencia', 0, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_saida`
--

CREATE TABLE `tipos_saida` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `usuario` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('admin','usuario','outro') NOT NULL,
  `email` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `fundo` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`usuario_id`, `nome`, `usuario`, `senha`, `perfil`, `email`, `foto`, `fundo`, `ativo`) VALUES
(1, 'Admin', 'admin', '$2y$10$ePyTf6Fbgt.RXsg4iDckMudP/0UruEuLR/gZGjHpm15UCad0sOQFa', 'admin', 'admin@hotmail.com', 'usuario_1.jpg', '', 1),
(6, 'Marcos Suel', 'Marcos', '$2y$10$WjIf37ZFxRVnzxH0QGJFd.XWta3RAzp4bTkbz9ybx7kQMzUQ.9o7y', 'usuario', 'marcossuel@hotmail.com', 'usuario_6.jpg', '', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_permissoes`
--

CREATE TABLE `usuario_permissoes` (
  `usuario_permissoes_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `permissao_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario_permissoes`
--

INSERT INTO `usuario_permissoes` (`usuario_permissoes_id`, `usuario_id`, `permissao_id`) VALUES
(154, 6, 1),
(155, 6, 3),
(156, 6, 5),
(153, 6, 6),
(160, 6, 7),
(158, 6, 9),
(159, 6, 11),
(157, 6, 18),
(151, 6, 20),
(149, 6, 25),
(150, 6, 26),
(152, 6, 43);

-- --------------------------------------------------------

--
-- Estrutura para tabela `volumes`
--

CREATE TABLE `volumes` (
  `volume_id` int(11) NOT NULL,
  `volume` varchar(10) DEFAULT NULL,
  `tipo` varchar(3) DEFAULT NULL,
  `quantidade` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `volumes`
--

INSERT INTO `volumes` (`volume_id`, `volume`, `tipo`, `quantidade`) VALUES
(4, 'CX 10', 'CX', 10),
(5, 'CX 20', 'CX', 20),
(7, 'SC 30', 'SC', 30),
(10, 'CX 12.5', 'CX', 12.5),
(12, 'CX 15', 'CX', 15),
(13, 'CX 8', 'CX', 8);

-- --------------------------------------------------------

--
-- Estrutura para tabela `volume_produto`
--

CREATE TABLE `volume_produto` (
  `volume_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `calibre`
--
ALTER TABLE `calibre`
  ADD PRIMARY KEY (`calibre_id`),
  ADD UNIQUE KEY `calibre` (`calibre`),
  ADD UNIQUE KEY `calibre_2` (`calibre`);

--
-- Índices de tabela `calibre_produto`
--
ALTER TABLE `calibre_produto`
  ADD PRIMARY KEY (`calibre_id`,`produto_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`categoria_id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `chave`
--
ALTER TABLE `chave`
  ADD PRIMARY KEY (`chave_id`);

--
-- Índices de tabela `conferencia`
--
ALTER TABLE `conferencia`
  ADD PRIMARY KEY (`conferencia_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `conferencia_ibfk_1` (`entrada_produtos_id`);

--
-- Índices de tabela `devolução`
--
ALTER TABLE `devolução`
  ADD PRIMARY KEY (`devolucao_id`);

--
-- Índices de tabela `entradas`
--
ALTER TABLE `entradas`
  ADD PRIMARY KEY (`entrada_id`),
  ADD KEY `parceiro_id` (`parceiro_id`) USING BTREE;

--
-- Índices de tabela `entrada_produtos`
--
ALTER TABLE `entrada_produtos`
  ADD PRIMARY KEY (`entrada_produtos_id`),
  ADD KEY `entradas_id` (`entradas_id`),
  ADD KEY `produtos_id` (`produtos_id`),
  ADD KEY `calibre_id` (`calibre_id`),
  ADD KEY `idx_entrada_produtos_volume` (`volume_id`);

--
-- Índices de tabela `estoque`
--
ALTER TABLE `estoque`
  ADD PRIMARY KEY (`produto_id`,`calibre_id`),
  ADD KEY `calibre_id` (`calibre_id`);

--
-- Índices de tabela `parceiros`
--
ALTER TABLE `parceiros`
  ADD PRIMARY KEY (`parceiro_id`);

--
-- Índices de tabela `perdas`
--
ALTER TABLE `perdas`
  ADD PRIMARY KEY (`perda_id`);

--
-- Índices de tabela `permissoes`
--
ALTER TABLE `permissoes`
  ADD PRIMARY KEY (`permissao_id`),
  ADD KEY `idx_tela_id` (`tela_id`),
  ADD KEY `idx_chave_id` (`chave_id`);

--
-- Índices de tabela `preferencias_usuario`
--
ALTER TABLE `preferencias_usuario`
  ADD PRIMARY KEY (`usuario_id`);

--
-- Índices de tabela `producao`
--
ALTER TABLE `producao`
  ADD PRIMARY KEY (`producao_id`);

--
-- Índices de tabela `producao_mp`
--
ALTER TABLE `producao_mp`
  ADD PRIMARY KEY (`producao_mp_id`),
  ADD KEY `fk_producao_mp_pa` (`producao_pa_id`);

--
-- Índices de tabela `producao_pa`
--
ALTER TABLE `producao_pa`
  ADD PRIMARY KEY (`producao_pa_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`produto_id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Índices de tabela `saidas`
--
ALTER TABLE `saidas`
  ADD PRIMARY KEY (`saidas_id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `tipo_saida_id` (`tipo_saida_id`);

--
-- Índices de tabela `telas`
--
ALTER TABLE `telas`
  ADD PRIMARY KEY (`tela_id`),
  ADD UNIQUE KEY `uk_telas_chave` (`chave`);

--
-- Índices de tabela `tipos_saida`
--
ALTER TABLE `tipos_saida`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`usuario_id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Índices de tabela `usuario_permissoes`
--
ALTER TABLE `usuario_permissoes`
  ADD PRIMARY KEY (`usuario_permissoes_id`),
  ADD UNIQUE KEY `uk_usuario_permissao` (`usuario_id`,`permissao_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_permissao` (`permissao_id`);

--
-- Índices de tabela `volumes`
--
ALTER TABLE `volumes`
  ADD PRIMARY KEY (`volume_id`),
  ADD UNIQUE KEY `volume` (`volume`);

--
-- Índices de tabela `volume_produto`
--
ALTER TABLE `volume_produto`
  ADD PRIMARY KEY (`volume_id`,`produto_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `calibre`
--
ALTER TABLE `calibre`
  MODIFY `calibre_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `categoria_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `chave`
--
ALTER TABLE `chave`
  MODIFY `chave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `conferencia`
--
ALTER TABLE `conferencia`
  MODIFY `conferencia_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de tabela `devolução`
--
ALTER TABLE `devolução`
  MODIFY `devolucao_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `entradas`
--
ALTER TABLE `entradas`
  MODIFY `entrada_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `entrada_produtos`
--
ALTER TABLE `entrada_produtos`
  MODIFY `entrada_produtos_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de tabela `parceiros`
--
ALTER TABLE `parceiros`
  MODIFY `parceiro_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `perdas`
--
ALTER TABLE `perdas`
  MODIFY `perda_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `permissoes`
--
ALTER TABLE `permissoes`
  MODIFY `permissao_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de tabela `producao`
--
ALTER TABLE `producao`
  MODIFY `producao_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `producao_mp`
--
ALTER TABLE `producao_mp`
  MODIFY `producao_mp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de tabela `producao_pa`
--
ALTER TABLE `producao_pa`
  MODIFY `producao_pa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `produto_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `saidas`
--
ALTER TABLE `saidas`
  MODIFY `saidas_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `telas`
--
ALTER TABLE `telas`
  MODIFY `tela_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `tipos_saida`
--
ALTER TABLE `tipos_saida`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `usuario_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `usuario_permissoes`
--
ALTER TABLE `usuario_permissoes`
  MODIFY `usuario_permissoes_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT de tabela `volumes`
--
ALTER TABLE `volumes`
  MODIFY `volume_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `calibre_produto`
--
ALTER TABLE `calibre_produto`
  ADD CONSTRAINT `calibre_produto_ibfk_1` FOREIGN KEY (`calibre_id`) REFERENCES `calibre` (`calibre_id`),
  ADD CONSTRAINT `calibre_produto_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`produto_id`);

--
-- Restrições para tabelas `conferencia`
--
ALTER TABLE `conferencia`
  ADD CONSTRAINT `conferencia_ibfk_1` FOREIGN KEY (`entrada_produtos_id`) REFERENCES `entrada_produtos` (`entrada_produtos_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conferencia_entrada_produtos` FOREIGN KEY (`entrada_produtos_id`) REFERENCES `entrada_produtos` (`entrada_produtos_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `entradas`
--
ALTER TABLE `entradas`
  ADD CONSTRAINT `entradas_ibfk_1` FOREIGN KEY (`parceiro_id`) REFERENCES `parceiros` (`parceiro_id`);

--
-- Restrições para tabelas `entrada_produtos`
--
ALTER TABLE `entrada_produtos`
  ADD CONSTRAINT `entrada_produtos_ibfk_1` FOREIGN KEY (`entradas_id`) REFERENCES `entradas` (`entrada_id`),
  ADD CONSTRAINT `entrada_produtos_ibfk_2` FOREIGN KEY (`produtos_id`) REFERENCES `produtos` (`produto_id`),
  ADD CONSTRAINT `entrada_produtos_ibfk_3` FOREIGN KEY (`calibre_id`) REFERENCES `calibre` (`calibre_id`),
  ADD CONSTRAINT `entrada_produtos_ibfk_volume` FOREIGN KEY (`volume_id`) REFERENCES `volumes` (`volume_id`);

--
-- Restrições para tabelas `estoque`
--
ALTER TABLE `estoque`
  ADD CONSTRAINT `estoque_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`produto_id`),
  ADD CONSTRAINT `estoque_ibfk_2` FOREIGN KEY (`calibre_id`) REFERENCES `calibre` (`calibre_id`);

--
-- Restrições para tabelas `permissoes`
--
ALTER TABLE `permissoes`
  ADD CONSTRAINT `fk_permissoes_chave` FOREIGN KEY (`chave_id`) REFERENCES `chave` (`chave_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_permissoes_tela` FOREIGN KEY (`tela_id`) REFERENCES `telas` (`tela_id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `preferencias_usuario`
--
ALTER TABLE `preferencias_usuario`
  ADD CONSTRAINT `preferencias_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuario_id`);

--
-- Restrições para tabelas `producao_mp`
--
ALTER TABLE `producao_mp`
  ADD CONSTRAINT `fk_producao_mp_pa` FOREIGN KEY (`producao_pa_id`) REFERENCES `producao_pa` (`producao_pa_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`categoria_id`);

--
-- Restrições para tabelas `saidas`
--
ALTER TABLE `saidas`
  ADD CONSTRAINT `saidas_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`produto_id`),
  ADD CONSTRAINT `saidas_ibfk_2` FOREIGN KEY (`tipo_saida_id`) REFERENCES `tipos_saida` (`id`);

--
-- Restrições para tabelas `usuario_permissoes`
--
ALTER TABLE `usuario_permissoes`
  ADD CONSTRAINT `fk_usuario_permissoes_permissao` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes` (`permissao_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_usuario_permissoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuario_id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `volume_produto`
--
ALTER TABLE `volume_produto`
  ADD CONSTRAINT `volume_produto_ibfk_1` FOREIGN KEY (`volume_id`) REFERENCES `volumes` (`volume_id`),
  ADD CONSTRAINT `volume_produto_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`produto_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
