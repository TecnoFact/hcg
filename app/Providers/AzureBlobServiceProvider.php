<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;

class AzureBlobServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Storage::extend('azure', function ($app, $config) {
            $blobEndpoint = $config['endpoint'];  // sin el ?
            $sasToken     = $config['sas'];       // solo el token
            $container    = $config['container'];

            $connectionString = "BlobEndpoint={$blobEndpoint};SharedAccessSignature={$sasToken}";

            $client = BlobRestProxy::createBlobService($connectionString);
            $adapter = new AzureBlobStorageAdapter($client, $container);

            return new FilesystemAdapter(new Filesystem($adapter), $adapter, $config);
        });
    }
}
