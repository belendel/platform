<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RoleType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('role', 'text', array(
                'required' => true,
            ))
            ->add('label', 'text', array(
                'required' => false,
            ));

        $factory = $builder->getFormFactory();

        // disable role name edit after role has been created
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($factory) {
            if ($event->getData() && $event->getData()->getId()) {
                $form = $event->getForm();

                $form->add($factory->createNamed('role', 'text', null, array_merge(
                    $form->get('role')->getConfig()->getOptions(),
                    array('disabled' => true)
                )));
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Oro\Bundle\UserBundle\Entity\Role',
            'intention'  => 'role',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_user_role';
    }
}