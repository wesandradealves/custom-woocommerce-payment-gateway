# Extend the official WordPress image
FROM wordpress:latest

# Optional: Install additional PHP extensions or tools
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy custom files (if needed)
# COPY custom-content /var/www/html

# Expose WordPress default port
EXPOSE 80