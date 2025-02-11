<?php

declare(strict_types=1);

namespace Setono\SyliusBulkEditPlugin\Controller\Action;

use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusBulkEditPlugin\Event\ProductVariantsUpdatedEvent;
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

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductVariantRepositoryInterface $productVariantRepository,
        ChannelRepositoryInterface $channelRepository,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->channelRepository = $channelRepository;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(Request $request): Response
    {
        /** @var list<ChannelInterface> $channels */
        $channels = $this->channelRepository->findAll();
        Assert::minCount($channels, 1);

        if (!$request->query->has('channelCode')) {
            $channelAwareUrl = sprintf('%s&channelCode=%s', $request->getUri(), (string) $channels[0]->getCode());

            return new RedirectResponse($channelAwareUrl);
        }

        $currentChannelCode = $request->query->get('channelCode');
        Assert::string($currentChannelCode);
        /** @var ChannelInterface|null $currentChannel */
        $currentChannel = $this->channelRepository->findOneByCode($currentChannelCode);
        Assert::notNull($currentChannel);

        if ($request->isMethod('POST')) {
            $updateAllPrices = $updateAllOriginalPrices = false;
            $updateAll = $request->request->get('updateAll');
            /** @psalm-suppress DocblockTypeContradiction,TypeDoesNotContainType */
            if (is_array($updateAll)) {
                $updateAllPrices = isset($updateAll['price']);
                $updateAllOriginalPrices = isset($updateAll['originalPrice']);
            }

            $updatedProductVariants = [];

            /** @var array<int, array<string, array{price: int, originalPrice: int}>> $variants */
            $variants = $request->request->get('variants') ?? [];
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

                    $price = self::transformPrice((string) $prices['price']);
                    $originalPrice = self::transformPrice((string) $prices['originalPrice']);

                    $channelPricing->setPrice($price);
                    $channelPricing->setOriginalPrice($originalPrice);

                    /**
                     * If the administrator checked the update all prices on either the price or original price
                     * we want to fetch all channels where the base currency is the same as the current channel,
                     * so we can update the prices for each of these channels
                     */
                    if ($updateAllPrices || $updateAllOriginalPrices) {
                        /** @var list<ChannelInterface> $channelsToUpdate */
                        $channelsToUpdate = $this->channelRepository->findBy([
                            'baseCurrency' => $currentChannel->getBaseCurrency(),
                        ]);

                        foreach ($channelsToUpdate as $channelToUpdate) {
                            $channelCodeToUpdate = (string) $channelToUpdate->getCode();
                            // we don't need to update this channel since it's already been updated above
                            if ($channelCodeToUpdate === $channelCode) {
                                continue;
                            }

                            $channelPricing = self::getChannelPricingFromChannelCode($channelCodeToUpdate, $obj);
                            if (null === $channelPricing) {
                                continue;
                            }

                            if ($updateAllPrices) {
                                $channelPricing->setPrice($price);
                            }

                            if ($updateAllOriginalPrices) {
                                $channelPricing->setOriginalPrice($originalPrice);
                            }
                        }
                    }
                }

                $this->productVariantRepository->add($obj);

                $updatedProductVariants[] = $obj;
            }

            if ([] !== $updatedProductVariants) {
                $this->eventDispatcher->dispatch(new ProductVariantsUpdatedEvent($updatedProductVariants));
            }
        }

        $ids = $request->query->get('ids') ?? [];
        Assert::isArray($ids);

        $products = $this->productRepository->findByIds($ids);

        $addTaxonsAction = sprintf('%s?%s', $this->urlGenerator->generate('setono_sylius_bulk_edit_admin_bulk_add_taxons_to_products'), (string) $request->getQueryString());
        $removeTaxonsAction = sprintf('%s?%s', $this->urlGenerator->generate('setono_sylius_bulk_edit_admin_bulk_remove_taxons_from_products'), (string) $request->getQueryString());

        return new Response($this->twig->render('@SetonoSyliusBulkEditPlugin/admin/bulk_edit/index.html.twig', [
            'products' => $products,
            'channels' => $channels,
            'currentChannel' => $currentChannel,
            'addTaxonsAction' => $addTaxonsAction,
            'removeTaxonsAction' => $removeTaxonsAction,
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
