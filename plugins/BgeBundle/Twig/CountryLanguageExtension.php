<?php

namespace MauticPlugin\BgeBundle\Twig;

use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Locales;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CountryLanguageExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('country_name', [$this, 'getCountryName']),
            new TwigFilter('language_name', [$this, 'getLanguageName']),
        ];
    }

    public function getCountryName($countryCode)
    {
        return Countries::getName($countryCode);
    }

    public function getLanguageName($languageCode)
    {
        return Locales::getName($languageCode);
    }
}