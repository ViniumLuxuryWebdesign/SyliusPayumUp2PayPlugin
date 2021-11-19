<?php

declare(strict_types=1);

namespace Vinium\SyliusUp2PayPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

final class Up2PayGatewayConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sandbox', CheckboxType::class, [
                'label' => 'vinium.up2pay.sandbox'
            ])
            ->add('hmac', TextType::class, [
                'label' => 'vinium.up2pay.hmac',
                'constraints' => [
                    new NotBlank([
                        'message' => 'vinium.up2pay.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('identifiant', TextType::class, [
                'label' => 'vinium.up2pay.identifiant',
                'constraints' => [
                    new NotBlank([
                        'message' => 'vinium.up2pay.identifiant.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('site', TextType::class, [
                'label' => 'vinium.up2pay.site',
                'constraints' => [
                    new NotBlank([
                        'message' => 'vinium.up2pay.site.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('rang', TextType::class, [
                'label' => 'vinium.up2pay.rang',
                'constraints' => [
                    new NotBlank([
                        'message' => 'vinium.up2pay.rang.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
        ;
    }
}
