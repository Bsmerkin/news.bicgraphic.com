<?php

namespace MauticPlugin\BgeBundle\Model;

use Symfony\Component\Form\FormFactoryInterface;
use Mautic\CoreBundle\Model\FormModel;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use MauticPlugin\BgeBundle\Entity\InternalEmail;
use MauticPlugin\BgeBundle\Form\Type\InternalEmailType;

/**
 * Class InternalEmailModel
 */
class InternalEmailModel extends FormModel
{

	/**
     * @return TagRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(InternalEmail::class);
    }

	/**
	 * {@inheritdoc}
	 */
	public function saveEntity($entity, $unlock = true): void
	{
		//make sure record is not duplicated
		$repo = $this->getRepository();
		$count = ($entity->getId() ? 0 : $repo->checkUniqueRecord($entity));

		if ($count == 0) {
			parent::saveEntity($entity, $unlock);
		}

	}

    /**
     * @param int|null $id
     */
    public function getEntity($id = null): ?InternalEmail
    {
        if (null === $id) {
            return new InternalEmail();
        }

        return parent::getEntity($id);
    }

	/**
     * @param Tag         $entity
     * @param string|null $action
     * @param array       $options
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof InternalEmail) {
            throw new MethodNotAllowedHttpException(['InternalEmail']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(InternalEmailType::class, $entity, $options);
    }

	/**
     * Retrieve the permissions base.
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'bge:internalemail';
    }

	/**
	 * Change Doctrine ordering constant
	 */
	public function getDefaultOrder(): array
	{
		return [
			['e.lead', \Doctrine\Common\Collections\Order::Ascending->value],
		];
	}

	/**
	 * Get the table alias for this entity
	 *
	 * @return string
	 */
	public function getTableAlias()
	{
		return 'e'; // This is commonly used as the default alias in Doctrine queries
	}
}
