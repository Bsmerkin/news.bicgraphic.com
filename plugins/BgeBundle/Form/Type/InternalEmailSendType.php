<?php

namespace MauticPlugin\BgeBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class InternalEmailSendType
 *
 * @package MauticPlugin\BgeBundle\Form\Type
 */
class InternalEmailSendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'email',
            EntityType::class,
            [
                'class' => Email::class,
                'choice_label' => 'name',
                'label' => 'Choose the email to be sent:',
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'class' => 'form-control',
                    'tooltip' => 'Choose the email to be sent'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please select an email to be sent.'])
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('e')
                        ->where('e.isPublished = :published')
                        ->andWhere('e.translationParent IS NULL')
                        ->setParameter('published', true)
                        ->orderBy('e.dateAdded', 'DESC');
                },
                'placeholder' => 'Select an email',
                'required' => true,
            ]
        );

        $builder->add(
            'category',
            EntityType::class,
            [
                'class' => Category::class,
                'choice_label' => 'title',
                'required' => true,
                'placeholder' => 'Select a category',
                'label' => 'Choose category',
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'class' => 'form-control',
                    'tooltip' => 'Category of recipients that will receive the selected email'
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.title', 'ASC');
                },
            ]
        );
        
        $builder->add(
            'countries',
            ChoiceType::class,
            [
                'choices' => $options['countriesChoices'],
                'required' => true,
                'label' => 'Choose countries',
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'class' => 'form-control',
                    'tooltip' => 'Countries must receive selected email. By default (empty) send to all countries'
                ],
                'multiple' => true,
                'expanded' => true,
            ]
        );
        
        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text'     => false,
                'save_text'      => 'Send',
                'save_class'     => 'btn btn-primary',
                'save_icon'      => 'fa fa-send',    
                'cancel_onclick' => 'javascript:void(0);',
                'cancel_attr'    => [
                    'data-dismiss' => 'modal',
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'countriesChoices' => [],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'internalemail_send';
    }
}
