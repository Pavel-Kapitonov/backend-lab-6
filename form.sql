-- Отключаем проверки, чтобы DROP TABLES сработал без ошибок связей
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS Connection;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS Language;
DROP TABLE IF EXISTS Request;
DROP TABLE IF EXISTS Admins;

CREATE TABLE Language (
    language_id INT AUTO_INCREMENT PRIMARY KEY,
    language_name VARCHAR(50) NOT NULL
);

CREATE TABLE Request (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    tel VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    dateborn DATE NOT NULL,
    gender ENUM('M','F') NOT NULL,
    bio TEXT,
    agreed BOOLEAN NOT NULL
);

CREATE TABLE Connection (
    request_id INT NOT NULL,
    language_id INT NOT NULL,
    PRIMARY KEY (request_id, language_id),
    FOREIGN KEY (request_id) REFERENCES Request(request_id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES Language(language_id) ON DELETE CASCADE
);

CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    login VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    FOREIGN KEY (request_id) REFERENCES Request(request_id) ON DELETE CASCADE
);

CREATE TABLE Admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL
);

-- Заполнение справочника языков
INSERT INTO Language (language_name) VALUES 
('Pascal'), ('C'), ('C++'), ('JavaScript'), ('PHP'), 
('Python'), ('Java'), ('Haskell'), ('Clojure'), ('Prolog'), ('Scala');


INSERT INTO Admins (login, password_hash) 
VALUES ('admin', '$2y$10$YmG3v/9XvE.GfS.lFvY7CeX6kY9mE.Nf.f/yP8Y.nF.f/yP8Y.nF.');

SET FOREIGN_KEY_CHECKS = 1;