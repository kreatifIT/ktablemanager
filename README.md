# ktablemanager

Der Tablemanager erleichtert den schnellen Aufbau von YForm Tabellen über Code
Skripte.
Dadurch können Felder und Tabellenstrukturen einfach und schnell angepasst
werden.

# Setup

## 1. Addon registrieren

In der boot.php des betreffenden Addons (z.B. project) den Pfad zu den
Tablemanager Skripten angeben

```
rex_extension::register('KREATIF_TABLEMANAGER_PATHS', static function (rex_extension_point $ep) {
    $addon = rex_addon::get('project');
    $paths = $ep->getSubject();
    $paths[] = $addon->getPath('install/db_structure');
    return $paths;
}, rex_extension::LATE);
```

## 2. Das Tablemanager Skript aufbauen

Im jeweiligen Addon z.B. project-Addon ein Verzeichnis `install/db_structure`
anlegen und dort ein Skript anlegen, z.B. `prj_card_template.php`
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

Im Redaxo Backend das Addon kTablemanager öffnen.
Dort gibt es eine Liste mit allen YForm Tabellen, die über den Tablemanager
erstellt werden.
Mit dem Button "Synchronisieren" wird das Tabellenschema angepasst und die
Felder in YForm angelegt und aktualisiert.
Dieser Button befindet sich zusätzlich auch in den YForm Tabellen bei der
Daten-Übersicht.

### 5. YForm Tabelle prüfen

Einen Datensatz der betreffende YForm Tabelle in der Detailansicht öffnen.
Nun sollten die neuen Felder und die neue Tabellenstruktur sichtbar sein.

# Verwendung in mehreren Addons

Der Tablemanager lässt sich auch addon übergreifend verwenden. Beispielsweise
kann ein Hotel addon eine `rex_hotel_offer` Tabelle für Angebote erstellen
und das project addon kann diese Tabelle dann projektspezifisch anpassen und
erweitern.

```
use KTableManager\TableManager;

$tm = TableManager::extendTableManager('hotel_offer');

// neue Felder werden nach dem übersetzten Feld "name" eingefügt (übersetzter Untertitel)
$tm->insertAfterLangField('name', function ($clangId) use ($tm) {
    $tm->addTextareaField("subtitle_$clangId", 'translate:label.prj_hotel_offer_subtitle', 'cke5-editor');
});
// neues Felder wird nach dem Feld "images" eingefügt
$tm->insertAfter('images', function () use ($tm) {
    $tm->addMediaField("custom_image", 'translate:label.prj_hotel_image', [
        'preview' => 1,
        'types' => '*',
    ]);
});

// nach zweiter Zeile wird eine neue Zeile eingefügt und dort ein neues Feld 'Preis-Informationen'
$tm->insertAfterRow(2, function () use ($tm) {
    $tm->addRow(static function () use ($tm): void {
        $tm->addColumn(6, static function () use ($tm): void {
            $tm->addTextareaField("price_info_$clangId", 'translate:label.prj_hotel_offer_price_info', 'cke5-editor');
        });
    });
});

// nicht mehr benötigte Felder können entfernt werden
$tm->forEachLang(function ($clangId) use ($tm) {
    $tm->removeField("teaser_$clangId");
});
$tm->removeField('images');

$tm->synchronize();
```

# Verwendung im Live-Betrieb

Fürs Deployment empfehlen wir die Nutzung von
ydeploy (https://github.com/yakamara/ydeploy).
Ist ydeploy im Einsatz, sollte der Tablemanager im Live-Betrieb nicht verwendet
werden, da er die Datenbankstruktur überschreibt.
Dort sollte das Verfahren so sein, dass man zuerst über den Tablemanager die
neuen Felder einbaut und aktualisiert und danach über ydeploy dann die Migration
erstellt.

Wenn kein Deployment Verfahren wie ydeploy verwendet wird, dann ist der
Tablemanager auch im Live-Betrieb nützlich, da
man nur, nachdem der neue Code hochgeladen wurde, bei den einzelnen Tabellen den
synchroniseren Button klicken muss, um die
Felder zu aktualisieren (vielleicht noch zusätzlich Feldlöschung, falls man die
alten Felder und deren Daten in der DB komplett löschen möchte).
