<?php

declare(strict_types=1);

namespace MauticPlugin\BgeBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Model\CompanyModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    private const SPECIAL_EMAIL_ID = 158;
    private const IMOGEN_EMAIL = 'imogen.bone@bicworld.com';

    public function __construct(
        private TranslatorInterface $translator,
        private CompanyModel $companyModel,
        private LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_SEND => ['onEmailGenerate', 0],
        ];
    }

    public function onEmailGenerate(EmailSendEvent $event): void
    {   

        $lead = $event->getLead();
        $email = $event->getEmail();
        
        if (!$email || empty($lead)) {
            return;
        }

        if ($email->getFromAddress()) {
            return;
        }
        
        // Skip if lead email domain is bicworld.com
        if (isset($lead['email']) && str_ends_with(strtolower($lead['email']), '@bicworld.com')) {
            return;
        }

        // Set the email from address from Primary Company's customer service email address
        $primaryCompany = $this->getPrimaryCompany($lead);
        
        if ($primaryCompany) {
            $csEmail = $primaryCompany['cc_contact_email'] ?? '';
            $csName = $primaryCompany['cc_contact_name'] ?? 'Customer Service BIC Graphic Europe';

            if (!empty($csEmail)) {
                $event->getHelper()->setFrom($csEmail, $csName);
                $event->getHelper()->setReplyTo($csEmail, $csName);

                // Log the modifications using Psr\Log\LoggerInterface
                $this->logger->info(sprintf(
                    'Email modified: From [%s] Name [%s], ReplyTo [%s] Name [%s]',
                    $csEmail,
                    $csName,
                    $csEmail,
                    $csName
                ));
            }
        }        
    }

    /**
     * @param array<string, mixed> $lead
     *
     * @return array<string, mixed>|null
     */
    private function getPrimaryCompany(array $lead): ?array
    {
        if (empty($lead['companies']) || !is_array($lead['companies'])) {
            return null;
        }

        foreach ($lead['companies'] as $company) {
            if (is_array($company) && (($company['is_primary'] ?? '') === '1')) {
                return $company;
            }
        }

        return null;
    }

    
}