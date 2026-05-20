<?php

return [
    'name'        => 'BgeBundle',
    'description' => 'Plugin for managing internal emails',
    'version'     => '1.0.0',
    'author'      => 'Your Name',
    'routes'      => [
        'main' => [
            'mautic_internalemail_index' => [
                'path'       => '/internals/{page}',
                'controller' => 'MauticPlugin\BgeBundle\Controller\InternalEmailController::indexAction',
            ],
            'mautic_internalemail_action' => [
                'path'       => '/internals/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\BgeBundle\Controller\InternalEmailController::executeAction',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'bge.internalemails' => [
                'id'    => 'mautic_internalemail_index',
                'route'    => 'mautic_internalemail_index',
                'access'   => 'bge:internalemail:view',
                'iconClass' => 'glyphicon-envelope',
                'priority' => -1,
            ],
        ],
    ],
    'services'    => [
        'forms' => [
            'mautic.form.type.internalemail' => [
                'class' => \MauticPlugin\BgeBundle\Form\Type\InternalEmailType::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                ],                
            ],
        ]
    ],
];
