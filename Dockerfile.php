FROM php:8.2-apache

# 1. dependencies
RUN apt-get update && apt-get install -y \
    wget \
    unzip \
    libaio1t64 \
    && rm -rf /var/lib/apt/lists/*

# 2. Oracle Instant Client
RUN wget -O /tmp/instantclient-basic.zip https://download.oracle.com/otn_software/linux/instantclient/2113000/instantclient-basic-linux.x64-21.13.0.0.0dbru.zip \
    && wget -O /tmp/instantclient-sdk.zip https://download.oracle.com/otn_software/linux/instantclient/2113000/instantclient-sdk-linux.x64-21.13.0.0.0dbru.zip \
    && unzip /tmp/instantclient-basic.zip -d /usr/local/ \
    && unzip /tmp/instantclient-sdk.zip -d /usr/local/ \
    && rm /tmp/instantclient-*.zip

# 3. environment
ENV LD_LIBRARY_PATH=/usr/local/instantclient_21_13
ENV ORACLE_HOME=/usr/local/instantclient_21_13

# 4. OCI8
RUN echo "instantclient,/usr/local/instantclient_21_13" | pecl install oci8 \
    && echo "extension=oci8.so" > /usr/local/etc/php/conf.d/oci8.ini


RUN ln -sf /usr/lib/x86_64-linux-gnu/libaio.so.1t64 /usr/lib/x86_64-linux-gnu/libaio.so.1

RUN a2enmod rewrite
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 5. check
RUN php -m | grep oci8