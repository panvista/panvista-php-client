![Panvista Mobile](http://panvistamobile.com/wp-content/themes/panvista_media/images/panvista.png)

# Panvista PHP Client

## Overview
This repository lets you interact with Panvista's API service.

## Usage

```php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Panvista' . DIRECTORY_SEPARATOR . 'Client.php';

echo "<pre>";

try {
    $client = new Panvista\Client('YOUR_CLIENT_TOKEN', 'YOUR_CLIENT_SECRET');
    print_r($client->call('/sections/list/'));
    print_r($client->call('/content/list/?section_id=CONTENT_SECTION_ID'));
    print_r($client->call('/library/list/?section_id=LIBRARY_SECTION_ID'));
    $data = array('title' => 'test from php client',
                  'object' => '@/path/to/image.jpg',
                  'section_id' => 'LIBRARY_SECTION_ID');

    $item = $client->call('/library/', 'PUT', $data);
    print_r($item);

    if (isset($item->id)) {
        print_r($client->call('/library/' . $item->id . '/', 'DELETE'));
    }
} catch (Panvista\Exception $e) {
    print_r($e);
}

echo "</pre>";
```

## Documentation

You can view our API documentation on all our available endpoints [here](http://docs.panvista.apiary.io/).
