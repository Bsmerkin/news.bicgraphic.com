<?php

namespace MauticPlugin\BgeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ORM\Entity(repositoryClass="MauticPlugin\BgeBundle\Entity\InternalEmailRepository")
 * @ORM\Table(name="internal_emails", indexes={
 *     @ORM\Index(name="lead_search", columns={"lead_id"}),
 *     @ORM\Index(name="email_search", columns={"email_id"}),
 *     @ORM\Index(name="category_search", columns={"category_id"})
 * })
 */
class InternalEmail extends FormEntity implements UuidInterface
{
    use UuidTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Lead
     *
     * @ORM\ManyToOne(targetEntity="Mautic\LeadBundle\Entity\Lead")
     * @ORM\JoinColumn(name="lead_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $lead;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="mautic.internalemail.country.required")
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=10, options={"default": "en"})
     * @Assert\NotBlank(message="mautic.internalemail.language.required")
     */
    private $language = 'en';

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Mautic\CategoryBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Assert\NotBlank(message="mautic.internalemail.category.required")
     */
    private $category;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var Email
     *
     * @ORM\ManyToOne(targetEntity="Mautic\EmailBundle\Entity\Email")
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $email;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateSent = null;

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint(
            'country',
            new Assert\NotBlank(['message' => 'mautic.internalemail.country.required'])
        );

        $metadata->addPropertyConstraint(
            'language',
            new Assert\NotBlank(['message' => 'mautic.internalemail.language.required'])
        );

        $metadata->addPropertyConstraint(
            'category',
            new Assert\NotBlank(['message' => 'mautic.internalemail.category.required'])
        );
    }

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('internal_emails')
            ->setCustomRepositoryClass(InternalEmailRepository::class)
            ->addIndex(['lead_id'], 'lead_search')
            ->addIndex(['email_id'], 'email_search')
            ->addIndex(['category_id'], 'category_search');

        $builder->addIdColumns();

        $builder->addField('country', 'string', ['length' => 255]);
        $builder->addField('language', 'string', ['length' => 10, 'default' => 'en']);

        $builder->createManyToOne('lead', Lead::class)
            ->addJoinColumn('lead_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('email', Email::class)
            ->addJoinColumn('email_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('category', Category::class)
            ->addJoinColumn('category_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addField('dateSent', 'datetime', ['nullable' => true]);
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                    'country',
                    'language',
                    'dateSent',
                ]
            )
            ->addProperties(
                [
                    'description',
                    'lead',
                    'email',
                ]
            )
            ->build();
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    // Getters and setters for the properties
    public function getId()
    {
        return $this->id;
    }

    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(Lead $lead)
    {
        $this->lead = $lead;
        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(Email $email)
    {
        $this->email = $email;
        return $this;
    }

    public function getDateSent()
    {
        return $this->dateSent;
    }

    public function setDateSent($dateSent)
    {
        $this->dateSent = $dateSent;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;
        return $this;
    }
}