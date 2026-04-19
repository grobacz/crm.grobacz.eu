## Docker Compose Failures and Fixes

### Issue 1: PHP Extension Build Failure
**Error**: Build fails with `cp: cannot stat 'modules/*': No such file or directory` and `make: *** [Makefile:88: install-modules] Error 1`

**Root Cause**: The `docker-php-ext-install` command fails when installing multiple PHP extensions in one RUN command.

**Fix**: Split PHP extension installation into separate RUN commands in Dockerfile.backend:
```dockerfile
RUN docker-php-ext-install pdo pdo_mysql mbstring xml ctype json \
    && docker-php-ext-install tokenizer
```

### Issue 2: Obsolete Docker Compose Version
**Problem**: `version: '3.8'` attribute in docker-compose.yml causes warnings.

**Fix**: Remove the version line entirely as it's no longer needed.

### Issue 3: Missing .dockerignore
**Problem**: Build context includes too many unnecessary files.

**Fix**: Create `.dockerignore` file excluding node_modules, .git, README.md, dist, build, etc.

### Issue 4: Dependency Installation Order
**Problem**: System dependencies and PHP extensions must install before Composer.

**Fix**: Ensure proper order in Dockerfile:
1. apt-get update && install system deps
2. docker-php-ext-install PHP extensions  
3. Copy composer.json and run composer install
4. Copy application source files