<?php

namespace KTableManager;

use Exception;
use rex;
use rex_clang;
use rex_exception;
use rex_extension;
use rex_extension_point;
use rex_file;
use rex_i18n;
use rex_sql;
use rex_sql_exception;
use rex_url;
use rex_yform_manager_table;
use rex_yform_manager_table_api;
use yform\usability\Usability;

class TableManager
{
    /**
     * @var array <TableManager> $tables
     */
    private static array $tables = [];
    public bool $allowsInserts = false;
    private array $config = [];
    /**
     * @var array<array> $fields
     */
    private array $fields = [];
    private string $table;
    private int $row = 0;
    private int $col = 0;
    private int $langTabs = 0;
    private int $fieldset = 0;

    /**
     * @throws rex_sql_exception
     */
    public function __construct(string $table)
    {
        $this->table = rex::getTable(ltrim($table, 'rex_'));
        Table::clearFieldSchema($table);
        $this->setDefaultConfig();
        static::$tables[$table] = $this;
    }


    /**
     * @throws rex_sql_exception
     */
    private function setDefaultConfig(): void
    {

        $table = rex_yform_manager_table::get($this->table);
        $maxPrio = rex_yform_manager_table::getMaximumTablePrio();
        $this->config = [
            'table_name' => $this->table,
            'name' => $this->table,
            'list_amount' => 50,
            'list_sortfield' => 'id',
            'list_sortorder' => 'DESC',
            'schema_overwrite' => 1,
            'add_new' => 1,
            'history' => 0,
            'mass_edit' => 0,
            'hidden' => 0,
            'status' => 1,
            'mass_deletion' => 0,
            'export' => 0,
            'import' => 0,
            'createdate' => $table ? $table['createdate'] : date('Y-m-d H:i:s'),
            'updatedate' => date('Y-m-d H:i:s'),
            'createuser' => $table ? $table['createuser'] : rex::getUser()->getLogin(),
            'updateuser' => rex::getUser()->getLogin(),
            'prio' => $table ? $table['prio'] : $maxPrio + 1
        ];
    }

    /**
     * @param string $table
     * @return null|static
     */
    public static function extendTableManager(string $table): ?self
    {
        $tm = static::$tables[$table];
        if ($tm instanceof static) {
            return $tm;
        }
        return null;
    }

    public function setName(string $name): void
    {
        $this->config['name'] = $name;
    }

    public function setListAmount(int $listAmount): void
    {
        $this->config['list_amount'] = $listAmount;
    }

    public function setOrder(string $field, string $direction = 'DESC'): void
    {
        $this->config['list_sortfield'] = $field;
        $this->config['list_sortorder'] = $direction;
    }

    public function setHidden(bool $hidden): void
    {
        $this->config['hidden'] = $hidden ? 1 : 0;
    }

    public function setAddNew(bool $addNew): void
    {
        $this->config['add_new'] = $addNew ? 1 : 0;
    }

    public function setHistory(bool $history): void
    {
        $this->config['history'] = $history ? 1 : 0;
    }

    /**
     * @param string $name
     * @param string $type
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addField(
        string $name,
        string $type,
        string $label,
        array  $options = []
    ): void
    {
        $index = $this->getIndex($name);
        // exists already
        if (is_int($index)) {
            return;
        }
        $options = array_merge([
            'list_hidden' => 1,
            'search' => 0,
        ], $options);

        $this->fields[] = [
            'fieldName' => $name,
            'typeName' => $type,
            'createValues' => [],
            'updateValues' => array_merge($options, [
                'label' => $label,
            ]),
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addCheckboxField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'checkbox',
            $label,
            array_merge([
                'db_type' => 'tinyint(1)',
            ], $options)
        );
    }

    /**
     * @param string $fieldName
     * @return false|int|string
     */
    private function getIndex(string $fieldName): bool|int|string
    {
        return array_search($fieldName, array_column($this->fields, 'fieldName'));
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addChoiceField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'choice',
            $label,
            array_merge([
                'db_type' => 'text',
            ], $options)
        );
    }

    /**
     * @param int $size
     * @param callable $fields
     * @return void
     */
    public function addColumn(int $size, callable $fields): void
    {
        $this->col++;
        $this->fields[] = [
            'fieldName' => "row{$this->row}__col_{$this->col}_start",
            'typeName' => 'html',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'html' => '<div class="col-lg-' . $size . '">',
            ],
        ];
        $fields();
        $this->fields[] = [
            'fieldName' => "row{$this->row}__col_{$this->col}_end",
            'typeName' => 'html',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'html' => '</div>',
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addDataDumpField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'data_dump',
            $label,
            array_merge([
                'db_type' => 'text',
            ], $options)
        );
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addDatePickerField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'date',
            $label,
            array_merge([
                'format' => 'Y-m-d',
                'widget' => 'input:date',
                'db_type' => 'date',
            ], $options)
        );
    }

    public function addDateTimePickerField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'datetime',
            $label,
            array_merge([
                'format' => 'Y-m-d H:i:s',
                'widget' => 'input:text',
                'current_date' => 1,
                'attributes' => json_encode([
                    'data-yform-tools-datetimepicker' => 'YYYY-MM-DD HH:mm:ss',
                ]),
                'db_type' => 'datetime',
            ], $options)
        );
    }


    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addLinkField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'be_link',
            $label,
            array_merge([
                'db_type' => 'int',
            ], $options)
        );
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addDateTimeField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'datestamp',
            $label,
            array_merge([
                'format' => 'Y-m-d H:i:s',
                'db_type' => 'datetime',
            ], $options)
        );
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addEmailField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'email',
            $label,
            array_merge([
                'db_type' => 'varchar(191)',
            ], $options)
        );
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return void
     */
    public function addValidateField(
        string $name,
        string $type,
        array  $options = []
    ): void
    {
        $this->fields[] = [
            'yformType' => 'validate',
            'fieldName' => $name,
            'typeName' => $type,
            'createValues' => [],
            'updateValues' => $options,
        ];
    }

    /**
     * @param string $label
     * @return void
     */
    public function addFieldset(string $label): void
    {
        $this->fields[] = [
            'fieldName' => "fieldset_$this->fieldset",
            'typeName' => 'fieldset',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
            ],
            'updateValues' => [
                'db_type' => 'none',
                'label' => $label,
            ],
        ];
        $this->fieldset++;
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addImageField(string $name, string $label, array $options = []): void
    {
        $this->addField(
            $name,
            'be_media',
            $label,
            array_merge([
                'preview' => true,
                'db_type' => 'varchar(191)',
            ], $options)
        );
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addIntegerField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'integer',
            $label,
            array_merge([
                'db_type' => 'int',
            ], $options)
        );
    }

    /**
     * @param callable $fields
     * @return void
     */
    public function addLangFields(callable $fields): void
    {
        $this->langTabs++;

        $this->fields[] = [
            'fieldName' => "tab_start_$this->langTabs",
            'typeName' => 'lang_tabs',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'partial' => 'start',
            ],
        ];
        $idx = 0;
        foreach (rex_clang::getAll() as $clang) {
            if ($idx > 0) {
                $breakIndex = $idx - 1;

                $this->fields[] = [
                    'fieldName' => "tab_break_{$breakIndex}_$this->langTabs",
                    'typeName' => 'lang_tabs',
                    'createValues' => [
                        'list_hidden' => 1,
                        'search' => 0,
                        'label' => '',
                    ],
                    'updateValues' => [
                        'db_type' => 'none',
                        'partial' => 'break',
                    ],
                ];
            }
            $fields($clang->getId());
            $idx++;
        }

        $this->fields[] = [
            'fieldName' => "tab_end_$this->langTabs",
            'typeName' => 'lang_tabs',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'partial' => 'end',
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param null|int $defaultZoom
     * @param null|string $defaultPoint
     * @param null|string $type
     * @return void
     */
    public function addMapField(
        string  $name,
        string  $label,
        ?int    $defaultZoom = null,
        ?string $defaultPoint = null,
        ?string $type = null
    ): void
    {
        $this->addField($name, 'map', $label, [
            'default_zoom' => $defaultZoom ?: 14,
            'default_point' => $defaultPoint ?: '46.4776762,11.33599039',
            'type' => $type ?: 'Point',
            'db_type' => 'text',
        ]);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addMediaField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'be_media',
            $label,
            array_merge([
                'preview' => false,
                'db_type' => 'text',
            ], $options)
        );
    }

    public function addImageListField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'be_media',
            $label,
            array_merge([
                'preview' => true,
                'db_type' => 'text',
                'multiple' => true,
            ], $options)
        );
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $table
     * @param string $field
     * @param string $relationTable
     * @param null|bool $required
     * @param array $options
     * @return void
     */
    public function addMultiRelation(
        string $name,
        string $label,
        string $table,
        string $field,
        string $relationTable,
        ?bool  $required = null,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'be_manager_relation',
            $label,
            array_merge([
                'db_type' => 'int',
                'table' => $table,
                'empty_option' => !$required ?? true,
                'field' => $field,
                'relation_table' => $relationTable,
                'type' => 3,
            ], $options)
        );
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addNumberField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'number',
            $label,
            array_merge([
                'scale' => 2,
                'precision' => 10,
            ], $options)
        );
    }

    /**
     * @param callable $fields
     * @return void
     */
    public function addRow(callable $fields): void
    {
        $this->col = 0;
        $this->row++;

        $this->fields[] = [
            'fieldName' => "row{$this->row}_start",
            'typeName' => 'html',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'html' => '<div class="row">',
            ],
        ];

        $fields();

        $this->fields[] = [
            'fieldName' => "row{$this->row}_end",
            'typeName' => 'html',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'html' => '</div>',
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $table
     * @param string $field
     * @param null|bool $required
     * @param array $options
     * @return void
     */
    public function addSingleRelation(
        string $name,
        string $label,
        string $table,
        string $field,
        ?bool  $required = null,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'be_manager_relation',
            $label,
            array_merge([
                'db_type' => 'int',
                'empty_option' => !$required ?? true,
                'table' => $table,
                'field' => $field,
                'type' => 2,
            ], $options)
        );
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $class
     * @param array $options
     * @return void
     */
    public function addTextareaField(
        string $name,
        string $label,
        string $class = '',
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'textarea',
            $label,
            array_merge([
                'db_type' => 'text',
                'attributes' => json_encode([
                    'class' => $class . ' form-control',
                    'rows' => 3,
                ]),
            ], $options)
        );
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $profile
     * @param array $options
     * @return void
     */
    public function addCk5EditorField(
        string $name,
        string $label,
        string $profile = 'default',
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'textarea',
            $label,
            array_merge([
                'db_type' => 'text',
                'attributes' => json_encode([
                    'class' => 'cke5-editor',
                    'data-profile' => $profile,
                ]),
            ], $options)
        );
    }

    /**
     * @param mixed $langId
     * @param string $fieldName
     * @return void
     */
    public function addTitleField($langId = false, string $fieldName = 'title'): void
    {
        $this->addNameField($langId, $fieldName);
    }

    /**
     * @param bool $langId
     * @param string $fieldName
     * @return void
     */
    public function addNameField($langId = false, string $fieldName = 'name'): void
    {
        $listHidden = 0;
        $search = 1;

        if (is_int($langId)) {
            $fieldName .= "_$langId";
            $listHidden = $langId == 1 ? 0 : 1;
            $search = $langId == 1 ? 1 : 0;
        }

        $this->fields[] = [
            'fieldName' => $fieldName,
            'typeName' => 'text',
            'createValues' => [
                'list_hidden' => $listHidden,
                'search' => $search,
                'label' => 'translate:label.designation',
                'db_type' => 'varchar(191)',
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @return void
     */
    public function addTextField(
        string $name,
        string $label,
        array  $options = []
    ): void
    {
        $this->addField(
            $name,
            'text',
            $label,
            array_merge([
                'db_type' => 'varchar(191)',
            ], $options)
        );
    }

    /**
     * @return void
     */
    public function createDateFields(): void
    {
        $this->fields[] = [
            'fieldName' => "createdate",
            'typeName' => 'datestamp',
            'createValues' => [
                'list_hidden' => 0,
                'search' => 0,
                'show_value' => 1,
                'label' => 'translate:created_at',
            ],
            'updateValues' => [
                'db_type' => 'datetime',
                'format' => 'Y-m-d H:i:s',
                'only_empty' => 1,
                'no_db' => 0,
            ],
        ];

        $this->fields[] = [
            'fieldName' => "updatedate",
            'typeName' => 'datestamp',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'show_value' => 1,
                'label' => 'translate:updated_at',
            ],
            'updateValues' => [
                'db_type' => 'datetime',
                'format' => 'Y-m-d H:i:s',
                'only_empty' => 0,
                'no_db' => 0,
            ],
        ];
    }

    /**
     * @param string $fields
     * @return void
     */
    public function createPriorityField(string $fields = 'name_1'): void
    {
        $this->fields[] = [
            'fieldName' => "prio",
            'typeName' => 'prio',
            'createValues' => [
                'label' => 'translate:label.priority',
                'fields' => $fields,
            ],
            'updateValues' => [
                'db_type' => 'int',
                'list_hidden' => 1,
                'search' => 0,
            ],
        ];
        $this->config['list_sortfield'] = 'prio';
        $this->config['list_sortorder'] = 'ASC';
    }

    /**
     * @param array $options
     * @return void
     */
    public function createStatusField(array $options = []): void
    {
        $this->addField(
            'status',
            'choice',
            'translate:status',
            array_merge([
                'list_hidden' => 1,
                'search' => 0,
                'db_type' => 'int',
                'expanded' => 0,
                'multiple' => 0,
                'default' => 1,
                'choices' => json_encode(
                    [
                        'translate:active' => 1,
                        'translate:inactive' => 0,
                    ]
                ),
            ], $options)
        );
    }

    /**
     * @return void
     */
    public function createUserFields(): void
    {
        $this->fields[] = [
            'fieldName' => "createuser",
            'typeName' => 'be_user',
            'createValues' => [
                'label' => 'translate:created_by',
            ],
            'updateValues' => [
                'db_type' => 'varchar(191)',
                'list_hidden' => 1,
                'search' => 0,
                'only_empty' => 1,
                'show_value' => 1,
            ],
        ];

        $this->fields[] = [
            'fieldName' => "updateuser",
            'typeName' => 'be_user',
            'createValues' => [
                'label' => 'translate:updated_by',
            ],
            'updateValues' => [
                'db_type' => 'varchar(191)',
                'list_hidden' => 1,
                'search' => 0,
                'only_empty' => 0,
                'show_value' => 1,
            ],
        ];
    }

    /**
     * @param callable $fields
     * @return void
     */
    public function forEachLang(callable $fields): void
    {
        foreach (rex_clang::getAll() as $clang) {
            $fields($clang->getId());
        }
    }

    /**
     * @param string $field
     * @param callable $fields
     * @return void
     * @throws Exception
     */
    public function insertAfterLangField(string $field, callable $fields): void
    {
        if (!$this->allowsInserts) {
            throw new Exception('Inserts are not allowed in this context');
        }
        $this->forEachLang(function ($clangId) use ($field, $fields) {
            $this->insertAfter($field . '_' . $clangId, $fields, $clangId);
        });
    }

    /**
     * @param string $field
     * @param callable $fields
     * @param mixed $clangId
     * @return void
     * @throws Exception
     */
    public function insertAfter(string $field, callable $fields, $clangId = null): void
    {
        if (!$this->allowsInserts) {
            throw new Exception('Inserts are not allowed in this context');
        }

        $fieldsCopy = $this->fields;
        $indexFromField = $this->getIndex($field);

        $this->fields = [];
        $fields($clangId);
        $this->fields = array_merge(
            array_slice($fieldsCopy, 0, $indexFromField + 1),
            $this->fields,
            array_slice(
                $fieldsCopy,
                $indexFromField + 1
            )
        );
    }

    /**
     * @param string $name
     * @return void
     * @throws rex_sql_exception
     */
    public function removeField(string $name): void
    {
        $index = $this->getIndex($name);
        if ($index === false) {
            return;
        }
        Table::removeField($this->table, $name);
        unset($this->fields[$index]);
        // workaround repair indexes
        $this->fields = array_values($this->fields);
    }

    /**
     * @return void
     * @throws rex_sql_exception
     */
    public function synchronize(): void
    {
        Table::ensureTableConfig($this->table, $this->config);
        Table::ensureFields($this->table, $this->fields);
    }
}
