<?php

namespace MauticPlugin\BgeBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Intl\Countries;

class LocalizedEmailSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private CoreParametersHelper $coreParametersHelper,
        private EmailModel $emailModel,
        private MailHashHelper $mailHash
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_SEND => ['onEmailGenerate', 10001],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailGenerate', 10001],
        ];
    }

    public function onEmailGenerate(EmailSendEvent $event): void
    {
        $emailObj = $event->getEmail();
        $locale = ($emailObj && $emailObj->getLanguage()) ? $emailObj->getLanguage() : $this->coreParametersHelper->get('locale', 'en');
        $idHash = $event->getIdHash() ?? uniqid();
        $lead   = $event->getLead();
        $email  = $event->getEmail();

        // Get email
        $toEmail = $this->getLeadEmail($lead);

        // Generate unsubscribe text
        $unsubscribeText = $this->generateUnsubscribeText($idHash, $toEmail, $lead, $locale);
        $event->addToken('{trans_unsubscribe_text}', EmojiHelper::toHtml($unsubscribeText));

        // Generate webview text
        $webviewText = $this->generateWebviewText($idHash, $locale);
        $event->addToken('{trans_webview_text}', EmojiHelper::toHtml($webviewText));

        // Translate dynamic tokens, excluding predefined ones
        $this->translateDynamicTokens($event, $locale, ['trans_unsubscribe_text', 'trans_webview_text']);
    }

    private function getLeadEmail($lead): ?string
    {
        if (is_array($lead) && array_key_exists('email', $lead) && is_string($lead['email'])) {
            return $lead['email'];
        } elseif ($lead instanceof Lead && is_string($lead->getEmail())) {
            return $lead->getEmail();
        }

        return null;
    }

    private function generateUnsubscribeText(string $idHash, ?string $toEmail, $lead, string $locale): string
    {
        $unsubscribeHash = $toEmail ? $this->mailHash->getEmailHash($toEmail) : null;

        $unsubscribeText = $this->translator->trans('mautic.email.unsubscribe.text', ['%link%' => '|URL|'], null, $locale);
        $unsubscribeText = TokenHelper::findLeadTokens($unsubscribeText, $lead, true);
        $unsubscribeText = str_replace('|URL|', $this->emailModel->buildUrl('mautic_email_unsubscribe', [
            'idHash' => $idHash,
            'urlEmail' => $toEmail,
            'secretHash' => $unsubscribeHash,
        ]), $unsubscribeText);

        return $unsubscribeText;
    }

    private function generateWebviewText(string $idHash, string $locale): string
    {
        $webviewText = $this->translator->trans('mautic.email.webview.text', ['%link%' => '|URL|'], null, $locale);
        $webviewText = str_replace('|URL|', $this->emailModel->buildUrl('mautic_email_webview', ['idHash' => $idHash]), $webviewText);

        return $webviewText;
    }

    private function translateDynamicTokens(EmailSendEvent $event, string $locale, array $excludedTokens = []): void
    {
        $content = $event->getContent();
        $lead = $event->getLead();

        // Safely extract country and language with defaults
        $countryName = null;
        $languageIso = null;
        
        if (!empty($lead['id'])) {
            $countryName = $lead['country'] ?? null;
            $languageIso = $lead['preferred_locale'] ?? null;
        } elseif ($lead instanceof Lead) {
            $countryName = $lead->getCountry();
            $languageIso = $lead->getPreferredLocale();
        }

        // Set defaults if empty
        $languageIso = !empty($languageIso) ? $languageIso : 'en';
        
        // Find ISO country code from country name
        $countryIso = 'GB'; // Default
        if (!empty($countryName)) {
            // Cache country names for performance (static property recommended)
            static $countryNameToIso = null;
            if ($countryNameToIso === null) {
                $countryNameToIso = array_flip(Countries::getNames());
            }
            
            // Direct lookup (faster than foreach)
            if (isset($countryNameToIso[$countryName])) {
                $countryIso = $countryNameToIso[$countryName];
            } else {
                // Fallback: case-insensitive search
                foreach (Countries::getNames() as $isoCode => $name) {
                    if (strcasecmp($name, $countryName) === 0) {
                        $countryIso = $isoCode;
                        break;
                    }
                }
            }
        }

        // First, replace any %web_locale% in the content
        if (strpos($content, '%web_locale%') !== false) {            
            $webLocale = sprintf('%s/%s', $countryIso, $languageIso);
            $content = str_replace('%web_locale%', $webLocale, $content);
            $event->setContent($content);
        }

        // Match tokens starting with "trans_" in the email content
        preg_match_all('/\{trans_([a-zA-Z0-9_]+)\}/', $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $tokenName) {
                // Skip excluded tokens
                if (in_array('trans_' . $tokenName, $excludedTokens, true)) {
                    continue;
                }

                $translationKey = 'mautic.email.token.trans_' . $tokenName;
                $translatedText = $this->translator->trans($translationKey, [], null, $locale);

                // Replace %web_locale% in translated text if it exists
                if (strpos($translatedText, '%web_locale%') !== false) {                                        
                    $webLocale = sprintf('%s/%s', $countryIso, $languageIso);
                    $translatedText = str_replace('%web_locale%', $webLocale, $translatedText);
                }

                // Add the token to the event after all replacements
                $event->addToken('{trans_' . $tokenName . '}', $translatedText);
            }
        }
    }
}