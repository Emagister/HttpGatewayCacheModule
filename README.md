#HttpGatewayCacheModule ![project status](http://stillmaintained.com/Emagister/HttpGatewayCacheModule.png) #

A reverse proxy cache for Zend Framework 2 applications

## What?

HttpGatewayCacheModule is a small Zend Framework 2 module that will act as a [reverse proxy cache](http://en.wikipedia.org/wiki/Reverse_proxy)
for your Zend Framework 2 application.

## How?

HttpGatewayCacheModule uses the standard Zend Framework 2 event system to hook on the application route event and on the finish event.

It acts as much as a page cache but with ESI tag support. So it allows to have different expiration times for different parts of
the page. The current implementation only supports the &lt;esi:include /&gt; tag.

## Where?

<a href="https://github.com/Emagister/HttpGatewayCacheModule/raw/master/HttpGatewayCacheModule.phar" class="button big icon fork">Download from Github!</a>

## How to use it?

###Install it

You can clone it or download it to your "vendor" directory (you can add it as a submodule).

```bash
$ cd /your/zend/framework/app/vendor
$ git clone https://github.com/Emagister/HttpGatewayCacheModule
```

### Configure it

Edit the file "module.config.php" under the HttpGatewayCacheModule's configs folder and update the _"cacheoptions"_ and _"cache"_
sections to fit your needs.

```php
<?php
return array(
  ...
  'di' => array(
    'alias' => array(
      ...
      'cache_options' => 'Zend\Cache\Storage\Adapter\ApcOptions',
      'cache'         => 'Zend\Cache\Storage\Adapter\Apc'
    ),
    'instance' => array(
      ...
      'cache_options' => array(
        'parameters' => array(
          'cfg' => array(
            'ttl' => 3600
          )
        )
      ),
      'cache' => array(
        'parameters' => array(
          'options' => 'cache_options'
        )
      )
    )
  )
);
```

Now edit the file "application.config.php" and add the module to the bootstrap phase

```php
<?php
return array(
  'modules' => array(
    ...
    'HttpGatewayCacheModule'
  ),
);
```

### Use it
From some controller

```php
<?php

namespace Album\Controller;

use Zend\Mvc\Controller\ActionController,
    Album\Model\AlbumTable,
    Album\Form\AlbumForm;

class AlbumController extends ActionController
{
  /**
   * @var \Album\Model\AlbumTable
   */
  protected $albumTable;

  public function indexAction()
  {
    return array(
      'albums' => $this->albumTable->fetchAll(),
    );
  }
}
```

From some view

```php
<div class="container">
  <?php echo $this->esi(
    $this->url(
      'my_route',
      array(
        'param1' => 'param1Value',
        'param2' => 'param2Value'
      )
    )
  ); ?>
</div>
```

* * *
Note: This module is still highly experimental! Not ready for production use!