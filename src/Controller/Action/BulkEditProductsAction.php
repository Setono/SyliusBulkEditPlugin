<?php

declare(strict_types=1);

namespace Setono\SyliusBulkEditPlugin\Controller\Action;

use Setono\SyliusBulkEditPlugin\Repository\ProductRepositoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Webmozart\Assert\Assert;

final class BulkEditProductsAction
{
    private ProductRepositoryInterface $productRepository;

    private ProductVariantRepositoryInterface $productVariantRepository;

    private ChannelRepositoryInterface $channelRepository;

    private Environment $twig;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductVariantRepositoryInterface $productVariantRepository,
        ChannelRepositoryInterface $channelRepository,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->channelRepository = $channelRepository;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
    }

    public function __invoke(Request $request): Response
    {
        /** @var ChannelInterface[] $channels */
        $channels = $this->channelRepository->findAll();
        Assert::minCount($channels, 1);

        if (!$request->query->has('channelCode')) {
            $channelAwareUrl = $request->getUri();
            $channelAwareUrl .= '&channelCode=' . $channels[0]->getCode();

            return new RedirectResponse($channelAwareUrl);
        }

        $currentChannelCode = $request->query->get('channelCode');
        Assert::string($currentChannelCode);
        /** @var ChannelInterface|null $currentChannel */
        $currentChannel = $this->channelRepository->findOneByCode($currentChannelCode);
        Assert::notNull($currentChannel);

        if ($request->isMethod('POST')) {
            $variants = $request->get('variants', []);
            foreach ($variants as $variantId => $variant) {
                /** @var ProductVariantInterface|null $obj */
                $obj = $this->productVariantRepository->find($variantId);

                if (null === $obj) {
                    continue;
                }

                foreach ($variant as $channelCode => $prices) {
                    $channelPricing = self::getChannelPricingFromChannelCode($channelCode, $obj);
                    if (null === $channelPricing) {
                        continue;
                    }

                    $channelPricing->setPrice(self::transformPrice((string) $prices['price']));
                    $channelPricing->setOriginalPrice(self::transformPrice((string) $prices['originalPrice']));
                }

                $this->productVariantRepository->add($obj);
            }
        }

        $products = $this->productRepository->findByIds($request->get('ids', []));

        $addTaxonsAction = $this->urlGenerator->generate('setono_sylius_bulk_edit_admin_bulk_add_taxons_to_products');
        $addTaxonsAction .= '?' . $request->getQueryString();

        return new Response($this->twig->render('@SetonoSyliusBulkEditPlugin/admin/bulk_edit/index.html.twig', [
            'products' => $products,
            'channels' => $channels,
            'currentChannel' => $currentChannel,
            'addTaxonsAction' => $addTaxonsAction,
        ]));
    }

    private static function getChannelPricingFromChannelCode(string $channelCode, ProductVariantInterface $productVariant): ?ChannelPricingInterface
    {
        foreach ($productVariant->getChannelPricings() as $channelPricing) {
            if ($channelPricing->getChannelCode() === $channelCode) {
                return $channelPricing;
            }
        }

        return null;
    }

    private static function transformPrice(string $price): ?int
    {
        if ('' === $price) {
            return null;
        }

        return (int) round((float) $price * 100);
    }
}
