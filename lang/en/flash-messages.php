<?php

//phpcs:disable
return [
    'permission-preset' => [
        'success' => [
            'create' => 'Permission preset successfully createdt',
            'update' => 'Permission preset successfully updated',
            'delete' => 'Permission preset successfully deleted',
        ],
        'error' => [
            'create' => 'Permission preset could not be saved. Please try again',
            'update' => 'Permission preset could not be updated. Please try again',
            'delete' => 'Permission preset could not be deleted. Please try again',
        ]
    ],
    'branding' => [
        'update' => 'Branding successfully updated'
    ],
    'communication_and_legal' => [
        'update' => 'Communication & Legal successfully updated'
    ],
    'interfaces' => [
        'failed_to_save' => 'Sage interface settings could not be updated, please try again.',
        'connection_test_failed' => 'Sage interface settings have been successfully updated, but the connection test has failed. Please check the interface settings and make sure that the interface is accessible.',
        'saved_successfully' => 'Sage interface settings successfully updated.',
        'import_executed_successfully' => 'Sage import was executed successfully.',
        'import_executed_unsuccessfully' => 'Sage import could not be executed, please try again.',
        'date_range_required' => 'Please specify a date range (From date).',
        'date_or_ktr_required' => 'Please specify at least a KTR or a date range.',
        'booking_days_deleted_successfully' => 'Booking data was successfully deleted.',
    ],
    'shift-qualification' => [
        'success' => [
            'create' => 'Qualification successfully saved',
            'update' => 'Qualification successfully updated.',
            'destroy' => 'Qualification successfully deleted.'
        ],
        'error' => [
            'create' => 'Qualification could not be saved, please try again',
            'update' => 'Qualification could not be updated, please try again.',
            'destroy' => 'Qualification could not be updated, please try again.'
        ]
    ],
    'budget-general-setting' => [
        'success' => [
            'update' => 'Setting was successfully saved.'
        ],
        'error' => [
            'update' => 'Setting could not be saved, please try again.'
        ]
    ],
    'budget-account-management' => [
        'success' => [
            'account' => [
                'create' => 'Account successfully saved.',
                'update' => 'Account successfully updated.',
                'delete' => 'Account successfully deleted.'
            ],
            'cost-unit' => [
                'create' => 'Cost unit successfully saved.',
                'update' => 'Cost unit successfully updated.',
                'delete' => 'Cost unit successfully deleted.'
            ]
        ],
        'error' => [
            'account' => [
                'create' => 'Account could not be saved, please try again.',
                'update' => 'Account could not be updated, please try again.',
                'delete' => 'Account could not be deleted, please try again.'
            ],
            'cost_unit' => [
                'create' => 'Cost unit could not be saved, please try again.',
                'update' => 'Cost unit could not be updated, please try again.',
                'delete' => 'Cost unit could not be deleted, please try again.'
            ]
        ]
    ],
    'inventory-management' => [
        'column' => [
            'errors' => [
                'create' => 'Column could not be saved. Please try again',
                'duplicate' => 'Column could not be duplicated. Please try again.',
                'updateName' => 'Column name could not be updated. Please try again.',
                'updateBackgroundColor' => 'Column color could not be updated. Please try again.',
                'updateTypeOptions' => 'Column selection options could not be updated. Please try again.',
                'delete' => 'Column could not be deleted. Please try again.'
            ]
        ],
        'category' => [
            'errors' => [
                'create' => 'Category could not be saved. Please try again.',
                'updateName' => 'Category name could not be updated. Please try again.',
                'updateOrder' => 'Category position could not be updated. Please try again.',
                'delete' => 'Category could not be deleted. Please try again.'
            ]
        ],
        'group' => [
            'errors' => [
                'create' => 'Group could not be saved. Please try again.',
                'updateName' => 'Group name could not be updated. Please try again.',
                'updateOrder' => 'Group position could not be updated. Please try again',
                'delete' => 'Group could not be deleted. Please try again.'
            ]
        ],
        'item' => [
            'errors' => [
                'create' => 'Item could not be saved. Please try again',
                'updateOrder' => 'Item position could not be updated. Please try again',
                'delete' => 'Item could not be deleted. Please try again.'
            ]
        ],
        'item-cell' => [
            'errors' => [
                'updateCellValue' => 'Value could not be updated. Please try again.'
            ]
        ],
        'filter' => [
            'errors' => [
                'updateOrCreate' => 'Filter settings could not be updated. Please try again.'
            ]
        ],
        'export' => [
            'errors' => [
                'download' => 'Export could not be created. Please try again.'
            ]
        ]
    ],
    'module-settings' => [
        'success' => [
            'update' => 'Module visibility has been successfully updated.'
        ]
    ]
];
