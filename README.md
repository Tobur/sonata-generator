**SETUP**

Add repository to your composer.json
```
    "repositories": [
        .....
        {
            "type": "git",
            "url": "https://github.com/Tobur/sonata-generator",
            "reference": "master"
        }
        ....
    ]
```   
Then add required to you composer.json

```
"sonata-generator": "dev-master",
```

Update it:

```
php composer update
```    

Create monolog chanel:

```
console:
    type:   console
    process_psr_3_messages: false
    level: debug
    channels: ["!event", "!doctrine", "!console"]
```

Add to config:
```
sonata_generator:
  path_to_admin_yaml: config/packages/admin.yaml
```

**HOW TO USE**

Generate admin for entity "Example"
```
php bin/console generate:sonata-admin 'App\Entity\Example' 'App\Admin'
```

Generate admin for all entities in interactive mode

```
php bin/console generate:sonata-admin 'App\Entity\' 'App\Admin' 1
```