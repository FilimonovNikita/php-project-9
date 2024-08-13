<?php

namespace PostgreSQL;

/**
 * Создание в PostgreSQL таблицы из демонстрации PHP
 */
class PostgreSQLCreateTable
{
    /**
     * объект PDO
     * @var \PDO
     */
    private $pdo;

    /**
     * инициализация объекта с объектом \PDO
     * @тип параметра $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * создание таблиц
     */
    public function createTables()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS urls (
                id SERIAL PRIMARY KEY, 
                name VARCHAR (255), 
                create_at timestamp
        )';

        $this->pdo->exec($sql);

        return $this;
    }
    public function insertUrls($urls)
    {
        // подготовка запроса для добавления данных
        $sql = 'INSERT INTO urls(name, create_at) VALUES(:urls, :create_at)';
        $stmt = $this->pdo->prepare($sql);
        print ($urls);
        $stmt->bindValue(':urls', $urls);
        $time = date('Y-m-d H:i:s');
        $stmt->bindValue(':create_at', $time);

        $stmt->execute();

        // возврат полученного значения id
        return $this->pdo->lastInsertId('urls_id_seq');
    }
    public function validateUrls($url) //проверяет url на корректность, если есть в базе возравщает id
    {
        $sql = "SELECT id FROM urls WHERE name=:url";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':url', $url);
        $stmt->execute();
        $id = $stmt->fetchAll();

        $errors = [];
        if (strlen($url) < 1) {
            $errors[] = 'URL не должен быть пустым';
        } elseif (strlen($url) > 255) {
            $errors[] = 'Длина превышает 255';
        } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = "Некорректный URL";
        } elseif (!empty($id)) {
            return $id;
        }
        return $errors;
    }
    public function getUrlData($id)
    {
        $sql = "SELECT * FROM urls WHERE id = :id ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }
    public function getAllData()
    {
        $sql = "SELECT * FROM urls";  // Запасной вариант, если можно использовать другой SQL-запрос
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetchAll();
        print_r($result);
        return $result;
    }
}
