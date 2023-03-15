# ktablemanager
Erleichtert den schnellen Aufbau von YForm Tabellen über Code Skripte

# BeispielCode
`project/install/db_structure/prj_card_template.php`

```
use KTableManager\TableManager;

$tm = new TableManager('prj_card_template');
$tm->addRow(function () use ($tm) {
    $tm->addColumn(4, function () use ($tm) {
        $tm->addLangFields(function ($langId) use ($tm) {
            $tm->addNameField($langId);
            $tm->addMediaField("image_{$langId}", 'Vorschaubild', 0, 1, [
                'preview' => 1,
                'category' => 4,
            ]);
            $tm->addMediaField("image_print_{$langId}", 'Vorlage Druck Bild', 0, 1);
        });
        $tm->addMediaField("cardholder_front", 'Cardholder Vorderseite', 0, 1, [
            'preview' => 1,
            'category' => 16,
        ]);
        $tm->addNumberField("surcharge", 'Aufpreis', 0, 1, [
            'precision' => 5,
            'scale' => 2,
            'unit' => '€',
        ]);
      
        $tm->addField('template_constructor', 'hidden_input', 'Vorlagen Klasse', 0, 1, [
            'db_type' => 'varchar(191)',
            'attributes' => '{"readonly":"readonly}',
        ]);
        $tm->addField('template_class_params', 'hidden_input', 'Vorlage Parameter', 0, 1, [
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
            0,
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
            0,
            1
        );
        $tm->addChoiceField('available_for_user_type', 'Für folgende Nutzergruppe verfügbar', 0, 1, [
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
            0,
            1,
            [
                'filter' => 'status=1',
                'notice' => 'Wenn keine Ortschaft ausgewählt ist die Karte für alle verfügbar',
            ]
        );
        $tm->addMultiRelation('dedications', '###label.dedication###', 'rex_prj_dedication', 'name_1', '', 0, 0, 1);
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
