-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de categorias
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    cor VARCHAR(7) DEFAULT '#2563eb',
    icone VARCHAR(50) DEFAULT 'fa-tag',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_categoria_usuario (usuario_id, nome, tipo)
);

-- Tabela de transações
CREATE TABLE transacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_id INT NOT NULL,
    descricao VARCHAR(200) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    data_transacao DATE NOT NULL,
    forma_pagamento ENUM('dinheiro', 'cartao_credito', 'cartao_debito', 'pix', 'boleto', 'transferencia') DEFAULT 'dinheiro',
    status ENUM('pendente', 'pago', 'cancelado') DEFAULT 'pago',
    observacao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    INDEX idx_usuario_data (usuario_id, data_transacao)
);

-- Inserir categorias padrão para novos usuários
INSERT INTO categorias (usuario_id, nome, tipo, cor, icone) VALUES
(1, 'Salário', 'receita', '#22c55e', 'fa-wallet'),
(1, 'Investimentos', 'receita', '#3b82f6', 'fa-chart-line'),
(1, 'Alimentação', 'despesa', '#f59e0b', 'fa-utensils'),
(1, 'Transporte', 'despesa', '#8b5cf6', 'fa-car'),
(1, 'Moradia', 'despesa', '#ef4444', 'fa-home'),
(1, 'Saúde', 'despesa', '#ec4899', 'fa-heartbeat'),
(1, 'Educação', 'despesa', '#06b6d4', 'fa-graduation-cap'),
(1, 'Lazer', 'despesa', '#f97316', 'fa-gamepad'),
(1, 'Compras', 'despesa', '#14b8a6', 'fa-shopping-bag');