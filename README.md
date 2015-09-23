# DiamanteDesk User Bundle #

**User bundle is required** for the proper work of DiamanteDesk as it contains User Entity.

### Requirements ###

DiamanteDesk supports OroCRM version 1.8+.

### Installation ###

**Step 1:** Add as dependency in composer: 

```bash
composer require diamante/user-bundle:dev-master
```

**Step 2:** After the composer installs this bundle, run the following command to update the application:

```bash
php app/console diamante:user:schema
```

### Contributing

We appreciate any effort to make DiamanteDesk functionality better; therefore, we welcome all kinds of contributions in the form of bug reporting, patches submitting, feature requests or documentation enhancement. Please refer to the DiamanteDesk [guidelines for contributing](http://docs.diamantedesk.com/en/latest/developer-guide/contributing.html) if you wish to be a part of the project.
