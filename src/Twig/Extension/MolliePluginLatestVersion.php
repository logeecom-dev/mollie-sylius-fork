<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Twig\Extension;

use BitBag\SyliusMolliePlugin\BitBagSyliusMolliePlugin;
use BitBag\SyliusMolliePlugin\Checker\Version\MolliePluginLatestVersionCheckerInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MolliePluginLatestVersion extends AbstractExtension
{
    /** @var MolliePluginLatestVersionCheckerInterface */
    private $latestVersionChecker;

    public function __construct(MolliePluginLatestVersionCheckerInterface $latestVersionChecker)
    {
        $this->latestVersionChecker = $latestVersionChecker;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'bitbag_render_version_widget',
                [$this, 'versionRenderWidget'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
        ];
    }

    public function versionRenderWidget(Environment $environment): string
    {
        $latestVersion = str_replace('v', '', $this->latestVersionChecker->checkLatestVersion());

        if ($latestVersion === BitBagSyliusMolliePlugin::VERSION || empty($latestVersion)) {
            return '';
        }

        return $environment->render('@SyliusAdmin/PaymentMethod/_versionNotification.html.twig', [
            'latestVersion' => $latestVersion,
            'currentVersion' => BitBagSyliusMolliePlugin::VERSION,
        ]);
    }
}
