CREATE TABLE IF NOT EXISTS urls (
    id SERIAL PRIMARY KEY, 
    name VARCHAR (255), 
    create_at timestamp
);

CREATE TABLE IF NOT EXISTS url_checks (
            id SERIAL PRIMARY KEY,
            url_id int REFERENCES urls (id),
            status_code int,
            h1 VARCHAR (255),
            title VARCHAR (255),
            description VARCHAR (255),
            name VARCHAR (255), 
            create_at timestamp
);