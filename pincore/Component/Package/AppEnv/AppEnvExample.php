<?php



namespace Pinoox\Component\Package\AppEnv;



final class AppEnvExample

{

    public static function appFile(): string

    {

        return <<<'ENV'

# App env — apps/{package}/

# Loaded in order (later overrides earlier):

#   .env → .env.local → .env.{APP_ENV} → .env.{APP_ENV}.local

#

# Maps automatically onto app.php (no env() in app.php needed).



# THEME=default

# MODE=development

# DEBUG=true

# LANG=en

# ENABLE=true

# CACHE_ENABLED=false

# CACHE_MODE=development



# Database (app scope — use DB_USE, not project DB_CONNECTION)

# DB_USE=platform

# DB_PREFIX=shop_

# DB_DRIVER=sqlite

# DB_DATABASE=storage/apps/{package}/database.sqlite



ENV;

    }



    public static function themeFile(): string

    {

        return <<<'ENV'

# Theme env — apps/{package}/theme/{theme}/

# Same layer order as app .env; overrides app keys for this theme.



# DEBUG=true

# LANG=fa



# Vite / frontend (theme:frontend)

# VITE_DEV=false

# VITE_API_URL=



ENV;

    }

}


