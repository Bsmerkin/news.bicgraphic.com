<?php

namespace MauticPlugin\BgeBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Intl\Countries;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Mautic\CategoryBundle\Entity\Category; // Add this import


/**
 * Class InternalEmailType
 *
 * @package MauticPlugin\BgeBundle\Form\Type
 */
class InternalEmailType extends AbstractType
{
    private $leadModel;

    /**
     * @param LeadModel $leadModel
     */
    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('buttons', FormButtonsType::class);
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        
        $builder->add(
            $builder->create(
                'lead',
                EntityType::class,
                [
                    'label'         => 'Contact',
                    'class'         => Lead::class,
                    'choice_label'  => 'primaryIdentifier',
                    'label_attr'    => ['class' => 'control-label'],
                    'attr'          => ['class' => 'form-control', 'data-placeholder' => 'Contact'],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('l')
                            ->where('l.email LIKE :email')
                            ->setParameter('email', '%bicworld.com')
                            ->orderBy('l.firstname, l.lastname, l.email', 'ASC');
                    },
                    'required'      => true,
                ]
            )
        );

        $builder->add('country', ChoiceType::class, [
            'choices' => array_flip(Countries::getNames()),
            'required' => true,
            'label' => 'Country',
            'label_attr' => ['class' => 'control-label'],
            'attr' => [
                'class' => 'form-control',
                'data-placeholder' => 'Country'
            ],
        ]);

        $builder->add('language', LocaleType::class, [
            'label' => 'mautic.core.language',
            'label_attr' => ['class' => 'control-label'],
            'attr' => [
                'class' => 'form-control'
            ],
            'required' => true,
        ]);

        // Replace 'type' with 'category'
        $builder->add('category', EntityType::class, [
            'class' => Category::class,
            'choice_label' => 'title',
            'required' => true,
            'label' => 'mautic.internalemail.category',
            'label_attr' => ['class' => 'control-label'],
            'attr' => [
                'class' => 'form-control',
                'data-placeholder' => 'Select a category'
            ],
        ]);

        // Add form action
        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'internal_email';
    }
}
