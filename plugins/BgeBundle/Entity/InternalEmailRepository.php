<?php

namespace MauticPlugin\BgeBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * InternalEmailRepository
 */
class InternalEmailRepository extends CommonRepository
{

    /**
     * @return array
     */
    public function findByForm($formId)
    {
        return $this->findBy(
            [
                'form' => (int) $formId,
            ]
        );
    }

    /**
     * Get entities based on the provided arguments.
     *
     * @param array $args
     *
     * @return array
     */
    public function getEntities(array $args = [])
    {
        $q = $this->createQueryBuilder('e');
        
        // Join the lead relationship
        $q->leftJoin('e.lead', 'l');
        
        // Join the category relationship
        $q->leftJoin('e.category', 'c');        

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            'l.email',         // Search in lead email
            'c.title',          // Search in category title
            'e.country',      // Search in country
            'e.language'      // Search in language
        ]);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias() . '.lead', \Doctrine\Common\Collections\Order::Ascending->value],
        ];
    }

    public function getTableAlias(): string
    {
        return 'e';
    }

    /**
     * @param InternalEmail $InternalEmail
     *
     * @return mixed
     */
    public function checkUniqueRecord(InternalEmail $InternalEmail)
    {
        $qb = $this->createQueryBuilder('i')
            ->select('count(i.id) as recordcount')
            ->leftJoin('i.lead', 'l')
            ->where('i.country = :country')
            ->andWhere('i.language = :language')
            ->andWhere('l.id = :leadId');
        
        // Handle category properly
        if ($InternalEmail->getCategory() instanceof \Mautic\CategoryBundle\Entity\Category) {
            $qb->andWhere('i.category = :category')
               ->setParameter('category', $InternalEmail->getCategory());
        }

        $qb->setParameter('country', $InternalEmail->getCountry());
        $qb->setParameter('language', $InternalEmail->getLanguage());
        $qb->setParameter('leadId', $InternalEmail->getLead());

        if ($InternalEmail->getId()) {
            $qb->andWhere('i.id != :id');
            $qb->setParameter('id', $InternalEmail->getId());
        }

        $results = $qb->getQuery()->getSingleResult();

        return $results['recordcount'];
    }

    /**
     * @param array $ids
     * @param string $email
     * @param string $contactType
     * @param array $countries
     *
     * @return array
     */
    public function getFilteredInternalEmails(array $ids = [], $category = null, array $countries = [])
    {
        $qb = $this->createQueryBuilder('e');

        if (!empty($ids)) {
            $qb->andWhere('e.id IN (:ids)')
               ->setParameter('ids', $ids);

            return $qb->getQuery()->getResult();
        }

        if (!empty($category)) {
            if ($category instanceof \Mautic\CategoryBundle\Entity\Category) {
                // If category is a Category object, filter by ID
                $qb->andWhere('e.category = :category')
                ->setParameter('category', $category);
            } elseif (is_numeric($category)) {
                // If category is an ID
                $qb->andWhere('e.category = :categoryId')
                ->setParameter('categoryId', $category);
            } else {
                // For backward compatibility - if category is a string
                $qb->leftJoin('e.category', 'c')
                ->andWhere('c.title LIKE :categoryTitle')
                ->setParameter('categoryTitle', '%' . $category . '%');
            }
        }

        if (!empty($countries)) {
            $qb->andWhere('e.country IN (:countries)')
               ->setParameter('countries', $countries);
        }        

                
        /*$query = $qb->getQuery();
        echo $query->getSQL();*/
        
        return $qb->getQuery()->getResult();
        
    }

    /**
     * Get all distinct countries from InternalEmail entities
     *
     * @return array
     */
    public function getAllDistinctCountries()
    {
        $qb = $this->createQueryBuilder('e')
            ->select('DISTINCT e.country')
            ->where('e.country IS NOT NULL')
            ->orderBy('e.country', 'ASC');

        return $qb->getQuery()->getResult();
    }
    
    public function getCategoryChoices()
    {
        $qb = $this->createQueryBuilder('e')
            ->select('DISTINCT c.title')
            ->leftJoin('e.category', 'c')
            ->where('c.title IS NOT NULL')
            ->orderBy('c.title', 'ASC');

        $results = $qb->getQuery()->getResult();
        $choices = [];
        foreach ($results as $result) {
            $choices[$result['title']] = $result['title'];
        }

        return $choices;
    }
}
