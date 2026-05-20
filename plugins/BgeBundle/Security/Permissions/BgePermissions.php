<?php

namespace MauticPlugin\BgeBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class BgePermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);

        // Add standard permissions for 'bge'
        $this->addStandardPermissions('internalemail', false);

    }

    public function getName(): string
    {
        return 'bge';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('bge', 'internalemail', $builder, $data);

    }
}
