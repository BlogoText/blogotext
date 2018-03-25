**All PR are welcome :)**


### In a few steps
1. clone the repo with `git clone --branch dev git@github.com:BlogoText/blogotext.git`
2. create a new branch `git checkout -b branch-name`
3. do your work
4. check your code (see below)
5. `git push`
6. Go to your github repository, and create a pull request from your branch to `dev` main repo.

### Few rules

Always work on a dedicated branch:

    git checkout -b branch-name

Before modifying code **and** pushing changes, stay up to date with the `dev` branch:

    // Add blogotext to upstream source
    git remote add upstream https://github.com/BlogoText/blogotext.git

    // Now, you can refresh your fork from the souce
    git pull --squash upstream dev

You can found some documentation on [help.github.com / syncing-a-fork](https://help.github.com/articles/syncing-a-fork/)

### Check your code

Travis CI will do it for you, but to prevent errors you can test yourself the code:

    # Download the tool
    curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar

    # Usage
    php phpcs.phar --standard=PSR2 -np --tab-width=4 --encoding=utf-8 .

If you are stuck with a **lot of errors**, use this tool which can refactor the code for you:

    # Download the tool
    curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar

    # Usage
    php phpcbf.phar --standard=PSR2 -np --tab-width=4 --encoding=utf-8 .

! phpcbf.phar can not work with window (require diff, path...) you can add ` --no-patch`
