<?php

declare(strict_types=1);

namespace Setono\SyliusBulkEditPlugin\Form\Type;

use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

final class RemoveProductsFromTaxonsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taxons', TaxonAutocompleteChoiceType::class, [
                'label' => 'setono_sylius_bulk_edit.form.taxons_to_remove',
                'multiple' => true,
                'mapped' => false,
            ])
        ;
    }
}
