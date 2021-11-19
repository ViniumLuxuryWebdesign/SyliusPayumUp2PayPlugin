<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Form\Type;

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
                'label' => 'vinium_payum_up2pay_plugin.sandbox'
            ])
            ->add('hmac', TextType::class, [
                'label' => 'vinium_payum_up2pay_plugin.hmac',
                'constraints' => [
                    new NotBlank([
                        'message' => 'vinium_payum_up2pay_plugin.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('identifiant', TextType::class, [
                'label' => 'vinium_payum_up2pay_plugin.identifiant',
                'constraints' => [
                    new NotBlank([
                        'message' => 'vinium_payum_up2pay_plugin.identifiant.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('site', TextType::class, [
                'label' => 'vinium_payum_up2pay_plugin.site',
                'constraints' => [
                    new NotBlank([
                        'message' => 'vinium_payum_up2pay_plugin.site.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('rang', TextType::class, [
                'label' => 'vinium_payum_up2pay_plugin.rang',
                'constraints' => [
                    new NotBlank([
                        'message' => 'vinium_payum_up2pay_plugin.rang.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
        ;
    }
}
