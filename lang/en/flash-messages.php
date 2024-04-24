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
        'import_executed_unsuccessfully' => 'Sage import could not be executed, please try again.'
    ],
    'shift-qualification' => [
        'success' => [
            'create' => 'Qualification successfully saved',
            'update' => 'Qualification successfully updated.'
        ],
        'error' => [
            'create' => 'Qualification could not be saved, please try again',
            'update' => 'Qualification could not be updated, please try again.'
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
    ]
];
