# WordPress Environment Setup

This guide outlines how to set up a local WordPress environment using Docker, leveraging the provided `Dockerfile`, `.env`, and `docker-compose.yml` files.

## Prerequisites

- [Docker](https://www.docker.com/get-started) installed on your system.
- [Docker Compose](https://docs.docker.com/compose/install/) installed.

## Files Overview

### `.env`
The `.env` file contains environment variables for configuring the Docker setup. These include database credentials and the domain.

```dotenv
DOMAIN=localhost
WORDPRESS_DB_HOST=db:3306
WORDPRESS_DB_USER=wordpress
WORDPRESS_DB_PASSWORD=wordpress
WORDPRESS_DB_NAME=wordpress
MYSQL_ROOT_PASSWORD=rootpassword
```

### `docker-compose.yml`
The `docker-compose.yml` file defines the services for WordPress, MySQL, and phpMyAdmin. It sets up ports, dependencies, and volume mappings.

```yaml
docker-compose.yml
version: '3.8'

services:
  wordpress:
    image: wordpress:latest
    container_name: wordpress
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: ${WORDPRESS_DB_HOST}
      WORDPRESS_DB_USER: ${WORDPRESS_DB_USER}
      WORDPRESS_DB_PASSWORD: ${WORDPRESS_DB_PASSWORD}
      WORDPRESS_DB_NAME: ${WORDPRESS_DB_NAME}
    volumes:
      - wordpress_data:/var/www/html
    depends_on:
      - db

  db:
    image: mysql:5.7
    container_name: wordpress_db
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${WORDPRESS_DB_NAME}
      MYSQL_USER: ${WORDPRESS_DB_USER}
      MYSQL_PASSWORD: ${WORDPRESS_DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql

  phpmyadmin:
    image: phpmyadmin/phpmyadmin:latest
    container_name: phpmyadmin
    ports:
      - "8081:80"
    environment:
      PMA_HOST: ${WORDPRESS_DB_HOST}
      PMA_USER: root
      PMA_PASSWORD: ${MYSQL_ROOT_PASSWORD}
    depends_on:
      - db

volumes:
  wordpress_data:
  db_data:
```

### `Dockerfile`
The `Dockerfile` defines the customizations for the WordPress container. Ensure the file is in the same directory as `docker-compose.yml`.

## Step-by-Step Setup

### 1. Clone the Repository

Clone this repository to your local machine:

```bash
git clone <repository-url>
cd <repository-directory>
```

### 2. Create `.env` File

Ensure the `.env` file exists in the root of the repository with the following content:

```dotenv
DOMAIN=localhost
WORDPRESS_DB_HOST=db:3306
WORDPRESS_DB_USER=wordpress
WORDPRESS_DB_PASSWORD=wordpress
WORDPRESS_DB_NAME=wordpress
MYSQL_ROOT_PASSWORD=rootpassword
```

You can modify these variables as needed for your environment.

### 3. Start the Environment

Run the following command to build and start the containers:

```bash
docker-compose up -d
```

### 4. Access the Services

- **WordPress:** Navigate to `http://localhost:8080`.
- **phpMyAdmin:** Navigate to `http://localhost:8081`.

### 5. Install Dependencies (Optional)

If you need to manage additional WordPress plugins or themes, use the provided `composer.json` file. Install dependencies with:

```bash
composer install
```

This setup uses `wpackagist` for managing plugins and themes.

## Managing the Environment

### Stop the Containers

```bash
docker-compose down
```

### Restart the Containers

```bash
docker-compose up -d
```

### View Logs

To view logs for a specific service, use:

```bash
docker-compose logs <service-name>
```

Replace `<service-name>` with `wordpress`, `db`, or `phpmyadmin`.

## Volumes

- `wordpress_data`: Stores WordPress files.
- `db_data`: Stores MySQL data.

These volumes ensure data persistence between container restarts.

## Troubleshooting

1. **Database Connection Issues:**
   - Verify database credentials in the `.env` file.
   - Ensure the `db` service is running.

2. **Port Conflicts:**
   - Check if ports `8080` or `8081` are already in use.
   - Modify the `docker-compose.yml` file to use different ports if necessary.

## License

This project is licensed under the MIT License.