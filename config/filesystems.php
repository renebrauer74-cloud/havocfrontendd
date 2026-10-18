<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
         * Alle Uploads (Media Library) landen auf zwei Disks, die sich einen Speicher teilen:
         *
         *   uploads       Website-Bilder aus dem Admin       <collection>/<uuid>/datei
         *   band-manager  Verträge, Rider, Fotos, Logos      band-manager/<collection>/<uuid>/datei
         *
         * Lokal liegt alles unter public/uploads/. Auf Laravel Cloud ersetzt der angehängte öffentliche
         * Bucket mit dem Disk-Namen "uploads" die Disk "uploads" (LARAVEL_CLOUD_DISK_CONFIG) – das
         * Dateisystem dort ist flüchtig. "band-manager" ist nur ein Unterordner davon.
         *
         * Die Band-Manager-Dateien werden nur über Controller mit Rechteprüfung ausgeliefert. Im öffentlichen
         * Bucket sind sie über ihre (unratbare) direkte URL trotzdem erreichbar. Umzug auf einen privaten Bucket:
         *   1. Privaten Bucket in Cloud mit dem Disk-Namen "band-manager-private" anhängen (nicht default)
         *   2. BAND_MANAGER_DISK=band-manager-private setzen, deployen – neue Uploads landen dort
         *   3. php artisan havoc:move-media band-manager band-manager-private
         */
        'uploads' => [
            'driver' => 'local',
            'root' => public_path('uploads'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/uploads',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'band-manager' => [
            'driver' => 'scoped',
            'disk' => 'uploads',
            'prefix' => 'band-manager',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
