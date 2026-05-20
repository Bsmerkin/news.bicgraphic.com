<?php

namespace MauticPlugin\BgeBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\EmailBundle\Entity\Email;
use MauticPlugin\BgeBundle\Entity\InternalEmail;
use MauticPlugin\BgeBundle\Form\Type\InternalEmailSendBatchType;
use MauticPlugin\BgeBundle\Form\Type\InternalEmailSendType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Countries;


class InternalEmailController extends AbstractStandardFormController
{
    /**
     * @phpstan-ignore-next-line
     */
    public function __construct(
        private CacheProvider $cacheProvider,
        FormFactoryInterface $formFactory,
        FormFieldHelper $fieldHelper,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,  // This is fine
        CorePermissions $security
    ) {
        parent::__construct($formFactory, $fieldHelper, $doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    protected function getTemplateBase(): string
    {
        return '@Bge/InternalEmail';
    }

    protected function getModelName(): string
    {
        return 'internalemail';
    }

    /**
     * @param int $page
     */
    public function indexAction(Request $request, $page = 1): Response
    {
        return parent::indexStandard($request, $page);
    }

    /**
     * Generates new form and processes post data.
     *
     * @return JsonResponse|Response
     */
    public function newAction(Request $request)
    {
        return parent::newStandard($request);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|Response
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        return parent::editStandard($request, $objectId, $ignorePost);
    }

    /**
     * Displays details on a Focus.
     *
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function viewAction(Request $request, $objectId)
    {
        return parent::viewStandard($request, $objectId, 'internalemail', 'plugin.internalEmail');
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function cloneAction(Request $request, $objectId)
    {
        return parent::cloneStandard($request, $objectId);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse|RedirectResponse
     */
    public function deleteAction(Request $request, $objectId)
    {
        return parent::deleteStandard($request, $objectId);
    }

    /**
     * Deletes a group of entities.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function batchDeleteAction(Request $request)
    {
        return parent::batchDeleteStandard($request);
    }

    /**
     * @throws \Exception
     */
    public function getViewArguments(array $args, $action): array
    {
        $args = parent::getViewArguments($args, $action);
        return $args;
    }

    /**
     * @return mixed[]
     */
    protected function getPostActionRedirectArguments(array $args, $action): array
    {
        $args = parent::getPostActionRedirectArguments($args, $action);
        return $args;
    }

    /**
     * Send email to selected recipient (if specified) or everyone
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendAction(Request $request)
    {

        $countriesChoices = $this->getModel('internalemail')->getRepository()->getAllDistinctCountries();

        // Format the choices for the form
        $formattedCountriesChoices = [];
        foreach ($countriesChoices as $country) {
            $isoCode = $country['country'];
            $countryName = Countries::getName($isoCode);
            $formattedCountriesChoices[$countryName] = $isoCode;
        }

        // Create the form
        $form = $this->createForm(InternalEmailSendType::class, null, [
            'action' => $this->generateUrl('mautic_internalemail_action', ['objectAction' => 'send']),
            'method' => 'POST',
            'countriesChoices' => $formattedCountriesChoices,
        ]);

        // Check for a submitted form and process it
        if ('POST' === $request->getMethod()) {
            $valid = false;
            if ($valid = $this->isFormValid($form)) {
                // Change from $request->request->get() to $request->request->all()
                $data = $form->getData();
                $email = $data['email'];
                $category = $data['category'];
                $countries = $data['countries'];

                // Get filtered internal email versions from the repository                
                $model = $this->getModel('internalemail');
                $internalEmailVersions = $model->getRepository()->getFilteredInternalEmails([], $category, $countries);

                if (count($internalEmailVersions) > 0) {
                    foreach ($internalEmailVersions as $version) {
                        $this->sendInternalEmail($version, $email);
                    }

                    $this->addFlashMessage(
                        'mautic.internalemail.notice.sent',
                        [],
                        FlashBag::LEVEL_NOTICE
                    );
                } else {
                    $this->addFlashMessage(
                        'mautic.internalemail.error.nopending',
                        [],
                        FlashBag::LEVEL_ERROR
                    );
                }

                return new JsonResponse([
                    'closeModal' => true,
                    'flashes'    => $this->getFlashContent(),
                ]);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
                'currentRoute' => $this->generateUrl('mautic_internalemail_action', ['objectAction' => 'send']),
            ],
            'contentTemplate' => $this->getTemplateName('send.html.twig'),
        ]);
    }

    /**
     * Send emails in bulk
     *
     * @return void
     */
    public function batchSendAction(Request $request, $objectId)
    {
        $formattedObjectId = json_encode([$objectId]);

        $form = $this->createForm(InternalEmailSendBatchType::class, null, [
            'action' => $this->generateUrl('mautic_internalemail_action', ['objectAction' => 'batchSend']),
            'data' => ['ids' => $formattedObjectId],
            'attr' => [
                'data-precheck' => 'batchActionPrecheck',
                'data-confirm-callback' => 'internalEmailBatchSubmit',
                'data-submit-callback' => 'internalEmailBatchSubmit',
            ]
        ]);

        // Check for a submitted form and process it
        if ('POST' === $request->getMethod()) {
            $valid = false;
            if ($valid = $this->isFormValid($form)) {
                $data = $form->getData();
                $email = $data['email'];
                $ids = json_decode($data['ids'], true);

                // Ensure $ids is an array
                if (!is_array($ids)) {
                    $ids = [];
                }

                // Get filtered internal email versions from the repository                
                $model = $this->getModel('internalemail');
                $internalEmailVersions = $model->getRepository()->getFilteredInternalEmails($ids);

                $count = count($internalEmailVersions);

                if ($count > 0) {
                    foreach ($internalEmailVersions as $version) {
                        $this->sendInternalEmail($version, $email);
                    }

                    $this->addFlashMessage(
                        'mautic.internalemail.notice.queued',
                        ['%count%' => $count],
                        FlashBag::LEVEL_NOTICE
                    );
                } else {
                    $this->addFlashMessage(
                        'mautic.internalemail.error.nopending',
                        [],
                        FlashBag::LEVEL_ERROR
                    );
                }

                return new JsonResponse([
                    'closeModal' => true,
                    'flashes'    => $this->getFlashContent(),
                ]);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
                'currentRoute' => $this->generateUrl('mautic_internalemail_action', ['objectAction' => 'batchsend', 'objectId' => $objectId]),
            ],
            'contentTemplate' => $this->getTemplateName('batchsend.html.twig'),
        ]);
    }

    private function sendInternalEmail(InternalEmail $version, Email $email): void
    {
        /** @var \MauticPlugin\BgeBundle\Model\InternalEmailModel $internalEmailModel */
        $internalEmailModel = $this->getModel('internalemail');

        /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
        $emailModel = $this->getModel('email');
        $leadModel = $this->getModel('lead');

        $lead = $leadModel->getEntity($version->getLead()->getId());
        $leadFields = array_merge(['id' => $lead->getId()], $lead->getProfileFields());

        // Set owner_id to support the "Owner is mailer" feature
        if ($lead->getOwner()) {
            $leadFields['owner_id'] = $lead->getOwner()->getId();
        }
        
        // Change lead options to recice the desired email version
        $leadFields['country'] = Countries::getName($version->getCountry());
        $leadFields['preferred_locale'] = $version->getLanguage();

        // Temporarily change the email type
        $email->setEmailType('template'); 
        
        // Prepare email options
        $options = [
            //'email_type' => 'marketing', // Set the email type
            'ignoreDNC' => true, // Ignore Do Not Contact
            'channel' => ['internalemail', $version->getId()],
        ];

        // Send email to contact
        $emailModel->sendEmail($email, $leadFields, $options);

        // Save last email sent 
        $version->setEmail($email);
        $version->setDateSent(new \DateTime());
        $internalEmailModel->saveEntity($version);
    }
}
