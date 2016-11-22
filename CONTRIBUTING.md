All PR are welcome :)

## Few rules

Always work on a dedicated branch:

    git checkout -b branch-name

Before modifying code **and** pushing changes, stay up to date with the `dev` branch:

    git pull --squash upstream dev


## Check your code

Travis CI will do it for you, but to prevent errors you can test yourself the code:

    # Download the tool
    curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar

    # Usage
    php phpcs.phar --standard=PSR2 -np --tab-width=4 --encoding=utf-8 .

If you are stuck with a lot of errors, use this tool which can refactor the code for you:

    # Download the tool
    curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar

    # Usage
    php phpcbf.phar --standard=PSR2 -np --tab-width=4 --encoding=utf-8 .
