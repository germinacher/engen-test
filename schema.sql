CREATE DATABASE engen_db;

USE engen_db;

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mp_subscription_id VARCHAR(255),
    estado ENUM('pendiente','activo','cancelado') DEFAULT 'pendiente',
    fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
