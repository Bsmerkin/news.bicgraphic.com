<?php

namespace MauticPlugin\BgeBundle\EventListener;

use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Model\TagModel;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Locales;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LeadSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class LeadSubscriber implements EventSubscriberInterface
{
    private CompanyModel $companyModel;
    private LeadModel $leadModel;
    private TagModel $tagModel;
    
    /**
     * LeadSubscriber constructor.
     *
     * @param CompanyModel $companyModel
     * @param LeadModel $leadModel
     * @param TagModel $tagModel
     */
    public function __construct(
        CompanyModel $companyModel,
        LeadModel $leadModel,
        TagModel $tagModel
    ) {
        $this->companyModel = $companyModel;
        $this->leadModel = $leadModel;
        $this->tagModel = $tagModel;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LEAD_PRE_SAVE => ['onLeadPreSave', -1],
            LeadEvents::COMPANY_PRE_SAVE => ['onCompanyPreSave', 0]
        ];
    }

    /**
     * Handles the "onLeadPreSave" event.
     *
     * @param LeadEvent $event The lead event object.
     */
    public function onLeadPreSave(LeadEvent $event)
    {
        $lead = $event->getLead();

        // Remove CS contact & IS contact if @bicworld.com
        if (strpos($lead->getEmail(), 'bicworld.com') !== false) {
            $lead->addUpdatedField('cscontactname', '');
            $lead->addUpdatedField('cscontactemail', '');
            $lead->addUpdatedField('iscontactname', '');
            $lead->addUpdatedField('iscontactemail', '');
        }

        // Convert iso country to Mautic Iso
        if ($countryIsoCode = $lead->getFieldValue('country')) {
            if (Countries::exists($countryIsoCode)) {
                $countryName = Countries::getName($countryIsoCode);
                $lead->addUpdatedField('country', $countryName);
            }
        }

        // Convert Locale to Mautic Iso 
        if ($preferredLocale = $lead->getFieldValue('preferred_locale')) {
            if (Locales::exists($preferredLocale)) {
                $preferredLocaleStd = strtolower($preferredLocale);
                $lead->addUpdatedField('preferred_locale', $preferredLocaleStd);
            }
        }
    }
    
    /**
     * Handles the "onCompanyPreSave" event.
     * When company sent_presentation_card is 1:
     * - Adds "Sent Presentation Card" tag to all contacts
     * - Updates presentation_card_sent with the current date/var/www/vhosts/bicgraphic.com/docker/mongo/
     * - Resets sent_presentation_card to empty string
     *
     * @param CompanyEvent $event The company event object.
     */
    public function onCompanyPreSave(CompanyEvent $event)
    {
        $company = $event->getCompany();
        
        // Check if sent_presentation_card is 1 (regardless of changes)
        if ($company->getFieldValue('sent_presentation_card') == 1) {
            
            // Get all contacts associated with this company
            $companyLeadRepo = $this->companyModel->getCompanyLeadRepository();
            
            // Use findBy to get CompanyLead entities with the given company ID
            $companyLeads = $companyLeadRepo->findBy(['company' => $company]);
            
            if (!empty($companyLeads)) {
                // Get the tag entity for "Sent Presentation Card" (ID: 3)
                $tag = $this->tagModel->getEntity(3);
                
                if ($tag) {
                    foreach ($companyLeads as $companyLead) {
                        // Get the contact entity from the CompanyLead
                        $contact = $companyLead->getLead();
                        if ($contact) {
                            // Add the tag to the contact directly
                            $contact->addTag($tag);
                            $this->leadModel->saveEntity($contact);
                        }
                    }
                }
                
                // Update the company's presentation_card_sent field with the current date
                $currentDate = new \DateTime();
                $dateString = $currentDate->format('d-m-Y H:i:s');

                // Set the presentation_card_sent field to the current date
                $company->addUpdatedField('presentation_card_sent', $dateString);
                
                // Reset the sent_presentation_card field to empty string
                $company->addUpdatedField('sent_presentation_card', '');
            }
        }
    }
}
