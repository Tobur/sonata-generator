**SETUP**

Run
```
composer require tobur/sonata-generator
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