
# BDM Digital Wordpress Plugin

This repository contains a fully Dockerized WordPress application with Composer for managing dependencies. The application includes:
- WordPress installed via Composer.
- MySQL database.
- PhpMyAdmin for database management.
- A custom plugin (`bdmdipag-gateway`).

---

## Prerequisites
Before setting up the environment, ensure the following dependencies are installed on your local machine:

1. **Docker**: [Install Docker](https://www.docker.com/get-started).
2. **Docker Compose**: Comes with Docker Desktop.
3. **Git**: [Install Git](https://git-scm.com/).

---

## Environment Setup

Follow the steps below to set up the environment:

### 1. Clone the Repository
```bash
git clone <repository-url>
cd <repository-folder>
```

### 2. Configure Environment Variables

Edit the `.env` file in the root directory to customize the following variables if necessary:

```env
# MySQL Database
MYSQL_DATABASE=bdm_digital_plugin
MYSQL_ROOT_PASSWORD=root
ENVIRONMENT=hml

# WordPress Database
WORDPRESS_DB_HOST=mysql
WORDPRESS_DB_USER=root
WORDPRESS_DB_PASSWORD=root
WORDPRESS_DB_NAME=bdm_digital_plugin
WP_DEBUG=FALSE
WP_DEBUG_DISPLAY=FALSE
# WordPress Site
WORDPRESS_DOMAIN=54.207.253.67:8000
WORDPRESS_USER=admin
WORDPRESS_PWD=admin
JWT_AUTH_SECRET_KEY=6oVSojxH7BlqRyq2l4iQbOiDikyzebKL4QtZiwBRvF5QWY91qL6kqNiatEFCE6Xb6RYsiwlr6cQpoabDQffQjw==


```

### 3. Build and Start the Containers

Run the following command to build and start the containers:

```bash
docker-compose up --build
```

This will:
- Build the WordPress Docker image based on the `Dockerfile`.
- Start the MySQL, WordPress, and PhpMyAdmin containers.

### 4. Access the Application

- WordPress site: [http://localhost:8000](http://localhost:8000)
- PhpMyAdmin: [http://localhost:8081](http://localhost:8081)

Use the credentials defined in the `.env` file for the database:
- Username: `root`
- Password: `root`

---

## Folder Structure

```plaintext
.
├── .env                   # Environment variables for Docker
├── composer.json          # Composer configuration for WordPress
├── docker-compose.yml     # Docker Compose configuration
├── Dockerfile             # Dockerfile for WordPress container
├── bdmdipag-gateway  # Custom plugin directory
└── ...
```

---

## Included Services

### 1. WordPress
- Installed via Composer.
- Served on [http://localhost:8000](http://localhost:8000).
- Custom plugin (`bdmdipag-gateway`) included.

### 2. MySQL
- Runs MySQL 5.7.
- Accessible via PhpMyAdmin or CLI.

### 3. PhpMyAdmin
- Accessible on [http://localhost:8081](http://localhost:8081).

---

## Custom Plugin

The custom plugin (`bdmdipag-gateway`) is included in the `/bdmdipag-gateway` folder. It is automatically copied into the WordPress plugins directory inside the container during the build process.

---

## Managing WordPress with WP-CLI

WP-CLI is pre-installed in the WordPress container. To use it:

1. Access the WordPress container:
   ```bash
   docker exec -it <container_name> bash
   ```

2. Run WP-CLI commands, e.g.:
   ```bash
   wp plugin list
   ```

---

## Composer

WordPress is managed via Composer. To install or update dependencies:

1. Access the WordPress container:
   ```bash
   docker exec -it <container_name> bash
   ```

2. Run Composer commands, e.g.:
   ```bash
   composer update
   ```

---

## Stopping the Environment

To stop the containers, run:
```bash
docker-compose down
```

This will stop and remove the containers.

---

## Troubleshooting

1. **Database Connection Errors**
   - Ensure the `WORDPRESS_DB_HOST` in `.env` matches the MySQL service name (`mysql`).

2. **Permission Issues**
   - Ensure the `bdmdipag-gateway` plugin directory has the correct permissions:
     ```bash
     chmod -R 755 bdmdipag-gateway
     ```

3. **Rebuilding the Environment**
   - If changes are made to the `Dockerfile`, rebuild the containers:
     ```bash
     docker-compose up --build
     ```

---

## Additional Notes

- Ports can be adjusted in `docker-compose.yml` if necessary.
- For advanced configuration, modify `docker-compose.override.yml` or extend the `Dockerfile`.

---
