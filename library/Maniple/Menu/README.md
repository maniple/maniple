# Maniple_Menu

MenuManager resource allows managing application menus. The menus are assembled by menu builders, on demand typically triggered by view helpers.

## Configuration

MenuManager can be configured in module bootstraps by providing a `getMenuManagerConfig()` method (and optionally marking module boostrap as `implements Maniple_Menu_MenuManagerProviderInterface` for explicitness).

For example:

```php
class Application_Bootstrap extends Maniple_Application_Module_Bootstrap
    implements Maniple_Menu_MenuManagerProviderInterface
{
    // ...

    public function getMenuManagerConfig()
    {
        return array(
            'builders' => array(
                'Application_Menu_MenuBuilder',
            ),
        );
    }
}
```

## Usage

Menu manager is registered in the DI container at `Maniple_Menu_MenuManager` key, and aliased as `maniple.menuManager`. You can either provide it to controllers or view helpers either via `@Inject` phpdoc annotation:

```php
class Application_MainController extends Maniple_Controller_Action
{
    /**
     * @Inject
     * @var Maniple_Menu_MenuManager
     */
    protected $_menuManager;

    // ...
}
```

or pull it explicitly from the DI container:

```php
/** @var Maniple_Menu_MenuManager $menuManager */
$menuManager = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('maniple.menuManager');
```
