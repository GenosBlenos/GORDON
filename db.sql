CREATE DATABASE biblioteca;
USE biblioteca;

CREATE TABLE livros (
    id INT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255) NOT NULL,
    ano_publicacao INT,
    isbn VARCHAR(20) UNIQUE,
    disponivel BOOLEAN DEFAULT TRUE,
    categoria VARCHAR(50) DEFAULT 'Outros'
);

CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(191) NOT NULL,
    email VARCHAR(191) UNIQUE NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE emprestimos (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    livro_id INT,
    usuario_id INT,
    data_emprestimo DATETIME DEFAULT CURRENT_TIMESTAMP, -- Usa data/hora atual
    data_devolucao DATE,
    status ENUM('emprestado', 'devolvido') DEFAULT 'emprestado',
    FOREIGN KEY (livro_id) REFERENCES livros(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE administradores (
    id INTEGER PRIMARY KEY,
    nome VARCHAR(191) NOT NULL,
    email VARCHAR(191) UNIQUE NOT NULL,
    senha VARCHAR(191) NOT NULL,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- INSERT INTO livros (id, titulo, autor, ano_publicacao, isbn, categoria)
-- VALUES (
--     'Dracula', 
--     'Bram Stoker', 
--     2018, 
--     '9786555984828', 
--     'Suspense'
-- );