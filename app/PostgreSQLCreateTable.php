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
                name VARCHAR (255) UNIQUE NOT NULL, 
                create_at timestamp
        )';

        $this->pdo->exec($sql);

        $sql2 = 'CREATE TABLE IF NOT EXISTS url_checks (
            id SERIAL PRIMARY KEY,
            url_id int REFERENCES urls (id),
            status_code int,
            h1 VARCHAR (255),
            title VARCHAR (255),
            description VARCHAR (255),
            name VARCHAR (255), 
            create_at timestamp
        )';

        $this->pdo->exec($sql2);

        return $this;
    }
    public function insertUrls($urls)
    {
        // подготовка запроса для добавления данных
        $sql = 'INSERT INTO urls(name, create_at) VALUES(:urls, :create_at)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':urls', $urls);
        $time = date('Y-m-d H:i:s');
        $stmt->bindValue(':create_at', $time);

        $stmt->execute();

        // возврат полученного значения id
        return $this->pdo->lastInsertId('urls_id_seq');
    }
    public function insertUrlsChecks(array $data)
    {
        $sql = "INSERT INTO url_checks(url_id, status_code, h1, title, description, name, create_at) 
            VALUES (:id, :status_code, :h1, :title, :description, :name, :create_at)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $data['url_id'] ?? null);
        $stmt->bindValue(':status_code', $data['status_code'] ?? null);
        $stmt->bindValue(':h1', $data['h1'] ?? '');
        $stmt->bindValue(':title', $data['title'] ?? '');
        $stmt->bindValue(':description', $data['description'] ?? '');
        $stmt->bindValue(':name', $data['name'] ?? '');
        $time = date('Y-m-d H:i:s');
        $stmt->bindValue(':create_at', $time);

        $stmt->execute();

        return $this->pdo->lastInsertId('url_checks_id_seq');
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
        } elseif (!$this->isValidUrl($url)) {
            $errors[] = "Некорректный URL";
        } elseif (!empty($id)) {
            return $id;
        }
        return $errors;
    }
    function isValidUrl($url) {
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !in_array($parsedUrl['scheme'], ['http', 'https'])) {
            return false;
        }
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
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
    public function getAllLastCheksData()
    {
        $sql = "
        SELECT ur1.id, ur1.name, ur2.create_at, ur2.status_code 
        FROM urls AS ur1
        LEFT JOIN url_checks AS ur2 ON ur1.id = ur2.url_id 
        AND ur2.create_at = (
            SELECT MAX(uc2.create_at)
            FROM url_checks AS uc2
            WHERE uc2.url_id = ur1.id
        ) ORDER BY ur1.id DESC
        ";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function getUrlChecksData($id)
    {
        $sql = "SELECT * FROM url_checks WHERE url_id=:id ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result;
    }
}
