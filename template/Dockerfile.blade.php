FROM {{ $from }}

# make Composer global vendor/bin available through PATH
RUN sed -i 's/\(^export PATH=.*\)/\1:\/root\/.composer\/vendor\/bin:\/phars/' /etc/profile

# install dependencies (ast)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install ast \
    && echo 'extension=ast.so' >> /usr/local/etc/php/php.ini \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/*

# install QA tools
RUN \
    # install and set up QA tools from Composer
    composer global require squizlabs/php_codesniffer \
        friendsofphp/php-cs-fixer \
        phan/phan \
    # download PHAR and make them executable
    && mkdir /phars \
    && curl -Lf https://phpmd.org/static/latest/phpmd.phar -o /phars/phpmd \
    && curl -Lf https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_linux_amd64 -o /phars/local-php-security-checker \
    && curl -Lf https://phar.phpunit.de/phpcpd.phar -o /phars/phpcpd \
    && curl -Lf https://phar.phpunit.de/phpunit-nightly.phar -o /phars/phpunit10 \
    && curl -Lf https://phar.phpunit.de/phpunit-9.5.phar -o /phars/phpunit \
    && chmod +x /phars/* \
    # post-install tools settings
    && /phars/local-php-security-checker --update-cache

ADD entrypoint.sh /kool/entrypoint.sh
RUN chmod +x /kool/entrypoint.sh

ENTRYPOINT [ "/kool/entrypoint.sh" ]
CMD [ "composer", "--version" ]
