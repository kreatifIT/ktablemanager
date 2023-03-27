# ktablemanager
Erleichtert den schnellen Aufbau von YForm Tabellen über Code Skripte

# Setup
##  1. Addon registrieren

In der boot.php des betreffenden Addons (z.B. project) den Pfad zu den Tablemanager Skripten angeben

```
rex_extension::register('KREATIF_TABLEMANAGER_PATHS', static function (rex_extension_point $ep) {
    $addon = rex_addon::get('project');
    $paths = $ep->getSubject();
    $paths[] = $addon->getPath('install/db_structure');
    return $paths;
}, rex_extension::LATE);
```

## 2. Im Redaxo Backend die YForm Tabelle anlegen
(nur den Tabellen-Kopf, nicht die einzelnen Felder)

## 3. Das Tabelmanager Skript aufbauen
`project/install/db_structure/prj_card_template.php`

```
use KTableManager\TableManager;

$tm = new TableManager('prj_card_template');
$tm->addRow(function () use ($tm) {
    $tm->addColumn(4, function () use ($tm) {
        $tm->addLangFields(function ($langId) use ($tm) {
            $tm->addNameField($langId);
            $tm->addMediaField("image_{$langId}", 'Vorschaubild', [
                'preview' => 1,
                'category' => 4,
            ]);
            $tm->addMediaField("image_print_{$langId}", 'Vorlage Druck Bild');
        });
        $tm->addMediaField("cardholder_front", 'Cardholder Vorderseite', [
            'preview' => 1,
            'category' => 16,
        ]);
        $tm->addNumberField("surcharge", 'Aufpreis',  [
            'precision' => 5,
            'scale' => 2,
            'unit' => '€',
        ]);
      
        $tm->addField('template_constructor', 'hidden_input', 'Vorlagen Klasse', [
            'db_type' => 'varchar(191)',
            'attributes' => '{"readonly":"readonly}',
        ]);
        $tm->addField('template_class_params', 'hidden_input', 'Vorlage Parameter',  [
            'db_type' => 'text',
            'attributes' => '{"class":"form-control codemirror","rows":1}',
        ]);
    });
    $tm->addColumn(4, function () use ($tm) {
        $tm->addSingleRelation(
            'template_group',
            'Vorlagengruppe',
            'rex_prj_card_template_group',
            'name_1',
            1,
            [
                'empty_value' => 'Bitte Vorlagengruppe auswählen',
            ]
        );
        $tm->addMultiRelation(
            'card_types',
            'Karten',
            'rex_prj_card_type',
            'name_1',
            'rex_prj_card_type_has_template',
            0,
        );
        $tm->addChoiceField('available_for_user_type', 'Für folgende Nutzergruppe verfügbar',  [
            'choices' => '{"Private": "private_member","Firmen": "company_member"}',
            'multiple' => '1',
        ]);
        $tm->addMultiRelation(
            'available_for_municipalities',
            'Für folgende Gemeinden verfügbar',
            'rex_prj_municipality',
            'name_1',
            'rex_prj_municipality_has_template',
            0,
            [
                'filter' => 'status=1',
                'notice' => 'Wenn keine Ortschaft ausgewählt ist die Karte für alle verfügbar',
            ]
        );
        $tm->addMultiRelation('dedications', '###label.dedication###', 'rex_prj_dedication', 'name_1', '', 0);
    });
    $tm->addColumn(4, function () use ($tm) {
        $tm->createStatusField();
        $tm->createPriorityField();
        $tm->createUserFields();
        $tm->createDateFields();
    });
});

$tm->synchronize();
```


## 4. Skript ausführen
Im Redaxo Backend die betreffende YForm Table öffnen und die Datensätze anzeigen. Dort gibt es einen neuen Button "synchronisieren". Mit Klick auf diesem Button wird nun das Tablemanager Skript aufgerufen und das betreffende YForm Feld-Schema überschrieben.
