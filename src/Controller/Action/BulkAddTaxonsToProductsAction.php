<?php

declare(strict_types=1);

namespace Setono\SyliusBulkEditPlugin\Controller\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Setono\SyliusBulkEditPlugin\Form\Type\AddProductsToTaxonsType;
use Setono\SyliusBulkEditPlugin\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Webmozart\Assert\Assert;

final class BulkAddTaxonsToProductsAction
{
    private ProductRepositoryInterface $productRepository;

    private Environment $twig;

    private FormFactoryInterface $formFactory;

    private FactoryInterface $productTaxonFactory;

    private EntityManager $productManager;

    private RouterInterface $router;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        Environment $twig,
        FormFactoryInterface $formFactory,
        FactoryInterface $productTaxonFactory,
        EntityManager $productManager,
        RouterInterface $router
    ) {
        $this->productRepository = $productRepository;
        $this->twig = $twig;
        $this->formFactory = $formFactory;
        $this->productTaxonFactory = $productTaxonFactory;
        $this->productManager = $productManager;
        $this->router = $router;
    }

    public function __invoke(Request $request): Response
    {
        $ids = $request->get('ids', []);
        Assert::allNumeric($ids);
        $products = $this->productRepository->findByIds((array) $ids);

        // @todo CSRF protection?
        $form = $this->formFactory->create(AddProductsToTaxonsType::class);
        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            /** @var ArrayCollection<array-key, TaxonInterface> $taxons */
            $taxons = $form->get('taxons')->getData();

            foreach ($products as $product) {
                foreach ($taxons as $taxon) {
                    if ($product->hasTaxon($taxon)) {
                        continue;
                    }

                    /** @var ProductTaxonInterface $productTaxon */
                    $productTaxon = $this->productTaxonFactory->createNew();
                    $productTaxon->setTaxon($taxon);

                    $product->addProductTaxon($productTaxon);
                }
            }

            $this->productManager->flush();

            $redirectUrl = $request->getUri();

            return new RedirectResponse($redirectUrl);
        }

        $editAction = $this->router->generate('setono_sylius_bulk_edit_admin_bulk_edit_products');
        $editAction .= '?' . $request->getQueryString();

        return new Response($this->twig->render(
            '@SetonoSyliusBulkEditPlugin/admin/bulk_add_to_taxon/index.html.twig',
            [
                'form' => $form->createView(),
                'action' => $request->getUri(),
                'editAction' => $editAction,
                'products' => $products,
            ]
        ));
    }
}
