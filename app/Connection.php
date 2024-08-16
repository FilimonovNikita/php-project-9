<?php

namespace PostgreSQL;

/**
 * Создание класса Connection
 */
final class Connection
{
    /**
     * Connection
     * тип @var
     */
    private static ?Connection $conn = null;

    /**
     * Подключение к базе данных и возврат экземпляра объекта \PDO
     * @return \PDO
     * @throws \Exception
     */
    public function connect()
    {
        if (getenv('DATABASE_URL')) {
            $params = parse_url(getenv('DATABASE_URL'));
        } /*else {
            throw new \Exception("Error reading database configuration url");
        }*/

        // подключение к базе данных postgresql
        $username = $params['user'] ?? 'user1';
        $password = $params['pass'] ?? 'sql';
        $host = $params['host'] ?? "localhost";
        $port = $params['port'] ?? 5432;
        $dbName = isset($params['path']) ? ltrim($params['path'], '/') : 'project9';

        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $host,
            $port,
            $dbName,
            $username,
            $password
        );

        $pdo = new \PDO($conStr);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * возврат экземпляра объекта Connection
     * тип @return
     */
    public static function get()
    {
        if (null === static::$conn) {
            static::$conn = new self();
        }

        return static::$conn;
    }

    protected function __construct()
    {
    }
}
