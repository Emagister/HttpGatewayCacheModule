# HttpGatewayCacheModule functional test
This is a functional test for the ```HttpGatewayCacheModule``` based on the [ZF2 tutorial](https://github.com/akrabat/zf2-tutorial) by Rob Alen.
## How to get started ?
### Database
In order to get this test up and running you will need to set up a database and run this queries

```sql
CREATE TABLE album (
  id int(11) NOT NULL auto_increment,
  artist varchar(100) NOT NULL,
  title varchar(100) NOT NULL,
  PRIMARY KEY (id)
);

INSERT INTO album (artist, title) VALUES ('Coldplay', 'Mylo Xyloto');
INSERT INTO album (artist, title) VALUES ('Noel Gallagher', "Noel Gallagher\'s High Flying Birds!");
INSERT INTO album (artist, title) VALUES ('Adele', '21');
INSERT INTO album (artist, title) VALUES ('Matt Cardle', 'Letters');
INSERT INTO album (artist, title) VALUES ('Steps', 'The Ultimate Collection');
```

And then edit the file ```module/Album/config/module.config.php``` with the database connection info

```php
<?php
return array(
    'di' => array(
        'instance' => array(
            ...
            'Album\Model\AlbumTable' => array(
                'parameters' => array(
                    'config' => 'Zend\Db\Adapter\Mysqli',
            )),
            'Zend\Db\Adapter\Mysqli' => array(
                'parameters' => array(
                    'config' => array(
                        'host' => 'localhost',
                        'username' => 'root',
                        'password' => '',
                        'dbname' => 'zf2tutorial',
                    ),
                ),
            ),
            ...
        )
    )
);
```

### Web server
The recommended setup for ```Apache``` web server would be adding this VHost definition to your vhosts file

```apache
<VirtualHost *:80>
  ServerName zf2-tutorial.localhost
  DocumentRoot /path/to/zf-2tutorial/public
  SetEnv APPLICATION_ENV "development"

  <Directory /path/to/zf2-tutorial/public>
    DirectoryIndex index.php
    AllowOverride All
    Order allow,deny
    Allow from all
  </Directory>
</VirtualHost>
```

And then that's it. The functional test can be accessed through [http://zf2-tutorial.localhost]